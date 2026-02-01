<?php
declare(strict_types=1);

final class UserController
{
    public static function index(array $config): void
    {
        Auth::requireRoles(['admin']);

        $q = (string)($_GET['q'] ?? '');
        $role = isset($_GET['role']) ? (string)$_GET['role'] : null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $pdo = Database::pdo($config);
        $rows = UserModel::list($pdo, $q, $role, $pageSize, $offset);
        $total = UserModel::count($pdo, $q, $role);

        Response::ok(['items' => $rows], [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ]);
    }

    public static function show(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $pdo = Database::pdo($config);
        $row = UserModel::findById($pdo, $id);
        if ($row === null) {
            Response::error('User not found', 404);
        }
        Response::ok(['user' => $row]);
    }

    public static function create(array $config): void
    {
        Auth::requireRoles(['admin']);
        $body = Request::json();
        $data = self::validateCreate($body);

        $pdo = Database::pdo($config);
        try {
            $id = UserModel::create($pdo, $data);
        } catch (\PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                Response::error('Email already exists', 409);
            }
            throw $e;
        }
        $row = UserModel::findById($pdo, $id);
        Response::ok(['user' => $row], ['created' => true]);
    }

    public static function update(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $body = Request::json();
        $data = self::validateUpdate($body);

        $pdo = Database::pdo($config);
        if (UserModel::findById($pdo, $id) === null) {
            Response::error('User not found', 404);
        }
        try {
            UserModel::update($pdo, $id, $data);
            if ($data['password'] !== null) {
                UserModel::updatePassword($pdo, $id, $data['password']);
            }
        } catch (\PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                Response::error('Email already exists', 409);
            }
            throw $e;
        }
        $row = UserModel::findById($pdo, $id);
        Response::ok(['user' => $row], ['updated' => true]);
    }

    public static function delete(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $pdo = Database::pdo($config);
        if (UserModel::findById($pdo, $id) === null) {
            Response::error('User not found', 404);
        }
        if (Auth::userId() === $id) {
            Response::error('You cannot delete your own account while logged in', 409);
        }
        UserModel::delete($pdo, $id);
        Response::ok(['deleted' => true]);
    }

    private static function validateCreate(array $body): array
    {
        $userName = trim((string)($body['user_name'] ?? ''));
        $email = strtolower(trim((string)($body['login_email'] ?? '')));
        $password = (string)($body['password'] ?? '');
        $role = (string)($body['role'] ?? '');
        $status = (string)($body['status'] ?? 'active');

        if ($userName === '' || strlen($userName) > 120) {
            Response::error('Invalid user_name', 422);
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid login_email', 422);
        }
        if ($password === '' || strlen($password) < 6) {
            Response::error('Invalid password', 422);
        }
        if (!in_array($role, ['admin', 'doctor', 'staff', 'patient'], true)) {
            Response::error('Invalid role', 422);
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            Response::error('Invalid status', 422);
        }

        return [
            'user_name' => $userName,
            'login_email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'status' => $status,
        ];
    }

    private static function validateUpdate(array $body): array
    {
        $userName = trim((string)($body['user_name'] ?? ''));
        $email = strtolower(trim((string)($body['login_email'] ?? '')));
        $role = (string)($body['role'] ?? '');
        $status = (string)($body['status'] ?? 'active');
        $password = $body['password'] ?? null;

        if ($userName === '' || strlen($userName) > 120) {
            Response::error('Invalid user_name', 422);
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid login_email', 422);
        }
        if (!in_array($role, ['admin', 'doctor', 'staff', 'patient'], true)) {
            Response::error('Invalid role', 422);
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            Response::error('Invalid status', 422);
        }

        $passwordHash = null;
        if ($password !== null) {
            if (!is_string($password) || strlen($password) < 6) {
                Response::error('Invalid password', 422);
            }
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        }

        return [
            'user_name' => $userName,
            'login_email' => $email,
            'role' => $role,
            'status' => $status,
            'password' => $passwordHash,
        ];
    }
}

