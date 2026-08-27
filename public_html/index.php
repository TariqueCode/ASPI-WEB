<?php
declare(strict_types=1);

$template = __DIR__ . '/index.html';
$html = is_file($template) ? file_get_contents($template) : false;
if ($html === false) {
    http_response_code(500);
    exit('Homepage template unavailable');
}

/* Use the existing ASPI SVG logo already present in the repository. */
$html = str_replace('assets/images/ASPI-Logo.png', 'assets/images/ASPI-Logo.svg', $html);

/* Alpine.data providers must be referenced by name. */
$html = str_replace('x-data="frontendApp()"', 'x-data="frontendApp"', $html);

/* Keep the existing logo; remove only the unnecessary frame around the splash logo. */
$html = str_replace(
    'class="w-full h-full object-contain rounded-full bg-white/10 p-2 border-2 border-brand-gold/50 shadow-xl animate-float"',
    'class="w-full h-full object-contain animate-float"',
    $html
);

/* Replace the old globe + language-name control with a compact BN/EN switch.
   Bengali mode shows EN; English mode shows বাং. */
$oldDesktopLang = '<div class="lang-switch" @click="switchLanguage(currentLang === \'bn\' ? \'en\' : \'bn\')">
                    <i class="fa-solid fa-globe"></i>
                    <span class="lang-label" x-text="currentLang === \'bn\' ? \'বাংলা\' : \'English\'"></span>
                </div>';
$newDesktopLang = '<button type="button" class="lang-switch aspi-lang-toggle" @click="switchLanguage(currentLang === \'bn\' ? \'en\' : \'bn\')" :aria-label="currentLang === \'bn\' ? \'Switch to English\' : \'বাংলায় পরিবর্তন করুন\'">
                    <span class="aspi-lang-bn" :class="currentLang === \'bn\' ? \'active\' : \'\'">বাং</span><span class="aspi-lang-divider">/</span><span class="aspi-lang-en" :class="currentLang === \'en\' ? \'active\' : \'\'">EN</span>
                </button>';
$html = str_replace($oldDesktopLang, $newDesktopLang, $html);

$oldMobileLang = '<button @click="switchLanguage(currentLang === \'bn\' ? \'en\' : \'bn\'); mobileMenuOpen = false" class="mobile-lang-btn text-center text-white">
                <i class="fa-solid fa-globe"></i>
                <span x-text="currentLang === \'bn\' ? \'বাংলা → English\' : \'English → বাংলা\'"></span>
            </button>';
$newMobileLang = '<button type="button" @click="switchLanguage(currentLang === \'bn\' ? \'en\' : \'bn\'); mobileMenuOpen = false" class="mobile-lang-btn aspi-lang-toggle">
                <span class="aspi-lang-bn" :class="currentLang === \'bn\' ? \'active\' : \'\'">বাং</span><span>/</span><span class="aspi-lang-en" :class="currentLang === \'en\' ? \'active\' : \'\'">EN</span>
            </button>';
$html = str_replace($oldMobileLang, $newMobileLang, $html);

