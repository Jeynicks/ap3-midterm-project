<?php
// Shared plumbing. Each table has its own model that extends this and owns its queries.
abstract class BaseModel {
    protected string $table;
    protected string $pk;
    protected array  $cols;              // columns the app may insert/update (whitelist)
    protected bool   $audited = true;    // table has created_by / updated_by columns

    public function __construct(protected PDO $pdo) {}

    public function find(int $id): ?array {
        $s = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->pk} = :id");
        $s->execute([':id' => $id]);
        return $s->fetch() ?: null;
    }

    // $by = user_id of the person making the change (recorded for auditing)
    public function create(array $d, ?int $by = null): int {
        $cols = $this->cols;
        $params = $this->pick($d);
        if ($this->audited) {
            array_push($cols, 'created_by', 'updated_by');
            $params[':created_by'] = $by;
            $params[':updated_by'] = $by;
        }
        $s = $this->pdo->prepare("INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (:"
            . implode(',:', $cols) . ") RETURNING {$this->pk}");
        $s->execute($params);
        return (int)$s->fetchColumn();
    }

    public function update(int $id, array $d, ?int $by = null): bool {
        $set = implode(',', array_map(fn($c) => "$c = :$c", $this->cols));
        $params = $this->pick($d) + [':_id' => $id];
        if ($this->audited) {
            $set .= ', updated_by = :_by';
            $params[':_by'] = $by;
        }
        $s = $this->pdo->prepare("UPDATE {$this->table} SET $set WHERE {$this->pk} = :_id");
        return $s->execute($params);
    }

    public function delete(int $id): bool {
        $s = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->pk} = :id");
        return $s->execute([':id' => $id]);
    }

    protected function pick(array $d): array {
        $out = [];
        foreach ($this->cols as $c) $out[":$c"] = $d[$c];
        return $out;
    }

    // $order and $per are never user input (whitelisted / cast by the caller).
    protected function page(string $select, string $from, string $where, array $params, string $order, int $page, int $per = 10): array {
        $c = $this->pdo->prepare("SELECT count(*) FROM $from $where");
        $c->execute($params);
        $total = (int)$c->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));
        $page  = min(max(1, $page), $pages);
        $s = $this->pdo->prepare("SELECT $select FROM $from $where ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per));
        $s->execute($params);
        return ['rows' => $s->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
}