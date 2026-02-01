<?php
declare(strict_types=1);

final class DoctorModel
{
    public static function list(\PDO $pdo, string $q, int $limit, int $offset): array
    {
        $q = trim($q);
        $sql = '
            SELECT
                d.doctor_id,
                d.user_id,
                u.user_name,
                u.login_email,
                d.name,
                d.specialization,
                d.qualification,
                d.mobile,
                d.experience,
                d.consultation_fee,
                d.status,
                d.department_id,
                dep.department_name,
                d.created_at,
                d.updated_at
            FROM doctors d
            JOIN users u ON u.user_id = d.user_id
            LEFT JOIN departments dep ON dep.department_id = d.department_id
        ';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE (d.name LIKE :q OR u.user_name LIKE :q OR u.login_email LIKE :q OR d.specialization LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY d.doctor_id DESC LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public static function count(\PDO $pdo, string $q): int
    {
        $q = trim($q);
        if ($q !== '') {
            $stmt = $pdo->prepare('
                SELECT COUNT(*) AS c
                FROM doctors d
                JOIN users u ON u.user_id = d.user_id
                WHERE (d.name LIKE :q OR u.user_name LIKE :q OR u.login_email LIKE :q OR d.specialization LIKE :q)
            ');
            $stmt->execute(['q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->query('SELECT COUNT(*) AS c FROM doctors');
        }
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function find(\PDO $pdo, int $doctorId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT
                d.doctor_id,
                d.user_id,
                u.user_name,
                u.login_email,
                u.status AS user_status,
                d.name,
                d.specialization,
                d.qualification,
                d.mobile,
                d.experience,
                d.consultation_fee,
                d.status,
                d.department_id,
                dep.department_name,
                d.created_at,
                d.updated_at
            FROM doctors d
            JOIN users u ON u.user_id = d.user_id
            LEFT JOIN departments dep ON dep.department_id = d.department_id
            WHERE d.doctor_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $doctorId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function findByUserId(\PDO $pdo, int $userId): ?array
    {
        $stmt = $pdo->prepare('SELECT doctor_id FROM doctors WHERE user_id = :uid LIMIT 1');
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        if (!is_array($row)) return null;
        return self::find($pdo, (int)$row['doctor_id']);
    }

    public static function create(\PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO doctors
              (user_id, name, specialization, qualification, mobile, experience, consultation_fee, status, department_id)
            VALUES
              (:user_id, :name, :specialization, :qualification, :mobile, :experience, :consultation_fee, :status, :department_id)
        ');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'specialization' => $data['specialization'],
            'qualification' => $data['qualification'],
            'mobile' => $data['mobile'],
            'experience' => $data['experience'],
            'consultation_fee' => $data['consultation_fee'],
            'status' => $data['status'],
            'department_id' => $data['department_id'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(\PDO $pdo, int $doctorId, array $data): bool
    {
        $stmt = $pdo->prepare('
            UPDATE doctors
            SET name = :name,
                specialization = :specialization,
                qualification = :qualification,
                mobile = :mobile,
                experience = :experience,
                consultation_fee = :consultation_fee,
                status = :status,
                department_id = :department_id
            WHERE doctor_id = :id
        ');
        $stmt->execute([
            'id' => $doctorId,
            'name' => $data['name'],
            'specialization' => $data['specialization'],
            'qualification' => $data['qualification'],
            'mobile' => $data['mobile'],
            'experience' => $data['experience'],
            'consultation_fee' => $data['consultation_fee'],
            'status' => $data['status'],
            'department_id' => $data['department_id'],
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(\PDO $pdo, int $doctorId): bool
    {
        // Delete doctor row; user row can be deleted separately (FK cascade from doctors->users exists via user_id unique FK).
        $stmt = $pdo->prepare('DELETE FROM doctors WHERE doctor_id = :id');
        $stmt->execute(['id' => $doctorId]);
        return $stmt->rowCount() > 0;
    }
}

