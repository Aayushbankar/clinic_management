<?php
declare(strict_types=1);

final class StaffModel
{
    public static function list(\PDO $pdo, string $q, int $limit, int $offset): array
    {
        $q = trim($q);
        $sql = '
            SELECT
                s.staff_id,
                s.user_id,
                u.user_name,
                u.login_email,
                s.role,
                s.salary,
                s.joining_date,
                s.status,
                s.created_at,
                s.updated_at
            FROM staff s
            JOIN users u ON u.user_id = s.user_id
        ';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE (u.user_name LIKE :q OR u.login_email LIKE :q OR s.role LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY s.staff_id DESC LIMIT :limit OFFSET :offset';

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
                FROM staff s
                JOIN users u ON u.user_id = s.user_id
                WHERE (u.user_name LIKE :q OR u.login_email LIKE :q OR s.role LIKE :q)
            ');
            $stmt->execute(['q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->query('SELECT COUNT(*) AS c FROM staff');
        }
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function find(\PDO $pdo, int $staffId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT
                s.staff_id,
                s.user_id,
                u.user_name,
                u.login_email,
                u.status AS user_status,
                s.role,
                s.salary,
                s.joining_date,
                s.status,
                s.created_at,
                s.updated_at
            FROM staff s
            JOIN users u ON u.user_id = s.user_id
            WHERE s.staff_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $staffId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function create(\PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO staff (user_id, role, salary, joining_date, status)
            VALUES (:user_id, :role, :salary, :joining_date, :status)
        ');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'role' => $data['role'],
            'salary' => $data['salary'],
            'joining_date' => $data['joining_date'],
            'status' => $data['status'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(\PDO $pdo, int $staffId, array $data): bool
    {
        $stmt = $pdo->prepare('
            UPDATE staff
            SET role = :role,
                salary = :salary,
                joining_date = :joining_date,
                status = :status
            WHERE staff_id = :id
        ');
        $stmt->execute([
            'id' => $staffId,
            'role' => $data['role'],
            'salary' => $data['salary'],
            'joining_date' => $data['joining_date'],
            'status' => $data['status'],
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(\PDO $pdo, int $staffId): bool
    {
        $stmt = $pdo->prepare('DELETE FROM staff WHERE staff_id = :id');
        $stmt->execute(['id' => $staffId]);
        return $stmt->rowCount() > 0;
    }
}

