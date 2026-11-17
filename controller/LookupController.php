<?php
class LookupController {
    public function __construct(private LookupModel $lookups) {}

    public function save(string $key, array $in): array {
        $c = $this->lookups->config($key);
        $d = [];
        foreach (array_keys($c['fields']) as $col) $d[$col] = trim((string)($in[$col] ?? ''));
        $err = [];
        if (!csrf_valid($in['csrf'] ?? null)) $err['form'] = 'Your session expired. Reload the page and try again.';

        foreach ($c['fields'] as $col => $meta) {
            if ($d[$col] === '' || mb_strlen($d[$col]) > $meta['max']) {
                $err[$col] = 'Enter a value (up to ' . $meta['max'] . ' characters).';
            }
        }

        if (!$err) {
            try {
                $this->lookups->add($key, $d);
                flash_set('success', metaLabel($c) . ' added.');
            } catch (PDOException $ex) {
                if (($ex->errorInfo[0] ?? '') === '23505') $err['form'] = 'That value already exists.';
                else { error_log($ex->getMessage()); $err['form'] = 'The record could not be saved. Try again.'; }
            }
        }
        return [$err, $d];
    }

    public function delete(string $key, array $in): void {
        if (!csrf_valid($in['csrf'] ?? null)) { flash_set('danger', 'Your session expired. Reload the page and try again.'); return; }
        if (!is_admin()) { flash_set('danger', 'Only administrators can delete records.'); return; }
        $c = $this->lookups->config($key);
        try {
            $this->lookups->delete($key, (int)($in['id'] ?? 0));
            flash_set('success', 'Entry deleted.');
        } catch (PDOException $ex) {
            if (($ex->errorInfo[0] ?? '') === '23503') flash_set('danger', 'This value is still in use. Reassign or delete dependent records first.');
            else { error_log($ex->getMessage()); flash_set('danger', 'The record could not be deleted. Try again.'); }
        }
    }
}

function metaLabel(array $c): string {
    return array_values($c['fields'])[0]['label'] ?? 'Entry';
}
