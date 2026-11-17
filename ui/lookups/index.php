<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../includes/ui_helpers.php';
require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../model/LookupModel.php';
require __DIR__ . '/../../controller/LookupController.php';

$lookups = new LookupModel($pdo);
$ctl = new LookupController($lookups);

$keys = LookupModel::keys();
$tab = in_array($_GET['tab'] ?? '', $keys, true) ? $_GET['tab'] : $keys[0];

$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['lookup_key'] ?? '';
    if (!in_array($key, $keys, true)) { flash_set('danger', 'Unknown lookup.'); header('Location: /ui/lookups/index.php'); exit; }
    if (($_POST['action'] ?? '') === 'delete') {
        $ctl->delete($key, $_POST);
        header('Location: /ui/lookups/index.php?tab=' . urlencode($key)); exit;
    }
    [$errors, $data] = $ctl->save($key, $_POST);
    if (!$errors) { header('Location: /ui/lookups/index.php?tab=' . urlencode($key)); exit; }
    $tab = $key;
}

$pageTitle = 'Lookups';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head"><h1>Reference data</h1></div>
<p class="text-secondary">Add new irrigation types, seasons, crop types, soil types and other reference values used throughout the system.</p>

<ul class="nav nav-tabs mb-3" role="tablist">
<?php foreach ($keys as $k):
    $c = $lookups->config($k);
    $title = ucfirst(str_replace('_', ' ', $k));
    if ($k === 'crop_category') $title = 'Crop types';
    if ($k === 'growing_season') $title = 'Seasons';
?>
  <li class="nav-item" role="presentation">
    <a class="nav-link<?= $tab === $k ? ' active' : '' ?>" href="/ui/lookups/index.php?tab=<?= urlencode($k) ?>"><?= e($title) ?></a>
  </li>
<?php endforeach; ?>
</ul>

<?php foreach ($keys as $k):
    if ($tab !== $k) continue;
    $c = $lookups->config($k);
    $rows = $lookups->all($k);
    $title = ucfirst(str_replace('_', ' ', $k));
    if ($k === 'crop_category') $title = 'Crop types';
    if ($k === 'growing_season') $title = 'Seasons';
    $errs = ($tab === $k) ? $errors : [];
    $vals = ($tab === $k && $data) ? $data : [];
    foreach (array_keys($c['fields']) as $col) if (!isset($vals[$col])) $vals[$col] = '';
?>
<div class="panel">
  <h2 class="h5 mb-3"><?= e($title) ?></h2>

  <?php if (isset($errs['form'])): ?><div class="alert-f1" role="alert"><?= e($errs['form']) ?></div><?php endif; ?>

  <div class="table-responsive rounded mb-3">
    <table class="table table-f1 table-striped table-hover">
      <thead><tr>
        <th>ID</th>
        <?php foreach ($c['fields'] as $col => $meta): ?><th><?= e($meta['label']) ?></th><?php endforeach; ?>
        <th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="<?= count($c['fields']) + 2 ?>" class="text-center text-secondary py-4">No entries yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r[$c['pk']] ?></td>
          <?php foreach (array_keys($c['fields']) as $col): ?><td><?= e($r[$col]) ?></td><?php endforeach; ?>
          <td class="text-end">
            <?php if (is_admin()): ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this entry?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="lookup_key" value="<?= e($k) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r[$c['pk']] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
              </form>
            <?php else: ?>
              <span class="text-secondary small">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <form method="post" class="row g-2 align-items-end" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="lookup_key" value="<?= e($k) ?>">
    <input type="hidden" name="action" value="add">
    <?php foreach ($c['fields'] as $col => $meta): ?>
      <div class="col-sm-<?= count($c['fields']) === 1 ? '8' : '5' ?>">
        <label class="form-label" for="<?= e($col) ?>"><?= e($meta['label']) ?></label>
        <input class="form-control<?= isset($errs[$col]) ? ' is-invalid' : '' ?>" id="<?= e($col) ?>" name="<?= e($col) ?>" maxlength="<?= (int)$meta['max'] ?>" value="<?= e($vals[$col]) ?>" required>
        <?php if (isset($errs[$col])): ?><div class="invalid-feedback"><?= e($errs[$col]) ?></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="col-sm-<?= count($c['fields']) === 1 ? '4' : '2' ?>">
      <button class="btn-f1 btn-inline" type="submit">Add</button>
    </div>
  </form>
</div>
<?php endforeach; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
