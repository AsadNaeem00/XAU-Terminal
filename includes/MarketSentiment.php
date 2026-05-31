<?php
/**
 * MarketSentiment — derives Gold sentiment from economic indicators
 * includes/MarketSentiment.php
 */

declare(strict_types=1);

class MarketSentiment
{
    private static string $cacheKey = 'market_sentiment';

    public static function get(): array
    {
        $cached = FileCache::get(self::$cacheKey);
        if ($cached !== null) return $cached;

        $indicators = self::gatherIndicators();
        $score      = self::computeScore($indicators);
        $label      = self::scoreToLabel($score);

        $result = [
            'score'       => $score,           // -100 (max bearish) to +100 (max bullish)
            'label'       => $label,            // string: "Strongly Bullish" etc.
            'direction'   => $score >= 0 ? 'bullish' : 'bearish',
            'indicators'  => $indicators,
            'timestamp'   => date('c'),
        ];

        FileCache::set(self::$cacheKey, $result, CACHE_SENTIMENT_TTL);
        return $result;
    }

    /**
     * Gather technical + fundamental indicators
     * Each indicator returns: ['label', 'value', 'signal', 'weight', 'description']
     * signal: 'bullish' | 'bearish' | 'neutral'
     */
    private static function gatherIndicators(): array
    {
        $indicators = [];

        // 1. DXY trend proxy via Alpha Vantage USD/EUR inverse
        $usdStrength = self::getDXYSignal();
        $indicators[] = $usdStrength;

        // 2. US 10Y Treasury yield signal (via Finnhub)
        $yieldSignal = self::getTreasuryYieldSignal();
        $indicators[] = $yieldSignal;

        // 3. Gold momentum (recent price vs SMA via Alpha Vantage)
        $momentum = self::getGoldMomentum();
        $indicators[] = $momentum;

        // 4. Upcoming high-impact events (risk-off proxy)
        $eventRisk = self::getEventRiskSignal();
        $indicators[] = $eventRisk;

        // 5. News sentiment aggregate
        $newsSentiment = self::getNewsSentiment();
        $indicators[] = $newsSentiment;

        return array_filter($indicators, fn($i) => $i !== null);
    }

    private static function getDXYSignal(): array
    {
        // Inverse: strong USD = bearish for gold
        $key = Settings::get('alpha_vantage_key');
        $signal = 'neutral';
        $value  = 'N/A';

        if ($key && $key !== 'YOUR_ALPHA_VANTAGE_KEY') {
            $url = ALPHA_VANTAGE_BASE . '?' . http_build_query([
                'function'      => 'CURRENCY_EXCHANGE_RATE',
                'from_currency' => 'USD',
                'to_currency'   => 'EUR',
                'apikey'        => $key,
            ]);
            $data = HttpClient::getJson($url, [], 'alpha_vantage');
            if ($data) {
                $rate = (float)($data['Realtime Currency Exchange Rate']['5. Exchange Rate'] ?? 0);
                // EUR/USD above 1.08 → weak dollar → bullish gold
                if ($rate > 1.08)       { $signal = 'bullish';  }
                elseif ($rate < 1.02)   { $signal = 'bearish';  }
                $value = number_format($rate, 4);
            }
        }

        return [
            'label'       => 'USD Strength (EUR/USD)',
            'value'       => $value,
            'signal'      => $signal,
            'weight'      => 25,
            'description' => 'Weak USD (high EUR/USD) supports Gold prices.',
            'icon'        => 'dollar',
        ];
    }

