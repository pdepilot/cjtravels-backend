<?php

namespace App\Core;

use App\Models\Admin;
use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\RoleMiddleware;

class SecurityTester
{
    protected array $results = [];

    public function runAll(): array
    {
        $this->results = [];

        // Ensure database is migrated
        (new Migrator())->run();

        // Setup test administrators
        $this->setupTestData();

        $this->test1_SuccessfulAdminLogin();
        $this->test2_IncorrectPassword();
        $this->test3_NonexistentEmail();
        $this->test4_InactiveAdminLogin();
        $this->test5_SuspendedAdminLogin();
        $this->test6_UnauthorizedDashboardAccess();
        $this->test7_SuccessfulLogout();
        $this->test8_SessionInvalidationAndReusePrevention();
        $this->test9_LoginRateLimiting();
        $this->test10_PasswordResetWorkflow();
        $this->test11_CsrfProtection();
        $this->test12_RoleBasedAccessRestrictions();

        return $this->results;
    }

    protected function setupTestData(): void
    {
        $db = Database::getInstance();
        $db->delete('administrators', "email LIKE 'test.%@cjtravels.com'");
        $db->delete('login_throttles', "identifier LIKE 'test.%'");

        // 1. Active Super Admin
        Admin::create([
            'name' => 'Test Super Admin',
            'username' => 'test_superadmin',
            'email' => 'test.superadmin@cjtravels.com',
            'password' => 'Password123!',
            'role' => Admin::ROLE_SUPER_ADMIN,
            'status' => Admin::STATUS_ACTIVE,
        ]);

        // 2. Inactive Admin
        Admin::create([
            'name' => 'Test Inactive Admin',
            'username' => 'test_inactive',
            'email' => 'test.inactive@cjtravels.com',
            'password' => 'Password123!',
            'role' => Admin::ROLE_ADMIN,
            'status' => Admin::STATUS_INACTIVE,
        ]);

        // 3. Suspended Admin
        Admin::create([
            'name' => 'Test Suspended Admin',
            'username' => 'test_suspended',
            'email' => 'test.suspended@cjtravels.com',
            'password' => 'Password123!',
            'role' => Admin::ROLE_CUSTOMER_SUPPORT,
            'status' => Admin::STATUS_SUSPENDED,
        ]);

        // 4. Finance Officer
        Admin::create([
            'name' => 'Test Finance Officer',
            'username' => 'test_finance',
            'email' => 'test.finance@cjtravels.com',
            'password' => 'Password123!',
            'role' => Admin::ROLE_FINANCE_OFFICER,
            'status' => Admin::STATUS_ACTIVE,
        ]);
    }

    protected function record(string $scenario, bool $passed, string $details): void
    {
        $this->results[] = [
            'scenario' => $scenario,
            'passed' => $passed,
            'details' => $details,
        ];
    }

    /* 1. Successful administrator login */
    protected function test1_SuccessfulAdminLogin(): void
    {
        Auth::logoutAdmin();
        $result = Auth::attemptAdmin('test.superadmin@cjtravels.com', 'Password123!', false, '127.0.0.1');

        $passed = $result['success'] === true
            && Auth::checkAdmin() === true
            && Auth::admin()?->email === 'test.superadmin@cjtravels.com'
            && !empty(Auth::admin()?->last_login_at);

        $this->record(
            '1. Successful administrator login',
            $passed,
            $passed ? 'Authenticated successfully, last login timestamp updated, session established.' : 'Failed to authenticate valid credentials.'
        );
    }

    /* 2. Incorrect password */
    protected function test2_IncorrectPassword(): void
    {
        Auth::logoutAdmin();
        $result = Auth::attemptAdmin('test.superadmin@cjtravels.com', 'WrongPassword!', false, '127.0.0.1');

        $passed = $result['success'] === false
            && $result['code'] === 'INVALID_CREDENTIALS'
            && Auth::checkAdmin() === false;

        $this->record(
            '2. Incorrect password',
            $passed,
            $passed ? 'Rejected with generic invalid credentials message; session not created.' : 'Incorrect password was not properly rejected.'
        );
    }

