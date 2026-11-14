<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'PlantingModel', 'FarmModel', 'CropModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/PlantingController.php';

$plantings = new PlantingModel($pdo);
$lookups   = new LookupModel($pdo);
$ctl       = new PlantingController($plantings, new FarmModel($pdo), new CropModel($pdo), $lookups);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {          // delete
    $ctl->delete($_POST);
    header('Location: /ui/planting/index.php'); exit;
}

$qs  = ['q' => trim($_GET['q'] ?? ''), 'status' => (int)($_GET['status'] ?? 0),
        'sort' => $_GET['sort'] ?? 'id', 'dir' => ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc'];
$res = $plantings->paginate($qs['q'], $qs['status'], $qs['sort'], $qs['dir'], (int)($_GET['page'] ?? 1));
$badge = ['Planned' => 'secondary', 'Growing' => 'success', 'Harvested' => 'primary', 'Failed' => 'danger'];

$pageTitle = 'Planting records';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head">
  <h1>Planting records</h1>
  <a class="btn-f1 btn-inline" href="/ui/planting/form.php">Add planting record</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-5">
    <input class="form-control" type="search" name="q" value="<?= e($qs['q']) ?>" placeholder="Search farm, crop or variety" aria-label="Search planting records">
  </div>
  <div class="col-8 col-md-4">
    <select class="form-select" name="status" aria-label="Filter by status">
      <option value="0">All statuses</option>
      <?php select_options($lookups->options('planting_status'), $qs['status']); ?>
    </select>
  </div>
  <div class="col-4 col-md-3 d-flex gap-2">
    <button class="btn-f1 btn-inline" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="/ui/planting/index.php">Reset</a>
  </div>
</form>

<div class="table-responsive rounded">
  <table class="table table-f1 table-striped table-hover">
    <thead><tr>
      <th><?= sort_th($qs, 'ID', 'id') ?></th><th><?= sort_th($qs, 'Farm', 'farm') ?></th><th><?= sort_th($qs, 'Crop', 'crop') ?></th>
      <th><?= sort_th($qs, 'Planted', 'planted') ?></th><th><?= sort_th($qs, 'Expected harvest', 'expected') ?></th>
      <th class="text-end">Area (ha)</th><th>Status</th><th class="text-end">Actions</th>
    </tr></thead>
    <tbody>
    <?php if (!$res['rows']): ?>
      <tr><td colspan="8" class="text-center text-secondary py-4">No planting records found. Try a different search, or add a record.</td></tr>
    <?php endif; ?>
    <?php foreach ($res['rows'] as $r): $crop = $r['crop_name'] . ' (' . $r['variety'] . ')'; ?>
      <tr>
        <td><?= (int)$r['planting_id'] ?></td>
        <td><?= e($r['farm_name']) ?></td>
        <td><?= e($r['crop_name']) ?></td>
        <td><?= e($r['planting_date']) ?></td>
        <td><?= e($r['expected_harvest_date']) ?></td>
        <td class="text-end"><?= number_format((float)$r['area_planted'], 2) ?></td>
        <td><span class="badge text-bg-<?= $badge[$r['status_name']] ?? 'secondary' ?>"><?= e($r['status_name']) ?></span></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal"<?= data_attrs([
              'farm' => $r['farm_name'], 'crop' => $crop, 'planted' => $r['planting_date'], 'expected' => $r['expected_harvest_date'],
              'area' => number_format((float)$r['area_planted'], 2) . ' hectares', 'seed' => number_format((float)$r['seed_quantity'], 2), 'status' => $r['status_name'],
              'created' => audit_text($r['created_at'], $r['created_by_name']), 'updated' => audit_text($r['updated_at'], $r['updated_by_name'])]) ?>>View</button>
          <a class="btn btn-sm btn-outline-secondary" href="/ui/planting/form.php?id=<?= (int)$r['planting_id'] ?>">Edit</a>
          <?php if (is_admin()): ?>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"<?= data_attrs([
                'id' => $r['planting_id'], 'name' => $r['farm_name'] . ' / ' . $r['crop_name'] . ' planted ' . $r['planting_date']]) ?>>Delete</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php pagination($res, $qs, 'planting record'); ?>
<?php view_modal('Planting record', ['Farm' => 'farm', 'Crop' => 'crop', 'Planting date' => 'planted', 'Expected harvest' => 'expected', 'Area planted' => 'area',
                                     'Seed quantity' => 'seed', 'Status' => 'status', 'Created' => 'created', 'Last updated' => 'updated']); ?>
<?php delete_modal('planting record'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>