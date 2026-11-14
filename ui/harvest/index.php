<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'HarvestModel', 'PlantingModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/HarvestController.php';

$harvests = new HarvestModel($pdo);
$lookups  = new LookupModel($pdo);
$ctl      = new HarvestController($harvests, new PlantingModel($pdo), $lookups);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {          // delete
    $ctl->delete($_POST);
    header('Location: /ui/harvest/index.php'); exit;
}

$qs  = ['q' => trim($_GET['q'] ?? ''), 'grade' => (int)($_GET['grade'] ?? 0),
        'sort' => $_GET['sort'] ?? 'id', 'dir' => ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc'];
$res = $harvests->paginate($qs['q'], $qs['grade'], $qs['sort'], $qs['dir'], (int)($_GET['page'] ?? 1));

$pageTitle = 'Harvest records';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head">
  <h1>Harvest records</h1>
  <a class="btn-f1 btn-inline" href="/ui/harvest/form.php">Add harvest record</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-5">
    <input class="form-control" type="search" name="q" value="<?= e($qs['q']) ?>" placeholder="Search farm, crop or remarks" aria-label="Search harvest records">
  </div>
  <div class="col-8 col-md-4">
    <select class="form-select" name="grade" aria-label="Filter by quality grade">
      <option value="0">All quality grades</option>
      <?php select_options($lookups->options('quality_grade'), $qs['grade']); ?>
    </select>
  </div>
  <div class="col-4 col-md-3 d-flex gap-2">
    <button class="btn-f1 btn-inline" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="/ui/harvest/index.php">Reset</a>
  </div>
</form>

<div class="table-responsive rounded">
  <table class="table table-f1 table-striped table-hover">
    <thead><tr>
      <th><?= sort_th($qs, 'ID', 'id') ?></th><th><?= sort_th($qs, 'Farm', 'farm') ?></th><th><?= sort_th($qs, 'Crop', 'crop') ?></th>
      <th><?= sort_th($qs, 'Harvested', 'date') ?></th><th class="text-end"><?= sort_th($qs, 'Quantity (kg)', 'qty') ?></th>
      <th class="text-end"><?= sort_th($qs, 'Price per kg', 'price') ?></th><th class="text-end">Estimated value</th><th>Grade</th><th class="text-end">Actions</th>
    </tr></thead>
    <tbody>
    <?php if (!$res['rows']): ?>
      <tr><td colspan="9" class="text-center text-secondary py-4">No harvest records found. Try a different search, or add a record.</td></tr>
    <?php endif; ?>
    <?php foreach ($res['rows'] as $r): $value = number_format((float)$r['est_value'], 2); ?>
      <tr>
        <td><?= (int)$r['harvest_id'] ?></td>
        <td><?= e($r['farm_name']) ?></td>
        <td><?= e($r['crop_name']) ?></td>
        <td><?= e($r['harvest_date']) ?></td>
        <td class="text-end"><?= number_format((float)$r['quantity_kg'], 2) ?></td>
        <td class="text-end">₱<?= number_format((float)$r['selling_price_per_kg'], 2) ?></td>
        <td class="text-end">₱<?= $value ?></td>
        <td><?= e($r['grade_name']) ?></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal"<?= data_attrs([
              'farm' => $r['farm_name'], 'crop' => $r['crop_name'] . ' (' . $r['variety'] . ')', 'planted' => $r['planting_date'], 'harvested' => $r['harvest_date'],
              'qty' => number_format((float)$r['quantity_kg'], 2) . ' kg', 'price' => '₱' . number_format((float)$r['selling_price_per_kg'], 2) . ' per kg',
              'value' => '₱' . $value, 'grade' => $r['grade_name'], 'remarks' => $r['remark_description'],
              'created' => audit_text($r['created_at'], $r['created_by_name']), 'updated' => audit_text($r['updated_at'], $r['updated_by_name'])]) ?>>View</button>
          <a class="btn btn-sm btn-outline-secondary" href="/ui/harvest/form.php?id=<?= (int)$r['harvest_id'] ?>">Edit</a>
          <?php if (is_admin()): ?>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"<?= data_attrs([
                'id' => $r['harvest_id'], 'name' => $r['farm_name'] . ' / ' . $r['crop_name'] . ' harvested ' . $r['harvest_date']]) ?>>Delete</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php pagination($res, $qs, 'harvest record'); ?>
<?php view_modal('Harvest record', ['Farm' => 'farm', 'Crop' => 'crop', 'Planting date' => 'planted', 'Harvest date' => 'harvested', 'Quantity' => 'qty',
                                    'Selling price' => 'price', 'Estimated value' => 'value', 'Quality grade' => 'grade', 'Remarks' => 'remarks',
                                    'Created' => 'created', 'Last updated' => 'updated']); ?>
<?php delete_modal('harvest record'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>