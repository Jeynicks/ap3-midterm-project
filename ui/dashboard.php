<?php
require __DIR__ . '/../includes/auth.php';
require_login();
$user = $_SESSION['user'];
$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="panel" style="max-width:560px">
  <h1>Welcome, <?= e($user['full_name']) ?></h1>
  <p class="sub">Signed in as <?= e($user['username']) ?> (<?= e($user['role_name']) ?>).</p>
  <a class="btn-f1 btn-inline" href="/ui/farmers/index.php">Open farmers</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>