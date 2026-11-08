<?php

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\PasswordReset;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function showLoginForm(): Response
    {
        if (Auth::checkAdmin()) {
            return $this->redirect('/admin/dashboard');
        }

        return $this->view('admin/auth/login', [
            'title' => 'Admin Sign In — CJ Travels',
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'identifier' => Session::getFlash('old_identifier', ''),
        ]);
    }

    public function login(Request $request): Response
    {
        $identifier = $request->input('identifier', '');
        $password = $request->input('password', '');
        $remember = (bool)$request->input('remember', false);
        $ip = $request->ip();

        $result = Auth::attemptAdmin($identifier, $password, $remember, $ip);

        if (!$result['success']) {
            Session::flash('error', $result['error']);
            Session::flash('old_identifier', $identifier);
            return $this->redirect('/admin/login');
        }

        $intended = Session::get('url.intended');
        Session::remove('url.intended');

        // Prevent open redirect by ensuring intended URL starts with /admin
        if (!$intended || !str_starts_with($intended, '/admin') || str_starts_with($intended, '/admin/login')) {
            $intended = '/admin/dashboard';
        }

        Session::flash('success', 'Welcome back, ' . htmlspecialchars($result['admin']->name, ENT_QUOTES, 'UTF-8') . '!');
        return $this->redirect($intended);
    }

    public function logout(): Response
    {
        Auth::logoutAdmin();
        Session::flash('success', 'You have been securely signed out of the administrator console.');
        return $this->redirect('/admin/login');
    }

    public function showForgotPasswordForm(): Response
    {
        if (Auth::checkAdmin()) {
            return $this->redirect('/admin/dashboard');
        }

        return $this->view('admin/auth/forgot-password', [
            'title' => 'Password Recovery — CJ Travels Admin',
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'devResetLink' => Session::getFlash('dev_reset_link'),
        ]);
    }

    public function sendResetLink(Request $request): Response
    {
        $email = trim($request->input('email', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid administrator email address.');
            return $this->redirect('/admin/forgot-password');
        }

        $token = PasswordReset::createToken($email);

        if ($token) {
            $resetUrl = url('/admin/reset-password?token=' . $token);

            // Log reset link
            $logDir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logEntry = sprintf(
                "[%s] PASSWORD RESET for %s: %s (IP: %s)\n",
                date('Y-m-d H:i:s'),
                $email,
                $resetUrl,
                $request->ip()
            );
            file_put_contents($logDir . DIRECTORY_SEPARATOR . 'mail.log', $logEntry, FILE_APPEND);

            // In local/demo environment where SMTP is unconfigured, provide the reset link in flash
            Session::flash('dev_reset_link', $resetUrl);
            Session::flash('success', 'Password reset instructions have been generated. Follow the link below to set your new password.');
        } else {
            // Generic message to avoid email enumeration
            Session::flash('success', 'If an administrator account exists for that email, recovery instructions have been prepared.');
        }

        return $this->redirect('/admin/forgot-password');
    }

    public function showResetPasswordForm(Request $request): Response
    {
        if (Auth::checkAdmin()) {
            return $this->redirect('/admin/dashboard');
        }

        $token = $request->input('token', '');
        $record = PasswordReset::validateToken($token);

        if (!$record) {
            Session::flash('error', 'This password reset link is invalid, has expired, or was already used. Please request a new one.');
            return $this->redirect('/admin/forgot-password');
        }

        return $this->view('admin/auth/reset-password', [
            'title' => 'Reset Password — CJ Travels Admin',
            'token' => $token,
            'email' => $record['email'],
            'error' => Session::getFlash('error'),
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        $token = $request->input('token', '');
        $password = $request->input('password', '');
        $passwordConfirmation = $request->input('password_confirmation', '');

        if ($password !== $passwordConfirmation) {
            Session::flash('error', 'Passwords do not match. Please verify and try again.');
            return $this->redirect('/admin/reset-password?token=' . urlencode($token));
        }

        $result = PasswordReset::resetPassword($token, $password);

        if (!$result['success']) {
            Session::flash('error', $result['error']);
            return $this->redirect('/admin/reset-password?token=' . urlencode($token));
        }

        Session::flash('success', 'Your password has been securely reset. You can now sign in with your new credentials.');
        return $this->redirect('/admin/login');
    }
}

