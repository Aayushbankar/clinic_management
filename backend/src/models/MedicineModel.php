<?php
declare(strict_types=1);

final class MedicineModel
{
    public static function list(\PDO $pdo, string $q, int $limit, int $offset): array
    {
        $q = trim($q);
        if ($q !== '') {
            $stmt = $pdo->prepare('
                SELECT medicine_id, medicine_name, company, price, stock, expiry_date, created_at, updated_at
                FROM medicines
                WHERE medicine_name LIKE :q OR company LIKE :q
                ORDER BY medicine_name ASC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':q', '%' . $q . '%', \PDO::PARAM_STR);
        } else {
            $stmt = $pdo->prepare('
                SELECT medicine_id, medicine_name, company, price, stock, expiry_date, created_at, updated_at
                FROM medicines
                ORDER BY medicine_name ASC
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
            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM medicines WHERE medicine_name LIKE :q OR company LIKE :q');
            $stmt->execute(['q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->query('SELECT COUNT(*) AS c FROM medicines');
        }
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function find(\PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('
            SELECT medicine_id, medicine_name, company, price, stock, expiry_date, created_at, updated_at
            FROM medicines
            WHERE medicine_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function create(\PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO medicines (medicine_name, company, price, stock, expiry_date)
            VALUES (:medicine_name, :company, :price, :stock, :expiry_date)
        ');
        $stmt->execute([
            'medicine_name' => $data['medicine_name'],
            'company' => $data['company'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'expiry_date' => $data['expiry_date'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(\PDO $pdo, int $id, array $data): bool
    {
        $stmt = $pdo->prepare('
            UPDATE medicines
            SET medicine_name = :medicine_name,
                company = :company,
                price = :price,
                stock = :stock,
                expiry_date = :expiry_date
            WHERE medicine_id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'medicine_name' => $data['medicine_name'],
            'company' => $data['company'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'expiry_date' => $data['expiry_date'],
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(\PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('DELETE FROM medicines WHERE medicine_id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}

