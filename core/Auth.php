<?php
declare(strict_types=1);

/**
 * Admin Authentication
 * Session-based authentication for the admin panel.
 * Uses PHP password_hash() / password_verify() — no external libraries.
 */
class Auth
{
    private const SESSION_KEY = 'devlync_admin_logged_in';
    private const SESSION_TIME_KEY = 'devlync_admin_login_time';
    private const SESSION_TIMEOUT = 7200; // 2 hours

    /**
     * Attempts to log in with the provided password.
     * Compares against the hash stored in settings table.
     *
     * @param string $password Plain-text password attempt
     * @return bool True on success, false on failure
     */
    public static function login(string $password): bool
    {
        if (!session_id()) {
            session_start();
        }

        // Check DB setting first, then env var fallback
        $hash = Setting::get('admin_password') ?: getenv('ADMIN_PASSWORD_HASH');
        $envPlain = getenv('ADMIN_PASSWORD');

        $verified = false;
        if ($hash && str_starts_with($hash, '$2')) {
            // bcrypt hash stored in settings
            $verified = password_verify($password, $hash);
        } elseif ($envPlain) {
            // Plain-text env var (dev/staging only)
            $verified = hash_equals($envPlain, $password);
        }

        if (!$verified) {
            return false;
        }

        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION[self::SESSION_TIME_KEY] = time();
        session_regenerate_id(true);
        return true;
    }

    /**
     * Destroys the admin session and logs out.
     *
     * @return void
     */
    public static function logout(): void
    {
        if (!session_id()) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Checks if an admin session is currently active and not expired.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        if (!session_id()) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        $loginTime = $_SESSION[self::SESSION_TIME_KEY] ?? 0;
        if ((time() - $loginTime) > self::SESSION_TIMEOUT) {
            self::logout();
            return false;
        }

        // Refresh session timestamp
        $_SESSION[self::SESSION_TIME_KEY] = time();
        return true;
    }

    /**
     * Requires admin to be logged in. Redirects to login page if not.
     *
     * @return void
     */
    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . url('/admin/login'));
            exit;
        }
    }

    /**
     * Hashes a plain-text password for storage.
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
