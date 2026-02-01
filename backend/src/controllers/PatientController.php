<?php
declare(strict_types=1);

final class PatientController
{
    public static function index(array $config): void
    {
        Auth::requireRoles(['admin', 'staff', 'doctor']);

        $q = (string)($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $pdo = Database::pdo($config);
        $rows = PatientModel::list($pdo, $q, $pageSize, $offset);
        $total = PatientModel::count($pdo, $q);

        Response::ok(['items' => $rows], [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ]);
    }

    public static function show(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'staff', 'doctor']);
        $pdo = Database::pdo($config);
        $row = PatientModel::find($pdo, $id);
        if ($row === null) {
            Response::error('Patient not found', 404);
        }
        Response::ok(['patient' => $row]);
    }

    public static function me(array $config): void
    {
        Auth::requireRoles(['patient']);
        $pdo = Database::pdo($config);
        $row = PatientModel::findByUserId($pdo, (int)Auth::userId());
        if ($row === null) {
            Response::error('Patient profile not found', 404);
        }
        Response::ok(['patient' => $row]);
    }

    public static function create(array $config): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $body = Request::json();

        $userData = self::validateUserForCreate($body);
        $patientData = self::validatePatientPayload($body);

        $pdo = Database::pdo($config);
        $pdo->beginTransaction();
        try {
            $userId = UserModel::create($pdo, $userData);
            $patientData['user_id'] = $userId;
            $patientId = PatientModel::create($pdo, $patientData);
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

        $row = PatientModel::find($pdo, $patientId);
        Response::ok(['patient' => $row], ['created' => true]);
    }

    public static function update(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $body = Request::json();
        $patientData = self::validatePatientPayload($body);

        $pdo = Database::pdo($config);
        if (PatientModel::find($pdo, $id) === null) {
            Response::error('Patient not found', 404);
        }
        PatientModel::update($pdo, $id, $patientData);
        $row = PatientModel::find($pdo, $id);
        Response::ok(['patient' => $row], ['updated' => true]);
    }

    public static function delete(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $pdo = Database::pdo($config);
        $row = PatientModel::find($pdo, $id);
        if ($row === null) {
            Response::error('Patient not found', 404);
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
            'role' => 'patient',
            'status' => $status,
        ];
    }

    private static function validatePatientPayload(array $body): array
    {
        $name = trim((string)($body['name'] ?? ''));
        $gender = $body['gender'] ?? null;
        $dob = $body['dob'] ?? null;
        $mobile = $body['mobile'] ?? null;
        $address = $body['address'] ?? null;
        $blood = $body['blood_group'] ?? null;

        $genderVal = null;
        if ($gender !== null && $gender !== '') {
            if (!is_string($gender) || !in_array($gender, ['male', 'female', 'other'], true)) {
                Response::error('Invalid gender', 422);
            }
            $genderVal = $gender;
        }

        $dobVal = null;
        if ($dob !== null && $dob !== '') {
            if (!is_string($dob) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dob)) {
                Response::error('Invalid dob', 422);
            }
            $dobVal = $dob;
        }

        $mobileVal = is_string($mobile) ? trim($mobile) : null;
        if ($mobileVal === '') $mobileVal = null;
        $addressVal = is_string($address) ? trim($address) : null;
        if ($addressVal === '') $addressVal = null;
        $bloodVal = is_string($blood) ? trim($blood) : null;
        if ($bloodVal === '') $bloodVal = null;

        if ($name === '' || strlen($name) > 160) {
            Response::error('Invalid name', 422);
        }

        return [
            'name' => $name,
            'gender' => $genderVal,
            'dob' => $dobVal,
            'mobile' => $mobileVal,
            'address' => $addressVal,
            'blood_group' => $bloodVal,
        ];
    }
}

