<?php
require_once '../config.php';
require_once '../session.php';

$lang = in_array($_COOKIE['aspi_lang'] ?? 'bn', ['bn','en'], true) ? $_COOKIE['aspi_lang'] : 'bn';
$error = '';
$remainingTime = 0;
$tr = [
    'bn' => ['title'=>'অ্যাডমিন লগইন','username'=>'ইউজারনেম','password'=>'পাসওয়ার্ড','login'=>'লগইন করুন','wait'=>'অতিরিক্ত চেষ্টা! দয়া করে %d সেকেন্ড অপেক্ষা করুন।','csrf'=>'CSRF টোকেন মেলেনি!','invalid'=>'ভুল ইউজারনেম বা পাসওয়ার্ড!'],
    'en' => ['title'=>'Admin Login','username'=>'Username','password'=>'Password','login'=>'Log in','wait'=>'Too many attempts. Please wait %d seconds.','csrf'=>'CSRF token mismatch.','invalid'=>'Invalid username or password.']
];
$t = $tr[$lang];
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$stmt->execute([$ip]);
$attempts = (int)$stmt->fetchColumn();

if ($attempts >= 5) {
    $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, MAX(attempt_time), DATE_ADD(NOW(), INTERVAL 15 MINUTE)) FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $remainingTime = (int)$stmt->fetchColumn();
    if ($remainingTime > 0) {
        $error = sprintf($t['wait'], $remainingTime);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!Security::validateCSRF($csrf)) {
        $error = $t['csrf'];
    } elseif ($username === $_ENV['ADMIN_USER'] && $password === $_ENV['ADMIN_PASS']) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
        header('Location: index.html');
        exit;
    } else {
        $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)")->execute([$ip]);
        $error = $t['invalid'];
    }
}

$csrfToken = Security::generateCSRF();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($t['title']); ?></title>
    <link rel="icon" type="image/png" href="../assets/images/ASPI-Logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

<style id="aspi-v10-login-ui">
:root{color-scheme:dark}
html,body{margin:0;min-height:100%;background:#07111f;color:#f8fafc}
body{
  font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  display:flex;align-items:center;justify-content:center;padding:24px;
}
.aspi-login-shell{
  width:min(440px,100%);
  background:linear-gradient(145deg,#14263d,#0d1b2d);
  border:1px solid rgba(148,163,184,.18);
  border-radius:24px;
  padding:30px;
  box-shadow:0 28px 80px rgba(0,0,0,.38);
}
.aspi-login-logo{
  width:78px;height:78px;object-fit:contain;border-radius:20px;background:#fff;
  display:block;margin:0 auto 16px;
  box-shadow:0 10px 30px rgba(0,0,0,.3),0 0 0 2px rgba(250,204,21,.16);
}
.aspi-login-title{text-align:center;font-size:1.5rem;font-weight:950;margin:0}
.aspi-login-sub{text-align:center;color:#91a4bb;font-size:.88rem;margin:7px 0 26px}
.aspi-login-shell label{display:block;font-size:.82rem;font-weight:800;color:#cbd5e1;margin:0 0 7px}
.aspi-login-shell input{
  width:100%;box-sizing:border-box;min-height:48px;padding:0 14px;
  border-radius:12px;border:1px solid #40546d;background:#081526;color:#fff;
  outline:none;margin-bottom:17px;
}
.aspi-login-shell input:focus{
  border-color:#facc15;box-shadow:0 0 0 3px rgba(250,204,21,.12);
}
.aspi-login-btn{
  width:100%;min-height:50px;border:1px solid #fde68a;border-radius:13px;
  background:linear-gradient(135deg,#fde047,#f59e0b);color:#111827;
  font-weight:950;cursor:pointer;transition:.18s ease;
  box-shadow:0 12px 28px rgba(245,158,11,.18);
}
.aspi-login-btn:hover{filter:brightness(1.06);transform:translateY(-1px)}
.aspi-login-error{
  border:1px solid rgba(239,68,68,.35);background:rgba(239,68,68,.10);
  color:#fecaca;border-radius:12px;padding:11px 13px;margin-bottom:18px;font-size:.86rem;
}
</style>

</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen p-4">
    <script>function setLoginLang(lang){document.cookie='aspi_lang='+lang+';path=/;max-age=31536000;SameSite=Lax';location.reload();}</script>
    <div class="bg-slate-800 p-8 rounded-2xl shadow-2xl w-full max-w-md border border-slate-700 relative">
        <button type="button" onclick="setLoginLang('<?php echo $lang==='bn'?'en':'bn'; ?>')" class="absolute top-4 right-4 px-3 py-1.5 rounded-full bg-slate-700 text-slate-200 text-xs font-bold"><?php echo $lang==='bn'?'EN':'বাংলা'; ?></button>
        <h2 class="text-2xl font-bold text-white text-center mb-6"><i class="fas fa-shield-alt text-blue-500 mr-2"></i> <?php echo htmlspecialchars($t['title']); ?></h2>
        <?php if ($error): ?>
            <div class="bg-red-900/30 border border-red-700 text-red-300 p-3 rounded-lg mb-4 text-sm"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="mb-4">
                <label class="block text-slate-300 text-sm font-bold mb-2"><?php echo htmlspecialchars($t['username']); ?></label>
                <input type="text" name="username" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label class="block text-slate-300 text-sm font-bold mb-2"><?php echo htmlspecialchars($t['password']); ?></label>
                <input type="password" name="password" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors" <?php echo ($remainingTime  class="aspi-login-btn"> 0) ? 'disabled' : ''; ?>>
                <?php echo htmlspecialchars($t['login']); ?>
            </button>
        </form>
    </div>
</body>
</html>