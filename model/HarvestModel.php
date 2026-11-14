<?php
class HarvestModel extends BaseModel {
    protected string $table = 'harvest';
    protected string $pk    = 'harvest_id';
    protected array  $cols  = ['planting_id', 'harvest_date', 'quantity_kg', 'selling_price_per_kg', 'grade_id', 'remark_id'];

    private const SORT = ['id' => 'h.harvest_id', 'farm' => 'f.farm_name', 'crop' => 'c.crop_name', 'date' => 'h.harvest_date',
                          'qty' => 'h.quantity_kg', 'price' => 'h.selling_price_per_kg'];
    private const FROM = 'harvest h
        JOIN planting p ON p.planting_id = h.planting_id
        JOIN farm f ON f.farm_id = p.farm_id
        JOIN crop c ON c.crop_id = p.crop_id
        JOIN quality_grade g ON g.grade_id = h.grade_id
        JOIN harvest_remark r ON r.remark_id = h.remark_id
        LEFT JOIN app_user cb ON cb.user_id = h.created_by
        LEFT JOIN app_user ub ON ub.user_id = h.updated_by';

    public function paginate(string $q, int $gradeId, string $sort, string $dir, int $page): array {
        $where = 'WHERE 1=1'; $p = [];
        if ($q !== '') { $where .= " AND (f.farm_name || ' ' || c.crop_name || ' ' || r.remark_description) ILIKE :q"; $p[':q'] = "%$q%"; }
        if ($gradeId > 0) { $where .= ' AND h.grade_id = :g'; $p[':g'] = $gradeId; }
        $order = (self::SORT[$sort] ?? 'h.harvest_id') . ($dir === 'desc' ? ' DESC' : ' ASC');
        return $this->page('h.*, p.planting_date, f.farm_name, c.crop_name, c.variety, g.grade_name, r.remark_description,
                h.quantity_kg * h.selling_price_per_kg AS est_value, cb.full_name AS created_by_name, ub.full_name AS updated_by_name',
            self::FROM, $where, $p, $order, $page);
    }
}