<?php
/**
 * Security — CSRF, rate limiting, auth helpers
 * includes/Security.php
 */

declare(strict_types=1);

class Security
{
    // ── Session ───────────────────────────────────────────────

    public static function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_name('XAUTERM_SESS');
        session_start();

        // Regenerate periodically
        if (!isset($_SESSION['_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_initiated'] = true;
        }
    }

    // ── CSRF ─────────────────────────────────────────────────

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    // ── Rate Limiting (per IP, file-based) ────────────────────

    public static function checkRateLimit(string $context = 'global'): bool
    {
        $ip   = self::clientIp();
        $key  = 'rl_' . $context . '_' . md5($ip);
        $data = FileCache::get($key) ?? ['count' => 0, 'window_start' => time()];

        if (time() - $data['window_start'] > RATE_LIMIT_WINDOW) {
            $data = ['count' => 1, 'window_start' => time()];
        } else {
            $data['count']++;
        }

        FileCache::set($key, $data, RATE_LIMIT_WINDOW + 5);

        return $data['count'] <= RATE_LIMIT_MAX;
    }

    // ── Authentication ────────────────────────────────────────

    public static function isAdminLoggedIn(): bool
    {
        self::startSecureSession();
        return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_user']);
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdminLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function adminLogin(string $username, string $password): bool
    {
        $row = Database::fetchOne(
            'SELECT id, password_hash FROM admin_users WHERE username = ? AND is_active = 1',
            [trim($username)]
        );

        if (!$row || !password_verify($password, $row['password_hash'])) {
            Logger::warning('auth', "Failed login attempt for username: $username from " . self::clientIp());
            return false;
        }

        // Upgrade hash if needed
        if (password_needs_rehash($row['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            Database::query('UPDATE admin_users SET password_hash = ? WHERE id = ?', [$newHash, $row['id']]);
        }

        Database::query('UPDATE admin_users SET last_login = NOW() WHERE id = ?', [$row['id']]);

        session_regenerate_id(true);
        $_SESSION['admin_id']   = $row['id'];
        $_SESSION['admin_user'] = $username;
        $_SESSION['admin_ip']   = self::clientIp();

        Logger::info('auth', "Admin login: $username from " . self::clientIp());
        return true;
    }

    public static function adminLogout(): void
    {
        session_destroy();
        setcookie('XAUTERM_SESS', '', time() - 3600, '/');
    }

    // ── Input Sanitisation ────────────────────────────────────

    public static function sanitize(mixed $input): string
    {
        return htmlspecialchars(strip_tags((string)$input), ENT_QUOTES, 'UTF-8');
    }

    public static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                return filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : '0.0.0.0';
            }
        }
        return '0.0.0.0';
    }

    // ── Security Headers ──────────────────────────────────────

    public static function sendSecurityHeaders(): void
    {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        header("Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://s3.tradingview.com https://cdnjs.cloudflare.com; "
            . "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data: https:; "
            . "connect-src 'self' https://s.tradingview.com; "
            . "frame-src https://s.tradingview.com;");
    }
}
