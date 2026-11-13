<?php
function loadEnv(string $path): void {
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

if (is_file(__DIR__ . '/../.env')) loadEnv(__DIR__ . '/../.env');
foreach (['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASSWORD'] as $k) {
    if (!isset($_ENV[$k]) && getenv($k) !== false) $_ENV[$k] = getenv($k);
}

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
    $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME']);

$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);