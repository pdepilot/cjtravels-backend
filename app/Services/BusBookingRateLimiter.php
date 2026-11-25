<?php

namespace App\Services;

class BusBookingRateLimiter
{
    protected static int $maxAttempts = 60;

    protected static int $windowSeconds = 600;

    protected static ?string $forcedFailure = null;

    public static function usePolicy(int $maxAttempts, int $windowSeconds): void
    {
        self::$maxAttempts = max(1, $maxAttempts);
        self::$windowSeconds = max(1, $windowSeconds);
    }

    public static function resetPolicy(): void
    {
        self::$maxAttempts = 60;
        self::$windowSeconds = 600;
    }

    public static function policy(): array
    {
        return [
            'maxAttempts' => self::$maxAttempts,
            'windowSeconds' => self::$windowSeconds,
        ];
    }

    public static function forceStorageFailure(?string $operation): void
    {
        self::$forcedFailure = $operation;
    }

    public static function attempt(string $ip): array
    {
        $ip = self::clientIp($ip);
        $now = time();
        $directory = self::directory();
        $path = self::pathFor($ip);

        if (self::consumeFailure('directory') || !self::prepareDirectory($directory)) {
            return self::storageFailure();
        }

        $handle = self::openBucket($path);
        if ($handle === false) {
            return self::storageFailure();
        }

        $locked = false;
        try {
            if (self::consumeFailure('lock') || !@flock($handle, LOCK_EX)) {
                return self::storageFailure();
            }
            $locked = true;

            if (self::consumeFailure('read')) {
                return self::storageFailure();
            }

            $raw = @stream_get_contents($handle);
            if ($raw === false) {
                return self::storageFailure();
            }

            $hits = self::activeHits($raw, $now);
            if ($hits === null) {
                return self::storageFailure();
            }

            if (count($hits) >= self::$maxAttempts) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'outcome' => 'limited',
                ];
            }

            $hits[] = $now;
            if (!self::persistHits($handle, $hits)) {
                return self::storageFailure();
            }

            return [
                'allowed' => true,
                'remaining' => max(0, self::$maxAttempts - count($hits)),
                'outcome' => 'ok',
            ];
        } finally {
            if ($locked) {
                @flock($handle, LOCK_UN);
            }
            @fclose($handle);
        }
    }

    public static function reset(?string $ip = null): void
    {
        if ($ip === null) {
            $directory = self::directory();
            if (!is_dir($directory)) {
                return;
            }
            foreach (glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
                @unlink($file);
            }
            return;
        }

        $path = self::pathFor(self::clientIp($ip));
        if (is_file($path)) {
            @unlink($path);
        }
    }

    protected static function storageFailure(): array
    {
        return [
            'allowed' => false,
            'remaining' => 0,
            'outcome' => 'storage',
        ];
    }

    protected static function prepareDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }

        return true;
    }

    protected static function openBucket(string $path)
    {
        if (self::consumeFailure('open')) {
            return false;
        }

        $handle = @fopen($path, 'c+');
        return $handle === false ? false : $handle;
    }

    protected static function activeHits(string $raw, int $now): ?array
    {
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $cutoff = $now - self::$windowSeconds;
        $hits = [];
        foreach ($decoded as $hit) {
            if (is_int($hit) && $hit > $cutoff) {
                $hits[] = $hit;
            }
        }

        return $hits;
    }

    protected static function persistHits($handle, array $hits): bool
    {
        if (self::consumeFailure('write') || self::consumeFailure('truncate') || self::consumeFailure('flush')) {
            return false;
        }

        $payload = json_encode(array_values($hits));
        if (!is_string($payload)) {
            return false;
        }

        if (@rewind($handle) !== true) {
            return false;
        }

        $written = @fwrite($handle, $payload);
        if ($written !== strlen($payload)) {
            return false;
        }

        if (@ftruncate($handle, strlen($payload)) !== true) {
            return false;
        }

        return @fflush($handle) === true;
    }

    protected static function consumeFailure(string $operation): bool
    {
        if (self::$forcedFailure !== $operation) {
            return false;
        }

        self::$forcedFailure = null;
        return true;
    }

    protected static function clientIp(string $ip): string
    {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '0.0.0.0';
    }

    protected static function directory(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bus-rate';
    }

    protected static function pathFor(string $ip): string
    {
        return self::directory() . DIRECTORY_SEPARATOR . hash('sha256', $ip) . '.json';
    }
}
