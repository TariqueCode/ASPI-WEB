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

/* SVG uploads need a dedicated safe handler because the generic API currently
   accepts raster images/PDF/video only. Keep every other upload on the existing API. */
$html = str_replace(
    "const res = await fetch('../api.php?action=upload', { method: 'POST', body: fd });",
    "const isSvg = String(file.name || '').toLowerCase().endsWith('.svg');\n                        const res = await fetch(isSvg ? 'actions/upload_svg.php' : '../api.php?action=upload', { method: 'POST', body: fd });",
    $html
);

$runtime = <<<'HTML'
<style id="aspi-production-dashboard-fix">
:root{--aspi-blue:#094f9d;--aspi-gold:#facc15}
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
/* The dashboard template registers adminApp through Alpine.data(). The wrapper
   only normalizes the x-data expression; all existing dashboard methods remain intact. */
</script>
HTML;

$html = str_ireplace('</head>', $runtime . '</head>', $html, $count);
if ($count === 0) $html = $runtime . $html;

header('Content-Type: text/html; charset=utf-8');
echo $html;
