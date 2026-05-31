<?php
/**
 * Admin Login — admin/login.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

// Already logged in
if (Security::isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (!Security::checkRateLimit('admin_login')) {
        $error = 'Too many attempts. Please wait 60 seconds.';
    } else {
        $username = Security::sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Security::adminLogin($username, $password)) {
            header('Location: /admin/index.php');
            exit;
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — XAUUSD Terminal</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { background: #030712; font-family: 'Courier New', monospace; }
  .glow { box-shadow: 0 0 20px rgba(251,191,36,0.3); }
  .border-glow { border-color: rgba(251,191,36,0.5); }
  input { background: rgba(255,255,255,0.05) !important; color: #f9fafb; }
  input:focus { outline: none; border-color: #fbbf24; box-shadow: 0 0 10px rgba(251,191,36,0.3); }
</style>
</head>
<body class="min-h-screen flex items-center justify-center">
<div class="w-full max-w-md mx-auto px-6">
  <div class="text-center mb-10">
    <div class="text-yellow-400 text-4xl font-bold tracking-widest">⬡ XAUUSD</div>
    <div class="text-gray-500 text-sm tracking-[0.3em] mt-1">INTELLIGENCE TERMINAL</div>
    <div class="text-red-500 text-xs mt-2 tracking-widest">ADMIN ACCESS</div>
  </div>

  <div class="border border-yellow-400/30 glow rounded-lg p-8 bg-gray-950/80 backdrop-blur">
    <?php if ($error): ?>
      <div class="mb-4 p-3 bg-red-900/40 border border-red-500/50 rounded text-red-400 text-sm">
        ⚠ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <?= Security::csrfField() ?>
      <div class="mb-4">
        <label class="block text-gray-500 text-xs tracking-widest mb-2">USERNAME</label>
        <input type="text" name="username" required
               class="w-full border border-gray-700 border-glow rounded px-4 py-3 text-sm"
               placeholder="admin">
      </div>
      <div class="mb-6">
        <label class="block text-gray-500 text-xs tracking-widest mb-2">PASSWORD</label>
        <input type="password" name="password" required
               class="w-full border border-gray-700 border-glow rounded px-4 py-3 text-sm"
               placeholder="••••••••••••">
      </div>
      <button type="submit"
              class="w-full py-3 bg-yellow-400/10 hover:bg-yellow-400/20 border border-yellow-400/50
                     text-yellow-400 text-sm tracking-widest rounded transition-all duration-200">
        AUTHENTICATE
      </button>
    </form>
  </div>

  <div class="text-center mt-6">
    <a href="/" class="text-gray-600 hover:text-gray-400 text-xs tracking-widest">← BACK TO TERMINAL</a>
  </div>
</div>
</body>
</html>
