<?php
// Validates input, calls the model, manages the session.
class AuthController {
    public function __construct(private UserModel $users) {}

    // Returns an error message, or null when the login succeeded.
    public function login(array $in): ?string {
        if (!csrf_valid($in['csrf'] ?? null)) return 'Your session expired. Reload the page and try again.';

        $username = trim((string)($in['username'] ?? ''));
        $password = (string)($in['password'] ?? '');
        if ($username === '' || $password === '') return 'Enter your username and password.';

        $user = $this->users->login($username, $password);
        if (!$user) return 'Username or password is incorrect.';

        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        return null;
    }

    public function logout(): void {
        logout_user();
    }
}