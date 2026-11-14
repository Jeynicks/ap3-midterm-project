<?php
class FarmerModel extends BaseModel {
    protected string $table = 'farmer';
    protected string $pk    = 'farmer_id';
    protected array  $cols  = ['first_name', 'last_name', 'contact_number', 'location_id'];

    private const SORT = ['id' => 'f.farmer_id', 'name' => 'f.last_name', 'city' => 'l.city_municipality', 'date' => 'f.date_registered'];
    private const FROM = 'farmer f JOIN location l ON l.location_id = f.location_id';

    public function paginate(string $q, int $locationId, string $sort, string $dir, int $page): array {
        $where = 'WHERE 1=1'; $p = [];
        if ($q !== '') { $where .= " AND (f.first_name || ' ' || f.last_name || ' ' || f.contact_number) ILIKE :q"; $p[':q'] = "%$q%"; }
        if ($locationId > 0) { $where .= ' AND f.location_id = :loc'; $p[':loc'] = $locationId; }
        $order = (self::SORT[$sort] ?? 'f.farmer_id') . ($dir === 'desc' ? ' DESC' : ' ASC');
        return $this->page('f.*, l.city_municipality, l.province', self::FROM, $where, $p, $order, $page);
    }

    // For dropdowns on other modules (Farms): [farmer_id => "Last, First"]
    public function options(): array {
        return $this->pdo->query("SELECT farmer_id, last_name || ', ' || first_name FROM farmer ORDER BY 2")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}