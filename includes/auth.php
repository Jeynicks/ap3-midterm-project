<?php
// Shared session + auth helpers. Require this at the top of every page.
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';   // Render terminates HTTPS
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
}

function is_logged_in(): bool { return !empty($_SESSION['user']); }

function require_login(): void {
    if (!is_logged_in()) { header('Location: /ui/login.php'); exit; }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['user']['role_name'] ?? '') !== $role) { http_response_code(403); exit('Access denied.'); }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool {
    return $token !== null && hash_equals($_SESSION['csrf'] ?? '', $token);
}

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

// ---- paste at the bottom of includes/auth.php ----

function is_admin(): bool { return ($_SESSION['user']['role_name'] ?? '') === 'Administrator'; }

function flash_set(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }

function flash_render(): void {
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">' . e($msg)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    unset($_SESSION['flash']);
}

// ---- paste at the bottom of includes/auth.php (after the first additions) ----

date_default_timezone_set('Asia/Manila');   // so "today" matches the Philippines, not the server

function current_user_id(): ?int {
    return isset($_SESSION['user']['user_id']) ? (int)$_SESSION['user']['user_id'] : null;
}

function fmt_dt(?string $ts): string {       // database timestamp -> "Nov 14, 2026 3:05 PM"
    if (!$ts) return '—';
    return (new DateTime($ts))->setTimezone(new DateTimeZone('Asia/Manila'))->format('M j, Y g:i A');
}

// Any uncaught error: log the details privately, show users a generic page (no hosts, users or stack traces).
set_exception_handler(function (Throwable $ex): void {
    error_log($ex->getMessage());
    if (!headers_sent()) http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Something went wrong</title>'
       . '<body style="font-family:system-ui,sans-serif;background:#15151e;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0">'
       . '<div style="max-width:420px;padding:1.5rem"><h1>Something went wrong</h1>'
       . '<p>The request could not be completed. Please try again in a moment.</p>'
       . '<a style="color:#ff2a1f" href="/ui/dashboard.php">Back to the dashboard</a></div></body>';
});