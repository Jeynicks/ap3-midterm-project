<?php
require __DIR__ . '/../includes/auth.php';
require_login();
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="/ui/assets/css/f1.css" rel="stylesheet">
</head>
<body class="f1">
  <div class="flag"></div>
  <main class="login-wrap">
    <div class="login-panel">
      <div class="panel">
        <h1>Welcome, <?= e($user['full_name']) ?></h1>
        <p class="sub">Signed in as <?= e($user['username']) ?> (<?= e($user['role_name']) ?>).</p>
        <form method="post" action="/ui/logout.php">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <button class="btn-f1" type="submit">Sign out</button>
        </form>
      </div>
    </div>
  </main>
</body>
</html>