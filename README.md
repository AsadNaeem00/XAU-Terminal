# ⬡ XAUUSD Intelligence Terminal

> A production-ready, self-hosted Gold market intelligence dashboard built with PHP 8+, MySQL, Three.js, TradingView, and a cyberpunk/financial command center aesthetic. Designed for deployment on Hostinger Business Shared Hosting — no Node.js, no React, no Docker required.

![License](https://img.shields.io/badge/license-MIT-gold)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange)
![Hosting](https://img.shields.io/badge/Hostinger-Business%20Shared-green)

---

## 📸 Features

| Feature | Description |
|---|---|
| **Live Gold Price** | XAUUSD spot price with bid/ask/spread via Alpha Vantage → Finnhub → Metals API fallback chain |
| **TradingView Chart** | Embedded professional candlestick chart with full toolbar |
| **3D Rotating Globe** | Three.js Earth with event pins for upcoming economic releases |
| **Economic Calendar** | USD + Gold high-impact events: NFP, CPI, FOMC, GDP, Rate Decisions |
| **Gold News Feed** | Aggregated headlines from NewsAPI + Finnhub with auto sentiment tagging |
| **Market Sentiment** | Composite bull/bear score derived from 5 weighted indicators |
| **Admin Panel** | API health dashboard, log viewer, cache manager |
| **Security** | CSRF, rate limiting, session hardening, CSP headers, input sanitisation |
| **Cyberpunk UI** | Orbitron/Rajdhani/Share Tech Mono fonts, neon glows, scanline overlay, animated ticker |

---

## 🗂️ Project Structure

```
xauusd-terminal/
├── index.php                    # Main dashboard (public)
├── error.php                    # Custom error handler
├── setup.php                    # CLI environment checker
├── database.sql                 # Full DB schema + seed data
├── .htaccess                    # Apache security, routing, compression
├── .gitignore
│
├── includes/                    # PHP backend classes
│   ├── bootstrap.php            # Autoloader + global init
│   ├── config.php               # Constants & defaults
│   ├── config.local.php.template  # → copy to config.local.php
│   ├── Database.php             # PDO singleton
│   ├── Logger.php               # DB + file logging
│   ├── FileCache.php            # Filesystem micro-cache
│   ├── Settings.php             # DB key-value settings
│   ├── Security.php             # CSRF, auth, rate limit, headers
│   ├── HttpClient.php           # cURL wrapper with logging
│   ├── GoldPrice.php            # Price service (3-source fallback)
│   ├── NewsAggregator.php       # NewsAPI + Finnhub news
│   ├── EconomicCalendar.php     # Finnhub economic events
│   └── MarketSentiment.php      # Composite sentiment engine
│
├── api/                         # AJAX JSON endpoints
│   ├── price.php                # GET /api/price
│   ├── news.php                 # GET /api/news
│   ├── calendar.php             # GET /api/calendar
│   └── sentiment.php            # GET /api/sentiment
│
├── admin/                       # Protected admin panel
│   ├── .htaccess
│   ├── index.php                # Dashboard: API health + logs
│   ├── login.php                # Admin login form
│   └── logout.php
│
├── assets/
│   ├── css/
│   │   └── terminal.css         # Full cyberpunk stylesheet
│   └── js/
│       ├── globe.js             # Three.js globe renderer
│       └── terminal.js          # Dashboard logic, AJAX polling
│
├── cache/                       # File cache (auto-created, gitignored)
│   └── .htaccess                # Deny all
└── logs/                        # App logs (auto-created, gitignored)
    └── .htaccess                # Deny all
```

---

## 🚀 Deployment Guide — Hostinger Business Shared Hosting

### Prerequisites

- Hostinger Business Shared Hosting plan (PHP 8.0+, MySQL 8.0+, Apache with mod_rewrite)
- Your own subdomain configured in hPanel (e.g., `terminal.yourdomain.com`)
- API keys for: Alpha Vantage, Finnhub, Metals API, NewsAPI (all free tier)

---

### Step 1 — Configure the Subdomain

1. Log into **hPanel** → **Domains** → **Subdomains**
2. Create subdomain: `terminal.yourdomain.com`
3. Set the **Document Root** to: `public_html/terminal` (or any directory you choose)
4. Wait for DNS propagation (~1–5 minutes on Hostinger)

---

### Step 2 — Upload Files

**Option A — File Manager (recommended for beginners):**
1. hPanel → **File Manager** → navigate to your subdomain document root
2. Upload the entire project as a ZIP, then extract it there
3. Ensure `.htaccess` is visible (enable "Show hidden files" in File Manager)

**Option B — FTP (FileZilla):**
1. hPanel → **FTP Accounts** → create an FTP account
2. Connect with FileZilla, upload all files to the document root
3. Verify `.htaccess`, `cache/`, and `logs/` are present

**Option C — Git (via SSH):**
```bash
# Connect via SSH (hPanel → Advanced → SSH Access)
ssh u123456789@yourdomain.com

cd ~/domains/terminal.yourdomain.com/public_html
git clone https://github.com/yourusername/xauusd-terminal.git .
```

---

### Step 3 — Create the Database

1. hPanel → **Databases** → **MySQL Databases**
2. Create a new database: `u123456789_xauusd` (Hostinger prefixes with your account ID)
3. Create a new user: `u123456789_terminal` with a **strong password**
4. **Add the user to the database** with **All Privileges**
5. Open **phpMyAdmin** → select your database → **Import** tab
6. Upload and run `database.sql`

---

### Step 4 — Configure `config.local.php`

In File Manager or SSH, create `includes/config.local.php` by copying the template:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_xauusd');      // your actual DB name
define('DB_USER', 'u123456789_terminal');     // your actual DB user
define('DB_PASS', 'YourStrongDBPassword!');   // your actual DB password
define('APP_ENV', 'production');
```

> ⚠️ **Never commit this file.** It is excluded by `.gitignore`.

---

### Step 5 — Set API Keys in Database

In **phpMyAdmin**, run these SQL queries (replace with your real keys):

```sql
UPDATE settings SET value = 'AV_YOUR_KEY_HERE'       WHERE `key` = 'alpha_vantage_key';
UPDATE settings SET value = 'FINNHUB_YOUR_KEY_HERE'   WHERE `key` = 'finnhub_key';
UPDATE settings SET value = 'METALS_YOUR_KEY_HERE'    WHERE `key` = 'metals_api_key';
UPDATE settings SET value = 'NEWSAPI_YOUR_KEY_HERE'   WHERE `key` = 'news_api_key';
```

#### Where to get API keys (all free):

| Service | URL | Free Limit |
|---|---|---|
| Alpha Vantage | https://www.alphavantage.co/support/#api-key | 25 calls/day |
| Finnhub | https://finnhub.io/register | 60 calls/min |
| Metals API | https://metals-api.com/register | 50 calls/month |
| NewsAPI | https://newsapi.org/register | 100 calls/day |

---

### Step 6 — Change Admin Password

The default admin credentials seeded by `database.sql` are:
- **Username:** `admin`
- **Password:** `Admin@Terminal2024`

**Change the password immediately** via phpMyAdmin:

```sql
-- Generate a new bcrypt hash in PHP first:
-- php -r "echo password_hash('YourNewPassword!', PASSWORD_BCRYPT, ['cost'=>12]);"
UPDATE admin_users
SET password_hash = '$2y$12$YOUR_GENERATED_HASH_HERE'
WHERE username = 'admin';
```

Or use the PHP CLI if SSH access is available:
```bash
php -r "echo password_hash('YourNewPassword!', PASSWORD_BCRYPT, ['cost'=>12]);"
```

---

### Step 7 — Set Directory Permissions

Via SSH or File Manager, ensure:

```bash
chmod 750 cache/
chmod 750 logs/
chmod 644 .htaccess
chmod 644 index.php
chmod 600 includes/config.local.php
```

---

### Step 8 — Verify Installation

Run the setup checker via CLI (if SSH available):
```bash
php setup.php
```

Or visit in browser: `https://terminal.yourdomain.com` — you should see the globe hero and dashboard loading.

Visit the admin panel: `https://terminal.yourdomain.com/admin/`

---

### Step 9 — Enable SSL (Free on Hostinger)

1. hPanel → **SSL** → **Let's Encrypt**
2. Select `terminal.yourdomain.com` → Install
3. The `.htaccess` already forces HTTPS redirect

---

## ⚙️ Configuration Reference

### Cache TTLs (in `includes/config.php`)

| Constant | Default | Purpose |
|---|---|---|
| `CACHE_PRICE_TTL` | 30s | Gold price micro-cache |
| `CACHE_NEWS_TTL` | 300s | News headlines |
| `CACHE_CALENDAR_TTL` | 600s | Economic calendar |
| `CACHE_SENTIMENT_TTL` | 120s | Sentiment composite |

> Even with "always live" UX feel, these micro-caches prevent hammering free-tier API rate limits on concurrent visitors.

### Rate Limiting (per IP)

| Constant | Default |
|---|---|
| `RATE_LIMIT_WINDOW` | 60 seconds |
| `RATE_LIMIT_MAX` | 60 requests per window |

Adjust in `includes/config.php` if needed.

---

## 🔌 API Endpoints

All endpoints return JSON. Used internally by the dashboard via AJAX polling.

| Endpoint | Method | Params | Description |
|---|---|---|---|
| `/api/price.php` | GET | — | Live XAUUSD price |
| `/api/news.php` | GET | `limit` (max 50) | Gold & USD news |
| `/api/calendar.php` | GET | `days` (max 30) | Economic events |
| `/api/sentiment.php` | GET | — | Composite sentiment |

### Example Response — `/api/price.php`

```json
{
  "ok": true,
  "data": {
    "price": 2387.50,
    "bid": 2387.00,
    "ask": 2388.00,
    "spread": 1.00,
    "change": 12.30,
    "change_pct": 0.518,
    "high": 2395.10,
    "low": 2371.40,
    "timestamp": "2024-11-15 14:32:00",
    "source": "finnhub",
    "delay_min": 0,
    "cached": false
  },
  "ts": 1731678720
}
```

---

## 🛡️ Security Implementation

| Layer | Implementation |
|---|---|
| **HTTPS** | Forced via `.htaccess` RewriteRule |
| **CSP** | Strict Content-Security-Policy via PHP headers |
| **CSRF** | Token-per-session on all admin POST forms |
| **XSS** | `htmlspecialchars()` on all output, `strip_tags()` on all input |
| **SQL Injection** | 100% PDO prepared statements — no raw queries |
| **Rate Limiting** | Per-IP file-based counter, 60 req/60s |
| **Session** | HttpOnly, SameSite=Strict, HTTPS-only cookies, periodic regeneration |
| **Directory Traversal** | `.htaccess` denies `cache/`, `logs/`, `includes/` |
| **Bot Blocking** | User-agent blocklist in `.htaccess` |
| **Password Hashing** | `PASSWORD_BCRYPT` cost=12, auto-upgrade on login |
| **Headers** | X-Frame-Options DENY, X-Content-Type-Options nosniff, Referrer-Policy |

---

## 🎨 Design System

### Color Palette

| Token | Hex | Usage |
|---|---|---|
| `--gold` | `#fbbf24` | Primary accent, price display |
| `--green` | `#00ff88` | Bullish, positive change |
| `--red` | `#ff4466` | Bearish, negative, alerts |
| `--blue` | `#00d4ff` | Forecast, information |
| `--bg` | `#020408` | Page background |
| `--bg-panel` | `rgba(8,14,26,0.92)` | Panel backgrounds |

### Typography

| Font | Usage |
|---|---|
| **Orbitron** | Logo, headlines, price display |
| **Rajdhani** | Body, labels, panel text |
| **Share Tech Mono** | Ticker, timestamps, code-style values |

---

## 📊 Data Flow

```
Browser (AJAX every 30s)
    ↓
/api/price.php
    ↓
FileCache::get('gold_price')  →  [HIT] → return cached JSON
    ↓ [MISS]
GoldPrice::fromAlphaVantage()
    ↓ [FAIL]
GoldPrice::fromFinnhub()
    ↓ [FAIL]
GoldPrice::fromMetalsApi()
    ↓ [FAIL]
GoldPrice::fallbackStatic()
    ↓
Logger::apiLog() → api_logs table
FileCache::set('gold_price', data, 30s)
    ↓
JSON response → terminal.js → DOM update
```

---

## 🔄 Update & Maintenance

### Flushing Cache

Admin panel → **FLUSH ALL CACHE** button, or via phpMyAdmin:
```bash
# SSH
rm -f cache/*.cache
```

### Viewing Logs

Admin panel → Application Logs section, or:
```bash
# SSH
tail -f logs/app-$(date +%Y-%m-%d).log
```

### Rotating Old Logs

Add a daily cron job in hPanel → **Cron Jobs**:
```bash
# Delete logs older than 30 days, run daily at 2 AM
0 2 * * * find /home/u123456789/domains/terminal.yourdomain.com/public_html/logs -name "*.log" -mtime +30 -delete
```

---

## 🔑 Admin Panel

URL: `https://terminal.yourdomain.com/admin/`

| Section | Description |
|---|---|
| API Health | 24-hour call count, success rate, avg response time per API |
| API Key Status | Shows which keys are configured vs. missing |
| Cache Management | Flush all cache files with one click |
| System Info | PHP version, server time, app version |
| Application Logs | Last 50 log entries with level filtering |

---

## 🗺️ Future Expansion Ideas

- **WebSocket price feed** — replace AJAX polling with persistent connection for true real-time updates
- **Price alerts** — email/SMS notifications when XAUUSD hits user-defined levels
- **Multi-asset** — add XAGUSD (silver), XPTUSD (platinum), DXY
- **Custom indicators** — RSI, MACD, Bollinger Bands via Alpha Vantage Technical Indicators API
- **Portfolio tracker** — user accounts with personal position tracking
- **Telegram bot** — push economic event alerts to a channel
- **Dark pool / COT data** — CFTC Commitments of Traders integration
- **AI narrative** — brief market summary updated daily

---

## ⚠️ Disclaimer

This application is for **informational and educational purposes only**. It does not constitute financial advice. Gold prices displayed may be delayed by up to 15 minutes on the free Alpha Vantage tier. Always verify prices with your broker before making trading decisions. Past market performance does not guarantee future results.

---

## 📄 License

MIT License — see `LICENSE` for details. Free for personal and commercial use with attribution.

---

## 👤 Author

Built as a production-ready financial intelligence terminal. Designed for Hostinger shared hosting with zero server-side dependencies beyond PHP 8 and MySQL.

---

*XAUUSD Intelligence Terminal v1.0.0*
