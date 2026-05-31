<?php
/**
 * Error page — error.php
 * Handles 403, 404, 500 errors with themed UI
 */

declare(strict_types=1);

$code    = (int)($_GET['code'] ?? http_response_code());
$allowed = [403, 404, 500];
if (!in_array($code, $allowed)) $code = 404;

http_response_code($code);

$messages = [
    403 => ['ACCESS DENIED',        'You do not have permission to access this resource.'],
    404 => ['SIGNAL NOT FOUND',     'The requested endpoint does not exist in this terminal.'],
    500 => ['SYSTEM FAULT',         'An internal error occurred. Command center is recovering.'],
];

[$title, $msg] = $messages[$code];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $code ?> — XAUUSD Terminal</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: #020408;
    color: #e2e8f0;
    font-family: 'Courier New', monospace;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
  }
  .code   { font-size: 96px; font-weight: 900; color: #fbbf24; text-shadow: 0 0 40px rgba(251,191,36,0.5); letter-spacing: -0.02em; }
  .title  { font-size: 16px; letter-spacing: 0.4em; color: #ff4466; margin: 12px 0 20px; }
  .msg    { font-size: 13px; color: #64748b; max-width: 360px; line-height: 1.7; }
  a       { color: #fbbf24; text-decoration: none; display: inline-block; margin-top: 32px; font-size: 11px; letter-spacing: 0.3em; border: 1px solid rgba(251,191,36,0.3); padding: 10px 24px; border-radius: 4px; }
  a:hover { background: rgba(251,191,36,0.1); }
</style>
</head>
<body>
  <div>
    <div class="code"><?= $code ?></div>
    <div class="title"><?= htmlspecialchars($title) ?></div>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <a href="/">← RETURN TO TERMINAL</a>
  </div>
</body>
</html>
