<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'FarmerModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/FarmerController.php';

$farmers = new FarmerModel($pdo);
$lookups = new LookupModel($pdo);
$ctl     = new FarmerController($farmers, $lookups);

$id     = (int)($_GET['id'] ?? 0);
$record = $id ? $farmers->find($id) : null;
if ($id && !$record) { flash_set('danger', 'That farmer no longer exists.'); header('Location: /ui/farmers/index.php'); exit; }

$errors = [];
$data   = $record ?? ['first_name' => '', 'last_name' => '', 'contact_number' => '', 'location_id' => 0, 'date_registered' => date('Y-m-d')];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $data] = $ctl->save($id ?: null, $_POST);
    if (!$errors) { header('Location: /ui/farmers/index.php'); exit; }
}
$bad = fn(string $k) => isset($errors[$k]) ? ' is-invalid' : '';
$msg = fn(string $k) => isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : '';

$pageTitle = $id ? 'Edit farmer' : 'Add farmer';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>

<form class="panel" method="post" novalidate style="max-width:560px">
  <?php if (isset($errors['form'])): ?><div class="alert-f1" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <div class="row g-3">
    <div class="col-sm-6">
      <label class="form-label" for="first_name">First name</label>
      <input class="form-control<?= $bad('first_name') ?>" id="first_name" name="first_name" maxlength="50" value="<?= e($data['first_name']) ?>" required>
      <?= $msg('first_name') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="last_name">Last name</label>
      <input class="form-control<?= $bad('last_name') ?>" id="last_name" name="last_name" maxlength="50" value="<?= e($data['last_name']) ?>" required>
      <?= $msg('last_name') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="contact_number">Contact number</label>
      <input class="form-control<?= $bad('contact_number') ?>" id="contact_number" name="contact_number" inputmode="numeric" maxlength="11"
             placeholder="09171234567" value="<?= e($data['contact_number']) ?>" required>
      <?= $msg('contact_number') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="date_registered">Date registered</label>
      <input class="form-control<?= $bad('date_registered') ?>" id="date_registered" name="date_registered" type="date"
             max="<?= date('Y-m-d') ?>" value="<?= e($data['date_registered']) ?>" required>
      <?= $msg('date_registered') ?>
    </div>
    <div class="col-12">
      <label class="form-label" for="location_id">Location</label>
      <select class="form-select<?= $bad('location_id') ?>" id="location_id" name="location_id" required>
        <option value="">Choose a location</option>
        <?php foreach ($lookups->options('location') as $lid => $label): ?>
          <option value="<?= $lid ?>" <?= (int)$data['location_id'] === $lid ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $msg('location_id') ?>
    </div>
  </div>

  <div class="d-flex gap-2 mt-4">
    <button class="btn-f1 btn-inline" type="submit"><?= $id ? 'Save changes' : 'Add farmer' ?></button>
    <a class="btn btn-outline-secondary" href="/ui/farmers/index.php">Cancel</a>
    <?php if ($id && is_admin()): ?>
      <button class="btn btn-outline-danger ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete farmer</button>
    <?php endif; ?>
  </div>
</form>

<?php if ($id && is_admin()): ?>
<!-- Delete confirmation: posts to the list page, which runs FarmerController::delete() -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="/ui/farmers/index.php">
    <div class="modal-header"><h2 class="modal-title fs-5" id="deleteTitle">Delete farmer</h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">Delete <strong><?= e($record['first_name'] . ' ' . $record['last_name']) ?></strong> permanently? This can't be undone.
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $id ?>"></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-danger">Delete farmer</button></div>
  </form></div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>