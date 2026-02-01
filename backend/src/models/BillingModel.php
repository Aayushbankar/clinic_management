<?php
declare(strict_types=1);

final class BillingModel
{
    public static function list(\PDO $pdo, array $filters, int $limit, int $offset): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['patient_id'])) {
            $where[] = 'b.patient_id = :patient_id';
            $params['patient_id'] = (int)$filters['patient_id'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'b.bill_date >= :from';
            $params['from'] = (string)$filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'b.bill_date <= :to';
            $params['to'] = (string)$filters['to'];
        }

        $sql = '
            SELECT
                b.bill_id,
                b.patient_id,
                p.name AS patient_name,
                b.total_amount,
                b.bill_date,
                COALESCE(pay.paid_amount, 0) AS paid_amount,
                (b.total_amount - COALESCE(pay.paid_amount, 0)) AS due_amount,
                b.created_at,
                b.updated_at
            FROM billing b
            JOIN patients p ON p.patient_id = b.patient_id
            LEFT JOIN (
                SELECT bill_id, SUM(amount) AS paid_amount
                FROM payments
                GROUP BY bill_id
            ) pay ON pay.bill_id = b.bill_id
        ';
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY b.bill_date DESC, b.bill_id DESC LIMIT :limit OFFSET :offset';

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
        if (!empty($filters['patient_id'])) {
            $where[] = 'patient_id = :patient_id';
            $params['patient_id'] = (int)$filters['patient_id'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'bill_date >= :from';
            $params['from'] = (string)$filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'bill_date <= :to';
            $params['to'] = (string)$filters['to'];
        }
        $sql = 'SELECT COUNT(*) AS c FROM billing';
        if (count($where) > 0) $sql .= ' WHERE ' . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function find(\PDO $pdo, int $billId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT bill_id, patient_id, total_amount, bill_date, created_at, updated_at
            FROM billing
            WHERE bill_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $billId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function create(\PDO $pdo, int $patientId, string $billDate): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO billing (patient_id, total_amount, bill_date)
            VALUES (:patient_id, 0, :bill_date)
        ');
        $stmt->execute(['patient_id' => $patientId, 'bill_date' => $billDate]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateTotalFromItems(\PDO $pdo, int $billId): void
    {
        $stmt = $pdo->prepare('
            UPDATE billing
            SET total_amount = (
                SELECT COALESCE(SUM(total), 0)
                FROM billing_items
                WHERE bill_id = :bill_id
            )
            WHERE bill_id = :bill_id
        ');
        $stmt->execute(['bill_id' => $billId]);
    }

    public static function delete(\PDO $pdo, int $billId): bool
    {
        $stmt = $pdo->prepare('DELETE FROM billing WHERE bill_id = :id');
        $stmt->execute(['id' => $billId]);
        return $stmt->rowCount() > 0;
    }
}

