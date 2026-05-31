<?php
/**
 * API: /api/news.php
 * Returns gold & USD news as JSON
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (!Security::checkRateLimit('news')) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded.']);
    exit;
}

$limit = min((int)($_GET['limit'] ?? 20), 50);

try {
    $articles = NewsAggregator::get($limit);
    echo json_encode(['ok' => true, 'data' => $articles, 'count' => count($articles), 'ts' => time()]);
} catch (Throwable $e) {
    Logger::error('api_news', $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to fetch news.']);
}
