<?php

namespace App\Core;

use App\Models\Admin;

class PasswordReset
{
    public const TOKEN_EXPIRY_SECONDS = 3600; // 1 hour
    protected static string $table = 'admin_password_resets';

    public static function createToken(string $email): ?string
    {
        $admin = Admin::findByEmail($email);
        if (!$admin) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRY_SECONDS);

        $db = Database::getInstance();

        // Invalidate any existing unused tokens for this email
        $db->update(
            self::$table,
            ['used_at' => $now],
            'email = :email AND used_at IS NULL',
            [':email' => $admin->email]
        );

        $db->insert(self::$table, [
            'email' => $admin->email,
            'token' => $token,
            'expires_at' => $expiresAt,
            'used_at' => null,
            'created_at' => $now,
        ]);

        return $token;
    }

    public static function validateToken(string $token): ?array
    {
        $clean = trim($token);
        if (empty($clean)) {
            return null;
        }

        $db = Database::getInstance();
        $row = $db->selectOne(
            "SELECT * FROM " . self::$table . " WHERE token = :token AND used_at IS NULL LIMIT 1",
            [':token' => $clean]
        );

        if (!$row) {
            return null;
        }

        if (strtotime($row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }

    public static function resetPassword(string $token, string $newPassword): array
    {
        $record = self::validateToken($token);
        if (!$record) {
            return [
                'success' => false,
                'error' => 'This password reset link is invalid or has expired. Please request a new one.',
            ];
        }

        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'error' => 'Your new password must be at least 8 characters long.',
            ];
        }

        $admin = Admin::findByEmail($record['email']);
        if (!$admin) {
            return [
                'success' => false,
                'error' => 'No administrator found for this password reset request.',
            ];
        }

        // Update password
        $admin->updatePassword($newPassword);

        // Mark token as used (single-use enforcement)
        $db = Database::getInstance();
        $db->update(
            self::$table,
            ['used_at' => date('Y-m-d H:i:s')],
            'id = :id',
            [':id' => $record['id']]
        );

        return [
            'success' => true,
            'admin' => $admin,
        ];
    }
}

