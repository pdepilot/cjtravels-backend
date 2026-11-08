<?php

namespace App\Core;

class RateLimiter
{
    protected static string $table = 'login_throttles';

    protected static function key(string $identifier, string $ip): string
    {
        return hash('sha256', strtolower(trim($identifier)) . '|' . trim($ip));
    }

    public static function tooManyAttempts(string $identifier, string $ip, int $maxAttempts = 5): bool
    {
        $db = Database::getInstance();
        $key = self::key($identifier, $ip);
        $row = $db->selectOne("SELECT * FROM " . self::$table . " WHERE key_hash = :key LIMIT 1", [':key' => $key]);

        if (!$row) {
            return false;
        }

        // Check if currently locked
        if (!empty($row['locked_until'])) {
            $lockedUntil = strtotime($row['locked_until']);
            if ($lockedUntil > time()) {
                return true;
            }
            // Lock expired, reset attempts
            $db->update(
                self::$table,
                ['attempts' => 0, 'locked_until' => null],
                'id = :id',
                [':id' => $row['id']]
            );
            return false;
        }

        return (int)$row['attempts'] >= $maxAttempts;
    }

    public static function hit(string $identifier, string $ip, int $decaySeconds = 60, int $maxAttempts = 5): void
    {
        $db = Database::getInstance();
        $key = self::key($identifier, $ip);
        $row = $db->selectOne("SELECT * FROM " . self::$table . " WHERE key_hash = :key LIMIT 1", [':key' => $key]);
        $now = date('Y-m-d H:i:s');

        if (!$row) {
            $db->insert(self::$table, [
                'key_hash' => $key,
                'identifier' => strtolower(trim($identifier)),
                'ip_address' => $ip,
                'attempts' => 1,
                'last_attempt_at' => $now,
                'locked_until' => null,
            ]);
            return;
        }

        $attempts = (int)$row['attempts'] + 1;
        $lockedUntil = null;

        if ($attempts >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + $decaySeconds);
        }

        $db->update(
            self::$table,
            [
                'attempts' => $attempts,
                'last_attempt_at' => $now,
                'locked_until' => $lockedUntil,
            ],
            'id = :id',
            [':id' => $row['id']]
        );
    }

    public static function clear(string $identifier, string $ip): void
    {
        $db = Database::getInstance();
        $key = self::key($identifier, $ip);
        $db->delete(self::$table, 'key_hash = :key', [':key' => $key]);
    }

    public static function availableIn(string $identifier, string $ip): int
    {
        $db = Database::getInstance();
        $key = self::key($identifier, $ip);
        $row = $db->selectOne("SELECT locked_until FROM " . self::$table . " WHERE key_hash = :key LIMIT 1", [':key' => $key]);

        if (!$row || empty($row['locked_until'])) {
            return 0;
        }

        $remaining = strtotime($row['locked_until']) - time();
        return max(0, $remaining);
    }

    public static function attempts(string $identifier, string $ip): int
    {
        $db = Database::getInstance();
        $key = self::key($identifier, $ip);
        $row = $db->selectOne("SELECT attempts FROM " . self::$table . " WHERE key_hash = :key LIMIT 1", [':key' => $key]);
        return $row ? (int)$row['attempts'] : 0;
    }
}

