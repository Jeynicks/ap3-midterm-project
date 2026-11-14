<?php
class FarmController {
    public function __construct(private FarmModel $farms, private FarmerModel $farmers, private LookupModel $lookups) {}

    // Returns [errors, cleanedData]. On success errors is empty and a flash message is queued.
    public function save(?int $id, array $in): array {
        $d = [
            'farmer_id'          => (int)($in['farmer_id'] ?? 0),
            'farm_name'          => trim((string)($in['farm_name'] ?? '')),
            'location_id'        => (int)($in['location_id'] ?? 0),
            'area_hectares'      => trim((string)($in['area_hectares'] ?? '')),
            'soil_type_id'       => (int)($in['soil_type_id'] ?? 0),
            'irrigation_type_id' => (int)($in['irrigation_type_id'] ?? 0),
        ];
        $err = [];
        if (!csrf_valid($in['csrf'] ?? null)) $err['form'] = 'Your session expired. Reload the page and try again.';

        if (!array_key_exists($d['farmer_id'], $this->farmers->options())) $err['farmer_id'] = 'Choose a farmer from the list.';
        if ($d['farm_name'] === '' || mb_strlen($d['farm_name']) > 100) $err['farm_name'] = 'Enter a farm name (100 characters max).';
        if (!array_key_exists($d['location_id'], $this->lookups->options('location'))) $err['location_id'] = 'Choose a location from the list.';
        if (!array_key_exists($d['soil_type_id'], $this->lookups->options('soil_type'))) $err['soil_type_id'] = 'Choose a soil type from the list.';
        if (!array_key_exists($d['irrigation_type_id'], $this->lookups->options('irrigation_type'))) $err['irrigation_type_id'] = 'Choose an irrigation type from the list.';

        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $d['area_hectares'])) $err['area_hectares'] = 'Enter a number such as 2.5 (up to 2 decimal places).';
        elseif ((float)$d['area_hectares'] <= 0 || (float)$d['area_hectares'] > 10000) $err['area_hectares'] = 'Area must be more than 0 and at most 10,000 hectares.';

        if (!$err) {
            try {
                $by = current_user_id();
                $id ? $this->farms->update($id, $d, $by) : $this->farms->create($d, $by);
                flash_set('success', $id ? 'Farm updated.' : 'Farm added.');
            } catch (PDOException $ex) {
                if (($ex->errorInfo[0] ?? '') === '23505') $err['farm_name'] = 'This farmer already has a farm with that name.';
                else { error_log($ex->getMessage()); $err['form'] = 'The record could not be saved. Try again.'; }
            }
        }
        return [$err, $d];
    }

    public function delete(array $in): void {
        if (!csrf_valid($in['csrf'] ?? null)) { flash_set('danger', 'Your session expired. Reload the page and try again.'); return; }
        if (!is_admin()) { flash_set('danger', 'Only administrators can delete records.'); return; }
        try {
            $this->farms->delete((int)($in['id'] ?? 0));
            flash_set('success', 'Farm deleted.');
        } catch (PDOException $ex) {
            if (($ex->errorInfo[0] ?? '') === '23503') flash_set('danger', 'This farm still has planting records. Delete or reassign those records first.');
            else { error_log($ex->getMessage()); flash_set('danger', 'The record could not be deleted. Try again.'); }
        }
    }
}