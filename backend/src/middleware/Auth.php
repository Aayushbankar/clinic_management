<?php
declare(strict_types=1);

final class Auth
{
    public static function userId(): ?int
    {
        $id = $_SESSION['auth']['user_id'] ?? null;
        return is_int($id) ? $id : (is_numeric($id) ? (int)$id : null);
    }

    public static function role(): ?string
    {
        $r = $_SESSION['auth']['role'] ?? null;
        return is_string($r) ? $r : null;
    }

    public static function requireLogin(): void
    {
        if (self::userId() === null) {
            Response::error('Unauthorized', 401);
        }
    }

    /**
     * @param array<int, string> $roles
     */
    public static function requireRoles(array $roles): void
    {
        self::requireLogin();
        $role = self::role();
        if ($role === null || !in_array($role, $roles, true)) {
            Response::error('Forbidden', 403);
        }
    }

    public static function login(int $userId, string $role): void
    {
        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'user_id' => $userId,
            'role' => $role,
            'logged_in_at' => time(),
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $params['path'] ?? '/', $params['domain'] ?? '', (bool)($params['secure'] ?? false), (bool)($params['httponly'] ?? true));
            session_destroy();
        }
    }
}

