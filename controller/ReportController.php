<?php
class ReportController {
    // cols: [key, label, type (text|int|dec|money), sum?]   filter: extra dropdown, if any
    public const DEF = [
        'farmers' => ['title' => 'Farmers and their farms', 'dateLabel' => 'Registration date', 'filter' => null,
            'note' => 'Every farmer with each of their farms. The date range filters by the farmer\'s registration date.',
            'cols' => [['farmer', 'Farmer'], ['contact_number', 'Contact'], ['farmer_city', 'Farmer location'], ['farm_name', 'Farm'],
                       ['area_hectares', 'Area (ha)', 'dec', true], ['soil_type', 'Soil'], ['irrigation_type', 'Irrigation']]],
        'crops_per_farm' => ['title' => 'Crops planted per farm', 'dateLabel' => 'Planting date', 'filter' => 'farm',
            'note' => 'How many times each crop was planted on each farm, and the total area planted.',
            'cols' => [['farm_name', 'Farm'], ['farmer', 'Farmer'], ['crop_name', 'Crop'], ['variety', 'Variety'],
                       ['plantings', 'Planting records', 'int', true], ['area_planted', 'Area planted (ha)', 'dec', true]]],
        'plantings_status' => ['title' => 'Planting records by status', 'dateLabel' => 'Planting date', 'filter' => 'status',
            'note' => 'Number of planting records and area planted for each status.',
            'cols' => [['status_name', 'Status'], ['plantings', 'Planting records', 'int', true], ['area_planted', 'Area planted (ha)', 'dec', true]]],
        'harvest_records' => ['title' => 'Harvest records', 'dateLabel' => 'Harvest date', 'filter' => 'grade',
            'note' => 'Each harvest with its estimated value (quantity x selling price).',
            'cols' => [['harvest_date', 'Harvested'], ['farm_name', 'Farm'], ['crop_name', 'Crop'], ['quantity_kg', 'Quantity (kg)', 'dec', true],
                       ['selling_price_per_kg', 'Price per kg', 'money'], ['est_value', 'Estimated value', 'money', true],
                       ['grade_name', 'Grade'], ['remark_description', 'Remarks']]],
        'harvest_qty' => ['title' => 'Total harvested quantity', 'dateLabel' => 'Harvest date', 'filter' => null,
            'note' => 'Total kilograms harvested for each crop.',
            'cols' => [['crop_name', 'Crop'], ['variety', 'Variety'], ['harvests', 'Harvest records', 'int', true], ['quantity_kg', 'Total quantity (kg)', 'dec', true]]],
        'harvest_value' => ['title' => 'Estimated harvest value', 'dateLabel' => 'Harvest date', 'filter' => null,
            'note' => 'Estimated value for each farm: quantity harvested x selling price per kg.',
            'cols' => [['farm_name', 'Farm'], ['farmer', 'Farmer'], ['quantity_kg', 'Quantity (kg)', 'dec', true], ['est_value', 'Estimated value', 'money', true]]],
    ];

    public function __construct(private ReportModel $reports, private FarmModel $farms, private LookupModel $lookups) {}

    public function build(array $get): array {
        $key  = isset(self::DEF[$get['report'] ?? '']) ? $get['report'] : 'farmers';
        $def  = self::DEF[$key];
        $def['cols'] = array_map(fn($c) => $c + [2 => 'text', 3 => false], $def['cols']);   // every column: [key, label, type, sum]
        $from = trim((string)($get['from'] ?? ''));
        $to   = trim((string)($get['to'] ?? ''));
        $extra = (int)($get['extra'] ?? 0);

        $error = null;
        if ($from !== '' && !is_valid_date($from))      $error = 'Enter a valid "from" date.';
        elseif ($to !== '' && !is_valid_date($to))      $error = 'Enter a valid "to" date.';
        elseif ($from !== '' && $to !== '' && $from > $to) $error = 'The "from" date cannot be after the "to" date.';

        $rows = [];
        if (!$error) {
            $rows = match ($key) {
                'farmers'          => $this->reports->farmersAndFarms($from, $to),
                'crops_per_farm'   => $this->reports->cropsPerFarm($from, $to, $extra),
                'plantings_status' => $this->reports->plantingsByStatus($from, $to, $extra),
                'harvest_records'  => $this->reports->harvestRecords($from, $to, $extra),
                'harvest_qty'      => $this->reports->harvestByCrop($from, $to),
                'harvest_value'    => $this->reports->valueByFarm($from, $to),
            };
        }

        [$extraLabel, $extraOptions] = match ($def['filter']) {
            'farm'   => ['All farms', $this->farms->options()],
            'status' => ['All statuses', $this->lookups->options('planting_status')],
            'grade'  => ['All quality grades', $this->lookups->options('quality_grade')],
            default  => [null, []],
        };
        return compact('key', 'def', 'from', 'to', 'extra', 'error', 'rows', 'extraLabel', 'extraOptions');
    }
}