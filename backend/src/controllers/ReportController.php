<?php
declare(strict_types=1);

final class ReportController
{
    public static function dashboard(array $config): void
    {
        Auth::requireRoles(['admin', 'staff', 'doctor', 'patient']);
        $pdo = Database::pdo($config);
        $role = (string)Auth::role();

        if ($role === 'admin' || $role === 'staff') {
            $counts = [
                'doctors' => (int)self::scalar($pdo, 'SELECT COUNT(*) FROM doctors'),
                'staff' => (int)self::scalar($pdo, 'SELECT COUNT(*) FROM staff'),
                'patients' => (int)self::scalar($pdo, 'SELECT COUNT(*) FROM patients'),
                'appointments_today' => (int)self::scalar($pdo, 'SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()'),
                'revenue_30d' => (float)self::scalar($pdo, 'SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date >= (NOW() - INTERVAL 30 DAY)'),
            ];
            Response::ok(['dashboard' => $counts]);
        }

        if ($role === 'doctor') {
            $me = DoctorModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Doctor profile not found', 404);
            $doctorId = (int)$me['doctor_id'];
            $counts = [
                'appointments_today' => (int)self::scalar($pdo, 'SELECT COUNT(*) FROM appointments WHERE doctor_id = :id AND appointment_date = CURDATE()', ['id' => $doctorId]),
                'upcoming_7d' => (int)self::scalar($pdo, 'SELECT COUNT(*) FROM appointments WHERE doctor_id = :id AND appointment_date BETWEEN CURDATE() AND (CURDATE() + INTERVAL 7 DAY) AND status = "scheduled"', ['id' => $doctorId]),
            ];
            Response::ok(['dashboard' => $counts]);
        }

        // patient
        $me = PatientModel::findByUserId($pdo, (int)Auth::userId());
        if ($me === null) Response::error('Patient profile not found', 404);
        $patientId = (int)$me['patient_id'];
        $counts = [
            'upcoming_appointments' => (int)self::scalar($pdo, 'SELECT COUNT(*) FROM appointments WHERE patient_id = :id AND appointment_date >= CURDATE() AND status = "scheduled"', ['id' => $patientId]),
            'due_amount' => (float)self::scalar($pdo, '
                SELECT COALESCE(SUM(b.total_amount - COALESCE(paid.paid_amount,0)),0) AS due
                FROM billing b
                LEFT JOIN (SELECT bill_id, SUM(amount) AS paid_amount FROM payments GROUP BY bill_id) paid
                  ON paid.bill_id = b.bill_id
                WHERE b.patient_id = :id
            ', ['id' => $patientId]),
        ];
        Response::ok(['dashboard' => $counts]);
    }

    public static function appointmentsPerDoctor(array $config): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $from = (string)($_GET['from'] ?? date('Y-m-01'));
        $to = (string)($_GET['to'] ?? date('Y-m-d'));
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $from)) Response::error('Invalid from', 422);
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $to)) Response::error('Invalid to', 422);

        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('
            SELECT
              d.doctor_id,
              d.name AS doctor_name,
              COUNT(a.appointment_id) AS appointment_count
            FROM doctors d
            LEFT JOIN appointments a
              ON a.doctor_id = d.doctor_id
             AND a.appointment_date BETWEEN :from AND :to
            GROUP BY d.doctor_id, d.name
            ORDER BY appointment_count DESC, doctor_name ASC
        ');
        $stmt->execute(['from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll();

        Response::ok(['items' => is_array($rows) ? $rows : []], ['from' => $from, 'to' => $to]);
    }

    public static function revenue(array $config): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $from = (string)($_GET['from'] ?? date('Y-m-01'));
        $to = (string)($_GET['to'] ?? date('Y-m-d'));
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $from)) Response::error('Invalid from', 422);
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $to)) Response::error('Invalid to', 422);

        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(amount),0) AS revenue
            FROM payments
            WHERE DATE(payment_date) BETWEEN :from AND :to
        ');
        $stmt->execute(['from' => $from, 'to' => $to]);
        $row = $stmt->fetch();
        Response::ok([
            'revenue' => (float)($row['revenue'] ?? 0),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public static function patientHistory(array $config): void
    {
        Auth::requireRoles(['admin', 'staff', 'doctor', 'patient']);
        $pdo = Database::pdo($config);

        $patientId = (int)($_GET['patient_id'] ?? 0);
        if (Auth::role() === 'patient') {
            $me = PatientModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Patient profile not found', 404);
            $patientId = (int)$me['patient_id'];
        }
        if ($patientId <= 0) Response::error('patient_id is required', 422);

        $stmt = $pdo->prepare('
            SELECT
              h.history_id,
              h.patient_id,
              p.name AS patient_name,
              h.doctor_id,
              d.name AS doctor_name,
              h.visit_date,
              h.notes,
              h.diagnosis,
              h.created_at
            FROM patient_history h
            JOIN patients p ON p.patient_id = h.patient_id
            JOIN doctors d ON d.doctor_id = h.doctor_id
            WHERE h.patient_id = :pid
            ORDER BY h.visit_date DESC, h.history_id DESC
        ');
        $stmt->execute(['pid' => $patientId]);
        $rows = $stmt->fetchAll();
        Response::ok(['items' => is_array($rows) ? $rows : []]);
    }

    /**
     * Export appointments as CSV
     */
    public static function appointmentsCsv(array $config): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $from = (string)($_GET['from'] ?? date('Y-m-01'));
        $to = (string)($_GET['to'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) Response::error('Invalid from', 422);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) Response::error('Invalid to', 422);

        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('
            SELECT
              a.appointment_id,
              a.appointment_date,
              a.appointment_time,
              a.status,
              p.name AS patient_name,
              p.mobile AS patient_mobile,
              d.name AS doctor_name,
              d.specialization,
              dep.department_name
            FROM appointments a
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN doctors d ON d.doctor_id = a.doctor_id
            LEFT JOIN departments dep ON dep.department_id = d.department_id
            WHERE a.appointment_date BETWEEN :from AND :to
            ORDER BY a.appointment_date, a.appointment_time
        ');
        $stmt->execute(['from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        self::outputCsv("appointments_{$from}_to_{$to}.csv", $rows, [
            'appointment_id' => 'ID',
            'appointment_date' => 'Date',
            'appointment_time' => 'Time',
            'status' => 'Status',
            'patient_name' => 'Patient',
            'patient_mobile' => 'Mobile',
            'doctor_name' => 'Doctor',
            'specialization' => 'Specialization',
            'department_name' => 'Department',
        ]);
    }

    /**
     * Export billing as CSV
     */
    public static function billingCsv(array $config): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $from = (string)($_GET['from'] ?? date('Y-m-01'));
        $to = (string)($_GET['to'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) Response::error('Invalid from', 422);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) Response::error('Invalid to', 422);

        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('
            SELECT
              b.bill_id,
              b.bill_date,
              p.name AS patient_name,
              p.mobile AS patient_mobile,
              b.total_amount,
              COALESCE(paid.paid_amount, 0) AS paid_amount,
              (b.total_amount - COALESCE(paid.paid_amount, 0)) AS due_amount
            FROM billing b
            JOIN patients p ON p.patient_id = b.patient_id
            LEFT JOIN (
              SELECT bill_id, SUM(amount) AS paid_amount FROM payments GROUP BY bill_id
            ) paid ON paid.bill_id = b.bill_id
            WHERE b.bill_date BETWEEN :from AND :to
            ORDER BY b.bill_date DESC
        ');
        $stmt->execute(['from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        self::outputCsv("billing_{$from}_to_{$to}.csv", $rows, [
            'bill_id' => 'Bill ID',
            'bill_date' => 'Date',
            'patient_name' => 'Patient',
            'patient_mobile' => 'Mobile',
            'total_amount' => 'Total',
            'paid_amount' => 'Paid',
            'due_amount' => 'Due',
        ]);
    }

    /**
     * Output CSV file directly
     */
    private static function outputCsv(string $filename, array $rows, array $headers): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: no-cache');

        $output = fopen('php://output', 'w');
        
        // BOM for Excel UTF-8 compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write header row
        fputcsv($output, array_values($headers));
        
        // Write data rows
        foreach ($rows as $row) {
            $line = [];
            foreach (array_keys($headers) as $key) {
                $line[] = $row[$key] ?? '';
            }
            fputcsv($output, $line);
        }
        
        fclose($output);
        exit;
    }

    private static function scalar(\PDO $pdo, string $sql, array $params = []): mixed
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return is_array($row) ? $row[0] : null;
    }
}

