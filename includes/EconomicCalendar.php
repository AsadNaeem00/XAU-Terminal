<?php
/**
 * EconomicCalendar — high-impact USD & gold events via Finnhub
 * includes/EconomicCalendar.php
 */

declare(strict_types=1);

class EconomicCalendar
{
    private static string $cacheKey = 'economic_calendar';

    /** High-impact USD event keywords to filter */
    private static array $highImpactKeywords = [
        'nonfarm', 'nfp', 'cpi', 'ppi', 'fomc', 'federal reserve', 'fed',
        'gdp', 'interest rate', 'unemployment', 'retail sales',
        'consumer price', 'producer price', 'inflation',
        'ism manufacturing', 'ism services', 'ism non-manufacturing',
        'initial jobless', 'trade balance', 'housing starts',
        'durable goods', 'pcm', 'pce', 'personal income',
        'gold', 'xau', 'comex',
    ];

    /** Globe pin coordinates per country code */
    private static array $countryCoords = [
        'US' => ['lat' => 38.9,  'lng' => -77.0,  'label' => 'Washington D.C.'],
        'GB' => ['lat' => 51.5,  'lng' => -0.1,   'label' => 'London'],
        'EU' => ['lat' => 50.1,  'lng' => 8.7,    'label' => 'Frankfurt'],
        'JP' => ['lat' => 35.7,  'lng' => 139.7,  'label' => 'Tokyo'],
        'CN' => ['lat' => 39.9,  'lng' => 116.4,  'label' => 'Beijing'],
        'CH' => ['lat' => 46.9,  'lng' => 7.4,    'label' => 'Bern'],
        'CA' => ['lat' => 45.4,  'lng' => -75.7,  'label' => 'Ottawa'],
        'AU' => ['lat' => -35.3, 'lng' => 149.1,  'label' => 'Canberra'],
    ];

    public static function get(int $days = 7): array
    {
        $cached = FileCache::get(self::$cacheKey);
        if ($cached !== null) return $cached;

        $events = self::fromFinnhub($days);

        if (empty($events)) {
            $events = self::staticFallback();
        }

        // Enrich with globe coordinates
        foreach ($events as &$ev) {
            $cc = strtoupper($ev['country'] ?? 'US');
            $ev['coords'] = self::$countryCoords[$cc] ?? self::$countryCoords['US'];
        }
        unset($ev);

        FileCache::set(self::$cacheKey, $events, CACHE_CALENDAR_TTL);
        return $events;
    }

    private static function fromFinnhub(int $days = 7): array
    {
        $key = Settings::get('finnhub_key');
        if (!$key || $key === 'YOUR_FINNHUB_KEY') return [];

        $from = date('Y-m-d');
        $to   = date('Y-m-d', strtotime("+{$days} days"));

        $url = FINNHUB_BASE . '/calendar/economic?' . http_build_query([
            'from'  => $from,
            'to'    => $to,
            'token' => $key,
        ]);

        $data = HttpClient::getJson($url, [], 'finnhub');
        if (!$data || !isset($data['economicCalendar'])) return [];

        $events = [];
        foreach ($data['economicCalendar'] as $ev) {
            if (!self::isHighImpact($ev)) continue;

            $events[] = [
                'event'       => $ev['event']        ?? 'Unknown Event',
                'country'     => strtoupper($ev['country'] ?? 'US'),
                'impact'      => self::mapImpact($ev['impact'] ?? ''),
                'datetime'    => $ev['time']          ?? date('c'),
                'actual'      => $ev['actual']        ?? null,
                'forecast'    => $ev['estimate']      ?? null,
                'previous'    => $ev['prev']          ?? null,
                'unit'        => $ev['unit']          ?? '',
                'description' => self::eventDescription($ev['event'] ?? ''),
            ];
        }

        usort($events, fn($a, $b) =>
            strtotime($a['datetime']) <=> strtotime($b['datetime'])
        );

        return $events;
    }

    private static function isHighImpact(array $ev): bool
    {
        // Only USD-related or gold-specific events
        $country = strtoupper($ev['country'] ?? '');
        if ($country !== 'US' && $country !== 'XAU') return false;

        // Must be high impact OR match keyword list
        $impact = strtolower($ev['impact'] ?? '');
        if (in_array($impact, ['high', '3'])) return true;

        $text = strtolower($ev['event'] ?? '');
        foreach (self::$highImpactKeywords as $kw) {
            if (str_contains($text, $kw)) return true;
        }
        return false;
    }

    private static function mapImpact(string|int $raw): string
    {
        $raw = strtolower((string)$raw);
        return match($raw) {
            '3', 'high'   => 'high',
            '2', 'medium' => 'medium',
            '1', 'low'    => 'low',
            default        => 'high',
        };
    }

    private static function eventDescription(string $event): string
    {
        $event = strtolower($event);
        $map = [
            'nonfarm'    => 'Key labor market indicator. Strong NFP = USD bullish, Gold bearish.',
            'cpi'        => 'Inflation measure. High CPI → Fed rate hike risk → Gold volatile.',
            'fomc'       => 'Fed policy decision. Rate hikes pressure Gold; cuts boost it.',
            'gdp'        => 'Economic growth indicator affecting USD and commodity demand.',
            'ppi'        => 'Producer inflation precursor. Impacts CPI outlook.',
            'fed'        => 'Federal Reserve communication affecting rate expectations.',
            'ism'        => 'Business activity index. Weak readings can lift Gold as safe haven.',
            'interest'   => 'Rate decisions directly affect Gold's opportunity cost.',
            'jobless'    => 'Labor market health indicator tied to Fed policy.',
            'retail'     => 'Consumer spending strength signals economic momentum.',
        ];
        foreach ($map as $kw => $desc) {
            if (str_contains($event, $kw)) return $desc;
        }
        return 'High-impact economic event affecting USD and Gold markets.';
    }

    /** Static fallback events if API is unavailable */
    private static function staticFallback(): array
    {
        return [
            [
                'event'       => 'FOMC Meeting Minutes',
                'country'     => 'US',
                'impact'      => 'high',
                'datetime'    => date('Y-m-d', strtotime('+3 days')) . 'T18:00:00Z',
                'actual'      => null,
                'forecast'    => null,
                'previous'    => null,
                'unit'        => '',
                'description' => 'Fed policy decision. Rate hikes pressure Gold; cuts boost it.',
            ],
            [
                'event'       => 'Initial Jobless Claims',
                'country'     => 'US',
                'impact'      => 'high',
                'datetime'    => date('Y-m-d', strtotime('+4 days')) . 'T12:30:00Z',
                'actual'      => null,
                'forecast'    => '215K',
                'previous'    => '220K',
                'unit'        => 'K',
                'description' => 'Labor market health indicator tied to Fed policy.',
            ],
        ];
    }
}
