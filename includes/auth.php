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