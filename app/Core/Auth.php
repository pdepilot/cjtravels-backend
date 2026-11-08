<?php

namespace App\Core;

use App\Models\Admin;

class Auth
{
    protected static ?Admin $cachedAdmin = null;
    public const SESSION_TIMEOUT = 7200; // 2 hours

    public static function admin(): ?Admin
    {
        if (self::$cachedAdmin !== null) {
            return self::$cachedAdmin;
        }

        Session::start();
        $adminId = Session::get('_admin_id');
        if (!$adminId) {
            return null;
        }

        // Check session inactivity timeout
        $lastActivity = Session::get('_admin_last_activity');
        if ($lastActivity && (time() - $lastActivity) > self::SESSION_TIMEOUT) {
            self::logoutAdmin();
            Session::flash('error', 'Your session has expired due to inactivity. Please sign in again.');
            return null;
        }

        // Update last activity timestamp
        Session::set('_admin_last_activity', time());

        $admin = Admin::findById((int)$adminId);
        if (!$admin || !$admin->isActive()) {
            // Account was deleted, deactivated or suspended since login
            self::logoutAdmin();
            return null;
        }

        self::$cachedAdmin = $admin;
        return $admin;
    }

    public static function refreshAdmin(): ?Admin
    {
        self::$cachedAdmin = null;
        return self::admin();
    }

    public static function checkAdmin(): bool
    {
        return self::admin() !== null;
    }

    public static function id(): ?int
    {
        return self::admin()?->id;
    }

    public static function attemptAdmin(
        string $identifier,
        string $password,
        bool $remember = false,
        string $ip = '127.0.0.1'
    ): array {
        $cleanId = trim($identifier);

        if (empty($cleanId) || empty($password)) {
            return [
                'success' => false,
                'error' => 'Please provide both your administrator email/username and password.',
                'code' => 'MISSING_FIELDS',
            ];
        }

        // 1. Rate limiting check
        if (RateLimiter::tooManyAttempts($cleanId, $ip)) {
            $seconds = RateLimiter::availableIn($cleanId, $ip);
            return [
                'success' => false,
                'error' => "Too many failed login attempts. Please wait {$seconds} seconds before trying again.",
                'code' => 'RATE_LIMITED',
                'seconds' => $seconds,
            ];
        }

        // 2. Lookup account
        $admin = Admin::findByUsernameOrEmail($cleanId);
        if (!$admin) {
            RateLimiter::hit($cleanId, $ip);
            return [
                'success' => false,
                'error' => 'These credentials do not match our administrator records.',
                'code' => 'INVALID_CREDENTIALS',
            ];
        }

        // 3. Verify password
        if (!password_verify($password, $admin->password)) {
            RateLimiter::hit($cleanId, $ip);
            return [
                'success' => false,
                'error' => 'These credentials do not match our administrator records.',
                'code' => 'INVALID_CREDENTIALS',
            ];
        }

        // 4. Status checks
        if ($admin->isInactive()) {
            RateLimiter::hit($cleanId, $ip);
            return [
                'success' => false,
                'error' => 'Your administrator account is inactive. Please contact the Super Administrator.',
                'code' => 'ACCOUNT_INACTIVE',
            ];
        }

        if ($admin->isSuspended()) {
            RateLimiter::hit($cleanId, $ip);
            return [
                'success' => false,
                'error' => 'Your administrator account has been suspended. Access denied.',
                'code' => 'ACCOUNT_SUSPENDED',
            ];
        }

        // 5. Successful login
        RateLimiter::clear($cleanId, $ip);

        // Regenerate session ID to prevent session fixation attacks
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        Session::set('_admin_id', $admin->id);
        Session::set('_admin_role', $admin->role);
        Session::set('_admin_logged_at', time());
        Session::set('_admin_last_activity', time());
        Session::set('_admin_ip', $ip);

        // Handle remember token if requested
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $admin->remember_token = hash('sha256', $token);
            // In addition, could set a cookie if desired
        }

        $admin->updateLastLogin($ip);
        self::$cachedAdmin = $admin;

        return [
            'success' => true,
            'admin' => $admin,
        ];
    }

    public static function logoutAdmin(): void
    {
        Session::start();
        Session::remove('_admin_id');
        Session::remove('_admin_role');
        Session::remove('_admin_logged_at');
        Session::remove('_admin_last_activity');
        Session::remove('_admin_ip');

        self::$cachedAdmin = null;

        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
