<?php
/**
 * GoldPrice — fetches XAUUSD price with fallback chain
 * Primary:   Alpha Vantage
 * Fallback:  Finnhub
 * Tertiary:  Metals API
 * includes/GoldPrice.php
 */

declare(strict_types=1);

class GoldPrice
{
    private static string $cacheKey = 'gold_price';

    public static function get(): array
    {
        // Micro-cache (30s) to respect free-tier limits
        $cached = FileCache::get(self::$cacheKey);
        if ($cached !== null) {
            $cached['cached'] = true;
            return $cached;
        }

        $result = self::fromAlphaVantage()
               ?? self::fromFinnhub()
               ?? self::fromMetalsApi()
               ?? self::fallbackStatic();

        if ($result['source'] !== 'static') {
            FileCache::set(self::$cacheKey, $result, CACHE_PRICE_TTL);
        }

        $result['cached'] = false;
        return $result;
    }

    // ── Alpha Vantage ─────────────────────────────────────────

    private static function fromAlphaVantage(): ?array
    {
        $key = Settings::get('alpha_vantage_key');
        if (!$key || $key === 'YOUR_ALPHA_VANTAGE_KEY') return null;

        $url = ALPHA_VANTAGE_BASE . '?' . http_build_query([
            'function'    => 'CURRENCY_EXCHANGE_RATE',
            'from_currency' => 'XAU',
            'to_currency'   => 'USD',
            'apikey'        => $key,
        ]);

        $data = HttpClient::getJson($url, [], 'alpha_vantage');
        if (!$data) return null;

        $rate = $data['Realtime Currency Exchange Rate'] ?? null;
        if (!$rate || !isset($rate['5. Exchange Rate'])) return null;

        $price = (float)$rate['5. Exchange Rate'];
        $bid   = isset($rate['8. Bid Price'])  ? (float)$rate['8. Bid Price']  : $price;
        $ask   = isset($rate['9. Ask Price'])   ? (float)$rate['9. Ask Price']   : $price;

        return [
            'price'     => $price,
            'bid'       => $bid,
            'ask'       => $ask,
            'spread'    => round($ask - $bid, 4),
            'timestamp' => $rate['6. Last Refreshed'] ?? date('Y-m-d H:i:s'),
            'source'    => 'alpha_vantage',
            'delay_min' => 15,
            'change'    => null,
            'change_pct'=> null,
        ];
    }

    // ── Finnhub ───────────────────────────────────────────────

    private static function fromFinnhub(): ?array
    {
        $key = Settings::get('finnhub_key');
        if (!$key || $key === 'YOUR_FINNHUB_KEY') return null;

        $url = FINNHUB_BASE . '/quote?' . http_build_query([
            'symbol' => 'OANDA:XAU_USD',
            'token'  => $key,
        ]);

        $data = HttpClient::getJson($url, [], 'finnhub');
        if (!$data || !isset($data['c']) || $data['c'] == 0) return null;

        return [
            'price'      => (float)$data['c'],
            'bid'        => (float)($data['c'] - 0.5),
            'ask'        => (float)($data['c'] + 0.5),
            'spread'     => 1.0,
            'high'       => (float)$data['h'],
            'low'        => (float)$data['l'],
            'open'       => (float)$data['o'],
            'prev_close' => (float)$data['pc'],
            'change'     => round($data['c'] - $data['pc'], 2),
            'change_pct' => round((($data['c'] - $data['pc']) / $data['pc']) * 100, 3),
            'timestamp'  => date('Y-m-d H:i:s', $data['t'] ?? time()),
            'source'     => 'finnhub',
            'delay_min'  => 0,
        ];
    }

    // ── Metals API ────────────────────────────────────────────

    private static function fromMetalsApi(): ?array
    {
        $key = Settings::get('metals_api_key');
        if (!$key || $key === 'YOUR_METALS_API_KEY') return null;

        $url = METALS_API_BASE . '/latest?' . http_build_query([
            'access_key' => $key,
            'base'       => 'XAU',
            'symbols'    => 'USD',
        ]);

        $data = HttpClient::getJson($url, [], 'metals_api');
        if (!$data || !isset($data['rates']['USD'])) return null;

        $price = (float)$data['rates']['USD'];
        return [
            'price'      => $price,
            'bid'        => $price - 0.5,
            'ask'        => $price + 0.5,
            'spread'     => 1.0,
            'timestamp'  => date('Y-m-d H:i:s', $data['timestamp'] ?? time()),
            'source'     => 'metals_api',
            'delay_min'  => 0,
            'change'     => null,
            'change_pct' => null,
        ];
    }

    // ── Static fallback ───────────────────────────────────────

    private static function fallbackStatic(): array
    {
        Logger::warning('gold_price', 'All API sources failed — returning static fallback');
        return [
            'price'      => 0,
            'bid'        => 0,
            'ask'        => 0,
            'spread'     => 0,
            'timestamp'  => date('Y-m-d H:i:s'),
            'source'     => 'static',
            'delay_min'  => 999,
            'change'     => null,
            'change_pct' => null,
            'error'      => 'All price feeds unavailable. Check API keys.',
        ];
    }
}
