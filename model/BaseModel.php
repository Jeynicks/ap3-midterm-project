<?php
// Shared plumbing. Each table has its own model that extends this and owns its queries.
abstract class BaseModel {
    protected string $table;
    protected string $pk;
    protected array  $cols;          // columns the app may insert/update (whitelist)

    public function __construct(protected PDO $pdo) {}

    public function find(int $id): ?array {
        $s = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->pk} = :id");
        $s->execute([':id' => $id]);
        return $s->fetch() ?: null;
    }

    public function create(array $d): int {
        $s = $this->pdo->prepare("INSERT INTO {$this->table} (" . implode(',', $this->cols) . ") VALUES (:"
            . implode(',:', $this->cols) . ") RETURNING {$this->pk}");
        $s->execute($this->pick($d));
        return (int)$s->fetchColumn();
    }

    public function update(int $id, array $d): bool {
        $set = implode(',', array_map(fn($c) => "$c = :$c", $this->cols));
        $s = $this->pdo->prepare("UPDATE {$this->table} SET $set WHERE {$this->pk} = :_id");
        return $s->execute($this->pick($d) + [':_id' => $id]);
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