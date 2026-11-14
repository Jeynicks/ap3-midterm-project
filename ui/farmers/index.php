<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../config/db.php';
foreach (['BaseModel', 'FarmerModel', 'LookupModel'] as $m) require_once __DIR__ . "/../../model/$m.php";
require __DIR__ . '/../../controller/FarmerController.php';

$farmers = new FarmerModel($pdo);
$lookups = new LookupModel($pdo);
$ctl     = new FarmerController($farmers, $lookups);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {          // delete
    $ctl->delete($_POST);
    header('Location: /ui/farmers/index.php'); exit;
}

$qs  = ['q' => trim($_GET['q'] ?? ''), 'location' => (int)($_GET['location'] ?? 0),
        'sort' => $_GET['sort'] ?? 'id', 'dir' => ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc'];
$res = $farmers->paginate($qs['q'], $qs['location'], $qs['sort'], $qs['dir'], (int)($_GET['page'] ?? 1));
$url = fn(array $o) => '?' . http_build_query(array_merge($qs, $o));
$th  = function (string $label, string $key) use ($qs, $url) {
    $next  = ($qs['sort'] === $key && $qs['dir'] === 'asc') ? 'desc' : 'asc';
    $arrow = $qs['sort'] === $key ? ($qs['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a class="text-decoration-none text-reset" href="' . e($url(['sort' => $key, 'dir' => $next, 'page' => 1])) . '">' . e($label . $arrow) . '</a>';
};

$pageTitle = 'Farmers';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head">
  <h1>Farmers</h1>
  <a class="btn-f1 btn-inline" href="/ui/farmers/form.php">Add farmer</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-5">
    <input class="form-control" type="search" name="q" value="<?= e($qs['q']) ?>" placeholder="Search name or contact number" aria-label="Search farmers">
  </div>
  <div class="col-8 col-md-4">
    <select class="form-select" name="location" aria-label="Filter by location">
      <option value="0">All locations</option>
      <?php foreach ($lookups->options('location') as $id => $label): ?>
        <option value="<?= $id ?>" <?= $qs['location'] === $id ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-4 col-md-3 d-flex gap-2">
    <button class="btn-f1 btn-inline" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="/ui/farmers/index.php">Reset</a>
  </div>
</form>

<div class="table-responsive rounded">
  <table class="table table-f1 table-striped table-hover">
    <thead><tr>
      <th><?= $th('ID', 'id') ?></th><th><?= $th('Name', 'name') ?></th><th>Contact</th>
      <th><?= $th('Location', 'city') ?></th><th><?= $th('Registered', 'date') ?></th><th class="text-end">Actions</th>
    </tr></thead>
    <tbody>
    <?php if (!$res['rows']): ?>
      <tr><td colspan="6" class="text-center text-secondary py-4">No farmers found. Try a different search, or add a farmer.</td></tr>
    <?php endif; ?>
    <?php foreach ($res['rows'] as $r): $name = $r['first_name'] . ' ' . $r['last_name']; ?>
      <tr>
        <td><?= (int)$r['farmer_id'] ?></td>
        <td><?= e($name) ?></td>
        <td><?= e($r['contact_number']) ?></td>
        <td><?= e($r['city_municipality'] . ', ' . $r['province']) ?></td>
        <td><?= e($r['date_registered']) ?></td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal"
                  data-name="<?= e($name) ?>" data-contact="<?= e($r['contact_number']) ?>"
                  data-location="<?= e($r['city_municipality'] . ', ' . $r['province']) ?>"
                  data-registered="<?= e($r['date_registered']) ?>" data-updated="<?= e($r['updated_at']) ?>">View</button>
          <a class="btn btn-sm btn-outline-secondary" href="/ui/farmers/form.php?id=<?= (int)$r['farmer_id'] ?>">Edit</a>
          <?php if (is_admin()): ?>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                    data-id="<?= (int)$r['farmer_id'] ?>" data-name="<?= e($name) ?>">Delete</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
  <span class="text-secondary small"><?= $res['total'] ?> farmer<?= $res['total'] === 1 ? '' : 's' ?> · page <?= $res['page'] ?> of <?= $res['pages'] ?></span>
  <ul class="pagination pagination-sm mb-0">
    <li class="page-item <?= $res['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($url(['page' => $res['page'] - 1])) ?>">Previous</a></li>
    <li class="page-item <?= $res['page'] >= $res['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($url(['page' => $res['page'] + 1])) ?>">Next</a></li>
  </ul>
</div>

<!-- View -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h2 class="modal-title fs-5" id="viewTitle">Farmer details</h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><dl class="detail-list mb-0">
      <dt>Name</dt><dd id="vName"></dd><dt>Contact number</dt><dd id="vContact"></dd>
      <dt>Location</dt><dd id="vLocation"></dd><dt>Registered</dt><dd id="vRegistered"></dd>
      <dt>Last updated</dt><dd id="vUpdated" class="mb-0"></dd>
    </dl></div>
  </div></div>
</div>

<!-- Delete confirmation -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post">
    <div class="modal-header"><h2 class="modal-title fs-5" id="deleteTitle">Delete farmer</h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">Delete <strong id="dName"></strong> permanently? This can't be undone.
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" id="dId"></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-danger">Delete farmer</button></div>
  </form></div>
</div>

<script>
  document.getElementById('viewModal').addEventListener('show.bs.modal', ev => {
    const d = ev.relatedTarget.dataset;
    vName.textContent = d.name; vContact.textContent = d.contact; vLocation.textContent = d.location;
    vRegistered.textContent = d.registered; vUpdated.textContent = d.updated;
  });
  document.getElementById('deleteModal').addEventListener('show.bs.modal', ev => {
    dName.textContent = ev.relatedTarget.dataset.name; dId.value = ev.relatedTarget.dataset.id;
  });
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>