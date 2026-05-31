<?php
/**
 * Settings — key-value site settings stored in DB
 * includes/Settings.php
 */

declare(strict_types=1);

class Settings
{
    private static array $cache = [];
    private static bool  $loaded = false;

    /** Load all settings into memory once */
    private static function loadAll(): void
    {
        if (self::$loaded) return;
        try {
            $rows = Database::fetchAll('SELECT `key`, `value` FROM settings');
            foreach ($rows as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        } catch (Throwable) {}
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::loadAll();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Database::query(
            'INSERT INTO settings (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, (string)$value]
        );
        self::$cache[$key] = (string)$value;
    }

    public static function all(): array
    {
        self::loadAll();
        return self::$cache;
    }
}
