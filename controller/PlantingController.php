<?php
class PlantingController {
    public function __construct(private PlantingModel $plantings, private FarmModel $farms, private CropModel $crops, private LookupModel $lookups) {}

    // Returns [errors, cleanedData]. On success errors is empty and a flash message is queued.
    public function save(?int $id, array $in): array {
        $d = [
            'farm_id'               => (int)($in['farm_id'] ?? 0),
            'crop_id'               => (int)($in['crop_id'] ?? 0),
            'planting_date'         => trim((string)($in['planting_date'] ?? '')),
            'area_planted'          => trim((string)($in['area_planted'] ?? '')),
            'seed_quantity'         => trim((string)($in['seed_quantity'] ?? '')),
            'expected_harvest_date' => trim((string)($in['expected_harvest_date'] ?? '')),
            'status_id'             => (int)($in['status_id'] ?? 0),
        ];
        $err = [];
        if (!csrf_valid($in['csrf'] ?? null)) $err['form'] = 'Your session expired. Reload the page and try again.';

        $farm = $this->farms->find($d['farm_id']);
        if (!$farm) $err['farm_id'] = 'Choose a farm from the list.';
        if (!$this->crops->find($d['crop_id'])) $err['crop_id'] = 'Choose a crop from the list.';
        if (!array_key_exists($d['status_id'], $this->lookups->options('planting_status'))) $err['status_id'] = 'Choose a status from the list.';

        foreach (['planting_date' => 'planting date', 'expected_harvest_date' => 'expected harvest date'] as $k => $label) {
            if (!is_valid_date($d[$k])) $err[$k] = "Enter a valid $label.";
        }
        if (!isset($err['planting_date'], $err['expected_harvest_date']) && is_valid_date($d['planting_date']) && is_valid_date($d['expected_harvest_date'])
            && $d['expected_harvest_date'] <= $d['planting_date']) {
            $err['expected_harvest_date'] = 'The expected harvest date must be after the planting date.';
        }

        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $d['area_planted'])) $err['area_planted'] = 'Enter a number such as 0.5 (up to 2 decimal places).';
        elseif ((float)$d['area_planted'] <= 0) $err['area_planted'] = 'Area planted must be more than 0.';
        elseif ($farm && (float)$d['area_planted'] > (float)$farm['area_hectares'])
            $err['area_planted'] = 'Area planted cannot be larger than the farm (' . number_format((float)$farm['area_hectares'], 2) . ' hectares).';

        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $d['seed_quantity'])) $err['seed_quantity'] = 'Enter a number such as 25 (up to 2 decimal places).';

        if (!$err) {
            try {
                $by = current_user_id();
                $id ? $this->plantings->update($id, $d, $by) : $this->plantings->create($d, $by);
                flash_set('success', $id ? 'Planting record updated.' : 'Planting record added.');
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                $err['form'] = 'The record could not be saved. Try again.';
            }
        }
        return [$err, $d];
    }

    public function delete(array $in): void {
        if (!csrf_valid($in['csrf'] ?? null)) { flash_set('danger', 'Your session expired. Reload the page and try again.'); return; }
        if (!is_admin()) { flash_set('danger', 'Only administrators can delete records.'); return; }
        try {
            $this->plantings->delete((int)($in['id'] ?? 0));
            flash_set('success', 'Planting record deleted.');
        } catch (PDOException $ex) {
            if (($ex->errorInfo[0] ?? '') === '23503') flash_set('danger', 'This planting record has harvest records. Delete those harvests first.');
            else { error_log($ex->getMessage()); flash_set('danger', 'The record could not be deleted. Try again.'); }
        }
    }
}