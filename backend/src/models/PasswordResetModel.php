<?php
declare(strict_types=1);

/**
 * Password Reset Model - Handles password reset token operations
 */
final class PasswordResetModel
{
    private const TOKEN_EXPIRY_HOURS = 1;

    /**
     * Generate a secure reset token for a user
     */
    public static function createToken(\PDO $pdo, int $userId): string
    {
        // Invalidate any existing tokens for this user
        self::invalidateTokens($pdo, $userId);

        // Generate cryptographically secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (self::TOKEN_EXPIRY_HOURS * 3600));

        $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    /**
     * Validate a reset token and return user_id if valid
     */
    public static function validateToken(\PDO $pdo, string $token): ?int
    {
        $sql = "SELECT user_id FROM password_reset_tokens 
                WHERE token = :token 
                AND expires_at > NOW() 
                AND used_at IS NULL 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? (int)$row['user_id'] : null;
    }

    /**
     * Mark a token as used
     */
    public static function markUsed(\PDO $pdo, string $token): void
    {
        $sql = "UPDATE password_reset_tokens SET used_at = NOW() WHERE token = :token";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
    }

    /**
     * Invalidate all tokens for a user
     */
    public static function invalidateTokens(\PDO $pdo, int $userId): void
    {
        $sql = "DELETE FROM password_reset_tokens WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Cleanup expired tokens (run periodically)
     */
    public static function cleanupExpired(\PDO $pdo): int
    {
        $sql = "DELETE FROM password_reset_tokens WHERE expires_at < NOW() OR used_at IS NOT NULL";
        return $pdo->exec($sql);
    }
}
