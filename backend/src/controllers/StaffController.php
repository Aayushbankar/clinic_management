<?php
declare(strict_types=1);

final class StaffController
{
    public static function index(array $config): void
    {
        Auth::requireRoles(['admin']);

        $q = (string)($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $pdo = Database::pdo($config);
        $rows = StaffModel::list($pdo, $q, $pageSize, $offset);
        $total = StaffModel::count($pdo, $q);

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
        $row = StaffModel::find($pdo, $id);
        if ($row === null) {
            Response::error('Staff not found', 404);
        }
        Response::ok(['staff' => $row]);
    }

    public static function create(array $config): void
    {
        Auth::requireRoles(['admin']);
        $body = Request::json();

        $userData = self::validateUserForCreate($body);
        $staffData = self::validateStaffPayload($body);

        $pdo = Database::pdo($config);
        $pdo->beginTransaction();
        try {
            $userId = UserModel::create($pdo, $userData);
            $staffData['user_id'] = $userId;
            $staffId = StaffModel::create($pdo, $staffData);
            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            if ((string)$e->getCode() === '23000') {
                Response::error('Email already exists', 409);
            }
            throw $e;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $row = StaffModel::find($pdo, $staffId);
        Response::ok(['staff' => $row], ['created' => true]);
    }

    public static function update(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $body = Request::json();
        $staffData = self::validateStaffPayload($body);

        $pdo = Database::pdo($config);
        if (StaffModel::find($pdo, $id) === null) {
            Response::error('Staff not found', 404);
        }
        StaffModel::update($pdo, $id, $staffData);
        $row = StaffModel::find($pdo, $id);
        Response::ok(['staff' => $row], ['updated' => true]);
    }

    public static function delete(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $pdo = Database::pdo($config);
        $row = StaffModel::find($pdo, $id);
        if ($row === null) {
            Response::error('Staff not found', 404);
        }
        $userId = (int)($row['user_id'] ?? 0);
        if ($userId === (int)Auth::userId()) {
            Response::error('You cannot delete your own account while logged in', 409);
        }
        UserModel::delete($pdo, $userId);
        Response::ok(['deleted' => true]);
    }

    private static function validateUserForCreate(array $body): array
    {
        $userName = trim((string)($body['user_name'] ?? ''));
        $email = strtolower(trim((string)($body['login_email'] ?? '')));
        $password = (string)($body['password'] ?? '');
        $status = (string)($body['user_status'] ?? 'active');

        if ($userName === '' || strlen($userName) > 120) {
            Response::error('Invalid user_name', 422);
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid login_email', 422);
        }
        if ($password === '' || strlen($password) < 6) {
            Response::error('Invalid password', 422);
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            Response::error('Invalid user_status', 422);
        }

        return [
            'user_name' => $userName,
            'login_email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => 'staff',
            'status' => $status,
        ];
    }

    private static function validateStaffPayload(array $body): array
    {
        $role = trim((string)($body['role'] ?? 'Staff'));
        $salary = $body['salary'] ?? null;
        $joining = $body['joining_date'] ?? null;
        $status = (string)($body['status'] ?? 'active');

        if ($role === '' || strlen($role) > 80) {
            Response::error('Invalid role', 422);
        }
        if ($salary !== null && $salary !== '' && (!is_numeric($salary) || (float)$salary < 0)) {
            Response::error('Invalid salary', 422);
        }
        $joiningDate = null;
        if ($joining !== null && $joining !== '') {
            if (!is_string($joining) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $joining)) {
                Response::error('Invalid joining_date', 422);
            }
            $joiningDate = $joining;
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            Response::error('Invalid status', 422);
        }

        return [
            'role' => $role,
            'salary' => ($salary === null || $salary === '') ? null : (float)$salary,
            'joining_date' => $joiningDate,
            'status' => $status,
        ];
    }
}

