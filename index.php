<?php
/**
 * XAUUSD Intelligence Terminal — Main Dashboard
 * index.php
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$siteTitle = Settings::get('site_title', 'XAUUSD Intelligence Terminal');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="XAUUSD Gold Intelligence Terminal — Live price, TradingView charts, economic calendar, market sentiment and gold news.">
<meta name="robots" content="index,follow">
<meta property="og:title" content="<?= htmlspecialchars($siteTitle) ?>">
<meta property="og:description" content="Professional gold market intelligence — live XAUUSD price, charts, sentiment and economic calendar.">
<title><?= htmlspecialchars($siteTitle) ?></title>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@300;400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Terminal CSS -->
<link rel="stylesheet" href="/assets/css/terminal.css">

<!-- Three.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  NAVIGATION                                                 -->
<!-- ══════════════════════════════════════════════════════════ -->
<nav id="nav">
  <div class="nav-logo">
    ⬡ XAUUSD
    <br>
    <span>INTELLIGENCE TERMINAL</span>
  </div>

  <div class="nav-ticker" style="display:flex;align-items:center;gap:16px">
    <div>
      <div class="nav-price" id="nav-price">—</div>
      <div class="nav-change" id="nav-change" style="font-size:11px"></div>
    </div>
    <div class="nav-status">
      <div class="status-dot"></div>
      <span id="market-clock" style="font-size:10px;letter-spacing:0.05em">—</span>
    </div>
  </div>

  <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-dim);text-align:right">
    <div id="price-source" class="badge signal-neutral mb-1">—</div>
    <div id="price-updated">—</div>
  </div>
</nav>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  TICKER BAR                                                 -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="ticker-bar" style="margin-top:56px">
  <div class="ticker-inner" id="ticker-inner">
    <span class="ticker-item" style="color:#64748b;padding:0 24px">
      XAUUSD INTELLIGENCE TERMINAL — LOADING LIVE DATA...
    </span>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  GLOBE HERO                                                 -->
<!-- ══════════════════════════════════════════════════════════ -->
<section id="globe-section">
  <canvas id="globe-canvas"></canvas>

  <div class="globe-overlay">
    <div class="globe-title">GOLD MARKET INTELLIGENCE</div>
    <div class="globe-subtitle">XAUUSD · REAL-TIME COMMAND CENTER</div>
    <div class="globe-price-hero" id="hero-price">
      <span class="loading-shimmer" style="display:inline-block;width:320px;height:80px;border-radius:4px"></span>
    </div>
    <div class="globe-price-label">XAU / USD · SPOT PRICE (USD PER TROY OUNCE)</div>
  </div>

  <button id="scroll-btn" class="scroll-hint" aria-label="Scroll to dashboard">
    ↓ &nbsp; ENTER TERMINAL &nbsp; ↓
  </button>
</section>

<!-- Tooltip -->
<div id="globe-tooltip" class="globe-tooltip"></div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  DASHBOARD                                                  -->
<!-- ══════════════════════════════════════════════════════════ -->
<main id="dashboard">

  <!-- SECTION LABEL -->
  <div class="section-label" style="margin-bottom:20px">MARKET COMMAND CENTER</div>

  <!-- ── Price Cells Row ── -->
  <div class="price-card panel-fadein" style="opacity:0">
    <?php
    $cells = [
      ['id' => 'cell-price',  'label' => 'SPOT PRICE'],
      ['id' => 'cell-bid',    'label' => 'BID'],
      ['id' => 'cell-ask',    'label' => 'ASK'],
      ['id' => 'cell-spread', 'label' => 'SPREAD'],
      ['id' => 'cell-change', 'label' => 'CHANGE'],
    ];
    foreach ($cells as $c): ?>
      <div class="price-cell">
        <div class="price-cell-label"><?= $c['label'] ?></div>
        <div class="price-cell-value gold" id="<?= $c['id'] ?>">—</div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Main 3-column grid ── -->
  <div class="dashboard-grid">

    <!-- ════════════════════════════════ LEFT COLUMN ══════════ -->
    <div>

      <!-- Sentiment Gauge -->
      <div class="panel panel-fadein" style="opacity:0;margin-bottom:16px">
        <div class="panel-header">
          <div style="display:flex;align-items:center">
            <div class="dot"></div>
            MARKET SENTIMENT
          </div>
          <span id="sentiment-direction" class="badge signal-neutral">—</span>
        </div>

        <div class="sentiment-gauge">
          <div class="gauge-ring">
            <svg viewBox="0 0 160 160" width="160" height="160">
              <circle class="gauge-bg" cx="80" cy="80" r="68"/>
              <circle class="gauge-fill" id="gauge-fill" cx="80" cy="80" r="68"
                      stroke-dasharray="427" stroke-dashoffset="427"/>
            </svg>
            <div class="gauge-text" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center">
              <div class="gauge-score" id="gauge-score" style="color:#64748b">—</div>
              <div class="gauge-label" id="gauge-label">LOADING</div>
            </div>
          </div>
        </div>

        <!-- Indicators -->
        <div id="indicators-list">
          <?php for ($i = 0; $i < 5; $i++): ?>
            <div class="indicator-row">
              <div class="loading-shimmer" style="width:80%;height:32px"></div>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Economic Calendar -->
      <div class="panel panel-fadein" style="opacity:0">
        <div class="panel-header">
          <div style="display:flex;align-items:center">
            <div class="dot"></div>
            ECONOMIC CALENDAR
          </div>
          <span style="font-size:9px;color:var(--text-dim)">USD · 7-DAY</span>
        </div>

        <div id="calendar-feed" style="max-height:480px;overflow-y:auto">
          <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="cal-event">
              <div class="loading-shimmer" style="width:100%;height:48px;border-radius:4px"></div>
            </div>
          <?php endfor; ?>
        </div>
      </div>

    </div>

    <!-- ════════════════════════════════ CENTRE COLUMN ════════ -->
    <div>

      <!-- TradingView Chart -->
      <div class="panel panel-fadein" style="opacity:0;margin-bottom:16px">
        <div class="panel-header">
          <div style="display:flex;align-items:center">
            <div class="dot"></div>
            XAUUSD LIVE CHART
          </div>
          <span style="font-size:9px;color:var(--text-dim)">POWERED BY TRADINGVIEW</span>
        </div>
        <div id="chart-container">
          <div id="tv-chart" style="height:420px"></div>
        </div>
      </div>

      <!-- Mini Market Stats Bar -->
      <div class="panel panel-fadein" style="opacity:0;padding:16px">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:0.2em;color:var(--text-dim);margin-bottom:6px">MARKET STATUS</div>
            <?php
            // Basic market session detection (UTC)
            $hour = (int)gmdate('H');
            $dow  = (int)gmdate('N'); // 1=Mon, 7=Sun
            $isForexOpen = ($dow >= 1 && $dow <= 5) && !($hour >= 22 && $dow === 5) && !($dow === 1 && $hour < 1);
            $session = match(true) {
                $hour >= 22 || $hour < 8  => 'SYDNEY / TOKYO',
                $hour >= 8  && $hour < 12  => 'LONDON OPEN',
                $hour >= 12 && $hour < 17  => 'LONDON / NY',
                $hour >= 17 && $hour < 22  => 'NEW YORK',
                default                     => 'TRANSITION',
            };
            $color = $isForexOpen ? '#00ff88' : '#ff4466';
            ?>
            <div style="color:<?= $color ?>;font-family:var(--font-logo);font-size:14px;font-weight:700">
              <?= $isForexOpen ? 'OPEN' : 'CLOSED' ?>
            </div>
            <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-dim);margin-top:3px"><?= $session ?></div>
          </div>

          <div>
            <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:0.2em;color:var(--text-dim);margin-bottom:6px">TROY OUNCE (SPOT)</div>
            <div style="color:var(--gold);font-family:var(--font-logo);font-size:14px;font-weight:700" id="cell-price-b">—</div>
            <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-dim);margin-top:3px">31.1035g</div>
          </div>

          <div>
            <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:0.2em;color:var(--text-dim);margin-bottom:6px">GOLD / OIL RATIO</div>
            <div style="color:var(--blue);font-family:var(--font-logo);font-size:14px;font-weight:700">~26x</div>
            <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-dim);margin-top:3px">HISTORICAL AVG</div>
          </div>
        </div>
      </div>

      <!-- Key Levels Reference -->
      <div class="panel panel-fadein" style="opacity:0;margin-top:16px;padding:0">
        <div class="panel-header">
          <div style="display:flex;align-items:center">
            <div class="dot"></div>
            KEY GOLD LEVELS
          </div>
          <span style="font-size:9px;color:var(--text-dim)">REFERENCE</span>
        </div>
        <?php
        $levels = [
            ['$3,500', 'All-Time High',        'red'],
            ['$3,200', 'Major Resistance',      'gold'],
            ['$3,000', 'Psychological Round',   'gold'],
            ['$2,750', 'Strong Support',        'blue'],
            ['$2,500', 'Previous ATH (2024)',   'blue'],
            ['$1,920', 'Pre-2024 ATH',          'dim'],
        ];
        foreach ($levels as [$price, $label, $color]): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 16px;border-bottom:1px solid var(--border)">
            <span style="font-family:var(--font-logo);font-size:14px;font-weight:700;color:var(--<?= $color ?>)"><?= $price ?></span>
            <span style="font-family:var(--font-mono);font-size:10px;color:var(--text-dim)"><?= $label ?></span>
          </div>
        <?php endforeach; ?>
      </div>

    </div>

    <!-- ════════════════════════════════ RIGHT COLUMN ═════════ -->
    <div>

      <!-- News Feed -->
      <div class="panel panel-fadein" style="opacity:0;height:100%">
        <div class="panel-header">
          <div style="display:flex;align-items:center">
            <div class="dot"></div>
            GOLD INTELLIGENCE NEWS
          </div>
          <span style="font-size:9px;color:var(--text-dim)">LIVE FEED</span>
        </div>

        <div id="news-feed" style="max-height:920px;overflow-y:auto">
          <?php for ($i = 0; $i < 5; $i++): ?>
            <div class="news-item">
              <div class="loading-shimmer" style="height:40px;width:100%;border-radius:3px"></div>
            </div>
          <?php endfor; ?>
        </div>
      </div>

    </div>

  </div><!-- /dashboard-grid -->

  <!-- ── Footer ── -->
  <div class="section-label" style="margin-top:40px;margin-bottom:20px">SYSTEM STATUS</div>

  <div class="panel" style="padding:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-dim)">
      XAUUSD INTELLIGENCE TERMINAL v<?= APP_VERSION ?> &nbsp;|&nbsp;
      Data sources: Alpha Vantage · Finnhub · Metals API · NewsAPI &nbsp;|&nbsp;
      Charts: TradingView
    </div>
    <div style="display:flex;gap:16px;align-items:center">
      <div class="nav-status">
        <div class="status-dot"></div>
        <span style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.1em;color:var(--text-dim)">ALL SYSTEMS OPERATIONAL</span>
      </div>
      <a href="/admin/" style="font-family:var(--font-mono);font-size:9px;letter-spacing:0.2em;color:var(--text-faint);hover:color:var(--text-dim);transition:color 0.2s">ADMIN</a>
    </div>
  </div>

</main>

<!-- Legal footer -->
<div style="text-align:center;padding:24px;font-family:var(--font-mono);font-size:10px;color:var(--text-faint);border-top:1px solid var(--border);margin-top:4px">
  ⚠ FOR INFORMATIONAL PURPOSES ONLY. NOT FINANCIAL ADVICE. GOLD PRICES MAY BE DELAYED 15 MINUTES. PAST PERFORMANCE DOES NOT GUARANTEE FUTURE RESULTS.
</div>

<!-- ── Scripts ─────────────────────────────────────────────── -->
<script src="/assets/js/globe.js" defer></script>
<script src="/assets/js/terminal.js" defer></script>

</body>
</html>
