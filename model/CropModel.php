<?php
class CropModel extends BaseModel {
    protected string $table = 'crop';
    protected string $pk    = 'crop_id';
    protected array  $cols  = ['crop_name', 'variety', 'category_id', 'growing_period_days', 'season_id'];

    private const SORT = ['id' => 'c.crop_id', 'name' => 'c.crop_name', 'category' => 'cc.category_name', 'days' => 'c.growing_period_days'];
    private const FROM = 'crop c
        JOIN crop_category cc ON cc.category_id = c.category_id
        JOIN growing_season gs ON gs.season_id = c.season_id';

    public function paginate(string $q, int $categoryId, string $sort, string $dir, int $page): array {
        $where = 'WHERE 1=1'; $p = [];
        if ($q !== '') { $where .= " AND (c.crop_name || ' ' || c.variety) ILIKE :q"; $p[':q'] = "%$q%"; }
        if ($categoryId > 0) { $where .= ' AND c.category_id = :cat'; $p[':cat'] = $categoryId; }
        $order = (self::SORT[$sort] ?? 'c.crop_id') . ($dir === 'desc' ? ' DESC' : ' ASC');
        return $this->page('c.*, cc.category_name, gs.season_name', self::FROM, $where, $p, $order, $page);
    }

    // For dropdowns on Planting: [crop_id => "Rice (NSIC Rc 222)"]
    public function options(): array {
        return $this->pdo->query("SELECT crop_id, crop_name || ' (' || variety || ')' FROM crop ORDER BY 2")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}