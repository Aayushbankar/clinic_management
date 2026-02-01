<?php
declare(strict_types=1);

final class UserModel
{
    public static function findByEmail(\PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare('
            SELECT user_id, user_name, login_email, password, role, status, created_at
            FROM users
            WHERE login_email = :email
            LIMIT 1
        ');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function findById(\PDO $pdo, int $userId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT user_id, user_name, login_email, role, status, created_at
            FROM users
            WHERE user_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function list(\PDO $pdo, string $q, ?string $role, int $limit, int $offset): array
    {
        $q = trim($q);
        $role = $role !== null ? trim($role) : null;

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(user_name LIKE :q OR login_email LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        if ($role !== null && $role !== '') {
            $where[] = 'role = :role';
            $params['role'] = $role;
        }

        $sql = '
            SELECT user_id, user_name, login_email, role, status, created_at
            FROM users
        ';
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY user_id DESC LIMIT :limit OFFSET :offset';

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

    public static function count(\PDO $pdo, string $q, ?string $role): int
    {
        $q = trim($q);
        $role = $role !== null ? trim($role) : null;

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(user_name LIKE :q OR login_email LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        if ($role !== null && $role !== '') {
            $where[] = 'role = :role';
            $params['role'] = $role;
        }

        $sql = 'SELECT COUNT(*) AS c FROM users';
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function create(\PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO users (user_name, login_email, password, role, status)
            VALUES (:user_name, :login_email, :password, :role, :status)
        ');
        $stmt->execute([
            'user_name' => $data['user_name'],
            'login_email' => $data['login_email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(\PDO $pdo, int $userId, array $data): bool
    {
        $stmt = $pdo->prepare('
            UPDATE users
            SET user_name = :user_name,
                login_email = :login_email,
                role = :role,
                status = :status
            WHERE user_id = :id
        ');
        $stmt->execute([
            'id' => $userId,
            'user_name' => $data['user_name'],
            'login_email' => $data['login_email'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function updatePassword(\PDO $pdo, int $userId, string $passwordHash): bool
    {
        $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE user_id = :id');
        $stmt->execute(['id' => $userId, 'password' => $passwordHash]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(\PDO $pdo, int $userId): bool
    {
        $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
        return $stmt->rowCount() > 0;
    }
}

