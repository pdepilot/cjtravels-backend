<?php

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Admin;

class SettingController extends AdminController
{
    public function index(): Response
    {
        $admin = Auth::admin();
        $mysql = config('database.connections.mysql', []);
        $version = '';
        try {
            $row = Database::getInstance()->selectOne('SELECT VERSION() AS version');
            $version = (string)($row['version'] ?? '');
        } catch (\Throwable $e) {
            $version = '';
        }

        return $this->adminView('settings', [
            'title' => 'Console Settings — CJ Travels Admin',
            'page' => 'settings',
            'admin' => $admin,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'promoCampaign' => \App\Services\RealtimeDataService::getActivePromoCampaign(),
            'contactPhone' => \App\Services\RealtimeDataService::contactPhone(),
            'environment' => [
                'engine' => $version !== '' ? $version : 'MySQL',
                'name' => (string)($mysql['database'] ?? ''),
                'host' => trim((string)($mysql['host'] ?? '') . ':' . (string)($mysql['port'] ?? ''), ':'),
                'server' => (string)($_SERVER['SERVER_SOFTWARE'] ?? 'PHP'),
            ],
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $admin = Auth::admin();
        if (!$admin) {
            return $this->redirect('/admin/login');
        }

        $name = trim((string)$request->input('name', ''));
        $email = strtolower(trim((string)$request->input('email', '')));
        $phone = trim((string)$request->input('phone', ''));
        $username = trim((string)$request->input('username', ''));
        $currentPassword = (string)$request->input('current_password', '');

        // 1. Password verification for sensitive account updates
        if (empty($currentPassword)) {
            Session::flash('error', 'Please enter your current administrator password to confirm your changes.');
            return $this->redirect('/admin/settings');
        }

        if (!$admin->verifyPassword($currentPassword)) {
            Session::flash('error', 'The current password you entered is incorrect. Changes were not saved.');
            return $this->redirect('/admin/settings');
        }

        // 2. Validate Name
        if (empty($name)) {
            Session::flash('error', 'Administrator name cannot be empty.');
            return $this->redirect('/admin/settings');
        }

        // 3. Validate Email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid administrator email address.');
            return $this->redirect('/admin/settings');
        }

        if (Admin::isEmailTakenByOther($email, (int)$admin->id)) {
            Session::flash('error', "The email address '{$email}' is already assigned to another administrator account.");
            return $this->redirect('/admin/settings');
        }

        // 4. Validate Phone
        if (!empty($phone)) {
            if (!preg_match('/^[0-9+\-\s()]{7,25}$/', $phone)) {
                Session::flash('error', 'Please enter a valid phone number (e.g., +234 812 345 6789).');
                return $this->redirect('/admin/settings');
            }

            if (Admin::isPhoneTakenByOther($phone, (int)$admin->id)) {
                Session::flash('error', "The phone number '{$phone}' is already registered to another administrator account.");
                return $this->redirect('/admin/settings');
            }
        }

        // 5. Validate Username (optional)
        if (!empty($username)) {
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
                Session::flash('error', 'Username must be between 3 and 30 characters and contain only letters, numbers, dots, dashes, or underscores.');
                return $this->redirect('/admin/settings');
            }

            if (Admin::isUsernameTakenByOther($username, (int)$admin->id)) {
                Session::flash('error', "The username '{$username}' is already in use by another administrator.");
                return $this->redirect('/admin/settings');
            }
        }

        // 6. Update Admin Record
        $admin->updateProfile([
            'name' => $name,
            'email' => $email,
            'phone' => !empty($phone) ? $phone : null,
            'username' => !empty($username) ? $username : null,
        ]);

        // Refresh cached Admin instance so topbar and current session reflect the updates immediately
        Auth::refreshAdmin();

        Session::flash('success', 'Administrator login details updated successfully! You can now use your updated email or phone number to sign in.');
        return $this->redirect('/admin/settings');
    }

    public function updatePassword(Request $request): Response
    {
        $admin = Auth::admin();
        if (!$admin) {
            return $this->redirect('/admin/login');
        }

        $currentPassword = (string)$request->input('current_password', '');
        $newPassword = (string)$request->input('new_password', '');
        $confirmPassword = (string)$request->input('new_password_confirmation', '');

        if (empty($currentPassword)) {
            Session::flash('error', 'Please enter your current administrator password.');
            return $this->redirect('/admin/settings');
        }

        if (!$admin->verifyPassword($currentPassword)) {
            Session::flash('error', 'The current password you entered is incorrect.');
            return $this->redirect('/admin/settings');
        }

        if (strlen($newPassword) < 8) {
            Session::flash('error', 'Your new password must be at least 8 characters long.');
            return $this->redirect('/admin/settings');
        }

        if ($newPassword !== $confirmPassword) {
            Session::flash('error', 'The new password and confirmation password do not match.');
            return $this->redirect('/admin/settings');
        }

        $admin->updatePassword($newPassword);
        Auth::refreshAdmin();

        Session::flash('success', 'Administrator password updated successfully!');
        return $this->redirect('/admin/settings');
    }

    public function updatePromoCampaign(Request $request): Response
    {
        $admin = Auth::admin();
        if (!$admin) {
            return $this->redirect('/admin/login');
        }

        $file = $_FILES['flier_image'] ?? null;
        $result = \App\Services\RealtimeDataService::updatePromoCampaign($request->all(), $file);

        if (!empty($result['success'])) {
            Session::flash('success', $result['message'] ?? 'Promotional flier and campaign settings saved successfully!');
        } else {
            Session::flash('error', $result['error'] ?? 'Failed to update promotional campaign.');
        }

        return $this->redirect('/admin/settings#promo-settings');
    }

    public function updateContactPhone(Request $request): Response
    {
        $admin = Auth::admin();
        if (!$admin) {
            return $this->redirect('/admin/login');
        }

        $result = \App\Services\RealtimeDataService::updateContactPhone((string)$request->input('contact_phone', ''));
        if (!empty($result['success'])) {
            Session::flash('success', $result['message'] ?? 'The public phone and WhatsApp number have been updated.');
        } else {
            Session::flash('error', $result['error'] ?? 'Could not update the public phone number.');
        }

        return $this->redirect('/admin/settings#public-phone');
    }
}
