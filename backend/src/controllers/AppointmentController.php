<?php
declare(strict_types=1);

final class AppointmentController
{
    public static function index(array $config): void
    {
        Auth::requireRoles(['admin', 'staff', 'doctor', 'patient']);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $filters = [
            'doctor_id' => $_GET['doctor_id'] ?? null,
            'patient_id' => $_GET['patient_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ];

        $pdo = Database::pdo($config);
        // Role scoping
        if (Auth::role() === 'doctor') {
            $me = DoctorModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Doctor profile not found', 404);
            $filters['doctor_id'] = (int)$me['doctor_id'];
        } elseif (Auth::role() === 'patient') {
            $me = PatientModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Patient profile not found', 404);
            $filters['patient_id'] = (int)$me['patient_id'];
        }

        $rows = AppointmentModel::list($pdo, $filters, $pageSize, $offset);
        $total = AppointmentModel::count($pdo, $filters);

        Response::ok(['items' => $rows], [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ]);
    }

    public static function show(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'staff', 'doctor', 'patient']);
        $pdo = Database::pdo($config);
        $row = AppointmentModel::find($pdo, $id);
        if ($row === null) Response::error('Appointment not found', 404);
        self::enforceAccessToAppointment($pdo, $row);
        Response::ok(['appointment' => $row]);
    }

    public static function create(array $config): void
    {
        Auth::requireRoles(['admin', 'staff', 'patient']);
        $body = Request::json();

        $pdo = Database::pdo($config);

        $patientId = (int)($body['patient_id'] ?? 0);
        if (Auth::role() === 'patient') {
            $me = PatientModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Patient profile not found', 404);
            $patientId = (int)$me['patient_id'];
        }
        $doctorId = (int)($body['doctor_id'] ?? 0);
        $date = (string)($body['appointment_date'] ?? '');
        $time = (string)($body['appointment_time'] ?? '');

        $data = self::validatePayload($patientId, $doctorId, $date, $time, 'scheduled');
        self::validateAgainstScheduleAndCapacity($pdo, $doctorId, $date, $time);

        try {
            $id = AppointmentModel::create($pdo, $data);
        } catch (\PDOException $e) {
            // Unique (doctor,date,time)
            if ((string)$e->getCode() === '23000') {
                Response::error('This time slot is already booked', 409);
            }
            throw $e;
        }

        $row = AppointmentModel::find($pdo, $id);
        Response::ok(['appointment' => $row], ['created' => true]);
    }

