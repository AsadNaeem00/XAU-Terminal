<?php
/**
 * Admin Dashboard — admin/index.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Security::requireAdmin();

// Handle logout
if (isset($_GET['logout'])) {
    Security::adminLogout();
    header('Location: /admin/login.php');
    exit;
}

// Handle cache flush
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flush_cache'])) {
    if (Security::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $count = FileCache::flush();
        Logger::info('admin', "Cache flushed by admin — $count files removed");
        $message = "Cache flushed: $count files removed.";
    }
}

$apiHealth  = Logger::getApiHealth();
$recentLogs = Logger::getRecentLogs(50);
$settings   = Settings::all();
$adminUser  = Security::sanitize($_SESSION['admin_user'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — XAUUSD Terminal</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { background: #030712; font-family: 'Courier New', monospace; color: #e5e7eb; }
  .panel { background: rgba(17,24,39,0.8); border: 1px solid rgba(251,191,36,0.15); border-radius: 8px; }
  .tag-high    { background: rgba(239,68,68,0.15); color: #f87171; }
  .tag-warning { background: rgba(245,158,11,0.15); color: #fbbf24; }
  .tag-info    { background: rgba(59,130,246,0.15); color: #60a5fa; }
  .tag-success { background: rgba(16,185,129,0.15); color: #34d399; }
  .scrollbox   { max-height: 380px; overflow-y: auto; }
  .scrollbox::-webkit-scrollbar { width: 4px; }
  .scrollbox::-webkit-scrollbar-thumb { background: rgba(251,191,36,0.3); border-radius: 2px; }
</style>
</head>
<body class="min-h-screen">

<!-- Header -->
<header class="border-b border-yellow-400/10 px-6 py-4 flex items-center justify-between">
  <div>
    <span class="text-yellow-400 font-bold tracking-widest">⬡ XAUUSD</span>
    <span class="text-gray-600 text-xs ml-3 tracking-widest">ADMIN TERMINAL</span>
  </div>
  <div class="flex items-center gap-4">
    <span class="text-gray-500 text-xs">Logged in as <span class="text-yellow-400"><?= $adminUser ?></span></span>
    <a href="/admin/?logout=1" class="text-xs text-red-400 hover:text-red-300 tracking-widest border border-red-400/30 px-3 py-1 rounded hover:bg-red-400/10 transition">LOGOUT</a>
    <a href="/" class="text-xs text-gray-500 hover:text-gray-300 tracking-widest">VIEW SITE →</a>
  </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-8">

  <?php if ($message): ?>
    <div class="mb-6 p-4 bg-green-900/20 border border-green-500/30 rounded text-green-400 text-sm">
      ✓ <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <!-- Top Row: Stats -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php
    $apiCount   = count($apiHealth);
    $successArr = array_filter($apiHealth, fn($a) => ($a['successful'] / max(1, $a['total_calls'])) > 0.8);
    $cacheFiles = count(glob(CACHE_PATH . '/*.cache') ?: []);
    $logCount   = count($recentLogs);
    $stats = [
        ['API Sources', $apiCount, 'text-yellow-400'],
        ['Healthy APIs', count($successArr), 'text-green-400'],
        ['Cache Files', $cacheFiles, 'text-blue-400'],
        ['Recent Logs', $logCount, 'text-purple-400'],
    ];
    foreach ($stats as [$label, $val, $color]): ?>
      <div class="panel p-5 text-center">
        <div class="<?= $color ?> text-3xl font-bold"><?= $val ?></div>
        <div class="text-gray-500 text-xs tracking-widest mt-1"><?= $label ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Two columns -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- API Health -->
    <div class="panel p-6">
      <h2 class="text-yellow-400 text-xs tracking-widest mb-4">API HEALTH (LAST 24H)</h2>
      <?php if (empty($apiHealth)): ?>
        <p class="text-gray-600 text-sm">No API calls logged yet.</p>
      <?php else: foreach ($apiHealth as $api):
        $rate = $api['total_calls'] > 0 ? round($api['successful'] / $api['total_calls'] * 100) : 0;
        $color = $rate >= 80 ? '#34d399' : ($rate >= 50 ? '#fbbf24' : '#f87171');
      ?>
        <div class="mb-4 pb-4 border-b border-gray-800/50 last:border-0">
          <div class="flex justify-between items-center mb-1">
            <span class="text-sm font-bold tracking-wider"><?= htmlspecialchars(strtoupper($api['api_name'])) ?></span>
            <span class="text-xs" style="color:<?= $color ?>"><?= $rate ?>% success</span>
          </div>
          <div class="w-full bg-gray-800 rounded-full h-1.5 mb-2">
            <div class="h-1.5 rounded-full" style="width:<?= $rate ?>%;background:<?= $color ?>"></div>
          </div>
          <div class="flex gap-4 text-xs text-gray-500">
            <span><?= $api['total_calls'] ?> calls</span>
            <span><?= $api['avg_response_time'] ?>s avg</span>
            <span>Last: <?= date('H:i', strtotime($api['last_call'])) ?> UTC</span>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Settings + Cache Actions -->
    <div class="panel p-6">
      <h2 class="text-yellow-400 text-xs tracking-widest mb-4">API KEY STATUS</h2>
      <?php
      $apiKeys = [
          'alpha_vantage_key' => 'Alpha Vantage',
          'finnhub_key'       => 'Finnhub',
          'metals_api_key'    => 'Metals API',
          'news_api_key'      => 'NewsAPI',
      ];
      foreach ($apiKeys as $setting => $label):
          $val = $settings[$setting] ?? '';
          $set = !empty($val) && !str_starts_with($val, 'YOUR_');
      ?>
        <div class="flex justify-between items-center py-2 border-b border-gray-800/40">
          <span class="text-sm text-gray-300"><?= $label ?></span>
          <span class="text-xs px-2 py-0.5 rounded <?= $set ? 'tag-success' : 'tag-high' ?>">
            <?= $set ? '● CONFIGURED' : '● NOT SET' ?>
          </span>
        </div>
      <?php endforeach; ?>

      <div class="mt-6">
        <h3 class="text-yellow-400 text-xs tracking-widest mb-3">CACHE MANAGEMENT</h3>
        <form method="POST">
          <?= Security::csrfField() ?>
          <button name="flush_cache" value="1" type="submit"
                  class="w-full py-2 border border-yellow-400/30 text-yellow-400 text-xs tracking-widest
                         rounded hover:bg-yellow-400/10 transition">
            FLUSH ALL CACHE
          </button>
        </form>
        <p class="text-gray-600 text-xs mt-2">Cache files: <?= $cacheFiles ?> | Path: /cache/</p>
      </div>

      <div class="mt-4">
        <h3 class="text-yellow-400 text-xs tracking-widest mb-3">SYSTEM INFO</h3>
        <div class="text-xs text-gray-500 space-y-1">
          <div>PHP: <span class="text-gray-300"><?= PHP_VERSION ?></span></div>
          <div>Server time: <span class="text-gray-300"><?= date('Y-m-d H:i:s') ?> UTC</span></div>
          <div>App version: <span class="text-gray-300"><?= APP_VERSION ?></span></div>
          <div>Environment: <span class="text-gray-300"><?= APP_ENV ?></span></div>
        </div>
      </div>
    </div>

  </div>

  <!-- Application Logs -->
  <div class="panel p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-yellow-400 text-xs tracking-widest">APPLICATION LOGS (LAST 50)</h2>
      <div class="flex gap-2">
        <?php foreach (['', 'error', 'warning', 'info'] as $lvl): ?>
          <a href="?level=<?= $lvl ?>" class="text-xs px-2 py-0.5 rounded border border-gray-700 text-gray-400 hover:text-yellow-400 hover:border-yellow-400/30">
            <?= $lvl ?: 'ALL' ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="scrollbox font-mono text-xs">
      <?php if (empty($recentLogs)): ?>
        <p class="text-gray-600">No log entries yet.</p>
      <?php else: foreach ($recentLogs as $log):
        $tc = match($log['level']) {
            'error','critical' => 'text-red-400',
            'warning'          => 'text-yellow-400',
            default            => 'text-gray-400',
        };
      ?>
        <div class="flex gap-3 py-1.5 border-b border-gray-800/30">
          <span class="text-gray-600 shrink-0"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
          <span class="<?= $tc ?> uppercase shrink-0 w-14"><?= htmlspecialchars($log['level']) ?></span>
          <span class="text-blue-400 shrink-0 w-20 truncate"><?= htmlspecialchars($log['context']) ?></span>
          <span class="text-gray-300 break-all"><?= htmlspecialchars($log['message']) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

</main>

<footer class="text-center py-6 text-gray-700 text-xs tracking-widest border-t border-gray-800/50 mt-8">
  XAUUSD INTELLIGENCE TERMINAL v<?= APP_VERSION ?> — ADMIN PANEL
</footer>

</body>
</html>