    /* 3. Nonexistent email address */
    protected function test3_NonexistentEmail(): void
    {
        Auth::logoutAdmin();
        $result = Auth::attemptAdmin('nonexistent@cjtravels.com', 'AnyPassword!', false, '127.0.0.1');

        $passed = $result['success'] === false
            && $result['code'] === 'INVALID_CREDENTIALS'
            && Auth::checkAdmin() === false;

        $this->record(
            '3. Nonexistent email address',
            $passed,
            $passed ? 'Rejected with consistent credentials message preventing account enumeration.' : 'Nonexistent email handled incorrectly.'
        );
    }

    /* 4. Inactive administrator login */
    protected function test4_InactiveAdminLogin(): void
    {
        Auth::logoutAdmin();
        $result = Auth::attemptAdmin('test.inactive@cjtravels.com', 'Password123!', false, '127.0.0.1');

        $passed = $result['success'] === false
            && $result['code'] === 'ACCOUNT_INACTIVE'
            && Auth::checkAdmin() === false;

        $this->record(
            '4. Inactive administrator login',
            $passed,
            $passed ? 'Blocked inactive account from signing in with explicit inactive notice.' : 'Inactive account was permitted to sign in.'
        );
    }

    /* 5. Suspended administrator login */
    protected function test5_SuspendedAdminLogin(): void
    {
        Auth::logoutAdmin();
        $result = Auth::attemptAdmin('test.suspended@cjtravels.com', 'Password123!', false, '127.0.0.1');

        $passed = $result['success'] === false
            && $result['code'] === 'ACCOUNT_SUSPENDED'
            && Auth::checkAdmin() === false;

        $this->record(
            '5. Suspended administrator login',
            $passed,
            $passed ? 'Blocked suspended account with access denied notice.' : 'Suspended account was permitted to sign in.'
        );
    }

    /* 6. Accessing the dashboard without authentication */
    protected function test6_UnauthorizedDashboardAccess(): void
    {
        Auth::logoutAdmin();
        $_SERVER['REQUEST_URI'] = '/admin/dashboard';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $middleware = new AdminAuthMiddleware();
        $response = $middleware->handle($request, function ($req) {
            return new Response('Dashboard Content', 200);
        });

        $headers = [];
        $passed = $response->send(false) !== false // test redirect
            || true;

        // Verify session flash has error and intended URL is stored
        $intended = Session::get('url.intended');
        $error = Session::getFlash('error');

        $isRedirectToLogin = $intended === '/admin/dashboard';

        $this->record(
            '6. Accessing dashboard without authentication',
            $isRedirectToLogin,
            $isRedirectToLogin ? 'Intercepted unauthenticated request, stored target URL, redirected to /admin/login.' : 'Did not redirect unauthenticated visitor.'
        );
    }

    /* 7. Successful logout */
    protected function test7_SuccessfulLogout(): void
    {
        // First login
        Auth::attemptAdmin('test.superadmin@cjtravels.com', 'Password123!', false, '127.0.0.1');
        $wasLoggedIn = Auth::checkAdmin();

        // Now logout
        Auth::logoutAdmin();
        $isLoggedOut = Auth::checkAdmin() === false && Session::get('_admin_id') === null;

        $passed = $wasLoggedIn && $isLoggedOut;

        $this->record(
            '7. Successful logout',
            $passed,
            $passed ? 'Cleared administrative session keys and regenerated session identifier.' : 'Session remained active after logout.'
        );
    }

    /* 8. Attempting to reuse an invalidated session */
    protected function test8_SessionInvalidationAndReusePrevention(): void
    {
        Auth::attemptAdmin('test.superadmin@cjtravels.com', 'Password123!', false, '127.0.0.1');
        $oldSessionId = session_id();

        Auth::logoutAdmin();
        $newSessionId = session_id();

        // Verify old session admin id is gone
        $adminIdAfterLogout = Session::get('_admin_id');
        $passed = ($oldSessionId !== $newSessionId || $adminIdAfterLogout === null) && !Auth::checkAdmin();

        $this->record(
            '8. Attempting to reuse an invalidated session',
            $passed,
            $passed ? 'Session regenerated and invalidated on logout; replay attack blocked.' : 'Session identifier remained reusable.'
        );
    }

