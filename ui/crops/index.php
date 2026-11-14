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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {          // delete
    $ctl->delete($_POST);
    header('Location: /ui/crops/index.php'); exit;
}

$qs  = ['q' => trim($_GET['q'] ?? ''), 'category' => (int)($_GET['category'] ?? 0),
        'sort' => $_GET['sort'] ?? 'id', 'dir' => ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc'];
$res = $crops->paginate($qs['q'], $qs['category'], $qs['sort'], $qs['dir'], (int)($_GET['page'] ?? 1));

$pageTitle = 'Crops';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head">
  <h1>Crops</h1>
  <a class="btn-f1 btn-inline" href="/ui/crops/form.php">Add crop</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-5">
    <input class="form-control" type="search" name="q" value="<?= e($qs['q']) ?>" placeholder="Search crop or variety" aria-label="Search crops">
  </div>
  <div class="col-8 col-md-4">
    <select class="form-select" name="category" aria-label="Filter by category">
      <option value="0">All categories</option>
      <?php select_options($lookups->options('crop_category'), $qs['category']); ?>
    </select>
  </div>
  <div class="col-4 col-md-3 d-flex gap-2">
    <button class="btn-f1 btn-inline" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="/ui/crops/index.php">Reset</a>
  </div>
</form>

<div class="table-responsive rounded">
  <table class="table table-f1 table-striped table-hover">
    <thead><tr>
      <th><?= sort_th($qs, 'ID', 'id') ?></th><th><?= sort_th($qs, 'Crop', 'name') ?></th><th>Variety</th>
      <th><?= sort_th($qs, 'Category', 'category') ?></th><th class="text-end"><?= sort_th($qs, 'Growing period (days)', 'days') ?></th>
      <th>Season</th><th class="text-end">Actions</th>
    </tr></thead>
    <tbody>
    <?php if (!$res['rows']): ?>
      <tr><td colspan="7" class="text-center text-secondary py-4">No crops found. Try a different search, or add a crop.</td></tr>
    <?php endif; ?>
    <?php foreach ($res['rows'] as $r): ?>
      <tr>
        <td><?= (int)$r['crop_id'] ?></td>
        <td><?= e($r['crop_name']) ?></td>
        <td><?= e($r['variety']) ?></td>
        <td><?= e($r['category_name']) ?></td>
        <td class="text-end"><?= (int)$r['growing_period_days'] ?></td>
        <td><?= e($r['season_name']) ?></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal"<?= data_attrs([
              'crop' => $r['crop_name'], 'variety' => $r['variety'], 'category' => $r['category_name'],
              'days' => $r['growing_period_days'] . ' days', 'season' => $r['season_name'],
              'created' => audit_text($r['created_at'], $r['created_by_name']), 'updated' => audit_text($r['updated_at'], $r['updated_by_name'])]) ?>>View</button>
          <a class="btn btn-sm btn-outline-secondary" href="/ui/crops/form.php?id=<?= (int)$r['crop_id'] ?>">Edit</a>
          <?php if (is_admin()): ?>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"<?= data_attrs(['id' => $r['crop_id'], 'name' => $r['crop_name'] . ' (' . $r['variety'] . ')']) ?>>Delete</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php pagination($res, $qs, 'crop'); ?>
<?php view_modal('Crop details', ['Crop' => 'crop', 'Variety' => 'variety', 'Category' => 'category', 'Growing period' => 'days',
                                  'Recommended season' => 'season', 'Created' => 'created', 'Last updated' => 'updated']); ?>
<?php delete_modal('crop'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>