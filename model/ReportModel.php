<?php
// Read-only report queries. Every figure comes live from the database.
class ReportModel {
    public function __construct(private PDO $pdo) {}

    private function run(string $sql, array $p): array {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        return $s->fetchAll();
    }

    // Inclusive date range on $col (an internal column name, never user input).
    private function range(string $col, string $from, string $to, array &$p): string {
        $w = '';
        if ($from !== '') { $w .= " AND $col >= :from"; $p[':from'] = $from; }
        if ($to !== '')   { $w .= " AND $col <= :to";   $p[':to']   = $to; }
        return $w;
    }

    public function farmersAndFarms(string $from, string $to): array {
        $p = []; $w = $this->range('fr.date_registered', $from, $to, $p);
        return $this->run("SELECT fr.last_name || ', ' || fr.first_name AS farmer, fr.contact_number, fl.city_municipality AS farmer_city,
                f.farm_name, f.area_hectares, s.soil_type, i.irrigation_type
            FROM farmer fr
            JOIN location fl ON fl.location_id = fr.location_id
            LEFT JOIN farm f ON f.farmer_id = fr.farmer_id
            LEFT JOIN soil_type s ON s.soil_type_id = f.soil_type_id
            LEFT JOIN irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id
            WHERE 1=1 $w ORDER BY fr.last_name, fr.first_name, f.farm_name", $p);
    }

    public function cropsPerFarm(string $from, string $to, int $farmId): array {
        $p = []; $w = $this->range('p.planting_date', $from, $to, $p);
        if ($farmId > 0) { $w .= ' AND f.farm_id = :farm'; $p[':farm'] = $farmId; }
        return $this->run("SELECT f.farm_name, fr.last_name || ', ' || fr.first_name AS farmer, c.crop_name, c.variety,
                count(*) AS plantings, SUM(p.area_planted) AS area_planted
            FROM planting p
            JOIN farm f ON f.farm_id = p.farm_id
            JOIN farmer fr ON fr.farmer_id = f.farmer_id
            JOIN crop c ON c.crop_id = p.crop_id
            WHERE 1=1 $w
            GROUP BY f.farm_id, f.farm_name, fr.last_name, fr.first_name, c.crop_id, c.crop_name, c.variety
            ORDER BY f.farm_name, c.crop_name", $p);
    }

    // Every status is listed, even with zero plantings in the range.
    public function plantingsByStatus(string $from, string $to, int $statusId): array {
        $p = []; $on = $this->range('p.planting_date', $from, $to, $p);
        $where = '';
        if ($statusId > 0) { $where = 'WHERE s.status_id = :st'; $p[':st'] = $statusId; }
        return $this->run("SELECT s.status_name, count(p.planting_id) AS plantings, COALESCE(SUM(p.area_planted), 0) AS area_planted
            FROM planting_status s LEFT JOIN planting p ON p.status_id = s.status_id $on
            $where GROUP BY s.status_id, s.status_name ORDER BY s.status_id", $p);
    }

    public function harvestRecords(string $from, string $to, int $gradeId): array {
        $p = []; $w = $this->range('h.harvest_date', $from, $to, $p);
        if ($gradeId > 0) { $w .= ' AND h.grade_id = :g'; $p[':g'] = $gradeId; }
        return $this->run("SELECT h.harvest_date, f.farm_name, c.crop_name, h.quantity_kg, h.selling_price_per_kg,
                h.quantity_kg * h.selling_price_per_kg AS est_value, g.grade_name, r.remark_description
            FROM harvest h
            JOIN planting p ON p.planting_id = h.planting_id
            JOIN farm f ON f.farm_id = p.farm_id
            JOIN crop c ON c.crop_id = p.crop_id
            JOIN quality_grade g ON g.grade_id = h.grade_id
            JOIN harvest_remark r ON r.remark_id = h.remark_id
            WHERE 1=1 $w ORDER BY h.harvest_date DESC, h.harvest_id DESC", $p);
    }

    public function harvestByCrop(string $from, string $to): array {
        $p = []; $w = $this->range('h.harvest_date', $from, $to, $p);
        return $this->run("SELECT c.crop_name, c.variety, count(h.harvest_id) AS harvests, SUM(h.quantity_kg) AS quantity_kg
            FROM harvest h
            JOIN planting p ON p.planting_id = h.planting_id
            JOIN crop c ON c.crop_id = p.crop_id
            WHERE 1=1 $w GROUP BY c.crop_id, c.crop_name, c.variety ORDER BY SUM(h.quantity_kg) DESC", $p);
    }

    public function valueByFarm(string $from, string $to): array {
        $p = []; $w = $this->range('h.harvest_date', $from, $to, $p);
        return $this->run("SELECT f.farm_name, fr.last_name || ', ' || fr.first_name AS farmer,
                SUM(h.quantity_kg) AS quantity_kg, SUM(h.quantity_kg * h.selling_price_per_kg) AS est_value
            FROM harvest h
            JOIN planting p ON p.planting_id = h.planting_id
            JOIN farm f ON f.farm_id = p.farm_id
            JOIN farmer fr ON fr.farmer_id = f.farmer_id
            WHERE 1=1 $w GROUP BY f.farm_id, f.farm_name, fr.last_name, fr.first_name
            ORDER BY SUM(h.quantity_kg * h.selling_price_per_kg) DESC", $p);
    }
}