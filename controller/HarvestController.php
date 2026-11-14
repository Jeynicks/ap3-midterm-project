<?php
class HarvestController {
    public function __construct(private HarvestModel $harvests, private PlantingModel $plantings, private LookupModel $lookups) {}

    // Returns [errors, cleanedData]. On success errors is empty and a flash message is queued.
    public function save(?int $id, array $in): array {
        $d = [
            'planting_id'          => (int)($in['planting_id'] ?? 0),
            'harvest_date'         => trim((string)($in['harvest_date'] ?? '')),
            'quantity_kg'          => trim((string)($in['quantity_kg'] ?? '')),
            'selling_price_per_kg' => trim((string)($in['selling_price_per_kg'] ?? '')),
            'grade_id'             => (int)($in['grade_id'] ?? 0),
            'remark_id'            => (int)($in['remark_id'] ?? 0),
        ];
        $err = [];
        if (!csrf_valid($in['csrf'] ?? null)) $err['form'] = 'Your session expired. Reload the page and try again.';

        $planting = $this->plantings->find($d['planting_id']);
        if (!$planting) $err['planting_id'] = 'Choose a planting record from the list.';
        if (!array_key_exists($d['grade_id'], $this->lookups->options('quality_grade'))) $err['grade_id'] = 'Choose a quality grade from the list.';
        if (!array_key_exists($d['remark_id'], $this->lookups->options('harvest_remark'))) $err['remark_id'] = 'Choose a remark from the list.';

        if (!is_valid_date($d['harvest_date'])) $err['harvest_date'] = 'Enter a valid harvest date.';
        elseif ($planting && $d['harvest_date'] < $planting['planting_date'])
            $err['harvest_date'] = 'The harvest date cannot be before the planting date (' . $planting['planting_date'] . ').';

        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $d['quantity_kg'])) $err['quantity_kg'] = 'Enter a number such as 1250.5 (up to 2 decimal places).';
        elseif ((float)$d['quantity_kg'] <= 0) $err['quantity_kg'] = 'Quantity must be more than 0 kg.';

        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $d['selling_price_per_kg'])) $err['selling_price_per_kg'] = 'Enter a price such as 32.50 (up to 2 decimal places).';
        elseif ((float)$d['selling_price_per_kg'] <= 0) $err['selling_price_per_kg'] = 'Price must be more than 0.';

        if (!$err) {
            try {
                $by = current_user_id();
                $id ? $this->harvests->update($id, $d, $by) : $this->harvests->create($d, $by);
                flash_set('success', $id ? 'Harvest record updated.' : 'Harvest record added.');
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
            $this->harvests->delete((int)($in['id'] ?? 0));
            flash_set('success', 'Harvest record deleted.');
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            flash_set('danger', 'The record could not be deleted. Try again.');
        }
    }
}