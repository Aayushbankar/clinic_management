<?php
declare(strict_types=1);

final class DoctorScheduleController
{
    public static function index(array $config): void
    {
        Auth::requireRoles(['admin', 'doctor', 'staff']);
        $doctorId = (int)($_GET['doctor_id'] ?? 0);
        if ($doctorId <= 0) {
            Response::error('doctor_id is required', 422);
        }

        // Doctors can only view their own schedule unless admin/staff.
        if (Auth::role() === 'doctor') {
            $pdo = Database::pdo($config);
            $me = DoctorModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null || (int)$me['doctor_id'] !== $doctorId) {
                Response::error('Forbidden', 403);
            }
        }

        $pdo = Database::pdo($config);
        $rows = DoctorScheduleModel::listByDoctor($pdo, $doctorId);
        Response::ok(['items' => $rows]);
    }

    public static function create(array $config): void
    {
        Auth::requireRoles(['admin', 'doctor']);
        $body = Request::json();

        $doctorId = (int)($body['doctor_id'] ?? 0);
        $data = self::validate($body);
        $data['doctor_id'] = $doctorId;

        $pdo = Database::pdo($config);
        if ($doctorId <= 0) {
            Response::error('Invalid doctor_id', 422);
        }
        // Doctor can only create their own schedules
        if (Auth::role() === 'doctor') {
            $me = DoctorModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null || (int)$me['doctor_id'] !== $doctorId) {
                Response::error('Forbidden', 403);
            }
        }

        // Ensure doctor exists
        if (DoctorModel::find($pdo, $doctorId) === null) {
            Response::error('Doctor not found', 404);
        }

        $id = DoctorScheduleModel::create($pdo, $data);
        $row = DoctorScheduleModel::find($pdo, $id);
        Response::ok(['schedule' => $row], ['created' => true]);
    }

    public static function update(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'doctor']);
        $body = Request::json();
        $data = self::validate($body);

        $pdo = Database::pdo($config);
        $existing = DoctorScheduleModel::find($pdo, $id);
        if ($existing === null) {
            Response::error('Schedule not found', 404);
        }
        if (Auth::role() === 'doctor') {
            $me = DoctorModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null || (int)$me['doctor_id'] !== (int)$existing['doctor_id']) {
                Response::error('Forbidden', 403);
            }
        }

        DoctorScheduleModel::update($pdo, $id, $data);
        $row = DoctorScheduleModel::find($pdo, $id);
        Response::ok(['schedule' => $row], ['updated' => true]);
    }

    public static function delete(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'doctor']);
        $pdo = Database::pdo($config);
        $existing = DoctorScheduleModel::find($pdo, $id);
        if ($existing === null) {
            Response::error('Schedule not found', 404);
        }
        if (Auth::role() === 'doctor') {
            $me = DoctorModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null || (int)$me['doctor_id'] !== (int)$existing['doctor_id']) {
                Response::error('Forbidden', 403);
            }
        }
        DoctorScheduleModel::delete($pdo, $id);
        Response::ok(['deleted' => true]);
    }

    private static function validate(array $body): array
    {
        $day = (string)($body['day'] ?? '');
        $start = (string)($body['start_time'] ?? '');
        $end = (string)($body['end_time'] ?? '');
        $max = $body['max_patients'] ?? 20;

        $validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        if (!in_array($day, $validDays, true)) {
            Response::error('Invalid day', 422);
        }
        if (!preg_match('/^\\d{2}:\\d{2}(:\\d{2})?$/', $start)) {
            Response::error('Invalid start_time', 422);
        }
        if (!preg_match('/^\\d{2}:\\d{2}(:\\d{2})?$/', $end)) {
            Response::error('Invalid end_time', 422);
        }
        // Normalize to HH:MM:SS
        if (strlen($start) === 5) $start .= ':00';
        if (strlen($end) === 5) $end .= ':00';

        if (!is_numeric($max) || (int)$max <= 0 || (int)$max > 500) {
            Response::error('Invalid max_patients', 422);
        }
        if ($start >= $end) {
            Response::error('start_time must be earlier than end_time', 422);
        }

        return [
            'day' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'max_patients' => (int)$max,
        ];
    }
}

