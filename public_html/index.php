<?php
declare(strict_types=1);

$template = __DIR__ . '/index.html';
$html = is_file($template) ? file_get_contents($template) : false;
if ($html === false) {
    http_response_code(500);
    exit('Homepage template unavailable');
}

/*
 * Keep the existing homepage and its existing ASPI logo. Do not inject a
 * second/fake splash logo. Alpine.data providers must be referenced by name
 * so the splash screen can initialize and disappear normally.
 */
$html = str_replace('x-data="frontendApp()"', 'x-data="frontendApp"', $html);

/* Use the existing ASPI logo asset in the original splash without adding a
 * new frame/background around it. */
$html = str_replace(
    'class="w-full h-full object-contain rounded-full bg-white/10 p-2 border-2 border-brand-gold/50 shadow-xl animate-float"',
    'class="w-full h-full object-contain animate-float"',
    $html
);

/* Keep the existing theme controls but correct their colours by mode. */
$critical = <<<'HTML'
<style id="aspi-home-runtime-fix">
:root{--aspi-blue:#094f9d;--aspi-gold:#facc15}
html:not(.dark) .header-control,html:not(.dark) .header-control i{color:var(--aspi-blue)!important;border-color:#094f9d55!important}
.dark .header-control,.dark .header-control i{color:var(--aspi-gold)!important;border-color:#facc1555!important}
html:not(.dark) .founder-highlight{background:linear-gradient(135deg,#fff,#eef5ff)!important;color:#172033!important;border-color:#dbe7f5!important}
html:not(.dark) .founder-highlight .founder-cta{background:linear-gradient(135deg,#094f9d,#1767bf)!important;color:#fff!important}
html:not(.dark) .glass-nav{background:rgba(255,255,255,.96)!important;border-color:#dbe7f5!important}
html:not(.dark) .glass-card{background:rgba(255,255,255,.96)!important;border-color:#dbe7f5!important}
html:not(.dark) .mobile-menu{background:#fff!important;border-color:#dbe7f5!important}
html:not(.dark) .mobile-menu a{color:#172033!important}
html:not(.dark) .bg-gradient-anim{background:linear-gradient(135deg,#f8fbff,#e8f1fa)!important}
</style>
HTML;

if (stripos($html, '</head>') !== false) {
    $html = str_ireplace('</head>', $critical . '</head>', $html, $headCount);
} else {
    $html = $critical . $html;
}

header('Content-Type: text/html; charset=utf-8');
echo $html;
