<?php
declare(strict_types=1);

$template = __DIR__ . '/index.html';
$html = is_file($template) ? file_get_contents($template) : false;
if ($html === false) { http_response_code(500); exit('Homepage template unavailable'); }

$critical = <<<'HTML'
<link rel="preload" as="image" href="assets/images/ASPI-Logo.svg" fetchpriority="high">
<style id="aspi-production-home-fix">
:root{--aspi-blue:#094f9d;--aspi-gold:#facc15;--aspi-light-bg:#f6f9fd;--aspi-light-surface:#fff;--aspi-light-text:#172033;--aspi-light-muted:#52657c}
html:not(.dark) body{background:var(--aspi-light-bg)!important;color:var(--aspi-light-text)!important}
html:not(.dark) .bg-gradient-anim{background:linear-gradient(135deg,#f8fbff 0%,#e8f1fa 100%)!important}
html:not(.dark) .glass-nav{background:rgba(255,255,255,.96)!important;border-color:#dbe7f5!important}
html:not(.dark) .mobile-menu{background:#fff!important;border-color:#dbe7f5!important;box-shadow:0 20px 45px #094f9d18!important}
html:not(.dark) .mobile-menu a{color:var(--aspi-light-text)!important}
html:not(.dark) .mobile-menu a:hover{background:#eef5ff!important;color:var(--aspi-blue)!important}
html:not(.dark) .glass-card{background:rgba(255,255,255,.96)!important;border-color:#dbe7f5!important;box-shadow:0 10px 35px #094f9d12!important}
html:not(.dark) .founder-highlight{background:linear-gradient(135deg,#fff,#eef5ff)!important;color:var(--aspi-light-text)!important;border:1px solid #dbe7f5}
html:not(.dark) .founder-highlight .founder-cta{background:linear-gradient(135deg,#094f9d,#1767bf)!important;color:#fff!important;box-shadow:0 14px 30px #094f9d25!important}
html:not(.dark) .admission-tab-btn.active{background:#094f9d!important;color:#fff!important;border-color:#094f9d!important}
html:not(.dark) .header-control,html:not(.dark) .header-control i{color:var(--aspi-blue)!important;border-color:#094f9d55!important}
.dark .header-control,.dark .header-control i{color:var(--aspi-gold)!important;border-color:#facc1555!important}
html:not(.dark) .aspi-theme-icon{color:var(--aspi-blue)!important}.dark .aspi-theme-icon{color:var(--aspi-gold)!important}
html:not(.dark) nav a i,html:not(.dark) header i{color:var(--aspi-blue)!important}.dark nav a i,.dark header i{color:var(--aspi-gold)!important}
.aspi-critical-logo{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:84px;height:84px;object-fit:contain;background:#fff;border-radius:22px;padding:7px;z-index:2147483647;box-shadow:0 15px 45px #0004;opacity:1;transition:opacity .35s ease,transform .35s ease;pointer-events:none}.aspi-critical-logo.hide{opacity:0;transform:translate(-50%,-50%) scale(.94)}
#aspiCommitteeHome{scroll-margin-top:90px}.aspi-committee-wrap{width:min(1280px,calc(100% - 32px));margin:70px auto}.aspi-committee-head{text-align:center;margin-bottom:28px}.aspi-committee-head h2{font-size:clamp(1.7rem,3vw,2.5rem);font-weight:900;margin:0;color:#172033}.dark .aspi-committee-head h2{color:#f8fafc}.aspi-committee-head p{margin:7px 0 0;color:#64748b}.dark .aspi-committee-head p{color:#94a3b8}.aspi-committee-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}.aspi-committee-card{padding:22px;text-align:center;border-radius:24px;background:rgba(255,255,255,.96);border:1px solid #dbe7f5;box-shadow:0 12px 35px #094f9d12;transition:transform .2s ease,box-shadow .2s ease}.dark .aspi-committee-card{background:#17263a;border-color:#334155;box-shadow:0 15px 40px #0005}.aspi-committee-card:hover{transform:translateY(-6px);box-shadow:0 20px 45px #094f9d20}.aspi-committee-photo{width:112px;height:112px;margin:0 auto 15px;border-radius:50%;overflow:hidden;background:#e8f1fa;border:4px solid #fff;box-shadow:0 8px 24px #094f9d20}.dark .aspi-committee-photo{border-color:#334155;background:#0f172a}.aspi-committee-photo img{width:100%;height:100%;object-fit:cover}.aspi-committee-name{font-size:1.08rem;font-weight:900;color:#172033}.dark .aspi-committee-name{color:#f8fafc}.aspi-committee-role{margin-top:5px;font-size:.88rem;font-weight:700;color:#094f9d}.dark .aspi-committee-role{color:#facc15}@media(max-width:1000px){.aspi-committee-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.aspi-committee-wrap{width:min(100% - 24px,520px);margin:45px auto}.aspi-committee-grid{grid-template-columns:1fr;gap:14px}.aspi-committee-card{padding:18px}.aspi-committee-photo{width:96px;height:96px}}
</style>
HTML;

$afterBody = <<<'HTML'
<div id="aspiCriticalLogo" class="aspi-critical-logo"><img src="assets/images/ASPI-Logo.svg" width="70" height="70" alt="ASPI"></div>
<script>
(function(){
function themeIcons(){document.querySelectorAll('header button,nav button,.header-control,button').forEach(b=>{if(/fa-(gear|bars|moon|sun|globe|language|sliders)/.test(b.innerHTML||''))b.classList.add('aspi-theme-icon')});document.querySelectorAll('header i,nav i').forEach(i=>i.classList.add('aspi-theme-icon'))}
function criticalLogo(){const e=document.getElementById('aspiCriticalLogo');if(!e)return;requestAnimationFrame(()=>requestAnimationFrame(()=>setTimeout(()=>e.classList.add('hide'),650)));setTimeout(()=>e.remove(),1150)}
async function committee(){try{const r=await fetch('committee.php?action=list&ts='+Date.now(),{cache:'no-store'});if(!r.ok)return;const j=await r.json(),rows=j.committee||[];if(!rows.length)return;const footer=document.querySelector('footer');const wrap=document.createElement('section');wrap.id='aspiCommitteeHome';wrap.className='aspi-committee-wrap';wrap.innerHTML='<div class="aspi-committee-head"><h2>সাংগঠনিক কমিটি</h2><p>আসহাব সিরাজ পলিটেকনিক ইনস্টিটিউট</p></div><div class="aspi-committee-grid"></div>';const grid=wrap.querySelector('.aspi-committee-grid');rows.forEach(m=>{const card=document.createElement('article');card.className='aspi-committee-card';const image=m.image_url?'<img loading="lazy" src="'+String(m.image_url).replace(/"/g,'&quot;')+'" alt="">':'<div style="height:100%;display:grid;place-items:center;font-size:34px;color:#094f9d">●</div>';card.innerHTML='<div class="aspi-committee-photo">'+image+'</div><div class="aspi-committee-name">'+esc(m.name_bn||m.name_en)+'</div><div class="aspi-committee-role">'+esc(m.designation_bn||m.designation_en)+'</div>';grid.appendChild(card)});if(footer)footer.parentNode.insertBefore(wrap,footer);else document.body.appendChild(wrap)}catch(e){console.warn('Committee section unavailable',e)}}
function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
window.addEventListener('DOMContentLoaded',()=>{themeIcons();criticalLogo();committee();setTimeout(themeIcons,800)});
})();
</script>
HTML;

$html = str_ireplace('</head>', $critical . '</head>', $html, $headCount);
$html = str_ireplace('</body>', $afterBody . '</body>', $html, $bodyCount);
if($headCount===0)$html=$critical.$html;
if($bodyCount===0)$html.=$afterBody;
header('Content-Type:text/html; charset=utf-8');
echo $html;
