<?php
declare(strict_types=1);

// Simple front controller to serve:
// - the frontend SPA (from /frontend)
// - shared static assets (from /assets)
// - and leave /api.php as the JSON API entrypoint

$uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($uri, PHP_URL_PATH);
if (!is_string($path) || $path === '') $path = '/';

// Normalize
if (strlen($path) > 1) $path = rtrim($path, '/');

// Do not handle API
if ($path === '/api.php' || str_starts_with($path, '/api.php/')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Use /api.php?route=/...\n";
    exit;
}

// Serve shared assets from project /assets
if (str_starts_with($path, '/assets/')) {
    $projectRoot = realpath(__DIR__ . '/../..');
    $assetsRoot = realpath($projectRoot . '/assets');
    $file = realpath($assetsRoot . substr($path, strlen('/assets')));
    if ($projectRoot === false || $assetsRoot === false || $file === false || !str_starts_with($file, $assetsRoot)) {
        http_response_code(404);
        exit;
    }
    if (!is_file($file)) {
        http_response_code(404);
        exit;
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
    ];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=3600');
    readfile($file);
    exit;
}

// Serve SPA
$projectRoot = realpath(__DIR__ . '/../..');
$frontendIndex = $projectRoot !== false ? ($projectRoot . '/frontend/index.html') : null;
if ($frontendIndex === null || !is_file($frontendIndex)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Frontend not found. Expected /frontend/index.html\n";
    exit;
}

header('Content-Type: text/html; charset=utf-8');
readfile($frontendIndex);
exit;

