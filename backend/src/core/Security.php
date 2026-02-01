<?php
declare(strict_types=1);

final class Security
{
    public static function setDefaultHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        // A conservative baseline CSP for the API. (Frontend will have its own CSP if served separately.)
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'");
    }

    public static function maybeCors(array $config): void
    {
        $origins = $config['app']['cors_allowed_origins'] ?? [];
        if (!is_array($origins) || count($origins) === 0) {
            return;
        }

        $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin !== '' && in_array($origin, $origins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        }

        if (Request::method() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

