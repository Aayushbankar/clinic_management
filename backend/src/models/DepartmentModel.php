<?php
declare(strict_types=1);

final class DepartmentModel
{
    public static function list(\PDO $pdo, string $q, int $limit, int $offset): array
    {
        $q = trim($q);
        if ($q !== '') {
            $stmt = $pdo->prepare('
                SELECT department_id, department_name, description, created_at, updated_at
                FROM departments
                WHERE department_name LIKE :q
                ORDER BY department_name ASC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':q', '%' . $q . '%', \PDO::PARAM_STR);
        } else {
            $stmt = $pdo->prepare('
                SELECT department_id, department_name, description, created_at, updated_at
                FROM departments
                ORDER BY department_name ASC
                LIMIT :limit OFFSET :offset
            ');
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
            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM departments WHERE department_name LIKE :q');
            $stmt->execute(['q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->query('SELECT COUNT(*) AS c FROM departments');
        }
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function find(\PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('
            SELECT department_id, department_name, description, created_at, updated_at
            FROM departments
            WHERE department_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function create(\PDO $pdo, string $name, ?string $description): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO departments (department_name, description)
            VALUES (:name, :description)
        ');
        $stmt->execute([
            'name' => $name,
            'description' => $description,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(\PDO $pdo, int $id, string $name, ?string $description): bool
    {
        $stmt = $pdo->prepare('
            UPDATE departments
            SET department_name = :name, description = :description
            WHERE department_id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(\PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('DELETE FROM departments WHERE department_id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}

