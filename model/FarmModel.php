<?php
class FarmModel extends BaseModel {
    protected string $table = 'farm';
    protected string $pk    = 'farm_id';
    protected array  $cols  = ['farmer_id', 'farm_name', 'location_id', 'area_hectares', 'soil_type_id', 'irrigation_type_id'];

    private const SORT = ['id' => 'f.farm_id', 'name' => 'f.farm_name', 'farmer' => 'fr.last_name', 'area' => 'f.area_hectares'];
    private const FROM = 'farm f
        JOIN farmer fr ON fr.farmer_id = f.farmer_id
        JOIN location l ON l.location_id = f.location_id
        JOIN soil_type s ON s.soil_type_id = f.soil_type_id
        JOIN irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id
        LEFT JOIN app_user cb ON cb.user_id = f.created_by
        LEFT JOIN app_user ub ON ub.user_id = f.updated_by';

    public function paginate(string $q, int $irrigationId, string $sort, string $dir, int $page): array {
        $where = 'WHERE 1=1'; $p = [];
        if ($q !== '') { $where .= " AND (f.farm_name || ' ' || fr.first_name || ' ' || fr.last_name || ' ' || l.city_municipality) ILIKE :q"; $p[':q'] = "%$q%"; }
        if ($irrigationId > 0) { $where .= ' AND f.irrigation_type_id = :irr'; $p[':irr'] = $irrigationId; }
        $order = (self::SORT[$sort] ?? 'f.farm_id') . ($dir === 'desc' ? ' DESC' : ' ASC');
        return $this->page("f.*, fr.first_name || ' ' || fr.last_name AS farmer_name, l.city_municipality, l.province, s.soil_type, i.irrigation_type,
            cb.full_name AS created_by_name, ub.full_name AS updated_by_name",
            self::FROM, $where, $p, $order, $page);
    }

    // For the Planting form: [farm_id => "Farm name (Farmer last name)"]
    public function options(): array {
        return $this->pdo->query("SELECT f.farm_id, f.farm_name || ' (' || fr.last_name || ')'
            FROM farm f JOIN farmer fr ON fr.farmer_id = f.farmer_id ORDER BY 2")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}