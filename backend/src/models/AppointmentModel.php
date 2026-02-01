<?php
declare(strict_types=1);

final class AppointmentModel
{
    public static function list(\PDO $pdo, array $filters, int $limit, int $offset): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['doctor_id'])) {
            $where[] = 'a.doctor_id = :doctor_id';
            $params['doctor_id'] = (int)$filters['doctor_id'];
        }
        if (!empty($filters['patient_id'])) {
            $where[] = 'a.patient_id = :patient_id';
            $params['patient_id'] = (int)$filters['patient_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'a.status = :status';
            $params['status'] = (string)$filters['status'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'a.appointment_date >= :from';
            $params['from'] = (string)$filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'a.appointment_date <= :to';
            $params['to'] = (string)$filters['to'];
        }

        $sql = '
            SELECT
                a.appointment_id,
                a.patient_id,
                p.name AS patient_name,
                pu.login_email AS patient_email,
                a.doctor_id,
                d.name AS doctor_name,
                du.login_email AS doctor_email,
                a.appointment_date,
                a.appointment_time,
                a.status,
                a.created_at,
                a.updated_at
            FROM appointments a
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = a.doctor_id
            JOIN users du ON du.user_id = d.user_id
        ';
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $type = is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $k, $v, $type);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public static function count(\PDO $pdo, array $filters): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['doctor_id'])) {
            $where[] = 'doctor_id = :doctor_id';
            $params['doctor_id'] = (int)$filters['doctor_id'];
        }
        if (!empty($filters['patient_id'])) {
            $where[] = 'patient_id = :patient_id';
            $params['patient_id'] = (int)$filters['patient_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = (string)$filters['status'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'appointment_date >= :from';
            $params['from'] = (string)$filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'appointment_date <= :to';
            $params['to'] = (string)$filters['to'];
        }

        $sql = 'SELECT COUNT(*) AS c FROM appointments';
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function find(\PDO $pdo, int $appointmentId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT
                a.appointment_id,
                a.patient_id,
                p.name AS patient_name,
                pu.login_email AS patient_email,
                a.doctor_id,
                d.name AS doctor_name,
                du.login_email AS doctor_email,
                a.appointment_date,
                a.appointment_time,
                a.status,
                a.created_at,
                a.updated_at
            FROM appointments a
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = a.doctor_id
            JOIN users du ON du.user_id = d.user_id
            WHERE a.appointment_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $appointmentId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function create(\PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status)
            VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :status)
        ');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'status' => $data['status'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(\PDO $pdo, int $appointmentId, array $data): bool
    {
        $stmt = $pdo->prepare('
            UPDATE appointments
            SET patient_id = :patient_id,
                doctor_id = :doctor_id,
                appointment_date = :appointment_date,
                appointment_time = :appointment_time,
                status = :status
            WHERE appointment_id = :id
        ');
        $stmt->execute([
            'id' => $appointmentId,
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'status' => $data['status'],
        ]);
        return $stmt->rowCount() > 0;
    }
}

