<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'FarmModel', 'FarmerModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/FarmController.php';

$farms   = new FarmModel($pdo);
$lookups = new LookupModel($pdo);
$ctl     = new FarmController($farms, new FarmerModel($pdo), $lookups);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {          // delete
    $ctl->delete($_POST);
    header('Location: /ui/farms/index.php'); exit;
}

$qs  = ['q' => trim($_GET['q'] ?? ''), 'irrigation' => (int)($_GET['irrigation'] ?? 0),
        'sort' => $_GET['sort'] ?? 'id', 'dir' => ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc'];
$res = $farms->paginate($qs['q'], $qs['irrigation'], $qs['sort'], $qs['dir'], (int)($_GET['page'] ?? 1));

$pageTitle = 'Farms';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head">
  <h1>Farms</h1>
  <a class="btn-f1 btn-inline" href="/ui/farms/form.php">Add farm</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-5">
    <input class="form-control" type="search" name="q" value="<?= e($qs['q']) ?>" placeholder="Search farm, farmer or city" aria-label="Search farms">
  </div>
  <div class="col-8 col-md-4">
    <select class="form-select" name="irrigation" aria-label="Filter by irrigation type">
      <option value="0">All irrigation types</option>
      <?php select_options($lookups->options('irrigation_type'), $qs['irrigation']); ?>
    </select>
  </div>
  <div class="col-4 col-md-3 d-flex gap-2">
    <button class="btn-f1 btn-inline" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="/ui/farms/index.php">Reset</a>
  </div>
</form>

<div class="table-responsive rounded">
  <table class="table table-f1 table-striped table-hover">
    <thead><tr>
      <th><?= sort_th($qs, 'ID', 'id') ?></th><th><?= sort_th($qs, 'Farm', 'name') ?></th><th><?= sort_th($qs, 'Farmer', 'farmer') ?></th>
      <th>Location</th><th class="text-end"><?= sort_th($qs, 'Area (ha)', 'area') ?></th><th>Soil</th><th>Irrigation</th><th class="text-end">Actions</th>
    </tr></thead>
    <tbody>
    <?php if (!$res['rows']): ?>
      <tr><td colspan="8" class="text-center text-secondary py-4">No farms found. Try a different search, or add a farm.</td></tr>
    <?php endif; ?>
    <?php foreach ($res['rows'] as $r): $loc = $r['city_municipality'] . ', ' . $r['province']; ?>
      <tr>
        <td><?= (int)$r['farm_id'] ?></td>
        <td><?= e($r['farm_name']) ?></td>
        <td><?= e($r['farmer_name']) ?></td>
        <td><?= e($loc) ?></td>
        <td class="text-end"><?= number_format((float)$r['area_hectares'], 2) ?></td>
        <td><?= e($r['soil_type']) ?></td>
        <td><?= e($r['irrigation_type']) ?></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal"<?= data_attrs([
              'farm' => $r['farm_name'], 'farmer' => $r['farmer_name'], 'location' => $loc,
              'area' => number_format((float)$r['area_hectares'], 2) . ' hectares', 'soil' => $r['soil_type'], 'irrigation' => $r['irrigation_type'],
              'created' => audit_text($r['created_at'], $r['created_by_name']), 'updated' => audit_text($r['updated_at'], $r['updated_by_name'])]) ?>>View</button>
          <a class="btn btn-sm btn-outline-secondary" href="/ui/farms/form.php?id=<?= (int)$r['farm_id'] ?>">Edit</a>
          <?php if (is_admin()): ?>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"<?= data_attrs(['id' => $r['farm_id'], 'name' => $r['farm_name']]) ?>>Delete</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php pagination($res, $qs, 'farm'); ?>
<?php view_modal('Farm details', ['Farm' => 'farm', 'Farmer' => 'farmer', 'Location' => 'location', 'Area' => 'area', 'Soil type' => 'soil',
                                  'Irrigation' => 'irrigation', 'Created' => 'created', 'Last updated' => 'updated']); ?>
<?php delete_modal('farm'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>