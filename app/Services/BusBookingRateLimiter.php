<?php

namespace App\Services;

class BusBookingRateLimiter
{
    protected static int $maxAttempts = 60;

    protected static int $windowSeconds = 600;

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

    public static function attempt(string $ip): array
    {
        $ip = self::clientIp($ip);
        $now = time();
        $path = self::pathFor($ip);
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return ['allowed' => true, 'remaining' => self::$maxAttempts];
        }

        try {
            flock($handle, LOCK_EX);
            $raw = stream_get_contents($handle);
            $hits = json_decode($raw ?: '[]', true);
            if (!is_array($hits)) {
                $hits = [];
            }

            $cutoff = $now - self::$windowSeconds;
            $hits = array_values(array_filter(
                $hits,
                static fn($hit): bool => is_int($hit) && $hit > $cutoff
            ));

            if (count($hits) >= self::$maxAttempts) {
                return ['allowed' => false, 'remaining' => 0];
            }

            $hits[] = $now;
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($hits));
            fflush($handle);

            return [
                'allowed' => true,
                'remaining' => max(0, self::$maxAttempts - count($hits)),
            ];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
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
            unlink($path);
        }
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
