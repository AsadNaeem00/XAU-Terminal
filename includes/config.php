<?php
/**
 * XAUUSD Intelligence Terminal
 * Configuration — includes/config.php
 *
 * IMPORTANT: Set real values in config.local.php (never commit that file).
 * This file contains safe defaults and loads the local override.
 */

declare(strict_types=1);

// ── Timezone ────────────────────────────────────────────────
date_default_timezone_set('UTC');

// ── Base paths ───────────────────────────────────────────────
define('ROOT_PATH',  dirname(__DIR__));
define('INC_PATH',   ROOT_PATH . '/includes');
define('API_PATH',   ROOT_PATH . '/api');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('LOG_PATH',   ROOT_PATH . '/logs');
define('ASSET_PATH', ROOT_PATH . '/assets');

// ── Database defaults (override in config.local.php) ─────────
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'xauusd_terminal');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── Application ───────────────────────────────────────────────
define('APP_VERSION', '1.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'production'); // 'development' | 'production'
define('DEBUG_MODE',  APP_ENV === 'development');

// ── API Keys (loaded from DB at runtime via Settings::get()) ──
// These constants are fallbacks; real keys come from the settings table.
define('ALPHA_VANTAGE_BASE', 'https://www.alphavantage.co/query');
define('FINNHUB_BASE',       'https://finnhub.io/api/v1');
define('METALS_API_BASE',    'https://metals-api.com/api');
define('NEWS_API_BASE',      'https://newsapi.org/v2');

// ── Security ─────────────────────────────────────────────────
define('SESSION_LIFETIME',  3600 * 8);  // 8 hours
define('CSRF_TOKEN_LENGTH', 32);
define('RATE_LIMIT_WINDOW', 60);        // seconds
define('RATE_LIMIT_MAX',    60);        // requests per window per IP

// ── Cache TTLs (seconds) ──────────────────────────────────────
// Even with "no caching" UX, we micro-cache API responses
// to avoid hammering free-tier rate limits.
define('CACHE_PRICE_TTL',    30);
define('CACHE_NEWS_TTL',     300);
define('CACHE_CALENDAR_TTL', 600);
define('CACHE_SENTIMENT_TTL',120);

// ── Load local overrides (Hostinger: set real DB creds here) ──
$localConfig = INC_PATH . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}
