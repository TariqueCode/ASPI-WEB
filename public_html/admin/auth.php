<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/session.php';

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Expire inactive admin sessions after 30 minutes.
$now = time();
$last = (int)($_SESSION['last_activity'] ?? $now);
if (($now - $last) > 1800) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}

$_SESSION['last_activity'] = $now;
?>
