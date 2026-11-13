<?php
declare(strict_types=1);

final class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetch(
            'SELECT u.*, r.code AS role_code, r.name AS role_name, p.code AS plan_code, p.name AS plan_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN plans p ON p.id = u.plan_id
             WHERE LOWER(u.email) = LOWER(?) AND u.status = "ACTIVE" LIMIT 1',
            [trim($email)]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = $user;
        Database::execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        audit('LOGIN', 'users', (string) $user['id']);
        return true;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        self::$user = Database::fetch(
            'SELECT u.*, r.code AS role_code, r.name AS role_name, p.code AS plan_code, p.name AS plan_name
             FROM users u JOIN roles r ON r.id = u.role_id
             LEFT JOIN plans p ON p.id = u.plan_id WHERE u.id = ? LIMIT 1',
            [(int) $_SESSION['user_id']]
        );
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::user() ? (int) self::user()['id'] : null;
    }

    public static function logout(): void
    {
        if (self::id()) {
            audit('LOGOUT', 'users', (string) self::id());
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$user = null;
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            flash('error', 'Entre na sua conta para continuar.');
            redirect('/login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::user()['role_code'], $roles, true)) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }
    }
}
