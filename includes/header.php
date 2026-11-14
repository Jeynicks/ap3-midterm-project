<?php /* Shared layout top. Set $pageTitle before including. Needs includes/auth.php loaded. */ ?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Agriculture Tracker') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="/ui/assets/css/f1.css" rel="stylesheet">
  <link href="/ui/assets/css/app.css" rel="stylesheet">
</head>
<body class="f1">
  <div class="flag"></div>
  <nav class="navbar navbar-expand-md nav-f1">
    <div class="container">
      <a class="navbar-brand fw-bold" href="/ui/dashboard.php">Agriculture Tracker</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="/ui/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="/ui/farmers/index.php">Farmers</a></li>
          <!-- add Farms, Crops, Planting, Harvest, Reports here as each page is built -->
        </ul>
        <form method="post" action="/ui/logout.php" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
          <span class="text-secondary small"><?= e($_SESSION['user']['full_name'] ?? '') ?> (<?= e($_SESSION['user']['role_name'] ?? '') ?>)</span>
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
        </form>
      </div>
    </div>
  </nav>
  <main class="container py-4">
    <?php flash_render(); ?>