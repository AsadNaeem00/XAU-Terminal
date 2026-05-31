<?php
/**
 * API: /api/calendar.php
 * Returns economic calendar events as JSON
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (!Security::checkRateLimit('calendar')) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded.']);
    exit;
}

$days = min((int)($_GET['days'] ?? 7), 30);

try {
    $events = EconomicCalendar::get($days);
    echo json_encode(['ok' => true, 'data' => $events, 'count' => count($events), 'ts' => time()]);
} catch (Throwable $e) {
    Logger::error('api_calendar', $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to fetch calendar.']);
}
