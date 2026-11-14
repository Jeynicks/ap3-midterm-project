<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'FarmModel', 'FarmerModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/FarmController.php';

$farms   = new FarmModel($pdo);
$farmers = new FarmerModel($pdo);
$lookups = new LookupModel($pdo);
$ctl     = new FarmController($farms, $farmers, $lookups);

$id     = (int)($_GET['id'] ?? 0);
$record = $id ? $farms->find($id) : null;
if ($id && !$record) { flash_set('danger', 'That farm no longer exists.'); header('Location: /ui/farms/index.php'); exit; }

$errors = [];
$data   = $record ?? ['farmer_id' => 0, 'farm_name' => '', 'location_id' => 0, 'area_hectares' => '', 'soil_type_id' => 0, 'irrigation_type_id' => 0];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $data] = $ctl->save($id ?: null, $_POST);
    if (!$errors) { header('Location: /ui/farms/index.php'); exit; }
}

$pageTitle = $id ? 'Edit farm' : 'Add farm';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>

<form class="panel" method="post" novalidate style="max-width:640px">
  <?php if (isset($errors['form'])): ?><div class="alert-f1" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <div class="row g-3">
    <div class="col-12">
      <label class="form-label" for="farm_name">Farm name</label>
      <input class="form-control<?= invalid($errors, 'farm_name') ?>" id="farm_name" name="farm_name" maxlength="100" value="<?= e($data['farm_name']) ?>" required>
      <?= feedback($errors, 'farm_name') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="farmer_id">Farmer</label>
      <select class="form-select<?= invalid($errors, 'farmer_id') ?>" id="farmer_id" name="farmer_id" required>
        <option value="">Choose a farmer</option><?php select_options($farmers->options(), $data['farmer_id']); ?>
      </select>
      <?= feedback($errors, 'farmer_id') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="location_id">Location</label>
      <select class="form-select<?= invalid($errors, 'location_id') ?>" id="location_id" name="location_id" required>
        <option value="">Choose a location</option><?php select_options($lookups->options('location'), $data['location_id']); ?>
      </select>
      <?= feedback($errors, 'location_id') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="area_hectares">Area (hectares)</label>
      <input class="form-control<?= invalid($errors, 'area_hectares') ?>" id="area_hectares" name="area_hectares" type="number" step="0.01" min="0.01" max="10000"
             inputmode="decimal" value="<?= e($data['area_hectares']) ?>" required>
      <?= feedback($errors, 'area_hectares') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="soil_type_id">Soil type</label>
      <select class="form-select<?= invalid($errors, 'soil_type_id') ?>" id="soil_type_id" name="soil_type_id" required>
        <option value="">Choose soil type</option><?php select_options($lookups->options('soil_type'), $data['soil_type_id']); ?>
      </select>
      <?= feedback($errors, 'soil_type_id') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="irrigation_type_id">Irrigation type</label>
      <select class="form-select<?= invalid($errors, 'irrigation_type_id') ?>" id="irrigation_type_id" name="irrigation_type_id" required>
        <option value="">Choose irrigation</option><?php select_options($lookups->options('irrigation_type'), $data['irrigation_type_id']); ?>
      </select>
      <?= feedback($errors, 'irrigation_type_id') ?>
    </div>
  </div>

  <div class="d-flex gap-2 mt-4">
    <button class="btn-f1 btn-inline" type="submit"><?= $id ? 'Save changes' : 'Add farm' ?></button>
    <a class="btn btn-outline-secondary" href="/ui/farms/index.php">Cancel</a>
    <?php if ($id && is_admin()): ?>
      <button class="btn btn-outline-danger ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal"<?= data_attrs(['id' => $id, 'name' => $record['farm_name']]) ?>>Delete farm</button>
    <?php endif; ?>
  </div>
</form>
<?php if ($id && is_admin()) delete_modal('farm', '/ui/farms/index.php'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>