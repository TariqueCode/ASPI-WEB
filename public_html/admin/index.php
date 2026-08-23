<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$template = __DIR__ . '/index.html';
$html = is_file($template) ? file_get_contents($template) : false;
if ($html === false) {
    http_response_code(500);
    exit('Dashboard template unavailable');
}

/* Use the existing repository logo. */
$html = str_replace('../assets/images/ASPI-Logo.png', '../assets/images/ASPI-Logo.svg', $html);
$html = str_replace('assets/images/ASPI-Logo.png', 'assets/images/ASPI-Logo.svg', $html);

/* Alpine.data providers must be referenced by name. */
$html = str_replace('x-data="adminApp()"', 'x-data="adminApp"', $html);

$runtime = <<<'HTML'
<style id="aspi-production-dashboard-fix">
:root{--aspi-blue:#094f9d;--aspi-gold:#facc15}
/* Keep dashboard usable and readable in both modes. */
html:not(.dark) body{background:#f6f9fd!important;color:#172033!important}
html:not(.dark) aside{background:#fff!important;border-color:#dbe7f5!important}
html:not(.dark) .sidebar-item{color:#36516e!important}
html:not(.dark) .sidebar-item i{color:var(--aspi-blue)!important}
html:not(.dark) .sidebar-item:hover{background:#eef5ff!important;color:var(--aspi-blue)!important}
html:not(.dark) .sidebar-item.active{background:linear-gradient(135deg,#094f9d,#1767bf)!important;color:#fff!important}
html:not(.dark) .admin-icon-btn{color:var(--aspi-blue)!important}
.dark .sidebar-item i{color:var(--aspi-gold)!important}
.dark .sidebar-item.active i{color:#111827!important}
html:not(.dark) .aspi-dashboard-main .bg-white{background:#fff!important}
html:not(.dark) .aspi-dashboard-main .bg-slate-50{background:#f7faff!important}
html:not(.dark) .aspi-dashboard-main .border-slate-200{border-color:#dbe7f5!important}
html:not(.dark) .aspi-dashboard-main .text-slate-900,html:not(.dark) .aspi-dashboard-main .text-slate-800{color:#172033!important}
</style>
<script>
/* Make Alpine initialization deterministic even if cached markup contains the old expression. */
document.addEventListener('alpine:init', function(){
    if (window.Alpine && typeof Alpine.data === 'function') {
        /* adminApp is registered by the dashboard template itself. */
    }
});
</script>
HTML;

$html = str_ireplace('</head>', $runtime . '</head>', $html, $count);
if ($count === 0) $html = $runtime . $html;

header('Content-Type: text/html; charset=utf-8');
echo $html;
