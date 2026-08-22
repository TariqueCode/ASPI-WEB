<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['lifetime'=>60*60*24*7,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Strict']);
    session_start();
    if (!isset($_SESSION['initiated'])) { session_regenerate_id(true); $_SESSION['initiated']=true; }
}
if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $last=(int)($_SESSION['last_activity']??time());
    if ($last>0 && (time()-$last)>1800) { $_SESSION=[]; session_destroy(); }
    else { $_SESSION['last_activity']=time(); }
}
?>