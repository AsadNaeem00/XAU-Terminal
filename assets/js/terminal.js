/**
 * XAUUSD Intelligence Terminal — Main Dashboard JS
 * assets/js/terminal.js
 */

'use strict';

/* ── Config ─────────────────────────────────────────────────── */
const REFRESH_PRICE    = 30000;   // 30s
const REFRESH_NEWS     = 300000;  // 5min
const REFRESH_CALENDAR = 600000;  // 10min
const REFRESH_SENTIMENT= 120000;  // 2min

/* ── State ──────────────────────────────────────────────────── */
const state = {
  price:     null,
  prevPrice: null,
  news:      [],
  calendar:  [],
  sentiment: null,
};

/* ── DOM Refs ────────────────────────────────────────────────── */
const $ = id => document.getElementById(id);

/* ── Utility ─────────────────────────────────────────────────── */
function fmt(n, dec = 2) {
  if (n === null || n === undefined || n === 0) return '—';
  return Number(n).toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

function fmtChange(n, pct) {
  if (n === null || n === undefined) return '';
  const sign = n >= 0 ? '+' : '';
  return `${sign}${fmt(n, 2)} (${sign}${fmt(pct, 3)}%)`;
}

function timeAgo(dt) {
  const diff = Math.floor((Date.now() - new Date(dt).getTime()) / 1000);
  if (diff < 60)   return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400)return `${Math.floor(diff / 3600)}h ago`;
  return new Date(dt).toLocaleDateString();
}

function formatEventTime(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  return d.toUTCString().slice(17, 22) + ' UTC';
}

function formatEventDate(dt) {
  if (!dt) return '';
  const d = new Date(dt);
  const today = new Date();
  const diff  = Math.floor((d - today) / 86400000);
  if (diff < 0)   return 'Past';
  if (diff === 0) return 'TODAY';
  if (diff === 1) return 'TOMORROW';
  return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}

