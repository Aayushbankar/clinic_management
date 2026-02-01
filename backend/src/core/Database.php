<?php
declare(strict_types=1);

final class Database
{
    private static ?\PDO $pdo = null;

    public static function pdo(array $config): \PDO
    {
        if (self::$pdo instanceof \PDO) {
            return self::$pdo;
        }

        $db = $config['db'] ?? [];
        $host = (string)($db['host'] ?? '127.0.0.1');
        $port = (int)($db['port'] ?? 3306);
        $name = (string)($db['name'] ?? '');
        $user = (string)($db['user'] ?? '');
        $pass = (string)($db['pass'] ?? '');
        $charset = (string)($db['charset'] ?? 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        $pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        self::$pdo = $pdo;
        return $pdo;
    }
}

