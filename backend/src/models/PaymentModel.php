<?php
declare(strict_types=1);

final class PaymentModel
{
    public static function listByBill(\PDO $pdo, int $billId): array
    {
        $stmt = $pdo->prepare('
            SELECT payment_id, bill_id, payment_mode, amount, payment_date, created_at
            FROM payments
            WHERE bill_id = :bill_id
            ORDER BY payment_date DESC, payment_id DESC
        ');
        $stmt->execute(['bill_id' => $billId]);
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public static function sumPaid(\PDO $pdo, int $billId): float
    {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS paid FROM payments WHERE bill_id = :bill_id');
        $stmt->execute(['bill_id' => $billId]);
        $row = $stmt->fetch();
        return (float)($row['paid'] ?? 0);
    }

    public static function create(\PDO $pdo, int $billId, string $mode, float $amount, string $paymentDateTime): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO payments (bill_id, payment_mode, amount, payment_date)
            VALUES (:bill_id, :payment_mode, :amount, :payment_date)
        ');
        $stmt->execute([
            'bill_id' => $billId,
            'payment_mode' => $mode,
            'amount' => $amount,
            'payment_date' => $paymentDateTime,
        ]);
        return (int)$pdo->lastInsertId();
    }
}

