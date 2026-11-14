<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'ReportModel', 'FarmModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/ReportController.php';

$ctl = new ReportController(new ReportModel($pdo), new FarmModel($pdo), new LookupModel($pdo));
extract($ctl->build($_GET));          // $key $def $from $to $extra $error $rows $extraLabel $extraOptions

$fmt = function ($v, string $type): string {
    if ($v === null) return '—';
    return match ($type) {
        'dec'   => number_format((float)$v, 2),
        'int'   => number_format((int)$v),
        'money' => '₱' . number_format((float)$v, 2),
        default => e((string)$v),
    };
};
$totals = [];
foreach ($def['cols'] as [$k, , , $sum]) {
    if (!empty($sum)) $totals[$k] = array_sum(array_map(fn($r) => (float)$r[$k], $rows));
}

$pageTitle = 'Reports';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head no-print">
  <h1>Reports</h1>
  <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Print report</button>
</div>

<ul class="nav nav-pills mb-3 no-print">
  <?php foreach (ReportController::DEF as $k => $d): ?>
    <li class="nav-item">
      <a class="nav-link<?= $k === $key ? ' active' : '' ?>" href="?<?= e(http_build_query(['report' => $k, 'from' => $from, 'to' => $to])) ?>"><?= e($d['title']) ?></a>
    </li>
  <?php endforeach; ?>
</ul>

<form class="row g-2 align-items-end mb-3 no-print" method="get">
  <input type="hidden" name="report" value="<?= e($key) ?>">
  <div class="col-6 col-md-3">
    <label class="form-label" for="from">From (<?= e(strtolower($def['dateLabel'])) ?>)</label>
    <input class="form-control" type="date" id="from" name="from" value="<?= e($from) ?>">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label" for="to">To</label>
    <input class="form-control" type="date" id="to" name="to" value="<?= e($to) ?>">
  </div>
  <?php if ($extraLabel): ?>
    <div class="col-12 col-md-3">
      <label class="form-label" for="extra">Filter</label>
      <select class="form-select" id="extra" name="extra">
        <option value="0"><?= e($extraLabel) ?></option><?php select_options($extraOptions, $extra); ?>
      </select>
    </div>
  <?php endif; ?>
  <div class="col-12 col-md-3 d-flex gap-2">
    <button class="btn-f1 btn-inline" type="submit">Run report</button>
    <a class="btn btn-outline-secondary" href="?report=<?= e($key) ?>">Reset</a>
  </div>
</form>

<div class="panel-lite">
  <h2 class="mb-1"><?= e($def['title']) ?></h2>
  <p class="text-secondary">
    <?= e($def['note']) ?>
    <?= ($from || $to) ? 'Range: ' . e($from ?: 'start') . ' to ' . e($to ?: 'today and beyond') . '.' : 'Showing all dates.' ?>
    Generated <?= e(fmt_dt(date('c'))) ?>.
  </p>

  <?php if ($error): ?>
    <div class="alert-f1" role="alert"><?= e($error) ?></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-f1 table-striped">
        <thead><tr>
          <?php foreach ($def['cols'] as [$k, $label, $type]): ?>
            <th class="<?= in_array($type ?? 'text', ['dec', 'int', 'money'], true) ? 'text-end' : '' ?>"><?= e($label) ?></th>
          <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="<?= count($def['cols']) ?>" class="text-center text-secondary py-4">No records match these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <?php foreach ($def['cols'] as [$k, , $type]): $type = $type ?? 'text'; ?>
              <td class="<?= $type !== 'text' ? 'text-end' : '' ?>"><?= $fmt($r[$k], $type) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($rows && $totals): ?>
          <tfoot><tr class="fw-bold">
            <?php foreach ($def['cols'] as $i => [$k, , $type]): $type = $type ?? 'text'; ?>
              <td class="<?= $type !== 'text' ? 'text-end' : '' ?>"><?= $i === 0 ? 'Total' : (isset($totals[$k]) ? $fmt($totals[$k], $type) : '') ?></td>
            <?php endforeach; ?>
          </tr></tfoot>
        <?php endif; ?>
      </table>
    </div>
    <p class="text-secondary small mb-0"><?= count($rows) ?> row<?= count($rows) === 1 ? '' : 's' ?></p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>