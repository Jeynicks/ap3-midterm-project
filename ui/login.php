<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../model/UserModel.php';
require __DIR__ . '/../controller/AuthController.php';

if (is_logged_in()) { header('Location: /ui/dashboard.php'); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/../config/db.php';          // defines $pdo
        $auth  = new AuthController(new UserModel($pdo));
        $error = $auth->login($_POST);
        if ($error === null) { header('Location: /ui/dashboard.php'); exit; }
    } catch (PDOException $ex) {
        error_log($ex->getMessage());                        // details go to the log, not the browser
        $error = "Can't reach the database. Try again in a moment.";
    }
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="/ui/assets/css/f1.css" rel="stylesheet">
</head>
<body class="f1">
  <div class="flag"></div>
  <main class="login-wrap">
    <div class="login-panel">

      <div class="gantry<?= $error ? ' fault' : '' ?>" aria-hidden="true">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <div class="bulb" style="--i:<?= $i ?>"><i class="lamp"></i><i class="lamp"></i></div>
        <?php endfor; ?>
      </div>

      <form class="panel" method="post" action="/ui/login.php" novalidate>
        <h1>Agriculture Tracker</h1>
        <p class="sub">Sign in to open the dashboard.</p>

        <?php if ($error): ?>
          <div class="alert-f1" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <div class="mb-3">
          <label class="form-label" for="username">Username</label>
          <input class="form-control" id="username" name="username" type="text"
                 value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" autofocus required>
        </div>

        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <div class="input-group">
            <input class="form-control" id="password" name="password" type="password"
                   autocomplete="current-password" required>
            <button class="btn btn-eye" type="button" id="togglePw"
                    aria-label="Show password" aria-pressed="false">
              <svg id="eyeOn" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg id="eyeOff" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A10.9 10.9 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <button class="btn-f1" type="submit">Sign in</button>
        <p class="hint">Forgot your password? Ask an administrator to reset it.</p>
      </form>

    </div>
  </main>

  <script>
    const pw = document.getElementById('password'), btn = document.getElementById('togglePw');
    btn.addEventListener('click', () => {
      const show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      document.getElementById('eyeOn').hidden = show;
      document.getElementById('eyeOff').hidden = !show;
      btn.setAttribute('aria-pressed', show);
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  </script>
</body>
</html>