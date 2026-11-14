<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../model/DashboardModel.php';
require __DIR__ . '/../controller/DashboardController.php';

$user = $_SESSION['user'];
$d    = (new DashboardController(new DashboardModel($pdo)))->summary();
$t    = $d['totals'];

// A module link only appears once its page exists, so the dashboard never has broken links.
$href  = fn(string $p) => file_exists(__DIR__ . '/..' . $p) ? $p : null;
$cards = [
    ['Farmers',          $t['farmers'],   '/ui/farmers/index.php'],
    ['Farms',            $t['farms'],     '/ui/farms/index.php'],
    ['Crops',            $t['crops'],     '/ui/crops/index.php'],
    ['Planting records', $t['plantings'], '/ui/planting/index.php'],
    ['Harvest records',  $t['harvests'],  '/ui/harvest/index.php'],
];
$maxStatus = max(1, ...array_map(fn($s) => (int)$s['total'], $d['statuses'] ?: [['total' => 1]]));
$badge = ['Planned' => 'secondary', 'Growing' => 'success', 'Harvested' => 'primary', 'Failed' => 'danger'];

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1>Dashboard</h1>
  <span class="text-secondary">Welcome, <?= e($user['full_name']) ?></span>
</div>

<div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
  <?php foreach ($cards as [$label, $count, $path]): $link = $href($path); ?>
    <div class="col">
      <?php if ($link): ?>
        <a class="stat-card" href="<?= e($link) ?>">
      <?php else: ?>
        <div class="stat-card stat-off">
      <?php endif; ?>
          <div class="num"><?= number_format((int)$count) ?></div>
          <div class="lbl"><?= e($label) ?></div>
          <div class="go"><?= $link ? 'Open ' . e(strtolower($label)) : 'Page not available yet' ?></div>
      <?= $link ? '</a>' : '</div>' ?>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-5">
    <div class="panel-lite">
      <h2>Harvest totals</h2>
      <div class="row text-center mb-4">
        <div class="col-6"><div class="big-num"><?= number_format((float)$t['total_kg'], 2) ?></div><div class="text-secondary">kg harvested</div></div>
        <div class="col-6"><div class="big-num">₱<?= number_format((float)$t['total_value'], 2) ?></div><div class="text-secondary">estimated value</div></div>
      </div>
      <h2>Plantings by status</h2>
      <?php foreach ($d['statuses'] as $s): ?>
        <div class="bar-row">
          <div class="d-flex justify-content-between"><span><?= e($s['status_name']) ?></span><strong><?= (int)$s['total'] ?></strong></div>
          <div class="bar-track" role="presentation"><div class="bar-fill" style="width:<?= round(100 * (int)$s['total'] / $maxStatus) ?>%"></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="panel-lite">
      <h2>Recent planting records</h2>
      <div class="table-responsive">
        <table class="table table-f1 table-striped">
          <thead><tr><th>Farm</th><th>Crop</th><th>Planted</th><th>Expected harvest</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (!$d['plantings']): ?><tr><td colspan="5" class="text-center text-secondary py-3">No planting records yet.</td></tr><?php endif; ?>
          <?php foreach ($d['plantings'] as $r): ?>
            <tr>
              <td><?= e($r['farm_name']) ?></td>
              <td><?= e($r['crop_name']) ?> <span class="text-secondary small"><?= e($r['variety']) ?></span></td>
              <td><?= e($r['planting_date']) ?></td>
              <td><?= e($r['expected_harvest_date']) ?></td>
              <td><span class="badge text-bg-<?= $badge[$r['status_name']] ?? 'secondary' ?>"><?= e($r['status_name']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="panel-lite">
  <h2>Recent harvest records</h2>
  <div class="table-responsive">
    <table class="table table-f1 table-striped">
      <thead><tr><th>Harvested</th><th>Farm</th><th>Crop</th><th class="text-end">Quantity (kg)</th><th class="text-end">Price per kg</th><th>Grade</th><th>Remarks</th></tr></thead>
      <tbody>
      <?php if (!$d['harvests']): ?><tr><td colspan="7" class="text-center text-secondary py-3">No harvest records yet.</td></tr><?php endif; ?>
      <?php foreach ($d['harvests'] as $r): ?>
        <tr>
          <td><?= e($r['harvest_date']) ?></td>
          <td><?= e($r['farm_name']) ?></td>
          <td><?= e($r['crop_name']) ?></td>
          <td class="text-end"><?= number_format((float)$r['quantity_kg'], 2) ?></td>
          <td class="text-end">₱<?= number_format((float)$r['selling_price_per_kg'], 2) ?></td>
          <td><?= e($r['grade_name']) ?></td>
          <td><?= e($r['remark_description']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>