<?php
// Read-only queries for the dashboard. Everything is counted live from the database.
class DashboardModel {
    public function __construct(private PDO $pdo) {}

    public function totals(): array {
        return $this->pdo->query("SELECT
            (SELECT count(*) FROM farmer)   AS farmers,
            (SELECT count(*) FROM farm)     AS farms,
            (SELECT count(*) FROM crop)     AS crops,
            (SELECT count(*) FROM planting) AS plantings,
            (SELECT count(*) FROM harvest)  AS harvests,
            (SELECT COALESCE(sum(quantity_kg), 0) FROM harvest) AS total_kg,
            (SELECT COALESCE(sum(quantity_kg * selling_price_per_kg), 0) FROM harvest) AS total_value")->fetch();
    }

    // Every status appears, even with zero plantings.
    public function plantingsByStatus(): array {
        return $this->pdo->query("SELECT s.status_name, count(p.planting_id) AS total
            FROM planting_status s LEFT JOIN planting p ON p.status_id = s.status_id
            GROUP BY s.status_id, s.status_name ORDER BY s.status_id")->fetchAll();
    }

    // "Recent" = most recently recorded.
    public function recentPlantings(int $limit = 5): array {
        return $this->pdo->query("SELECT p.planting_id, p.planting_date, p.expected_harvest_date, p.area_planted,
                f.farm_name, c.crop_name, c.variety, s.status_name
            FROM planting p
            JOIN farm f ON f.farm_id = p.farm_id
            JOIN crop c ON c.crop_id = p.crop_id
            JOIN planting_status s ON s.status_id = p.status_id
            ORDER BY p.created_at DESC, p.planting_id DESC LIMIT " . (int)$limit)->fetchAll();
    }

    public function recentHarvests(int $limit = 5): array {
        return $this->pdo->query("SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                f.farm_name, c.crop_name, g.grade_name, r.remark_description
            FROM harvest h
            JOIN planting p ON p.planting_id = h.planting_id
            JOIN farm f ON f.farm_id = p.farm_id
            JOIN crop c ON c.crop_id = p.crop_id
            JOIN quality_grade g ON g.grade_id = h.grade_id
            JOIN harvest_remark r ON r.remark_id = h.remark_id
            ORDER BY h.created_at DESC, h.harvest_id DESC LIMIT " . (int)$limit)->fetchAll();
    }
}