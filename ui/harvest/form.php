<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'HarvestModel', 'PlantingModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/HarvestController.php';

$harvests  = new HarvestModel($pdo);
$plantings = new PlantingModel($pdo);
$lookups   = new LookupModel($pdo);
$ctl       = new HarvestController($harvests, $plantings, $lookups);

$id     = (int)($_GET['id'] ?? 0);
$record = $id ? $harvests->find($id) : null;
if ($id && !$record) { flash_set('danger', 'That harvest record no longer exists.'); header('Location: /ui/harvest/index.php'); exit; }

$errors = [];
$data   = $record ?? ['planting_id' => 0, 'harvest_date' => date('Y-m-d'), 'quantity_kg' => '', 'selling_price_per_kg' => '', 'grade_id' => 0, 'remark_id' => 0];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $data] = $ctl->save($id ?: null, $_POST);
    if (!$errors) { header('Location: /ui/harvest/index.php'); exit; }
}

$pageTitle = $id ? 'Edit harvest record' : 'Add harvest record';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>

<form class="panel" method="post" novalidate style="max-width:680px">
  <?php if (isset($errors['form'])): ?><div class="alert-f1" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <div class="row g-3">
    <div class="col-12">
      <label class="form-label" for="planting_id">Planting record</label>
      <select class="form-select<?= invalid($errors, 'planting_id') ?>" id="planting_id" name="planting_id" required>
        <option value="">Choose a planting record</option><?php select_options($plantings->options(), $data['planting_id']); ?>
      </select>
      <?= feedback($errors, 'planting_id') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="harvest_date">Harvest date</label>
      <input class="form-control<?= invalid($errors, 'harvest_date') ?>" id="harvest_date" name="harvest_date" type="date" value="<?= e($data['harvest_date']) ?>" required>
      <?= feedback($errors, 'harvest_date') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="quantity_kg">Quantity (kg)</label>
      <input class="form-control<?= invalid($errors, 'quantity_kg') ?>" id="quantity_kg" name="quantity_kg" type="number" step="0.01" min="0.01"
             inputmode="decimal" value="<?= e($data['quantity_kg']) ?>" required>
      <?= feedback($errors, 'quantity_kg') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="selling_price_per_kg">Selling price per kg (₱)</label>
      <input class="form-control<?= invalid($errors, 'selling_price_per_kg') ?>" id="selling_price_per_kg" name="selling_price_per_kg" type="number" step="0.01" min="0.01"
             inputmode="decimal" value="<?= e($data['selling_price_per_kg']) ?>" required>
      <?= feedback($errors, 'selling_price_per_kg') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="grade_id">Quality grade</label>
      <select class="form-select<?= invalid($errors, 'grade_id') ?>" id="grade_id" name="grade_id" required>
        <option value="">Choose a grade</option><?php select_options($lookups->options('quality_grade'), $data['grade_id']); ?>
      </select>
      <?= feedback($errors, 'grade_id') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="remark_id">Remarks</label>
      <select class="form-select<?= invalid($errors, 'remark_id') ?>" id="remark_id" name="remark_id" required>
        <option value="">Choose a remark</option><?php select_options($lookups->options('harvest_remark'), $data['remark_id']); ?>
      </select>
      <?= feedback($errors, 'remark_id') ?>
    </div>
  </div>

  <div class="d-flex gap-2 mt-4">
    <button class="btn-f1 btn-inline" type="submit"><?= $id ? 'Save changes' : 'Add harvest record' ?></button>
    <a class="btn btn-outline-secondary" href="/ui/harvest/index.php">Cancel</a>
    <?php if ($id && is_admin()): ?>
      <button class="btn btn-outline-danger ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal"<?= data_attrs([
          'id' => $id, 'name' => 'harvest record #' . $id]) ?>>Delete record</button>
    <?php endif; ?>
  </div>
</form>
<?php if ($id && is_admin()) delete_modal('harvest record', '/ui/harvest/index.php'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>