    public static function update(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'staff', 'doctor', 'patient']);
        $body = Request::json();

        $pdo = Database::pdo($config);
        $existing = AppointmentModel::find($pdo, $id);
        if ($existing === null) Response::error('Appointment not found', 404);
        self::enforceAccessToAppointment($pdo, $existing);

        $role = Auth::role();
        $status = (string)($body['status'] ?? $existing['status']);

        // Role rules:
        // - patient can only cancel their own scheduled appointment
        // - doctor can update status of their appointments
        // - staff/admin can reschedule and update status
        if ($role === 'patient') {
            if ($status !== 'cancelled') {
                Response::error('Patients can only cancel appointments', 403);
            }
            if ((string)$existing['status'] !== 'scheduled') {
                Response::error('Only scheduled appointments can be cancelled', 409);
            }
            $data = [
                'patient_id' => (int)$existing['patient_id'],
                'doctor_id' => (int)$existing['doctor_id'],
                'appointment_date' => (string)$existing['appointment_date'],
                'appointment_time' => (string)$existing['appointment_time'],
                'status' => 'cancelled',
            ];
            AppointmentModel::update($pdo, $id, $data);
            $row = AppointmentModel::find($pdo, $id);
            Response::ok(['appointment' => $row], ['updated' => true]);
        }

        if ($role === 'doctor') {
            if (!in_array($status, ['scheduled','completed','cancelled','no_show'], true)) {
                Response::error('Invalid status', 422);
            }
            $data = [
                'patient_id' => (int)$existing['patient_id'],
                'doctor_id' => (int)$existing['doctor_id'],
                'appointment_date' => (string)$existing['appointment_date'],
                'appointment_time' => (string)$existing['appointment_time'],
                'status' => $status,
            ];
            AppointmentModel::update($pdo, $id, $data);
            $row = AppointmentModel::find($pdo, $id);
            Response::ok(['appointment' => $row], ['updated' => true]);
        }

        // admin/staff
        $patientId = (int)($body['patient_id'] ?? $existing['patient_id']);
        $doctorId = (int)($body['doctor_id'] ?? $existing['doctor_id']);
        $date = (string)($body['appointment_date'] ?? $existing['appointment_date']);
        $time = (string)($body['appointment_time'] ?? $existing['appointment_time']);
        if (!in_array($status, ['scheduled','completed','cancelled','no_show'], true)) {
            Response::error('Invalid status', 422);
        }
        $data = self::validatePayload($patientId, $doctorId, $date, $time, $status);
        // If changing slot, validate schedule/capacity + uniqueness constraint may trigger
        self::validateAgainstScheduleAndCapacity($pdo, $doctorId, $date, $time, $id);

        try {
            AppointmentModel::update($pdo, $id, $data);
        } catch (\PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                Response::error('This time slot is already booked', 409);
            }
            throw $e;
        }
        $row = AppointmentModel::find($pdo, $id);
        Response::ok(['appointment' => $row], ['updated' => true]);
    }

    private static function validatePayload(int $patientId, int $doctorId, string $date, string $time, string $status): array
    {
        if ($patientId <= 0) Response::error('Invalid patient_id', 422);
        if ($doctorId <= 0) Response::error('Invalid doctor_id', 422);
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) Response::error('Invalid appointment_date', 422);
        if (!preg_match('/^\\d{2}:\\d{2}(:\\d{2})?$/', $time)) Response::error('Invalid appointment_time', 422);
        if (strlen($time) === 5) $time .= ':00';
        if (!in_array($status, ['scheduled','completed','cancelled','no_show'], true)) {
            Response::error('Invalid status', 422);
        }
        return [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'status' => $status,
        ];
    }

    private static function validateAgainstScheduleAndCapacity(\PDO $pdo, int $doctorId, string $date, string $time, int $ignoreAppointmentId = 0): void
    {
        // Validate doctor exists and active
        $doc = DoctorModel::find($pdo, $doctorId);
        if ($doc === null) Response::error('Doctor not found', 404);
        if ((string)($doc['status'] ?? '') !== 'active') Response::error('Doctor is inactive', 409);

        // Day name in English matches ENUM in doctor_schedule
        $stmt = $pdo->prepare('SELECT DAYNAME(:d) AS day_name');
        $stmt->execute(['d' => $date]);
        $dayName = (string)(($stmt->fetch() ?? [])['day_name'] ?? '');
        if ($dayName === '') Response::error('Invalid appointment_date', 422);

        $schedule = DoctorScheduleModel::findMatchingSchedule($pdo, $doctorId, $dayName, $time);
        if ($schedule === null) {
            Response::error('Selected time is outside doctor schedule', 409, ['day' => $dayName]);
        }

        // Capacity check per day using schedule.max_patients
        $sql = 'SELECT COUNT(*) AS c FROM appointments WHERE doctor_id = :doctor_id AND appointment_date = :d AND status IN ("scheduled","completed","no_show")';
        $params = ['doctor_id' => $doctorId, 'd' => $date];
        if ($ignoreAppointmentId > 0) {
            $sql .= ' AND appointment_id <> :ignore_id';
            $params['ignore_id'] = $ignoreAppointmentId;
        }
        $stmt2 = $pdo->prepare($sql);
        $stmt2->execute($params);
        $count = (int)(($stmt2->fetch() ?? [])['c'] ?? 0);
        $max = (int)($schedule['max_patients'] ?? 0);
        if ($max > 0 && $count >= $max) {
            Response::error('Doctor is fully booked for this day', 409, ['max_patients' => $max]);
        }
    }

    private static function enforceAccessToAppointment(\PDO $pdo, array $appointment): void
    {
        $role = Auth::role();
        if ($role === 'admin' || $role === 'staff') {
            return;
        }
        if ($role === 'doctor') {
            $me = DoctorModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Doctor profile not found', 404);
            if ((int)$appointment['doctor_id'] !== (int)$me['doctor_id']) {
                Response::error('Forbidden', 403);
            }
            return;
        }
        if ($role === 'patient') {
            $me = PatientModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Patient profile not found', 404);
            if ((int)$appointment['patient_id'] !== (int)$me['patient_id']) {
                Response::error('Forbidden', 403);
            }
            return;
        }
        Response::error('Forbidden', 403);
    }
}

