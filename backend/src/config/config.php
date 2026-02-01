<?php
declare(strict_types=1);

/**
 * Central configuration.
 * Prefer environment variables in production.
 */
return [
    'app' => [
        'name' => 'Clinic Management System',
        // In production: set to false and log errors instead.
        'debug' => (getenv('CMS_DEBUG') ?: '0') === '1',
        // If frontend is on a different origin, set an explicit allowlist.
        'cors_allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string)getenv('CMS_CORS_ORIGINS'))))),
    ],
    'db' => [
        'host' => getenv('CMS_DB_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('CMS_DB_PORT') ?: 3306),
        'name' => getenv('CMS_DB_NAME') ?: 'clinic_management',
        'user' => getenv('CMS_DB_USER') ?: 'root',
        'pass' => getenv('CMS_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => getenv('CMS_SESSION_NAME') ?: 'CMSSESSID',
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_secure' => (getenv('CMS_COOKIE_SECURE') ?: '0') === '1',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
];

