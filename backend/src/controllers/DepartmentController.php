<?php
declare(strict_types=1);

final class DepartmentController
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
        $rows = DepartmentModel::list($pdo, $q, $pageSize, $offset);
        $total = DepartmentModel::count($pdo, $q);

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
        $row = DepartmentModel::find($pdo, $id);
        if ($row === null) {
            Response::error('Department not found', 404);
        }
        Response::ok(['department' => $row]);
    }

    public static function create(array $config): void
    {
        Auth::requireRoles(['admin']);
        $body = Request::json();

        $name = trim((string)($body['department_name'] ?? ''));
        $description = $body['description'] ?? null;
        $description = is_string($description) ? trim($description) : null;
        if ($description === '') $description = null;

        if ($name === '' || strlen($name) > 120) {
            Response::error('Invalid department_name', 422);
        }

        $pdo = Database::pdo($config);
        try {
            $id = DepartmentModel::create($pdo, $name, $description);
        } catch (\PDOException $e) {
            // Unique name constraint
            if ((string)$e->getCode() === '23000') {
                Response::error('Department already exists', 409);
            }
            throw $e;
        }
        $row = DepartmentModel::find($pdo, $id);
        Response::ok(['department' => $row], ['created' => true]);
    }

    public static function update(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $body = Request::json();

        $name = trim((string)($body['department_name'] ?? ''));
        $description = $body['description'] ?? null;
        $description = is_string($description) ? trim($description) : null;
        if ($description === '') $description = null;

        if ($name === '' || strlen($name) > 120) {
            Response::error('Invalid department_name', 422);
        }

        $pdo = Database::pdo($config);
        if (DepartmentModel::find($pdo, $id) === null) {
            Response::error('Department not found', 404);
        }
        try {
            DepartmentModel::update($pdo, $id, $name, $description);
        } catch (\PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                Response::error('Department name already exists', 409);
            }
            throw $e;
        }

        $row = DepartmentModel::find($pdo, $id);
        Response::ok(['department' => $row], ['updated' => true]);
    }

    public static function delete(array $config, int $id): void
    {
        Auth::requireRoles(['admin']);
        $pdo = Database::pdo($config);
        if (DepartmentModel::find($pdo, $id) === null) {
            Response::error('Department not found', 404);
        }
        try {
            DepartmentModel::delete($pdo, $id);
        } catch (\PDOException $e) {
            // Might be referenced by doctors (FK)
            if ((string)$e->getCode() === '23000') {
                Response::error('Department is in use and cannot be deleted', 409);
            }
            throw $e;
        }
        Response::ok(['deleted' => true]);
    }
}

