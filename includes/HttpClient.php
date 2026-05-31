<?php
/**
 * HttpClient — cURL wrapper with timing and logging
 * includes/HttpClient.php
 */

declare(strict_types=1);

class HttpClient
{
    private static int $timeout = 10;

    /**
     * GET request — returns decoded JSON or null on failure.
     */
    public static function getJson(
        string $url,
        array  $headers = [],
        string $apiName = 'unknown'
    ): array|null {
        $start = microtime(true);
        $ch    = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => array_merge(
                ['Accept: application/json', 'User-Agent: XAUUSD-Terminal/1.0'],
                $headers
            ),
            CURLOPT_ENCODING       => 'gzip, deflate',
        ]);

        $body     = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $elapsed  = microtime(true) - $start;
        $error    = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300 && $body !== false);

        Logger::apiLog(
            $apiName,
            $url,
            $httpCode,
            $elapsed,
            $success,
            $error ?: ($success ? '' : "HTTP $httpCode")
        );

        if (!$success) {
            Logger::error($apiName, "Request failed [$httpCode]: $url — $error");
            return null;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error($apiName, 'JSON decode error: ' . json_last_error_msg());
            return null;
        }

        return $decoded;
    }
}
