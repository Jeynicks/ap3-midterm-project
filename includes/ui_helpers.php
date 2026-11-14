<?php
// Small view helpers shared by the list and form pages (Farms, Crops and the modules after them).
function list_url(array $qs, array $over = []): string { return '?' . http_build_query(array_merge($qs, $over)); }

function sort_th(array $qs, string $label, string $key): string {
    $next  = ($qs['sort'] === $key && $qs['dir'] === 'asc') ? 'desc' : 'asc';
    $arrow = $qs['sort'] === $key ? ($qs['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a class="text-decoration-none text-reset" href="' . e(list_url($qs, ['sort' => $key, 'dir' => $next, 'page' => 1])) . '">' . e($label . $arrow) . '</a>';
}

function pagination(array $res, array $qs, string $noun): void {
    $t = $res['total'];
    echo '<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">'
       . '<span class="text-secondary small">' . $t . ' ' . e($noun) . ($t === 1 ? '' : 's') . ' · page ' . $res['page'] . ' of ' . $res['pages'] . '</span>'
       . '<ul class="pagination pagination-sm mb-0">'
       . '<li class="page-item' . ($res['page'] <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . e(list_url($qs, ['page' => $res['page'] - 1])) . '">Previous</a></li>'
       . '<li class="page-item' . ($res['page'] >= $res['pages'] ? ' disabled' : '') . '"><a class="page-link" href="' . e(list_url($qs, ['page' => $res['page'] + 1])) . '">Next</a></li>'
       . '</ul></div>';
}

function data_attrs(array $a): string {            // ['name' => 'x'] -> ' data-name="x"'
    $o = '';
    foreach ($a as $k => $v) $o .= ' data-' . $k . '="' . e((string)$v) . '"';
    return $o;
}

function audit_text(?string $ts, ?string $by): string { return fmt_dt($ts) . ' by ' . ($by ?? 'not recorded'); }

function select_options(array $opts, $selected): void {
    foreach ($opts as $id => $label) {
        echo '<option value="' . (int)$id . '"' . ((int)$selected === (int)$id ? ' selected' : '') . '>' . e($label) . '</option>';
    }
}

function invalid(array $errors, string $k): string { return isset($errors[$k]) ? ' is-invalid' : ''; }
function feedback(array $errors, string $k): string { return isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : ''; }

// $rows = ['Label' => 'data-key'], filled from the data-* attributes of the button that opened it.
function view_modal(string $title, array $rows): void {
    echo '<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">'
       . '<div class="modal-header"><h2 class="modal-title fs-5" id="viewTitle">' . e($title) . '</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
       . '<div class="modal-body"><dl class="detail-list mb-0">';
    foreach ($rows as $label => $key) echo '<dt>' . e($label) . '</dt><dd data-fill="' . e($key) . '"></dd>';
    echo '</dl></div></div></div></div>';
}

// The opening button needs data-id and data-name.
function delete_modal(string $noun, string $action = ''): void {
    echo '<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered">'
       . '<form class="modal-content" method="post" action="' . e($action) . '">'
       . '<div class="modal-header"><h2 class="modal-title fs-5" id="deleteTitle">Delete ' . e($noun) . '</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
       . '<div class="modal-body">Delete <strong data-fill="name"></strong> permanently? This can\'t be undone.'
       . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="id" data-fill="id"></div>'
       . '<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>'
       . '<button type="submit" class="btn btn-danger">Delete ' . e($noun) . '</button></div></form></div></div>';
}

function is_valid_date(string $s): bool {       // strict YYYY-MM-DD that is a real calendar date
    $dt = DateTime::createFromFormat('Y-m-d', $s);
    return $dt && $dt->format('Y-m-d') === $s;
}