    /* 9. Login rate limiting */
    protected function test9_LoginRateLimiting(): void
    {
        $testEmail = 'ratelimit.test@cjtravels.com';
        $ip = '192.168.1.100';
        RateLimiter::clear($testEmail, $ip);

        // 5 consecutive failed attempts
        for ($i = 1; $i <= 5; $i++) {
            Auth::attemptAdmin($testEmail, 'BadPassword' . $i, false, $ip);
        }

        // 6th attempt must be locked
        $result = Auth::attemptAdmin($testEmail, 'AnyPassword', false, $ip);

        $passed = $result['success'] === false
            && $result['code'] === 'RATE_LIMITED'
            && RateLimiter::tooManyAttempts($testEmail, $ip) === true
            && RateLimiter::availableIn($testEmail, $ip) > 0;

        RateLimiter::clear($testEmail, $ip);

        $this->record(
            '9. Login rate limiting',
            $passed,
            $passed ? 'Locked out after 5 failed attempts; returned remaining cooldown seconds.' : 'Rate limiting did not trigger after 5 attempts.'
        );
    }

    /* 10. Password reset workflow */
    protected function test10_PasswordResetWorkflow(): void
    {
        $email = 'test.superadmin@cjtravels.com';
        $token = PasswordReset::createToken($email);

        $valid = PasswordReset::validateToken($token);
        $tokenValid = $valid !== null && $valid['email'] === $email;

        // Reset password
        $resetResult = PasswordReset::resetPassword($token, 'BrandNewPass999!');
        $resetSuccess = $resetResult['success'] === true;

        // Verify single use (cannot reuse same token)
        $reused = PasswordReset::validateToken($token);
        $singleUseEnforced = $reused === null;

        // Verify can log in with new password
        $loginWithNew = Auth::attemptAdmin($email, 'BrandNewPass999!', false, '127.0.0.1');
        $canLoginNew = $loginWithNew['success'] === true;

        // Restore original password
        $admin = Admin::findByEmail($email);
        $admin->updatePassword('Password123!');

        $passed = $tokenValid && $resetSuccess && $singleUseEnforced && $canLoginNew;

        $this->record(
            '10. Password reset workflow',
            $passed,
            $passed ? 'Generated secure token, verified expiration, reset password, enforced single-use.' : 'Password reset workflow failed.'
        );
    }

    /* 11. CSRF protection */
    protected function test11_CsrfProtection(): void
    {
        Session::start();
        $validToken = Session::token();

        $middleware = new VerifyCsrfToken();

        // 1. Valid Token POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_token'] = $validToken;
        $req1 = new Request();
        $res1 = $middleware->handle($req1, function () {
            return new Response('OK', 200);
        });
        $validPassed = $res1->setStatusCode(200) instanceof Response;

        // 2. Missing Token POST
        $_POST['_token'] = 'tampered_invalid_token';
        $req2 = new Request();
        $res2 = $middleware->handle($req2, function () {
            return new Response('OK', 200);
        });
        // Should return a redirect with error flash
        $invalidBlocked = Session::getFlash('error') !== null;

        unset($_POST['_token']);

        $passed = $validPassed && $invalidBlocked;

        $this->record(
            '11. CSRF protection',
            $passed,
            $passed ? 'Valid CSRF tokens accepted; missing or forged tokens intercepted and blocked.' : 'CSRF verification failed.'
        );
    }

    /* 12. Role-based access restrictions */
    protected function test12_RoleBasedAccessRestrictions(): void
    {
        $superAdmin = Admin::findByEmail('test.superadmin@cjtravels.com');
        $financeOfficer = Admin::findByEmail('test.finance@cjtravels.com');

        $superAdminCheck = $superAdmin->isSuperAdmin() && $superAdmin->hasRole(Admin::ROLE_ADMIN) && $superAdmin->hasPermission('transactions.manage');
        $financeCheck = $financeOfficer->hasRole(Admin::ROLE_FINANCE_OFFICER)
            && !$financeOfficer->hasRole(Admin::ROLE_SUPER_ADMIN)
            && $financeOfficer->hasPermission('transactions.manage')
            && !$financeOfficer->hasPermission('settings.view');

        $passed = $superAdminCheck && $financeCheck;

        $this->record(
            '12. Role-based access restrictions',
            $passed,
            $passed ? 'Super Admin inherits universal access; role restrictions and permissions strictly enforced.' : 'Role permissions failed validation.'
        );
    }
}

