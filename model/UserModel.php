<?php
// Talks to PostgreSQL only. No HTML, no session code.
class UserModel {
    public function __construct(private PDO $pdo) {}

    // Returns user_id, username, full_name, role_name, or null if the login is wrong.
    public function login(string $username, string $password): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM verify_login(:u, :p)');
        $stmt->execute([':u' => $username, ':p' => $password]);
        return $stmt->fetch() ?: null;
    }
}