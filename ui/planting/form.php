<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'PlantingModel', 'FarmModel', 'CropModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/PlantingController.php';

$plantings = new PlantingModel($pdo);
$farms     = new FarmModel($pdo);
$crops     = new CropModel($pdo);
$lookups   = new LookupModel($pdo);
$ctl       = new PlantingController($plantings, $farms, $crops, $lookups);

$id     = (int)($_GET['id'] ?? 0);
$record = $id ? $plantings->find($id) : null;
if ($id && !$record) { flash_set('danger', 'That planting record no longer exists.'); header('Location: /ui/planting/index.php'); exit; }

$errors = [];
$data   = $record ?? ['farm_id' => 0, 'crop_id' => 0, 'planting_date' => date('Y-m-d'), 'area_planted' => '', 'seed_quantity' => '', 'expected_harvest_date' => '', 'status_id' => 0];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $data] = $ctl->save($id ?: null, $_POST);
    if (!$errors) { header('Location: /ui/planting/index.php'); exit; }
}

$pageTitle = $id ? 'Edit planting record' : 'Add planting record';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>

<form class="panel" method="post" novalidate style="max-width:680px">
  <?php if (isset($errors['form'])): ?><div class="alert-f1" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <div class="row g-3">
    <div class="col-sm-6">
      <label class="form-label" for="farm_id">Farm</label>
      <select class="form-select<?= invalid($errors, 'farm_id') ?>" id="farm_id" name="farm_id" required>
        <option value="">Choose a farm</option><?php select_options($farms->options(), $data['farm_id']); ?>
      </select>
      <?= feedback($errors, 'farm_id') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="crop_id">Crop</label>
      <select class="form-select<?= invalid($errors, 'crop_id') ?>" id="crop_id" name="crop_id" required>
        <option value="">Choose a crop</option><?php select_options($crops->options(), $data['crop_id']); ?>
      </select>
      <?= feedback($errors, 'crop_id') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="planting_date">Planting date</label>
      <input class="form-control<?= invalid($errors, 'planting_date') ?>" id="planting_date" name="planting_date" type="date" value="<?= e($data['planting_date']) ?>" required>
      <?= feedback($errors, 'planting_date') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="expected_harvest_date">Expected harvest date</label>
      <input class="form-control<?= invalid($errors, 'expected_harvest_date') ?>" id="expected_harvest_date" name="expected_harvest_date" type="date" value="<?= e($data['expected_harvest_date']) ?>" required>
      <?= feedback($errors, 'expected_harvest_date') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="status_id">Status</label>
      <select class="form-select<?= invalid($errors, 'status_id') ?>" id="status_id" name="status_id" required>
        <option value="">Choose a status</option><?php select_options($lookups->options('planting_status'), $data['status_id']); ?>
      </select>
      <?= feedback($errors, 'status_id') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="area_planted">Area planted (hectares)</label>
      <input class="form-control<?= invalid($errors, 'area_planted') ?>" id="area_planted" name="area_planted" type="number" step="0.01" min="0.01"
             inputmode="decimal" value="<?= e($data['area_planted']) ?>" required>
      <?= feedback($errors, 'area_planted') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="seed_quantity">Seed quantity</label>
      <input class="form-control<?= invalid($errors, 'seed_quantity') ?>" id="seed_quantity" name="seed_quantity" type="number" step="0.01" min="0"
             inputmode="decimal" value="<?= e($data['seed_quantity']) ?>" required>
      <?= feedback($errors, 'seed_quantity') ?>
    </div>
  </div>

  <div class="d-flex gap-2 mt-4">
    <button class="btn-f1 btn-inline" type="submit"><?= $id ? 'Save changes' : 'Add planting record' ?></button>
    <a class="btn btn-outline-secondary" href="/ui/planting/index.php">Cancel</a>
    <?php if ($id && is_admin()): ?>
      <button class="btn btn-outline-danger ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal"<?= data_attrs([
          'id' => $id, 'name' => 'planting record #' . $id]) ?>>Delete record</button>
    <?php endif; ?>
  </div>
</form>
<?php if ($id && is_admin()) delete_modal('planting record', '/ui/planting/index.php'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>