<?php
/**
 * API: /api/price.php
 * Returns live XAUUSD price data as JSON
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));

if (!Security::checkRateLimit('price')) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Please slow down.']);
    exit;
}

try {
    $data = GoldPrice::get();
    echo json_encode([
        'ok'   => true,
        'data' => $data,
        'ts'   => time(),
    ]);
} catch (Throwable $e) {
    Logger::error('api_price', $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to fetch price data.']);
}
