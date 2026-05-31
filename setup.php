#!/usr/bin/env php
<?php
/**
 * Setup verification script
 * Run from project root: php setup.php
 *
 * Checks PHP version, extensions, DB connection,
 * directory permissions, and seeds initial data.
 */

declare(strict_types=1);

echo "\n";
echo "╔══════════════════════════════════════════════╗\n";
echo "║   XAUUSD Intelligence Terminal — Setup       ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

$pass = 0; $fail = 0;

function check(string $label, bool $ok, string $info = ''): void {
    global $pass, $fail;
    $icon = $ok ? '✓' : '✗';
    $clr  = $ok ? "\033[32m" : "\033[31m";
    echo "  {$clr}{$icon}\033[0m  {$label}";
    if ($info) echo " — {$info}";
    echo "\n";
    $ok ? $pass++ : $fail++;
}

// ── PHP Version ───────────────────────────────────────────────
check('PHP version ≥ 8.0', version_compare(PHP_VERSION, '8.0.0', '>='), PHP_VERSION);

// ── Required extensions ───────────────────────────────────────
foreach (['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring', 'openssl', 'session'] as $ext) {
    check("Extension: {$ext}", extension_loaded($ext));
}

// ── Config file ───────────────────────────────────────────────
$localCfg = __DIR__ . '/includes/config.local.php';
check('config.local.php exists', file_exists($localCfg),
      file_exists($localCfg) ? 'OK' : 'Copy config.local.php.template → config.local.php and fill in credentials');

// ── Load bootstrap (needs config.local.php) ───────────────────
if (file_exists($localCfg)) {
    require_once __DIR__ . '/includes/bootstrap.php';

    // ── DB connection ─────────────────────────────────────────
    try {
        $pdo = Database::getInstance();
        check('Database connection', true, DB_HOST . '/' . DB_NAME);

        // Check tables
        foreach (['admin_users', 'api_logs', 'app_logs', 'settings'] as $table) {
            $exists = $pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount() > 0;
            check("Table: {$table}", $exists, $exists ? '' : 'Run database.sql first');
        }

        // Check admin user
        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        check('Admin user exists', $adminCount > 0, $adminCount > 0 ? "{$adminCount} user(s)" : 'Run database.sql seed');

        // Check settings
        $apiKeys = ['alpha_vantage_key', 'finnhub_key'];
        foreach ($apiKeys as $k) {
            $val = Settings::get($k, '');
            $set = !empty($val) && !str_starts_with($val, 'YOUR_');
            check("API key: {$k}", $set, $set ? 'Configured' : "Not set — update settings table");
        }

    } catch (Throwable $e) {
        check('Database connection', false, $e->getMessage());
    }

    // ── Directory permissions ─────────────────────────────────
    foreach ([CACHE_PATH, LOG_PATH] as $dir) {
        $writable = is_writable($dir) || (!is_dir($dir) && is_writable(dirname($dir)));
        check("Writable: " . basename($dir) . "/", $writable ?: @mkdir($dir, 0750, true) && is_writable($dir));
    }
}

// ── Summary ───────────────────────────────────────────────────
echo "\n";
echo "  Results: \033[32m{$pass} passed\033[0m / \033[31m{$fail} failed\033[0m\n\n";

if ($fail === 0) {
    echo "  \033[32m✓ Terminal is ready to launch!\033[0m\n\n";
} else {
    echo "  \033[33m⚠ Fix the issues above before launching.\033[0m\n\n";
    echo "  Quick steps:\n";
    echo "  1. Copy includes/config.local.php.template → includes/config.local.php\n";
    echo "  2. Fill in DB credentials\n";
    echo "  3. Import database.sql via phpMyAdmin\n";
    echo "  4. Update API keys in the settings table\n";
    echo "  5. Run this script again\n\n";
}
