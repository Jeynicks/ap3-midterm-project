<?php
class LookupModel {
    private const CFG = [
        'soil_type' => ['table'=>'soil_type','pk'=>'soil_type_id','fields'=>['soil_type'=>['label'=>'Soil type','max'=>50]],'label'=>'soil_type'],
        'irrigation_type' => ['table'=>'irrigation_type','pk'=>'irrigation_type_id','fields'=>['irrigation_type'=>['label'=>'Irrigation type','max'=>50]],'label'=>'irrigation_type'],
        'crop_category' => ['table'=>'crop_category','pk'=>'category_id','fields'=>['category_name'=>['label'=>'Category name','max'=>50]],'label'=>'category_name'],
        'growing_season' => ['table'=>'growing_season','pk'=>'season_id','fields'=>['season_name'=>['label'=>'Season name','max'=>30]],'label'=>'season_name'],
        'planting_status' => ['table'=>'planting_status','pk'=>'status_id','fields'=>['status_name'=>['label'=>'Status name','max'=>30]],'label'=>'status_name'],
        'quality_grade' => ['table'=>'quality_grade','pk'=>'grade_id','fields'=>['grade_name'=>['label'=>'Grade name','max'=>30]],'label'=>'grade_name'],
        'harvest_remark' => ['table'=>'harvest_remark','pk'=>'remark_id','fields'=>['remark_description'=>['label'=>'Remark','max'=>100]],'label'=>'remark_description','auto_id'=>true],
        'location' => ['table'=>'location','pk'=>'location_id','fields'=>['city_municipality'=>['label'=>'City / Municipality','max'=>80],'province'=>['label'=>'Province','max'=>80]],'label'=>"city_municipality || ', ' || province"],
    ];

    public function __construct(private PDO $pdo) {}

    public static function keys(): array { return array_keys(self::CFG); }

    public function config(string $key): array {
        if (!isset(self::CFG[$key])) throw new InvalidArgumentException('Unknown lookup: ' . $key);
        return self::CFG[$key];
    }

    public function options(string $key): array {
        $c = $this->config($key);
        return $this->pdo->query("SELECT {$c['pk']} AS id, {$c['label']} AS label FROM {$c['table']} ORDER BY 2")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function all(string $key): array {
        $c = $this->config($key);
        $cols = array_merge([$c['pk']], array_keys($c['fields']));
        return $this->pdo->query("SELECT " . implode(', ', $cols) . " FROM {$c['table']} ORDER BY {$c['label']}")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add(string $key, array $data): int {
        $c = $this->config($key);
        $bind = [];
        foreach (array_keys($c['fields']) as $col) $bind[':' . $col] = trim((string)($data[$col] ?? ''));
        $insertCols = array_keys($c['fields']);
        if (!empty($c['auto_id'])) {
            $next = (int)$this->pdo->query("SELECT COALESCE(MAX({$c['pk']}), 0) + 1 FROM {$c['table']}")->fetchColumn();
            array_unshift($insertCols, $c['pk']);
            $bind[':' . $c['pk']] = $next;
        }
        $placeholders = array_map(fn($x) => ':' . $x, $insertCols);
        $stmt = $this->pdo->prepare("INSERT INTO {$c['table']} (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ") RETURNING {$c['pk']}");
        $stmt->execute($bind);
        return (int)$stmt->fetchColumn();
    }

    public function delete(string $key, int $id): bool {
        $c = $this->config($key);
        $stmt = $this->pdo->prepare("DELETE FROM {$c['table']} WHERE {$c['pk']} = :id");
        return $stmt->execute([':id' => $id]);
    }
}
