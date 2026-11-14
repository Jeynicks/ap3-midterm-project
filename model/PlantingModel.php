<?php
class PlantingModel extends BaseModel {
    protected string $table = 'planting';
    protected string $pk    = 'planting_id';
    protected array  $cols  = ['farm_id', 'crop_id', 'planting_date', 'area_planted', 'seed_quantity', 'expected_harvest_date', 'status_id'];

    private const SORT = ['id' => 'p.planting_id', 'farm' => 'f.farm_name', 'crop' => 'c.crop_name',
                          'planted' => 'p.planting_date', 'expected' => 'p.expected_harvest_date'];
    private const FROM = 'planting p
        JOIN farm f ON f.farm_id = p.farm_id
        JOIN crop c ON c.crop_id = p.crop_id
        JOIN planting_status s ON s.status_id = p.status_id
        LEFT JOIN app_user cb ON cb.user_id = p.created_by
        LEFT JOIN app_user ub ON ub.user_id = p.updated_by';

    public function paginate(string $q, int $statusId, string $sort, string $dir, int $page): array {
        $where = 'WHERE 1=1'; $p = [];
        if ($q !== '') { $where .= " AND (f.farm_name || ' ' || c.crop_name || ' ' || c.variety) ILIKE :q"; $p[':q'] = "%$q%"; }
        if ($statusId > 0) { $where .= ' AND p.status_id = :st'; $p[':st'] = $statusId; }
        $order = (self::SORT[$sort] ?? 'p.planting_id') . ($dir === 'desc' ? ' DESC' : ' ASC');
        return $this->page('p.*, f.farm_name, c.crop_name, c.variety, s.status_name, cb.full_name AS created_by_name, ub.full_name AS updated_by_name',
            self::FROM, $where, $p, $order, $page);
    }

    // For the Harvest form: [planting_id => "Farm - Crop (planted 2026-01-10)"]
    public function options(): array {
        return $this->pdo->query("SELECT p.planting_id, f.farm_name || ' - ' || c.crop_name || ' (planted ' || p.planting_date || ')'
            FROM planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
            ORDER BY f.farm_name, p.planting_date")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}