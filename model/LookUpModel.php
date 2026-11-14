<?php
// Dropdown values from the lookup tables: returns [id => label].
class LookupModel {
    private const MAP = [
        'location'        => ['location',        'location_id',        "city_municipality || ', ' || province"],
        'soil_type'       => ['soil_type',       'soil_type_id',       'soil_type'],
        'irrigation_type' => ['irrigation_type', 'irrigation_type_id', 'irrigation_type'],
        'crop_category'   => ['crop_category',   'category_id',        'category_name'],
        'growing_season'  => ['growing_season',  'season_id',          'season_name'],
    ];

    public function __construct(private PDO $pdo) {}

    public function options(string $key): array {
        [$table, $id, $label] = self::MAP[$key];
        return $this->pdo->query("SELECT $id AS id, $label AS label FROM $table ORDER BY 2")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}