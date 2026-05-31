<?php
/**
 * NewsAggregator — gold & USD news from NewsAPI + Finnhub
 * includes/NewsAggregator.php
 */

declare(strict_types=1);

class NewsAggregator
{
    private static string $cacheKey = 'gold_news';

    private static array $goldKeywords = [
        'gold', 'XAU', 'XAUUSD', 'bullion', 'precious metals',
        'Federal Reserve', 'Fed rate', 'inflation', 'CPI', 'NFP',
        'nonfarm payroll', 'FOMC', 'interest rate', 'dollar', 'DXY',
        'treasury yield', 'safe haven', 'commodities',
    ];

    public static function get(int $limit = 20): array
    {
        $cached = FileCache::get(self::$cacheKey);
        if ($cached !== null) {
            return array_slice($cached, 0, $limit);
        }

        $articles = array_merge(
            self::fromNewsApi(),
            self::fromFinnhubNews()
        );

        // Deduplicate by title similarity
        $seen      = [];
        $deduplicated = [];
        foreach ($articles as $a) {
            $key = strtolower(substr($a['title'] ?? '', 0, 60));
            if (!isset($seen[$key])) {
                $seen[$key]     = true;
                $deduplicated[] = $a;
            }
        }

        // Sort by published date (newest first)
        usort($deduplicated, fn($a, $b) =>
            strtotime($b['published_at']) <=> strtotime($a['published_at'])
        );

        FileCache::set(self::$cacheKey, $deduplicated, CACHE_NEWS_TTL);
        return array_slice($deduplicated, 0, $limit);
    }

    private static function fromNewsApi(): array
    {
        $key = Settings::get('news_api_key');
        if (!$key || $key === 'YOUR_NEWS_API_KEY') return [];

        $url = NEWS_API_BASE . '/everything?' . http_build_query([
            'q'        => 'gold price OR XAUUSD OR "Federal Reserve" OR "gold bullion"',
            'language' => 'en',
            'sortBy'   => 'publishedAt',
            'pageSize' => 20,
            'apiKey'   => $key,
        ]);

        $data = HttpClient::getJson($url, [], 'news_api');
        if (!$data || !isset($data['articles'])) return [];

        return array_map(fn($a) => [
            'title'        => self::sanitizeTitle($a['title'] ?? ''),
            'summary'      => self::sanitizeSummary($a['description'] ?? ''),
            'url'          => filter_var($a['url'] ?? '', FILTER_VALIDATE_URL) ? $a['url'] : '#',
            'source'       => $a['source']['name'] ?? 'NewsAPI',
            'image_url'    => $a['urlToImage'] ?? null,
            'published_at' => $a['publishedAt'] ?? date('c'),
            'sentiment'    => self::quickSentiment($a['title'] . ' ' . ($a['description'] ?? '')),
            'feed'         => 'newsapi',
        ], array_filter($data['articles'], fn($a) => !empty($a['title']) && $a['title'] !== '[Removed]'));
    }

    private static function fromFinnhubNews(): array
    {
        $key = Settings::get('finnhub_key');
        if (!$key || $key === 'YOUR_FINNHUB_KEY') return [];

        $url = FINNHUB_BASE . '/news?' . http_build_query([
            'category' => 'forex',
            'token'    => $key,
        ]);

        $data = HttpClient::getJson($url, [], 'finnhub');
        if (!is_array($data)) return [];

        $goldRelated = array_filter($data, function ($item) {
            $text = strtolower(($item['headline'] ?? '') . ' ' . ($item['summary'] ?? ''));
            foreach (self::$goldKeywords as $kw) {
                if (str_contains($text, strtolower($kw))) return true;
            }
            return false;
        });

        return array_map(fn($a) => [
            'title'        => self::sanitizeTitle($a['headline'] ?? ''),
            'summary'      => self::sanitizeSummary($a['summary'] ?? ''),
            'url'          => filter_var($a['url'] ?? '', FILTER_VALIDATE_URL) ? $a['url'] : '#',
            'source'       => $a['source'] ?? 'Finnhub',
            'image_url'    => $a['image'] ?? null,
            'published_at' => date('c', $a['datetime'] ?? time()),
            'sentiment'    => self::quickSentiment($a['headline'] . ' ' . ($a['summary'] ?? '')),
            'feed'         => 'finnhub',
        ], array_values($goldRelated));
    }

    /** Rudimentary keyword-based sentiment */
    private static function quickSentiment(string $text): string
    {
        $text = strtolower($text);
        $bullish = ['surge', 'rally', 'rise', 'gain', 'high', 'bull', 'buy', 'up', 'strong',
                    'climb', 'soar', 'record', 'breakout', 'positive'];
        $bearish = ['fall', 'drop', 'decline', 'sell', 'down', 'bear', 'crash', 'weak',
                    'slump', 'plunge', 'low', 'bearish', 'negative', 'pressure'];

        $b = 0; $be = 0;
        foreach ($bullish as $w) { if (str_contains($text, $w)) $b++; }
        foreach ($bearish as $w) { if (str_contains($text, $w)) $be++; }

        if ($b > $be + 1) return 'bullish';
        if ($be > $b + 1) return 'bearish';
        return 'neutral';
    }

    private static function sanitizeTitle(string $t): string
    {
        return htmlspecialchars(strip_tags(trim($t)), ENT_QUOTES, 'UTF-8');
    }

    private static function sanitizeSummary(string $t): string
    {
        $clean = htmlspecialchars(strip_tags(trim($t)), ENT_QUOTES, 'UTF-8');
        return mb_strlen($clean) > 300 ? mb_substr($clean, 0, 297) . '…' : $clean;
    }
}
