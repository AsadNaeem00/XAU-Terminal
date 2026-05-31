<?php
/**
 * FileCache — filesystem micro-cache
 * Used to respect free-tier API rate limits (30–600s TTL)
 * includes/FileCache.php
 */

declare(strict_types=1);

class FileCache
{
    private static string $dir = '';

    private static function dir(): string
    {
        if (self::$dir === '') {
            self::$dir = CACHE_PATH;
            if (!is_dir(self::$dir)) {
                mkdir(self::$dir, 0750, true);
            }
        }
        return self::$dir;
    }

    private static function path(string $key): string
    {
        return self::dir() . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.cache';
    }

    public static function get(string $key): mixed
    {
        $file = self::path($key);
        if (!file_exists($file)) return null;

        $data = @unserialize(file_get_contents($file));
        if ($data === false || !is_array($data)) return null;
        if (time() > $data['expires']) {
            @unlink($file);
            return null;
        }
        return $data['value'];
    }

    public static function set(string $key, mixed $value, int $ttl = 60): void
    {
        $payload = serialize(['expires' => time() + $ttl, 'value' => $value]);
        @file_put_contents(self::path($key), $payload, LOCK_EX);
    }

    public static function delete(string $key): void
    {
        @unlink(self::path($key));
    }

    public static function flush(): int
    {
        $count = 0;
        foreach (glob(self::dir() . '/*.cache') as $file) {
            if (@unlink($file)) $count++;
        }
        return $count;
    }

    /** Delete only expired cache entries */
    public static function gc(): int
    {
        $count = 0;
        foreach (glob(self::dir() . '/*.cache') as $file) {
            $data = @unserialize(file_get_contents($file));
            if ($data === false || time() > ($data['expires'] ?? 0)) {
                if (@unlink($file)) $count++;
            }
        }
        return $count;
    }
}
