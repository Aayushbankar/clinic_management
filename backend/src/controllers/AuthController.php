<?php
declare(strict_types=1);

final class AuthController
{
    public static function csrf(): void
    {
        Response::ok(['csrf_token' => Csrf::token()]);
    }

    public static function me(array $config): void
    {
        Auth::requireLogin();
        $pdo = Database::pdo($config);
        $user = UserModel::findById($pdo, (int)Auth::userId());
        if ($user === null) {
            Auth::logout();
            Response::error('Unauthorized', 401);
        }
        Response::ok(['user' => $user]);
    }

    public static function login(array $config): void
    {
        $body = Request::json();
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email', 422);
        }
        if ($password === '' || strlen($password) < 6) {
            Response::error('Invalid password', 422);
        }

        $pdo = Database::pdo($config);
        
        // Rate limiting check
        $rateLimiter = new RateLimiter($pdo);
        $ip = $rateLimiter->getClientIp();
        
        if ($rateLimiter->isBlocked($ip, $email)) {
            $remaining = $rateLimiter->getLockoutRemainingSeconds($ip);
            $minutes = ceil($remaining / 60);
            Response::error("Too many login attempts. Please try again in {$minutes} minute(s).", 429);
        }
        
        $user = UserModel::findByEmail($pdo, $email);
        if ($user === null) {
            $rateLimiter->recordAttempt($ip, $email);
            Response::error('Invalid credentials', 401);
        }
        if (($user['status'] ?? '') !== 'active') {
            Response::error('Account inactive', 403);
        }

        $hash = (string)($user['password'] ?? '');
        if (!password_verify($password, $hash)) {
            $rateLimiter->recordAttempt($ip, $email);
            Response::error('Invalid credentials', 401);
        }

        // Successful login - clear rate limit entries
        $rateLimiter->clearAttempts($ip, $email);

        Auth::login((int)$user['user_id'], (string)$user['role']);
        // Issue CSRF token for subsequent state-changing requests.
        $csrf = Csrf::token();

        // Never return password hash to client.
        unset($user['password']);
        Response::ok(['user' => $user, 'csrf_token' => $csrf]);
    }

    public static function logout(): void
    {
        Auth::requireLogin();
        Auth::logout();
        Response::ok(['logged_out' => true]);
    }

    /**
     * Request password reset - generates token (in production, send via email)
     */
    public static function requestPasswordReset(array $config): void
    {
        $body = Request::json();
        $email = strtolower(trim((string)($body['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email', 422);
        }

        $pdo = Database::pdo($config);
        $user = UserModel::findByEmail($pdo, $email);

        // Always return success to prevent email enumeration
        if ($user === null) {
            Response::ok(['message' => 'If this email exists, a reset link has been sent.']);
            return;
        }

        $token = PasswordResetModel::createToken($pdo, (int)$user['user_id']);

        // In production, send email with reset link
        // For demo, return token (remove in production!)
        $resetLink = "http://localhost:8080/#reset-password?token={$token}";

        Response::ok([
            'message' => 'If this email exists, a reset link has been sent.',
            'debug_token' => $token, // REMOVE IN PRODUCTION
            'debug_link' => $resetLink, // REMOVE IN PRODUCTION
        ]);
    }

    /**
     * Reset password using token
     */
    public static function resetPassword(array $config): void
    {
        $body = Request::json();
        $token = trim((string)($body['token'] ?? ''));
        $newPassword = (string)($body['password'] ?? '');

        if ($token === '' || strlen($token) !== 64) {
            Response::error('Invalid or expired reset token', 400);
        }
        if ($newPassword === '' || strlen($newPassword) < 8) {
            Response::error('Password must be at least 8 characters', 422);
        }

        // Validate password strength
        if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            Response::error('Password must contain at least one uppercase letter and one number', 422);
        }

        $pdo = Database::pdo($config);
        $userId = PasswordResetModel::validateToken($pdo, $token);

        if ($userId === null) {
            Response::error('Invalid or expired reset token', 400);
        }

        // Update password
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['password' => $hash, 'id' => $userId]);

        // Mark token as used
        PasswordResetModel::markUsed($pdo, $token);

        Response::ok(['message' => 'Password updated successfully. Please log in with your new password.']);
    }
    /**
 * Change password for logged-in user
 */
public static function changePassword(array $config): void
{
    Auth::requireLogin();
    $body = Request::json();
    $newPassword = (string)($body['new_password'] ?? '');

    if ($newPassword === '' || strlen($newPassword) < 6) {
        Response::error('Password must be at least 6 characters', 422);
    }

    $pdo = Database::pdo($config);
    $userId = Auth::userId();

    // Update to new password
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['password' => $hash, 'id' => $userId]);

    Response::ok(['message' => 'Password changed successfully']);
}
}
