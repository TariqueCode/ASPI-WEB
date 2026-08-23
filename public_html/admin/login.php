<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/session.php';

$lang = in_array($_COOKIE['aspi_lang'] ?? 'bn', ['bn', 'en'], true) ? $_COOKIE['aspi_lang'] : 'bn';
$tr = [
    'bn' => [
        'title' => 'অ্যাডমিন লগইন', 'username' => 'ইউজারনেম', 'password' => 'পাসওয়ার্ড',
        'login' => 'লগইন করুন', 'wait' => 'অতিরিক্ত চেষ্টা! দয়া করে %d সেকেন্ড অপেক্ষা করুন।',
        'csrf' => 'CSRF টোকেন মেলেনি!', 'invalid' => 'ভুল ইউজারনেম বা পাসওয়ার্ড!',
        'expired' => 'নিরাপত্তার কারণে আপনার আগের সেশন শেষ হয়েছে। আবার লগইন করুন।'
    ],
    'en' => [
        'title' => 'Admin Login', 'username' => 'Username', 'password' => 'Password',
        'login' => 'Log in', 'wait' => 'Too many attempts. Please wait %d seconds.',
        'csrf' => 'CSRF token mismatch.', 'invalid' => 'Invalid username or password.',
        'expired' => 'Your previous session expired for security. Please log in again.'
    ]
];
$t = $tr[$lang];
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$error = isset($_GET['expired']) ? $t['expired'] : '';
$remainingTime = 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$stmt->execute([$ip]);
$attempts = (int)$stmt->fetchColumn();

if ($attempts >= 5) {
    $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, MAX(attempt_time), DATE_ADD(NOW(), INTERVAL 15 MINUTE)) FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $remainingTime = max(0, (int)$stmt->fetchColumn());
    if ($remainingTime > 0) $error = sprintf($t['wait'], $remainingTime);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $remainingTime <= 0) {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!Security::validateCSRF($csrf)) {
        $error = $t['csrf'];
    } else {
        $expectedUser = (string)($_ENV['ADMIN_USER'] ?? '');
        $expectedPass = (string)($_ENV['ADMIN_PASS'] ?? '');
        $validUser = $expectedUser !== '' && hash_equals($expectedUser, $username);
        $validPass = $expectedPass !== '' && hash_equals($expectedPass, $password);

        if ($validUser && $validPass) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['admin_user'] = $expectedUser;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
            header('Location: index.php', true, 303);
            exit;
        }

        $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)")->execute([$ip]);
        $error = $t['invalid'];
    }
}

$csrfToken = Security::generateCSRF();
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></title>
<link rel="icon" href="../assets/images/ASPI-Logo.svg">
<style>
:root{font-family:'Segoe UI','Noto Sans Bengali',system-ui,sans-serif;color-scheme:dark;background:#07111f;color:#f8fafc}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:linear-gradient(135deg,#07111f,#0f2036)}
.card{width:min(430px,100%);padding:32px;border:1px solid #29415d;border-radius:24px;background:#102138;box-shadow:0 24px 70px #0008}
.logo{width:82px;height:82px;display:block;margin:0 auto 18px;padding:7px;border-radius:20px;background:#fff;object-fit:contain}
h1{text-align:center;font-size:1.45rem;margin:0;font-weight:800}.sub{text-align:center;color:#9fb0c4;margin:7px 0 24px;font-size:.85rem}
label{display:block;margin:0 0 7px;font-weight:700;font-size:.86rem;color:#dbe7f5}input{width:100%;height:50px;margin-bottom:17px;padding:0 14px;border-radius:12px;border:1px solid #405a76;background:#081526;color:#fff;outline:0}input:focus{border-color:#facc15;box-shadow:0 0 0 3px #facc1520}
button{width:100%;height:50px;border:0;border-radius:13px;background:linear-gradient(135deg,#fde047,#f59e0b);color:#111827;font-weight:900;cursor:pointer}button:disabled{opacity:.55;cursor:not-allowed}
.err{margin-bottom:18px;padding:11px 13px;border-radius:12px;border:1px solid #ef444455;background:#ef444415;color:#fecaca;font-size:.86rem}.lang{position:fixed;right:18px;top:18px;color:#dbe7f5;text-decoration:none;border:1px solid #38516c;border-radius:999px;padding:7px 12px;background:#132a43}
</style>
</head>
<body>
<a class="lang" href="?lang=<?= $lang==='bn'?'en':'bn' ?>"><?= $lang==='bn'?'EN':'বাংলা' ?></a>
<form class="card" method="post" autocomplete="on">
<img class="logo" src="../assets/images/ASPI-Logo.svg" alt="ASPI">
<h1><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></h1><div class="sub">ASPI Administration</div>
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
<label><?= htmlspecialchars($t['username'], ENT_QUOTES, 'UTF-8') ?></label><input name="username" type="text" autocomplete="username" required>
<label><?= htmlspecialchars($t['password'], ENT_QUOTES, 'UTF-8') ?></label><input name="password" type="password" autocomplete="current-password" required>
<button type="submit" <?= $remainingTime>0?'disabled':'' ?>><?= htmlspecialchars($t['login'], ENT_QUOTES, 'UTF-8') ?></button>
</form>
</body></html>
