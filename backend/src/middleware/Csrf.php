<?php
declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        $t = $_SESSION['csrf_token'] ?? null;
        if (is_string($t) && $t !== '') {
            return $t;
        }
        $t = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $t;
        return $t;
    }

    public static function requireForStateChangingRequests(): void
    {
        $method = Request::method();
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        // Allow login without existing CSRF token (first call).
        $route = Request::route();
        // Public routes that don't require CSRF (they have their own security)
        $exemptRoutes = [
            '/auth/login',
            '/auth/request-password-reset',
            '/auth/reset-password',
        ];
        if (in_array($route, $exemptRoutes, true)) {
            return;
        }

        $expected = self::token();
        $provided = self::readProvidedToken();
        if ($provided === null || !hash_equals($expected, $provided)) {
            Response::error('Invalid CSRF token', 419);
        }
    }

    private static function readProvidedToken(): ?string
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (is_string($header) && $header !== '') {
            return $header;
        }
        $body = Request::json();
        $t = $body['_csrf'] ?? null;
        return is_string($t) && $t !== '' ? $t : null;
    }
}

