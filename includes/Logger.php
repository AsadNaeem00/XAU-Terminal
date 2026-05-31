<?php
/**
 * Logger — writes to DB app_logs + rotating flat files
 * includes/Logger.php
 */

declare(strict_types=1);

class Logger
{
    private static array $levels = ['info', 'warning', 'error', 'critical'];

    public static function info(string $context, string $message): void
    {
        self::write('info', $context, $message);
    }

    public static function warning(string $context, string $message): void
    {
        self::write('warning', $context, $message);
    }

    public static function error(string $context, string $message): void
    {
        self::write('error', $context, $message);
    }

    public static function critical(string $context, string $message): void
    {
        self::write('critical', $context, $message);
    }

    public static function apiLog(
        string $apiName,
        string $endpoint,
        int    $statusCode,
        float  $responseTime,
        bool   $success,
        string $errorMessage = ''
    ): void {
        try {
            Database::query(
                'INSERT INTO api_logs (api_name, endpoint, status_code, response_time, success, error_message)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $apiName,
                    substr($endpoint, 0, 512),
                    $statusCode,
                    round($responseTime, 4),
                    (int)$success,
                    $errorMessage ?: null,
                ]
            );
        } catch (Throwable) {
            // fail silently — logging must never break the app
        }
    }

    private static function write(string $level, string $context, string $message): void
    {
        // DB log
        try {
            Database::query(
                'INSERT INTO app_logs (level, context, message, ip_address) VALUES (?, ?, ?, ?)',
                [
                    $level,
                    substr($context, 0, 128),
                    $message,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch (Throwable) {}

        // Flat file log (daily rotation)
        $logFile = LOG_PATH . '/app-' . date('Y-m-d') . '.log';
        $line = sprintf(
            "[%s] [%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $context,
            $message
        );
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /** Retrieve recent logs for admin panel */
    public static function getRecentLogs(int $limit = 100, string $level = ''): array
    {
        if ($level && in_array($level, self::$levels)) {
            return Database::fetchAll(
                'SELECT * FROM app_logs WHERE level = ? ORDER BY created_at DESC LIMIT ?',
                [$level, $limit]
            );
        }
        return Database::fetchAll(
            'SELECT * FROM app_logs ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }

    /** Retrieve recent API logs */
    public static function getApiLogs(int $limit = 200): array
    {
        return Database::fetchAll(
            'SELECT * FROM api_logs ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }

    /** API health summary */
    public static function getApiHealth(): array
    {
        return Database::fetchAll(
            'SELECT
               api_name,
               COUNT(*) AS total_calls,
               SUM(success) AS successful,
               ROUND(AVG(response_time), 3) AS avg_response_time,
               MAX(created_at) AS last_call
             FROM api_logs
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY api_name
             ORDER BY api_name'
        );
    }
}
