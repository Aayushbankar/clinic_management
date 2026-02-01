<?php
declare(strict_types=1);

final class PatientModel
{
    public static function list(\PDO $pdo, string $q, int $limit, int $offset): array
    {
        $q = trim($q);
        $sql = '
            SELECT
                p.patient_id,
                p.user_id,
                u.user_name,
                u.login_email,
                p.name,
                p.gender,
                p.dob,
                p.mobile,
                p.address,
                p.blood_group,
                p.created_at,
                p.updated_at
            FROM patients p
            JOIN users u ON u.user_id = p.user_id
        ';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE (p.name LIKE :q OR u.user_name LIKE :q OR u.login_email LIKE :q OR p.mobile LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY p.patient_id DESC LIMIT :limit OFFSET :offset';

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
                FROM patients p
                JOIN users u ON u.user_id = p.user_id
                WHERE (p.name LIKE :q OR u.user_name LIKE :q OR u.login_email LIKE :q OR p.mobile LIKE :q)
            ');
            $stmt->execute(['q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->query('SELECT COUNT(*) AS c FROM patients');
        }
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function find(\PDO $pdo, int $patientId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT
                p.patient_id,
                p.user_id,
                u.user_name,
                u.login_email,
                u.status AS user_status,
                p.name,
                p.gender,
                p.dob,
                p.mobile,
                p.address,
                p.blood_group,
                p.created_at,
                p.updated_at
            FROM patients p
            JOIN users u ON u.user_id = p.user_id
            WHERE p.patient_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $patientId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function findByUserId(\PDO $pdo, int $userId): ?array
    {
        $stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE user_id = :uid LIMIT 1');
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        if (!is_array($row)) return null;
        return self::find($pdo, (int)$row['patient_id']);
    }

    public static function create(\PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO patients (user_id, name, gender, dob, mobile, address, blood_group)
            VALUES (:user_id, :name, :gender, :dob, :mobile, :address, :blood_group)
        ');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'gender' => $data['gender'],
            'dob' => $data['dob'],
            'mobile' => $data['mobile'],
            'address' => $data['address'],
            'blood_group' => $data['blood_group'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(\PDO $pdo, int $patientId, array $data): bool
    {
        $stmt = $pdo->prepare('
            UPDATE patients
            SET name = :name,
                gender = :gender,
                dob = :dob,
                mobile = :mobile,
                address = :address,
                blood_group = :blood_group
            WHERE patient_id = :id
        ');
        $stmt->execute([
            'id' => $patientId,
            'name' => $data['name'],
            'gender' => $data['gender'],
            'dob' => $data['dob'],
            'mobile' => $data['mobile'],
            'address' => $data['address'],
            'blood_group' => $data['blood_group'],
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(\PDO $pdo, int $patientId): bool
    {
        $stmt = $pdo->prepare('DELETE FROM patients WHERE patient_id = :id');
        $stmt->execute(['id' => $patientId]);
        return $stmt->rowCount() > 0;
    }
}

