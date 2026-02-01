<?php
declare(strict_types=1);

final class BillingItemModel
{
    public static function listByBill(\PDO $pdo, int $billId): array
    {
        $stmt = $pdo->prepare('
            SELECT item_id, bill_id, description, quantity, price, total, created_at
            FROM billing_items
            WHERE bill_id = :bill_id
            ORDER BY item_id ASC
        ');
        $stmt->execute(['bill_id' => $billId]);
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public static function create(\PDO $pdo, int $billId, array $item): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO billing_items (bill_id, description, quantity, price, total)
            VALUES (:bill_id, :description, :quantity, :price, :total)
        ');
        $stmt->execute([
            'bill_id' => $billId,
            'description' => $item['description'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'total' => $item['total'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function deleteByBill(\PDO $pdo, int $billId): void
    {
        $stmt = $pdo->prepare('DELETE FROM billing_items WHERE bill_id = :bill_id');
        $stmt->execute(['bill_id' => $billId]);
    }
}

