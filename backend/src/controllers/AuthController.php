<?php
declare(strict_types=1);

final class AuthController
{
    public static function csrf(): void
    {
        Response::ok(['csrf_token' => Csrf::token()]);
    }

    public static function me(array $config): void
    {
        Auth::requireLogin();
        $pdo = Database::pdo($config);
        $user = UserModel::findById($pdo, (int)Auth::userId());
        if ($user === null) {
            Auth::logout();
            Response::error('Unauthorized', 401);
        }
        Response::ok(['user' => $user]);
    }

    public static function login(array $config): void
    {
        $body = Request::json();
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email', 422);
        }
        if ($password === '' || strlen($password) < 6) {
            Response::error('Invalid password', 422);
        }

        $pdo = Database::pdo($config);
        $user = UserModel::findByEmail($pdo, $email);
        if ($user === null) {
            Response::error('Invalid credentials', 401);
        }
        if (($user['status'] ?? '') !== 'active') {
            Response::error('Account inactive', 403);
        }

        $hash = (string)($user['password'] ?? '');
        if (!password_verify($password, $hash)) {
            Response::error('Invalid credentials', 401);
        }

        Auth::login((int)$user['user_id'], (string)$user['role']);
        // Issue CSRF token for subsequent state-changing requests.
        $csrf = Csrf::token();

        // Never return password hash to client.
        unset($user['password']);
        Response::ok(['user' => $user, 'csrf_token' => $csrf]);
    }

    public static function logout(): void
    {
        Auth::requireLogin();
        Auth::logout();
        Response::ok(['logged_out' => true]);
    }
}

