<?php
declare(strict_types=1);

final class Response
{
    public static function json(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok(array $data = [], array $meta = []): never
    {
        self::json([
            'ok' => true,
            'data' => $data,
            'meta' => $meta,
        ]);
    }

    public static function error(string $message, int $statusCode = 400, array $details = []): never
    {
        self::json([
            'ok' => false,
            'error' => [
                'message' => $message,
                'details' => $details,
            ],
        ], $statusCode);
    }
}

