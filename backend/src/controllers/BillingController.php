<?php
declare(strict_types=1);

final class BillingController
{
    public static function index(array $config): void
    {
        Auth::requireRoles(['admin', 'staff', 'patient']);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $filters = [
            'patient_id' => $_GET['patient_id'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ];

        $pdo = Database::pdo($config);
        if (Auth::role() === 'patient') {
            $me = PatientModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Patient profile not found', 404);
            $filters['patient_id'] = (int)$me['patient_id'];
        }

        $rows = BillingModel::list($pdo, $filters, $pageSize, $offset);
        $total = BillingModel::count($pdo, $filters);

        Response::ok(['items' => $rows], [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ]);
    }

    public static function show(array $config, int $billId): void
    {
        Auth::requireRoles(['admin', 'staff', 'patient']);
        $pdo = Database::pdo($config);
        $bill = BillingModel::find($pdo, $billId);
        if ($bill === null) Response::error('Bill not found', 404);
        self::enforceAccessToBill($pdo, $bill);

        $items = BillingItemModel::listByBill($pdo, $billId);
        $payments = PaymentModel::listByBill($pdo, $billId);
        $paid = PaymentModel::sumPaid($pdo, $billId);
        $due = max(0.0, (float)$bill['total_amount'] - $paid);

        Response::ok([
            'bill' => $bill,
            'items' => $items,
            'payments' => $payments,
            'summary' => [
                'paid_amount' => $paid,
                'due_amount' => $due,
            ],
        ]);
    }

    public static function create(array $config): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $body = Request::json();

        $patientId = (int)($body['patient_id'] ?? 0);
        $billDate = (string)($body['bill_date'] ?? date('Y-m-d'));
        $items = $body['items'] ?? [];

        if ($patientId <= 0) Response::error('Invalid patient_id', 422);
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $billDate)) Response::error('Invalid bill_date', 422);
        if (!is_array($items) || count($items) === 0) Response::error('At least one item is required', 422);

        $pdo = Database::pdo($config);
        if (PatientModel::find($pdo, $patientId) === null) Response::error('Patient not found', 404);

        $normalizedItems = self::normalizeItems($items);

        $pdo->beginTransaction();
        try {
            $billId = BillingModel::create($pdo, $patientId, $billDate);
            foreach ($normalizedItems as $it) {
                BillingItemModel::create($pdo, $billId, $it);
            }
            BillingModel::updateTotalFromItems($pdo, $billId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        self::show($config, $billId);
    }

    public static function update(array $config, int $billId): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $body = Request::json();
        $items = $body['items'] ?? null;
        if (!is_array($items) || count($items) === 0) {
            Response::error('items is required', 422);
        }

        $pdo = Database::pdo($config);
        $bill = BillingModel::find($pdo, $billId);
        if ($bill === null) Response::error('Bill not found', 404);

        $normalizedItems = self::normalizeItems($items);

        $pdo->beginTransaction();
        try {
            BillingItemModel::deleteByBill($pdo, $billId);
            foreach ($normalizedItems as $it) {
                BillingItemModel::create($pdo, $billId, $it);
            }
            BillingModel::updateTotalFromItems($pdo, $billId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        self::show($config, $billId);
    }

    public static function delete(array $config, int $billId): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $pdo = Database::pdo($config);
        if (BillingModel::find($pdo, $billId) === null) Response::error('Bill not found', 404);
        BillingModel::delete($pdo, $billId);
        Response::ok(['deleted' => true]);
    }

    public static function addPayment(array $config, int $billId): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $body = Request::json();

        $mode = trim((string)($body['payment_mode'] ?? 'cash'));
        $amount = $body['amount'] ?? null;
        $paymentDate = (string)($body['payment_date'] ?? date('Y-m-d H:i:s'));

        if ($mode === '' || strlen($mode) > 60) Response::error('Invalid payment_mode', 422);
        if (!is_numeric($amount) || (float)$amount <= 0) Response::error('Invalid amount', 422);
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$/', $paymentDate)) {
            Response::error('Invalid payment_date (expected YYYY-MM-DD HH:MM:SS)', 422);
        }

        $pdo = Database::pdo($config);
        $bill = BillingModel::find($pdo, $billId);
        if ($bill === null) Response::error('Bill not found', 404);

        $paid = PaymentModel::sumPaid($pdo, $billId);
        $due = max(0.0, (float)$bill['total_amount'] - $paid);
        if ((float)$amount > $due + 0.0001) {
            Response::error('Payment exceeds due amount', 409, ['due_amount' => $due]);
        }

        PaymentModel::create($pdo, $billId, $mode, (float)$amount, $paymentDate);
        self::show($config, $billId);
    }

    private static function normalizeItems(array $items): array
    {
        $out = [];
        foreach ($items as $idx => $it) {
            if (!is_array($it)) Response::error('Invalid item format', 422, ['index' => $idx]);
            $desc = trim((string)($it['description'] ?? ''));
            $qty = $it['quantity'] ?? 1;
            $price = $it['price'] ?? 0;
            if ($desc === '' || strlen($desc) > 255) Response::error('Invalid item description', 422, ['index' => $idx]);
            if (!is_numeric($qty) || (int)$qty <= 0) Response::error('Invalid item quantity', 422, ['index' => $idx]);
            if (!is_numeric($price) || (float)$price < 0) Response::error('Invalid item price', 422, ['index' => $idx]);
            $total = (int)$qty * (float)$price;
            $out[] = [
                'description' => $desc,
                'quantity' => (int)$qty,
                'price' => (float)$price,
                'total' => (float)$total,
            ];
        }
        return $out;
    }

    private static function enforceAccessToBill(\PDO $pdo, array $bill): void
    {
        if (Auth::role() === 'admin' || Auth::role() === 'staff') return;
        if (Auth::role() === 'patient') {
            $me = PatientModel::findByUserId($pdo, (int)Auth::userId());
            if ($me === null) Response::error('Patient profile not found', 404);
            if ((int)$bill['patient_id'] !== (int)$me['patient_id']) Response::error('Forbidden', 403);
            return;
        }
        Response::error('Forbidden', 403);
    }
}

