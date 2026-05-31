<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Security::adminLogout();
header('Location: /admin/login.php');
exit;