    private static function getTreasuryYieldSignal(): array
    {
        $key    = Settings::get('finnhub_key');
        $signal = 'neutral';
        $value  = 'N/A';

        if ($key && $key !== 'YOUR_FINNHUB_KEY') {
            // US10Y bond yield proxy via Finnhub quote
            $url = FINNHUB_BASE . '/quote?' . http_build_query([
                'symbol' => 'US10Y',
                'token'  => $key,
            ]);
            $data = HttpClient::getJson($url, [], 'finnhub');
            if ($data && isset($data['c']) && $data['c'] > 0) {
                $yield = (float)$data['c'];
                $value = number_format($yield, 2) . '%';
                // High yield → opportunity cost for Gold → bearish
                if ($yield > 4.5)       { $signal = 'bearish';  }
                elseif ($yield < 3.5)   { $signal = 'bullish';  }
            }
        }

        return [
            'label'       => 'US 10Y Treasury Yield',
            'value'       => $value,
            'signal'      => $signal,
            'weight'      => 25,
            'description' => 'High bond yields increase opportunity cost of holding Gold.',
            'icon'        => 'chart',
        ];
    }

    private static function getGoldMomentum(): array
    {
        $price = GoldPrice::get();
        $signal = 'neutral';
        $value  = $price['price'] ? '$' . number_format($price['price'], 2) : 'N/A';

        // Simple threshold: above $2400 = momentum bullish, below $2200 = bearish
        if ($price['price'] > 0) {
            if ($price['change_pct'] !== null) {
                if ($price['change_pct'] > 0.5)       $signal = 'bullish';
                elseif ($price['change_pct'] < -0.5)  $signal = 'bearish';
            }
        }

        return [
            'label'       => 'Gold Price Momentum',
            'value'       => $value,
            'signal'      => $signal,
            'weight'      => 30,
            'description' => 'Intraday price momentum and direction.',
            'icon'        => 'gold',
        ];
    }

    private static function getEventRiskSignal(): array
    {
        $calendar = EconomicCalendar::get(3); // Next 3 days
        $highCount = count(array_filter($calendar, fn($e) => $e['impact'] === 'high'));

        $signal = 'neutral';
        $value  = "$highCount high-impact events";

        // Many upcoming events = uncertainty = mild bullish for gold (safe haven)
        if ($highCount >= 3) { $signal = 'bullish'; }
        elseif ($highCount === 0) { $signal = 'neutral'; }

        return [
            'label'       => 'Event Risk (3-day)',
            'value'       => $value,
            'signal'      => $signal,
            'weight'      => 10,
            'description' => 'High event count increases uncertainty — Gold benefits as safe haven.',
            'icon'        => 'calendar',
        ];
    }

    private static function getNewsSentiment(): array
    {
        $news    = NewsAggregator::get(15);
        $bull    = count(array_filter($news, fn($n) => ($n['sentiment'] ?? '') === 'bullish'));
        $bear    = count(array_filter($news, fn($n) => ($n['sentiment'] ?? '') === 'bearish'));
        $total   = count($news);

        $signal = 'neutral';
        $value  = "$bull bullish / $bear bearish";

        if ($total > 0) {
            $ratio = $bull / max(1, $total);
            if ($ratio > 0.55)       $signal = 'bullish';
            elseif ($ratio < 0.35)   $signal = 'bearish';
        }

        return [
            'label'       => 'News Sentiment',
            'value'       => $value,
            'signal'      => $signal,
            'weight'      => 10,
            'description' => 'Aggregate sentiment from gold and USD news headlines.',
            'icon'        => 'news',
        ];
    }

    private static function computeScore(array $indicators): int
    {
        $weighted = 0;
        $total    = 0;

        foreach ($indicators as $ind) {
            $w = $ind['weight'] ?? 0;
            $total += $w;
            $multiplier = match($ind['signal'] ?? 'neutral') {
                'bullish' => 1,
                'bearish' => -1,
                default   => 0,
            };
            $weighted += $w * $multiplier;
        }

        if ($total === 0) return 0;
        return (int)round(($weighted / $total) * 100);
    }

    private static function scoreToLabel(int $score): string
    {
        if ($score >= 60)  return 'Strongly Bullish';
        if ($score >= 25)  return 'Bullish';
        if ($score >= -24) return 'Neutral';
        if ($score >= -59) return 'Bearish';
        return 'Strongly Bearish';
    }
}
