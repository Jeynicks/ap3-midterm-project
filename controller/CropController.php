<?php
class CropController {
    public function __construct(private CropModel $crops, private LookupModel $lookups) {}

    // Returns [errors, cleanedData]. On success errors is empty and a flash message is queued.
    public function save(?int $id, array $in): array {
        $d = [
            'crop_name'           => trim((string)($in['crop_name'] ?? '')),
            'variety'             => trim((string)($in['variety'] ?? '')),
            'category_id'         => (int)($in['category_id'] ?? 0),
            'growing_period_days' => trim((string)($in['growing_period_days'] ?? '')),
            'season_id'           => (int)($in['season_id'] ?? 0),
        ];
        $err = [];
        if (!csrf_valid($in['csrf'] ?? null)) $err['form'] = 'Your session expired. Reload the page and try again.';

        if ($d['crop_name'] === '' || mb_strlen($d['crop_name']) > 80) $err['crop_name'] = 'Enter a crop name (80 characters max).';
        if ($d['variety'] === ''   || mb_strlen($d['variety'])   > 80) $err['variety']   = 'Enter a variety (80 characters max).';
        if (!array_key_exists($d['category_id'], $this->lookups->options('crop_category'))) $err['category_id'] = 'Choose a category from the list.';
        if (!array_key_exists($d['season_id'], $this->lookups->options('growing_season'))) $err['season_id'] = 'Choose a season from the list.';

        if (!preg_match('/^\d{1,4}$/', $d['growing_period_days'])) $err['growing_period_days'] = 'Enter a whole number of days.';
        elseif ((int)$d['growing_period_days'] < 1 || (int)$d['growing_period_days'] > 1000) $err['growing_period_days'] = 'Growing period must be between 1 and 1,000 days.';

        if (!$err) {
            try {
                $by = current_user_id();
                $id ? $this->crops->update($id, $d, $by) : $this->crops->create($d, $by);
                flash_set('success', $id ? 'Crop updated.' : 'Crop added.');
            } catch (PDOException $ex) {
                if (($ex->errorInfo[0] ?? '') === '23505') $err['variety'] = 'This crop and variety already exist.';
                else { error_log($ex->getMessage()); $err['form'] = 'The record could not be saved. Try again.'; }
            }
        }
        return [$err, $d];
    }

    public function delete(array $in): void {
        if (!csrf_valid($in['csrf'] ?? null)) { flash_set('danger', 'Your session expired. Reload the page and try again.'); return; }
        if (!is_admin()) { flash_set('danger', 'Only administrators can delete records.'); return; }
        try {
            $this->crops->delete((int)($in['id'] ?? 0));
            flash_set('success', 'Crop deleted.');
        } catch (PDOException $ex) {
            if (($ex->errorInfo[0] ?? '') === '23503') flash_set('danger', 'This crop is used in planting records. Delete or reassign those records first.');
            else { error_log($ex->getMessage()); flash_set('danger', 'The record could not be deleted. Try again.'); }
        }
    }
}