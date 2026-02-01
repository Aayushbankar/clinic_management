<?php
declare(strict_types=1);

/**
 * Rate Limiter - Prevents brute force login attacks
 */
final class RateLimiter
{
    private \PDO $pdo;
    private int $maxAttempts;
    private int $windowSeconds;
    private int $lockoutSeconds;

    public function __construct(\PDO $pdo, int $maxAttempts = 5, int $windowSeconds = 300, int $lockoutSeconds = 900)
    {
        $this->pdo = $pdo;
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->lockoutSeconds = $lockoutSeconds;
    }

    public function getClientIp(): string
    {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                return trim($ip);
            }
        }
        return '127.0.0.1';
    }

    public function isBlocked(string $ip, ?string $email = null): bool
    {
        // Clean up old entries first
        $this->cleanup();

        $sql = "SELECT locked_until FROM login_rate_limits 
                WHERE ip_address = :ip AND locked_until > NOW() 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ip' => $ip]);
        
        if ($stmt->fetch()) {
            return true;
        }

        if ($email !== null) {
            $sql = "SELECT locked_until FROM login_rate_limits 
                    WHERE login_email = :email AND locked_until > NOW() 
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            
            if ($stmt->fetch()) {
                return true;
            }
        }

        return false;
    }

    public function recordAttempt(string $ip, ?string $email = null): void
    {
        $windowStart = date('Y-m-d H:i:s', time() - $this->windowSeconds);
        
        // Count recent attempts
        $sql = "SELECT COUNT(*) as cnt FROM login_rate_limits 
                WHERE ip_address = :ip AND first_attempt_at > :window";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ip' => $ip, 'window' => $windowStart]);
        $count = (int)$stmt->fetchColumn();

        // Insert new attempt
        $sql = "INSERT INTO login_rate_limits (ip_address, login_email, attempt_count, first_attempt_at) 
                VALUES (:ip, :email, 1, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ip' => $ip, 'email' => $email]);

        // If exceeded max attempts, lock
        if ($count + 1 >= $this->maxAttempts) {
            $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutSeconds);
            $sql = "UPDATE login_rate_limits 
                    SET locked_until = :lock 
                    WHERE ip_address = :ip AND first_attempt_at > :window";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['lock' => $lockUntil, 'ip' => $ip, 'window' => $windowStart]);
        }
    }

    public function clearAttempts(string $ip, ?string $email = null): void
    {
        $sql = "DELETE FROM login_rate_limits WHERE ip_address = :ip";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ip' => $ip]);

        if ($email !== null) {
            $sql = "DELETE FROM login_rate_limits WHERE login_email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
        }
    }

    private function cleanup(): void
    {
        // Remove entries older than 24 hours
        $sql = "DELETE FROM login_rate_limits WHERE first_attempt_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $this->pdo->exec($sql);
    }

    public function getRemainingAttempts(string $ip): int
    {
        $windowStart = date('Y-m-d H:i:s', time() - $this->windowSeconds);
        $sql = "SELECT COUNT(*) FROM login_rate_limits 
                WHERE ip_address = :ip AND first_attempt_at > :window";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ip' => $ip, 'window' => $windowStart]);
        $count = (int)$stmt->fetchColumn();
        return max(0, $this->maxAttempts - $count);
    }

    public function getLockoutRemainingSeconds(string $ip): int
    {
        $sql = "SELECT locked_until FROM login_rate_limits 
                WHERE ip_address = :ip AND locked_until > NOW() 
                ORDER BY locked_until DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($row && !empty($row['locked_until'])) {
            return max(0, strtotime($row['locked_until']) - time());
        }
        return 0;
    }
}
