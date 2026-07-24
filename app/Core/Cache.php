<?php

namespace App\Core;

final class Cache
{
    public static function remember(string $key, int $seconds, callable $callback): mixed
    {
        $path = self::path($key);
        if (is_file($path)) {
            $cached = json_decode((string) file_get_contents($path), true);
            if (is_array($cached) && ($cached['expires_at'] ?? 0) >= time()) {
                return $cached['value'];
            }
        }

        $value = $callback();
        file_put_contents($path, json_encode([
            'expires_at' => time() + $seconds,
            'value' => $value,
        ], JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $value;
    }

    public static function forget(string $key): void
    {
        $path = self::path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public static function forgetDashboard(int $userId): void
    {
        self::forget('dashboard-user-' . $userId);
    }

    private static function path(string $key): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir . '/' . hash('sha256', $key) . '.json';
    }
}
