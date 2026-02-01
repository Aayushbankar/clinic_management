<?php
declare(strict_types=1);

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function route(): string
    {
        // Primary: /api.php?route=/auth/login
        $route = (string)($_GET['route'] ?? '');
        if ($route !== '') {
            return self::normalizePath($route);
        }

        // Fallback: infer from request URI path after api.php
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            return '/';
        }
        return self::normalizePath($path);
    }

    public static function json(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function form(): array
    {
        return is_array($_POST ?? null) ? $_POST : [];
    }

    public static function query(): array
    {
        return is_array($_GET ?? null) ? $_GET : [];
    }

    private static function normalizePath(string $p): string
    {
        $p = trim($p);
        if ($p === '') return '/';
        if ($p[0] !== '/') $p = '/' . $p;
        // Strip query portion if someone passed it into route.
        $p = explode('?', $p, 2)[0];
        // Remove trailing slash except root.
        if (strlen($p) > 1) $p = rtrim($p, '/');
        return $p;
    }
}