/* ── API Fetch ───────────────────────────────────────────────── */
async function apiFetch(endpoint) {
  try {
    const res = await fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (e) {
    console.warn(`[Terminal] API error ${endpoint}:`, e.message);
    return null;
  }
}

/* ── Price ───────────────────────────────────────────────────── */
async function refreshPrice() {
  const data = await apiFetch('/api/price.php');
  if (!data?.ok) return;

  const p = data.data;
  state.prevPrice = state.price;
  state.price     = p;

  updatePriceDisplay(p);
  updateTicker(p);
}

function updatePriceDisplay(p) {
  const price  = p.price || 0;
  const change = p.change;
  const pct    = p.change_pct;
  const isUp   = change === null ? null : change >= 0;

  // Hero globe price
  const heroEl = $('hero-price');
  if (heroEl) {
    heroEl.textContent = price ? '$' + fmt(price) : '—';
    heroEl.className = 'globe-price-hero' + (isUp === null ? '' : isUp ? ' text-green' : ' text-red');
  }

  // Nav price
  const navEl = $('nav-price');
  if (navEl) navEl.textContent = price ? '$' + fmt(price) : '—';

  // Nav change
  const navChg = $('nav-change');
  if (navChg) {
    navChg.textContent = change !== null ? fmtChange(change, pct) : '';
    navChg.className   = 'nav-change ' + (isUp === null ? '' : isUp ? 'up' : 'down');
  }

  // Price cells
  setCellVal('cell-price',  price ? '$' + fmt(price) : '—', isUp === null ? 'gold' : isUp ? 'green' : 'red');
  setCellVal('cell-bid',    p.bid  ? '$' + fmt(p.bid)   : '—', 'gold');
  setCellVal('cell-ask',    p.ask  ? '$' + fmt(p.ask)   : '—', 'gold');
  setCellVal('cell-spread', p.spread ? fmt(p.spread, 2) : '—', 'gold');

  if (change !== null) {
    setCellVal('cell-change', fmtChange(change, pct), isUp ? 'green' : 'red');
  }

  // Source badge
  const srcEl = $('price-source');
  if (srcEl) {
    const src = p.source || 'unknown';
    const delay = p.delay_min ? ` (${p.delay_min}min delay)` : '';
    srcEl.textContent = src.replace('_', ' ').toUpperCase() + delay;
    srcEl.className = 'badge ' + (p.source === 'static' ? 'signal-bearish' : 'signal-neutral');
  }

  // Last updated
  const updEl = $('price-updated');
  if (updEl) updEl.textContent = 'Updated: ' + (p.timestamp ? new Date(p.timestamp.replace(' ', 'T') + 'Z').toLocaleTimeString() : '—');

  // Flash animation
  flashUpdate('cell-price');
}

function setCellVal(id, val, colorClass = '') {
  const el = $(id);
  if (!el) return;
  el.textContent = val;
  el.className   = 'price-cell-value ' + colorClass;
}

function flashUpdate(id) {
  const el = $(id);
  if (!el) return;
  el.style.transition = 'none';
  el.style.textShadow = '0 0 30px #fbbf24, 0 0 60px rgba(251,191,36,0.5)';
  setTimeout(() => {
    el.style.transition = 'text-shadow 1.5s ease';
    el.style.textShadow = '';
  }, 50);
}

/* ── Ticker bar ──────────────────────────────────────────────── */
function updateTicker(p) {
  const inner = $('ticker-inner');
  if (!inner || !p.price) return;

  const items = [
    { label: 'XAU/USD', val: '$' + fmt(p.price), up: p.change >= 0 },
    { label: 'BID',     val: '$' + fmt(p.bid),   up: true },
    { label: 'ASK',     val: '$' + fmt(p.ask),   up: true },
    { label: 'SPREAD',  val: fmt(p.spread, 2),   up: true },
    p.high ? { label: 'HIGH', val: '$' + fmt(p.high), up: true }   : null,
    p.low  ? { label: 'LOW',  val: '$' + fmt(p.low),  up: false }  : null,
    p.open ? { label: 'OPEN', val: '$' + fmt(p.open), up: true }   : null,
  ].filter(Boolean);

  // Duplicate for seamless loop
  const html = [...items, ...items].map(it =>
    `<span class="ticker-item">
       <span style="color:#64748b;margin-right:4px">${it.label}</span>
       <span style="color:${it.up ? '#00ff88' : '#ff4466'}">${it.val}</span>
     </span>`
  ).join('');

  inner.innerHTML = html;
}

/* ── News ────────────────────────────────────────────────────── */
async function refreshNews() {
  const data = await apiFetch('/api/news.php?limit=20');
  if (!data?.ok) return;

  state.news = data.data || [];
  renderNews();
}

function renderNews() {
  const container = $('news-feed');
  if (!container) return;

  if (!state.news.length) {
    container.innerHTML = '<div class="p-4 text-center" style="color:#64748b;font-size:12px">No news available. Check API keys.</div>';
    return;
  }

  container.innerHTML = state.news.map(a => `
    <a href="${a.url}" target="_blank" rel="noopener noreferrer" class="news-item block">
      <div class="news-title">${a.title}</div>
      <div class="news-meta">
        <span class="news-sentiment sentiment-${a.sentiment}">${a.sentiment}</span>
        <span>${a.source}</span>
        <span>${timeAgo(a.published_at)}</span>
      </div>
    </a>
  `).join('');
}

/* ── Calendar ────────────────────────────────────────────────── */
async function refreshCalendar() {
  const data = await apiFetch('/api/calendar.php?days=7');
  if (!data?.ok) return;

  state.calendar = data.data || [];
  renderCalendar();

  // Update globe pins
  if (window.GlobeRenderer) {
    GlobeRenderer.addEventPins(state.calendar);
  }
}

function renderCalendar() {
  const container = $('calendar-feed');
  if (!container) return;

  if (!state.calendar.length) {
    container.innerHTML = '<div class="p-4 text-center" style="color:#64748b;font-size:12px">No upcoming events. Configure Finnhub API key.</div>';
    return;
  }

  // Group by date
  const grouped = {};
  state.calendar.forEach(ev => {
    const dateKey = formatEventDate(ev.datetime);
    if (!grouped[dateKey]) grouped[dateKey] = [];
    grouped[dateKey].push(ev);
  });

  container.innerHTML = Object.entries(grouped).map(([date, events]) => `
    <div class="px-3 py-1.5" style="background:rgba(251,191,36,0.04);border-bottom:1px solid rgba(251,191,36,0.08)">
      <span style="font-family:var(--font-mono);font-size:9px;letter-spacing:0.2em;color:#fbbf24">${date}</span>
    </div>
    ${events.map(ev => `
      <div class="cal-event">
        <div class="cal-impact impact-${ev.impact}"></div>
        <div class="cal-time">${formatEventTime(ev.datetime)}</div>
        <div class="cal-body">
          <div class="cal-name" title="${ev.description || ''}">${ev.event}</div>
          <div class="cal-values">
            ${ev.forecast !== null ? `<span class="cal-forecast">F: ${ev.forecast}${ev.unit}</span>` : ''}
            ${ev.previous !== null ? `<span class="cal-prev">P: ${ev.previous}${ev.unit}</span>` : ''}
            ${ev.actual   !== null ? `<span class="cal-actual">A: ${ev.actual}${ev.unit}</span>`   : ''}
          </div>
        </div>
        <div>
          <span class="badge signal-${ev.impact === 'high' ? 'bearish' : 'neutral'}" style="font-size:8px">
            ${ev.impact}
          </span>
        </div>
      </div>
    `).join('')}
  `).join('');
}

/* ── Sentiment ───────────────────────────────────────────────── */
async function refreshSentiment() {
  const data = await apiFetch('/api/sentiment.php');
  if (!data?.ok) return;

  state.sentiment = data.data;
  renderSentiment(data.data);
}

function renderSentiment(s) {
  if (!s) return;

  const score     = s.score || 0;
  const label     = s.label || 'Neutral';
  const dir       = s.direction || 'neutral';
  const color     = dir === 'bullish' ? '#00ff88' : dir === 'bearish' ? '#ff4466' : '#64748b';

  // Gauge
  const r            = 68;
  const circumference = 2 * Math.PI * r;
  const fillPct      = Math.abs(score) / 100;
  const offset       = circumference * (1 - fillPct);

  const gaugeEl    = $('gauge-fill');
  const scoreEl    = $('gauge-score');
  const labelEl    = $('gauge-label');
  const dirBadge   = $('sentiment-direction');

  if (gaugeEl) {
    gaugeEl.style.strokeDasharray  = circumference;
    gaugeEl.style.strokeDashoffset = offset;
    gaugeEl.style.stroke = color;
  }
  if (scoreEl) { scoreEl.textContent = (score >= 0 ? '+' : '') + score; scoreEl.style.color = color; }
  if (labelEl)  { labelEl.textContent = label; }
  if (dirBadge) {
    dirBadge.textContent = dir.toUpperCase();
    dirBadge.className   = 'badge signal-' + dir;
  }

  // Indicators
  const indContainer = $('indicators-list');
  if (indContainer && s.indicators) {
    indContainer.innerHTML = s.indicators.map(ind => `
      <div class="indicator-row">
        <div>
          <div class="indicator-label">${ind.label}</div>
          <div class="indicator-value">${ind.value}</div>
        </div>
        <span class="signal-badge signal-${ind.signal}">${ind.signal}</span>
      </div>
    `).join('');
  }
}

/* ── Live clock ──────────────────────────────────────────────── */
function startClock() {
  const el = $('market-clock');
  if (!el) return;

  function tick() {
    const now    = new Date();
    const utcStr = now.toUTCString().slice(17, 25);
    const nyTZ   = new Date().toLocaleTimeString('en-US', { timeZone: 'America/New_York', hour12: false });
    const lonTZ  = new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/London',    hour12: false });
    const tokTZ  = new Date().toLocaleTimeString('en-US', { timeZone: 'Asia/Tokyo',       hour12: false });

    el.innerHTML = `
      <span style="color:#64748b">UTC</span> ${utcStr} &nbsp;|&nbsp;
      <span style="color:#64748b">NY</span> ${nyTZ} &nbsp;|&nbsp;
      <span style="color:#64748b">LON</span> ${lonTZ} &nbsp;|&nbsp;
      <span style="color:#64748b">TOK</span> ${tokTZ}
    `;
  }

  tick();
  setInterval(tick, 1000);
}

/* ── TradingView widget ──────────────────────────────────────── */
function initTradingView() {
  const container = $('tv-chart');
  if (!container || typeof TradingView === 'undefined') return;

  new TradingView.widget({
    autosize:        true,
    symbol:          'OANDA:XAUUSD',
    interval:        '60',
    timezone:        'UTC',
    theme:           'dark',
    style:           '1',
    locale:          'en',
    toolbar_bg:      '#020408',
    enable_publishing: false,
    hide_top_toolbar: false,
    hide_legend:     false,
    save_image:      false,
    container_id:    'tv-chart',
    backgroundColor: '#020408',
    gridColor:       'rgba(251,191,36,0.05)',
    overrides: {
      'paneProperties.background':          '#020408',
      'paneProperties.backgroundType':      'solid',
      'scalesProperties.lineColor':         'rgba(251,191,36,0.1)',
      'scalesProperties.textColor':         '#64748b',
      'mainSeriesProperties.candleStyle.upColor':        '#00ff88',
      'mainSeriesProperties.candleStyle.downColor':      '#ff4466',
      'mainSeriesProperties.candleStyle.wickUpColor':    '#00ff88',
      'mainSeriesProperties.candleStyle.wickDownColor':  '#ff4466',
      'mainSeriesProperties.candleStyle.borderUpColor':  '#00ff88',
      'mainSeriesProperties.candleStyle.borderDownColor':'#ff4466',
    },
  });
}

/* ── Boot ────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {

  // Clock
  startClock();

  // Globe
  if (window.GlobeRenderer) {
    GlobeRenderer.init('globe-canvas');
  }

  // TradingView (async load)
  const tvScript = document.createElement('script');
  tvScript.src   = 'https://s3.tradingview.com/tv.js';
  tvScript.async = true;
  tvScript.onload = initTradingView;
  document.head.appendChild(tvScript);

  // Initial data load
  await refreshPrice();
  await refreshCalendar();
  await refreshNews();
  await refreshSentiment();

  // Polling intervals
  setInterval(refreshPrice,     REFRESH_PRICE);
  setInterval(refreshNews,      REFRESH_NEWS);
  setInterval(refreshCalendar,  REFRESH_CALENDAR);
  setInterval(refreshSentiment, REFRESH_SENTIMENT);

  // Smooth scroll to dashboard
  const scrollBtn = $('scroll-btn');
  if (scrollBtn) {
    scrollBtn.addEventListener('click', () => {
      $('dashboard')?.scrollIntoView({ behavior: 'smooth' });
    });
  }

  // Intersection fade-ins
  const panels = document.querySelectorAll('.panel-fadein');
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('fade-in-up');
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.1 });
  panels.forEach(p => observer.observe(p));

});
