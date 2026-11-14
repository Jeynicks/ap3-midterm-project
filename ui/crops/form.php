<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'CropModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/CropController.php';

$crops   = new CropModel($pdo);
$lookups = new LookupModel($pdo);
$ctl     = new CropController($crops, $lookups);

$id     = (int)($_GET['id'] ?? 0);
$record = $id ? $crops->find($id) : null;
if ($id && !$record) { flash_set('danger', 'That crop no longer exists.'); header('Location: /ui/crops/index.php'); exit; }

$errors = [];
$data   = $record ?? ['crop_name' => '', 'variety' => '', 'category_id' => 0, 'growing_period_days' => '', 'season_id' => 0];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $data] = $ctl->save($id ?: null, $_POST);
    if (!$errors) { header('Location: /ui/crops/index.php'); exit; }
}

$pageTitle = $id ? 'Edit crop' : 'Add crop';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>

<form class="panel" method="post" novalidate style="max-width:640px">
  <?php if (isset($errors['form'])): ?><div class="alert-f1" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <div class="row g-3">
    <div class="col-sm-6">
      <label class="form-label" for="crop_name">Crop name</label>
      <input class="form-control<?= invalid($errors, 'crop_name') ?>" id="crop_name" name="crop_name" maxlength="80" value="<?= e($data['crop_name']) ?>" required>
      <?= feedback($errors, 'crop_name') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="variety">Variety</label>
      <input class="form-control<?= invalid($errors, 'variety') ?>" id="variety" name="variety" maxlength="80" value="<?= e($data['variety']) ?>" required>
      <?= feedback($errors, 'variety') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="category_id">Category</label>
      <select class="form-select<?= invalid($errors, 'category_id') ?>" id="category_id" name="category_id" required>
        <option value="">Choose a category</option><?php select_options($lookups->options('crop_category'), $data['category_id']); ?>
      </select>
      <?= feedback($errors, 'category_id') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="growing_period_days">Growing period (days)</label>
      <input class="form-control<?= invalid($errors, 'growing_period_days') ?>" id="growing_period_days" name="growing_period_days" type="number"
             step="1" min="1" max="1000" inputmode="numeric" value="<?= e($data['growing_period_days']) ?>" required>
      <?= feedback($errors, 'growing_period_days') ?>
    </div>
    <div class="col-sm-4">
      <label class="form-label" for="season_id">Recommended season</label>
      <select class="form-select<?= invalid($errors, 'season_id') ?>" id="season_id" name="season_id" required>
        <option value="">Choose a season</option><?php select_options($lookups->options('growing_season'), $data['season_id']); ?>
      </select>
      <?= feedback($errors, 'season_id') ?>
    </div>
  </div>

  <div class="d-flex gap-2 mt-4">
    <button class="btn-f1 btn-inline" type="submit"><?= $id ? 'Save changes' : 'Add crop' ?></button>
    <a class="btn btn-outline-secondary" href="/ui/crops/index.php">Cancel</a>
    <?php if ($id && is_admin()): ?>
      <button class="btn btn-outline-danger ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal"<?= data_attrs(['id' => $id, 'name' => $record['crop_name'] . ' (' . $record['variety'] . ')']) ?>>Delete crop</button>
    <?php endif; ?>
  </div>
</form>
<?php if ($id && is_admin()) delete_modal('crop', '/ui/crops/index.php'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>