$runtime = <<<'HTML'
<style id="aspi-home-production-fix">
/* Light mode must actually be light across the whole page. */
html:not(.dark),html:not(.dark) body{background:#f8fbff!important;color:#172033!important}
html:not(.dark) .bg-gradient-anim{background:linear-gradient(135deg,#f8fbff 0%,#e8f1fa 100%)!important}
html.dark,html.dark body{background:#050b16!important}

/* Compact Bengali/English switcher. In Bangla it visibly offers EN. */
.aspi-lang-toggle{display:inline-flex!important;align-items:center;justify-content:center;gap:.28rem;min-width:64px!important;height:40px;padding:.35rem .65rem!important;border-radius:9999px!important;font-weight:900!important;letter-spacing:.02em;cursor:pointer;transition:.2s ease}
.aspi-lang-toggle .aspi-lang-bn,.aspi-lang-toggle .aspi-lang-en{opacity:.55;transition:.2s ease}
.aspi-lang-toggle .aspi-lang-bn.active,.aspi-lang-toggle .aspi-lang-en.active{opacity:1}
.aspi-lang-toggle .aspi-lang-divider{opacity:.35}
html:not(.dark) .aspi-lang-toggle{background:#fff!important;color:#172033!important;border:1px solid #d7e2ee!important;box-shadow:0 6px 18px rgba(15,23,42,.08)!important}
html.dark .aspi-lang-toggle{background:#0f1b2e!important;color:#fff!important;border:1px solid rgba(250,204,21,.35)!important}

/* Theme controls: blue in light mode, gold in dark mode. */
html:not(.dark) .header-control,html:not(.dark) .header-control i{color:#094f9d!important;border-color:#094f9d55!important}
html.dark .header-control,html.dark .header-control i{color:#facc15!important;border-color:#facc1555!important}
html:not(.dark) .mobile-menu{background:#fff!important;border-color:#dbe7f5!important}
html:not(.dark) .mobile-menu a{color:#172033!important}

/* Gallery page: keep the same dark visual surface as its cards/header.
   The gallery route previously inherited a white section background. */
body:has(.gallery-card){background:#08111f!important;color:#e6f4fb!important}
body:has(.gallery-card) .bg-gradient-anim{background:linear-gradient(135deg,#050b16 0%,#08111f 55%,#0b1726 100%)!important}
body:has(.gallery-card) main,
body:has(.gallery-card) main>section,
body:has(.gallery-card) main>div,
body:has(.gallery-card) .min-h-screen{background:transparent!important}
body:has(.gallery-card) footer{background:#08111f!important;color:#9fb2c1!important;border-color:rgba(148,163,184,.14)!important}
body:has(.gallery-card) .gallery-card{background:#0f2633!important;border:1px solid rgba(96,165,250,.18)!important;box-shadow:0 12px 30px rgba(0,0,0,.22)!important}
body:has(.gallery-card) .gallery-card .text-slate-900,
body:has(.gallery-card) .gallery-card .text-gray-900{color:#effcff!important}
body:has(.gallery-card) .gallery-card .text-slate-500,
body:has(.gallery-card) .gallery-card .text-slate-600{color:#8ea8b7!important}
@media(max-width:640px){
  body:has(.gallery-card) main{background:transparent!important}
}


/* Hard route-level gallery surface fix. The public gallery is a SPA route, so
   styling only a gallery card is not enough: the surrounding page shell can
   still paint white in light mode. */
body.aspi-gallery-route,
body.aspi-gallery-route #app,
body.aspi-gallery-route main,
body.aspi-gallery-route main.container,
body.aspi-gallery-route main > section,
body.aspi-gallery-route footer,
body.aspi-gallery-route .glass-card {
  background:#08111f!important;
  color:#e6f4fb!important;
}
body.aspi-gallery-route .gallery-card {
  background:#0f2633!important;
  border-color:rgba(96,165,250,.22)!important;
  box-shadow:0 12px 30px rgba(0,0,0,.28)!important;
}
body.aspi-gallery-route .gallery-card .text-slate-900,
body.aspi-gallery-route .gallery-card .text-slate-800,
body.aspi-gallery-route .gallery-card .text-gray-900 { color:#effcff!important; }
body.aspi-gallery-route .gallery-card .text-slate-500,
body.aspi-gallery-route .gallery-card .text-slate-600 { color:#8ea8b7!important; }
body.aspi-gallery-route #gallery_section,
body.aspi-gallery-route #gallery_section > div { background:transparent!important; }
body.aspi-gallery-route footer { border-color:rgba(148,163,184,.14)!important; }
@media(max-width:640px){
  body.aspi-gallery-route main.container { padding-left:16px!important; padding-right:16px!important; }
}
</style>
<script>
(function(){
  function markGalleryRoute(){
    var path = (window.location.pathname || '').replace(/\\/+$/, '') || '/';
    var isGallery = path === '/gallery' || path === '/gallery.php' || /\\/gallery$/i.test(path);
    document.body.classList.toggle('aspi-gallery-route', isGallery);
  }
  document.addEventListener('DOMContentLoaded', markGalleryRoute);
  window.addEventListener('popstate', markGalleryRoute);
})();
</script>
HTML;

if (stripos($html, '</head>') !== false) {
    $html = str_ireplace('</head>', $runtime . '</head>', $html, $headCount);
} else {
    $html = $runtime . $html;
}

header('Content-Type: text/html; charset=utf-8');
echo $html;
