<?php
/**
 * API: /api/sentiment.php
 * Returns computed market sentiment as JSON
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (!Security::checkRateLimit('sentiment')) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded.']);
    exit;
}

try {
    $sentiment = MarketSentiment::get();
    echo json_encode(['ok' => true, 'data' => $sentiment, 'ts' => time()]);
} catch (Throwable $e) {
    Logger::error('api_sentiment', $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to compute sentiment.']);
}
