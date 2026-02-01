<?php
declare(strict_types=1);

final class DoctorController
{
    public static function index(array $config): void
    {
        // Staff needs to view doctors for booking, doctors can view peers, admin can manage.
        Auth::requireRoles(['admin', 'doctor', 'staff']);

        $q = (string)($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $pdo = Database::pdo($config);
        $rows = DoctorModel::list($pdo, $q, $pageSize, $offset);
        $total = DoctorModel::count($pdo, $q);

        Response::ok(['items' => $rows], [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ]);
    }

    public static function show(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'doctor', 'staff']);
        $pdo = Database::pdo($config);
        $row = DoctorModel::find($pdo, $id);
        if ($row === null) {
            Response::error('Doctor not found', 404);
        }
        Response::ok(['doctor' => $row]);
    }

    public static function me(array $config): void
    {
        Auth::requireRoles(['doctor']);
        $pdo = Database::pdo($config);
        $row = DoctorModel::findByUserId($pdo, (int)Auth::userId());
        if ($row === null) {
            Response::error('Doctor profile not found', 404);
        }
        Response::ok(['doctor' => $row]);
    }

    public static function create(array $config): void
    {
        // Staff cannot edit doctors per requirement.
        Auth::requireRoles(['admin']);
        $body = Request::json();

        $userData = self::validateUserForCreate($body);
        $doctorData = self::validateDoctorPayload($body);

        $pdo = Database::pdo($config);
        $pdo->beginTransaction();
        try {
            $userId = UserModel::create($pdo, $userData);
            $doctorData['user_id'] = $userId;
            $doctorId = DoctorModel::create($pdo, $doctorData);
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

        $row = DoctorModel::find($pdo, $doctorId);
        Response::ok(['doctor' => $row], ['created' => true]);
    }

    public static function update(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $body = Request::json();
        $doctorData = self::validateDoctorPayload($body);

        $pdo = Database::pdo($config);
        if (DoctorModel::find($pdo, $id) === null) {
            Response::error('Doctor not found', 404);
        }
        DoctorModel::update($pdo, $id, $doctorData);
        $row = DoctorModel::find($pdo, $id);
        Response::ok(['doctor' => $row], ['updated' => true]);
    }

    public static function delete(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $pdo = Database::pdo($config);
        $row = DoctorModel::find($pdo, $id);
        if ($row === null) {
            Response::error('Doctor not found', 404);
        }
        // Delete the user account to cascade-remove doctor profile
        $userId = (int)($row['user_id'] ?? 0);
        if ($userId === (int)Auth::userId()) {
            Response::error('You cannot delete your own account while logged in', 409);
        }
        UserModel::delete($pdo, $userId);
        Response::ok(['deleted' => true]);
    }

    private static function validateUserForCreate(array $body): array
    {
        $userName = trim((string)($body['user_name'] ?? ($body['name'] ?? '')));
        $email = strtolower(trim((string)($body['login_email'] ?? ($body['email'] ?? ''))));
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
            'role' => 'doctor',
            'status' => $status,
        ];
    }

    private static function validateDoctorPayload(array $body): array
    {
        $name = trim((string)($body['name'] ?? ''));
        $specialization = $body['specialization'] ?? null;
        $qualification = $body['qualification'] ?? null;
        $mobile = $body['mobile'] ?? null;
        $experience = $body['experience'] ?? null;
        $fee = $body['consultation_fee'] ?? null;
        $status = (string)($body['status'] ?? 'active');
        $departmentId = $body['department_id'] ?? null;

        $specialization = is_string($specialization) ? trim($specialization) : null;
        if ($specialization === '') $specialization = null;
        $qualification = is_string($qualification) ? trim($qualification) : null;
        if ($qualification === '') $qualification = null;
        $mobile = is_string($mobile) ? trim($mobile) : null;
        if ($mobile === '') $mobile = null;

        if ($name === '' || strlen($name) > 160) {
            Response::error('Invalid name', 422);
        }
        if ($experience !== null && (!is_numeric($experience) || (int)$experience < 0 || (int)$experience > 80)) {
            Response::error('Invalid experience', 422);
        }
        if ($fee !== null && (!is_numeric($fee) || (float)$fee < 0)) {
            Response::error('Invalid consultation_fee', 422);
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            Response::error('Invalid status', 422);
        }

        $dept = null;
        if ($departmentId !== null && $departmentId !== '') {
            if (!is_numeric($departmentId) || (int)$departmentId <= 0) {
                Response::error('Invalid department_id', 422);
            }
            $dept = (int)$departmentId;
        }

        return [
            'name' => $name,
            'specialization' => $specialization,
            'qualification' => $qualification,
            'mobile' => $mobile,
            'experience' => $experience === null ? null : (int)$experience,
            'consultation_fee' => $fee === null ? null : (float)$fee,
            'status' => $status,
            'department_id' => $dept,
        ];
    }
}

