<?php
declare(strict_types=1);

final class MedicineController
{
    public static function index(array $config): void
    {
        Auth::requireRoles(['admin', 'doctor', 'staff']);

        $q = (string)($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $pdo = Database::pdo($config);
        $rows = MedicineModel::list($pdo, $q, $pageSize, $offset);
        $total = MedicineModel::count($pdo, $q);

        Response::ok(['items' => $rows], [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ]);
    }

    public static function show(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'doctor', 'staff']);
        $pdo = Database::pdo($config);
        $row = MedicineModel::find($pdo, $id);
        if ($row === null) {
            Response::error('Medicine not found', 404);
        }
        Response::ok(['medicine' => $row]);
    }

    public static function create(array $config): void
    {
        // Staff can manage medicines; doctors can read-only
        Auth::requireRoles(['admin', 'staff']);
        $body = Request::json();

        $data = self::validatePayload($body);
        $pdo = Database::pdo($config);
        $id = MedicineModel::create($pdo, $data);
        $row = MedicineModel::find($pdo, $id);
        Response::ok(['medicine' => $row], ['created' => true]);
    }

    public static function update(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $body = Request::json();
        $data = self::validatePayload($body);

        $pdo = Database::pdo($config);
        if (MedicineModel::find($pdo, $id) === null) {
            Response::error('Medicine not found', 404);
        }
        MedicineModel::update($pdo, $id, $data);
        $row = MedicineModel::find($pdo, $id);
        Response::ok(['medicine' => $row], ['updated' => true]);
    }

    public static function delete(array $config, int $id): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $pdo = Database::pdo($config);
        if (MedicineModel::find($pdo, $id) === null) {
            Response::error('Medicine not found', 404);
        }
        MedicineModel::delete($pdo, $id);
        Response::ok(['deleted' => true]);
    }

    private static function validatePayload(array $body): array
    {
        $name = trim((string)($body['medicine_name'] ?? ''));
        $company = $body['company'] ?? null;
        $company = is_string($company) ? trim($company) : null;
        if ($company === '') $company = null;

        $price = $body['price'] ?? 0;
        $stock = $body['stock'] ?? 0;
        $expiry = $body['expiry_date'] ?? null; // YYYY-MM-DD or null

        if ($name === '' || strlen($name) > 190) {
            Response::error('Invalid medicine_name', 422);
        }
        if (!is_numeric($price) || (float)$price < 0) {
            Response::error('Invalid price', 422);
        }
        if (!is_numeric($stock) || (int)$stock < 0) {
            Response::error('Invalid stock', 422);
        }
        if ($expiry !== null) {
            if (!is_string($expiry) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $expiry)) {
                Response::error('Invalid expiry_date', 422);
            }
        }

        return [
            'medicine_name' => $name,
            'company' => $company,
            'price' => (float)$price,
            'stock' => (int)$stock,
            'expiry_date' => $expiry,
        ];
    }
}

