<?php
session_cache_limiter('private_no_cache');
session_cache_expire(180);

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../env';
}
if (!file_exists($envPath)) {
    die('সিস্টেম কনফিগারেশন ফাইল পাওয়া যায়নি।');
}

$envVars = parse_ini_file($envPath);
if ($envVars === false) {
    die('.env ফাইল পড়া যায়নি।');
}

foreach ($envVars as $key => $value) {
    $_ENV[$key] = $value;
    putenv("$key=$value");
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/functions.php';

$pdo = Database::getInstance();

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/storage/logs/error.log');
date_default_timezone_set('Asia/Dhaka');
?>