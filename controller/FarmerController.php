<?php
class FarmerController {
    public function __construct(private FarmerModel $farmers, private LookupModel $lookups) {}

    // Returns [errors, cleanedData]. On success errors is empty and a flash message is queued.
    public function save(?int $id, array $in): array {
        $d = [
            'first_name'     => trim((string)($in['first_name'] ?? '')),
            'last_name'      => trim((string)($in['last_name'] ?? '')),
            'contact_number' => preg_replace('/\s+/', '', (string)($in['contact_number'] ?? '')),
            'location_id'    => (int)($in['location_id'] ?? 0),
        ];
        $err = [];
        if (!csrf_valid($in['csrf'] ?? null)) $err['form'] = 'Your session expired. Reload the page and try again.';
        if ($d['first_name'] === '' || mb_strlen($d['first_name']) > 50) $err['first_name'] = 'Enter a first name (50 characters max).';
        if ($d['last_name']  === '' || mb_strlen($d['last_name'])  > 50) $err['last_name']  = 'Enter a last name (50 characters max).';
        if (!preg_match('/^09\d{9}$/', $d['contact_number'])) $err['contact_number'] = 'Enter an 11-digit number starting with 09.';
        if (!array_key_exists($d['location_id'], $this->lookups->options('location'))) $err['location_id'] = 'Choose a location from the list.';

        if (!$err) {
            try {
                $id ? $this->farmers->update($id, $d) : $this->farmers->create($d);
                flash_set('success', $id ? 'Farmer updated.' : 'Farmer added.');
            } catch (PDOException $ex) {
                if (($ex->errorInfo[0] ?? '') === '23505') $err['contact_number'] = 'This contact number is already registered.';
                else { error_log($ex->getMessage()); $err['form'] = 'The record could not be saved. Try again.'; }
            }
        }
        return [$err, $d];
    }

    public function delete(array $in): void {
        if (!csrf_valid($in['csrf'] ?? null)) { flash_set('danger', 'Your session expired. Reload the page and try again.'); return; }
        if (!is_admin()) { flash_set('danger', 'Only administrators can delete records.'); return; }
        try {
            $this->farmers->delete((int)($in['id'] ?? 0));
            flash_set('success', 'Farmer deleted.');
        } catch (PDOException $ex) {
            if (($ex->errorInfo[0] ?? '') === '23503') flash_set('danger', 'This farmer still has farms. Delete or reassign those farms first.');
            else { error_log($ex->getMessage()); flash_set('danger', 'The record could not be deleted. Try again.'); }
        }
    }
}