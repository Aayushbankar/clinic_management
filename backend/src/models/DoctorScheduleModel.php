<?php
declare(strict_types=1);

final class DoctorScheduleModel
{
    public static function listByDoctor(\PDO $pdo, int $doctorId): array
    {
        $stmt = $pdo->prepare('
            SELECT schedule_id, doctor_id, day, start_time, end_time, max_patients, created_at, updated_at
            FROM doctor_schedule
            WHERE doctor_id = :doctor_id
            ORDER BY FIELD(day, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), start_time ASC
        ');
        $stmt->execute(['doctor_id' => $doctorId]);
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public static function find(\PDO $pdo, int $scheduleId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT schedule_id, doctor_id, day, start_time, end_time, max_patients, created_at, updated_at
            FROM doctor_schedule
            WHERE schedule_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $scheduleId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function create(\PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO doctor_schedule (doctor_id, day, start_time, end_time, max_patients)
            VALUES (:doctor_id, :day, :start_time, :end_time, :max_patients)
        ');
        $stmt->execute([
            'doctor_id' => $data['doctor_id'],
            'day' => $data['day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'max_patients' => $data['max_patients'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(\PDO $pdo, int $scheduleId, array $data): bool
    {
        $stmt = $pdo->prepare('
            UPDATE doctor_schedule
            SET day = :day,
                start_time = :start_time,
                end_time = :end_time,
                max_patients = :max_patients
            WHERE schedule_id = :id
        ');
        $stmt->execute([
            'id' => $scheduleId,
            'day' => $data['day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'max_patients' => $data['max_patients'],
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(\PDO $pdo, int $scheduleId): bool
    {
        $stmt = $pdo->prepare('DELETE FROM doctor_schedule WHERE schedule_id = :id');
        $stmt->execute(['id' => $scheduleId]);
        return $stmt->rowCount() > 0;
    }

    public static function findMatchingSchedule(\PDO $pdo, int $doctorId, string $dayName, string $time): ?array
    {
        // time must be within [start_time, end_time)
        $stmt = $pdo->prepare('
            SELECT schedule_id, doctor_id, day, start_time, end_time, max_patients
            FROM doctor_schedule
            WHERE doctor_id = :doctor_id
              AND day = :day
              AND :t1 >= start_time
              AND :t2 < end_time
            ORDER BY start_time ASC
            LIMIT 1
        ');
        $stmt->execute([
            'doctor_id' => $doctorId,
            'day' => $dayName,
            't1' => $time,
            't2' => $time,
        ]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }
}

