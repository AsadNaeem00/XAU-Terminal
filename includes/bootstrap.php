<?php
/**
 * Bootstrap — loads config and all class files
 * includes/bootstrap.php
 */

declare(strict_types=1);

// ── Config first (defines constants) ─────────────────────────
require_once __DIR__ . '/config.php';

// ── Core classes ──────────────────────────────────────────────
$coreClasses = [
    'Database',
    'Logger',
    'FileCache',
    'Settings',
    'Security',
    'HttpClient',
    'GoldPrice',
    'NewsAggregator',
    'EconomicCalendar',
    'MarketSentiment',
];

foreach ($coreClasses as $cls) {
    require_once INC_PATH . "/{$cls}.php";
}

// ── Global error handler (production) ────────────────────────
if (!DEBUG_MODE) {
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
        Logger::error('php_error', "[$errno] $errstr in $errfile:$errline");
        return true;
    });

    set_exception_handler(function (Throwable $e): void {
        Logger::critical('exception', get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if (php_sapi_name() !== 'cli') {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal server error.']);
        }
        exit(1);
    });
}

// ── Ensure cache/logs dirs exist ─────────────────────────────
foreach ([CACHE_PATH, LOG_PATH] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
}

// ── Start session (all requests) ─────────────────────────────
Security::startSecureSession();
Security::sendSecurityHeaders();
