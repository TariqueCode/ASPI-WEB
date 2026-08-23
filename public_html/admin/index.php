<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$template = __DIR__ . '/index.html';
$html = is_file($template) ? file_get_contents($template) : false;
if ($html === false) {
    http_response_code(500);
    exit('Dashboard template unavailable');
}

$runtime = <<<'HTML'
<style id="aspi-production-dashboard-fix">
:root{--aspi-blue:#094f9d;--aspi-gold:#facc15}
html:not(.dark) body{background:#f6f9fd!important;color:#172033!important}
html:not(.dark) .aspi-dashboard-main{background:#f6f9fd!important;color:#172033!important}
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
#aspiCommitteeModal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:16px;background:#020617aa;backdrop-filter:blur(8px)}
#aspiCommitteeModal.open{display:flex}.aspi-committee-box{width:min(980px,100%);max-height:92vh;overflow:auto;border-radius:24px;background:#fff;color:#172033;box-shadow:0 30px 90px #0007}.dark .aspi-committee-box{background:#0f172a;color:#f8fafc}.aspi-c-head{display:flex;justify-content:space-between;gap:15px;padding:18px 22px;border-bottom:1px solid #dbe7f5}.dark .aspi-c-head{border-color:#334155}.aspi-c-grid{display:grid;grid-template-columns:minmax(280px,.8fr) minmax(0,1.2fr);gap:20px;padding:22px}.aspi-c-form,.aspi-c-card{border:1px solid #dbe7f5;border-radius:18px;padding:18px;background:#f7faff}.dark .aspi-c-form,.dark .aspi-c-card{border-color:#334155;background:#17263a}.aspi-c-form label{display:block;font-weight:800;font-size:13px;margin:0 0 12px}.aspi-c-form input{width:100%;margin-top:5px;padding:10px;border:1px solid #cbd8e8;border-radius:10px;background:#fff;color:#172033}.dark .aspi-c-form input{background:#0b1728;color:#fff;border-color:#475569}.aspi-c-actions{display:flex;gap:8px}.aspi-c-btn{border:0;border-radius:10px;padding:10px 14px;font-weight:800;cursor:pointer}.aspi-c-primary{background:#094f9d;color:#fff}.aspi-c-danger{background:#dc2626;color:#fff}.aspi-c-muted{background:#e2e8f0;color:#172033}.dark .aspi-c-muted{background:#334155;color:#fff}.aspi-c-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.aspi-c-person{display:flex;gap:12px;align-items:center}.aspi-c-avatar{width:58px;height:58px;border-radius:50%;overflow:hidden;background:#e2e8f0;flex:0 0 58px}.aspi-c-avatar img{width:100%;height:100%;object-fit:cover}.aspi-c-small{font-size:12px;color:#64748b}.dark .aspi-c-small{color:#94a3b8}@media(max-width:760px){.aspi-c-grid{grid-template-columns:1fr}.aspi-c-list{grid-template-columns:1fr}}
</style>
<div id="aspiCommitteeModal" aria-hidden="true">
<div class="aspi-committee-box">
<div class="aspi-c-head"><div><div style="font-size:20px;font-weight:900">সাংগঠনিক কমিটি</div><div class="aspi-c-small">Homepage committee management</div></div><button class="aspi-c-btn aspi-c-muted" id="aspiCClose">✕</button></div>
<div class="aspi-c-grid">
<form class="aspi-c-form" id="aspiCForm">
<input type="hidden" id="aspiCId">
<label>নাম (বাংলা)<input id="aspiCBn" required></label><label>Name (English)<input id="aspiCEn"></label>
<label>পদবি (বাংলা)<input id="aspiDgBn"></label><label>Designation (English)<input id="aspiDgEn"></label>
<label>ছবি<input id="aspiCImage" type="file" accept="image/jpeg,image/png,image/webp"></label>
<label><input id="aspiCStatus" type="checkbox" checked style="width:auto;margin-right:6px"> Homepage-এ প্রকাশ করুন</label>
<label>ক্রম<input id="aspiCSort" type="number" min="0" value="0"></label>
<div class="aspi-c-actions"><button class="aspi-c-btn aspi-c-primary" type="submit">সংরক্ষণ</button><button class="aspi-c-btn aspi-c-muted" type="button" id="aspiCReset">নতুন</button></div>
<div id="aspiCMsg" class="aspi-c-small" style="margin-top:10px"></div>
</form>
<div><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px"><strong>সদস্য তালিকা</strong><button class="aspi-c-btn aspi-c-muted" id="aspiCRefresh" type="button">Refresh</button></div><div id="aspiCList" class="aspi-c-list"></div></div>
</div></div></div>
<script>
(function(){
let token='',items=[];const $=id=>document.getElementById(id);
function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
async function json(url,opt){const r=await fetch(url,opt||{credentials:'same-origin',cache:'no-store'});let j={};try{j=await r.json()}catch(e){}if(!r.ok)throw Error(j.error||('HTTP '+r.status));return j}
async function csrf(){token=(await json('../committee.php?action=csrf')).csrf_token||''}
async function load(){try{items=(await json('../committee.php?action=list&admin=1')).committee||[];render()}catch(e){$('aspiCMsg').textContent=e.message}}
function render(){const box=$('aspiCList');box.innerHTML=items.length?'':'<div class="aspi-c-small">কোনো সদস্য যোগ করা হয়নি।</div>';items.forEach(m=>{const d=document.createElement('div');d.className='aspi-c-card';d.innerHTML='<div class="aspi-c-person"><div class="aspi-c-avatar">'+(m.image_url?'<img src="../'+esc(m.image_url)+'">':'')+'</div><div><div style="font-weight:900">'+esc(m.name_bn||m.name_en)+'</div><div class="aspi-c-small">'+esc(m.designation_bn||m.designation_en)+'</div></div></div><div class="aspi-c-actions" style="margin-top:10px"><button class="aspi-c-btn aspi-c-primary" data-edit="'+m.id+'">Edit</button><button class="aspi-c-btn aspi-c-danger" data-del="'+m.id+'">Delete</button></div>';box.appendChild(d)});box.querySelectorAll('[data-edit]').forEach(b=>b.onclick=()=>edit(+b.dataset.edit));box.querySelectorAll('[data-del]').forEach(b=>b.onclick=()=>del(+b.dataset.del))}
function reset(){['aspiCId','aspiCBn','aspiCEn','aspiDgBn','aspiDgEn'].forEach(x=>$(x).value='');$('aspiCImage').value='';$('aspiCStatus').checked=true;$('aspiCSort').value=0}
function edit(id){const m=items.find(x=>+x.id===id);if(!m)return;$('aspiCId').value=m.id;$('aspiCBn').value=m.name_bn||'';$('aspiCEn').value=m.name_en||'';$('aspiDgBn').value=m.designation_bn||'';$('aspiDgEn').value=m.designation_en||'';$('aspiCStatus').checked=!!+m.status;$('aspiCSort').value=m.sort_order||0}
async function upload(f){if(!f)return '';const fd=new FormData();fd.append('file',f);fd.append('csrf_token',token);return (await json('../committee.php?action=upload',{method:'POST',body:fd,credentials:'same-origin'})).url}
async function del(id){if(!confirm('এই সদস্যকে মুছে ফেলবেন?'))return;const j=await json('../committee.php?action=delete',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf_token:token,id})});if(j.status==='success')load()}
function open(){ $('aspiCommitteeModal').classList.add('open');$('aspiCommitteeModal').setAttribute('aria-hidden','false');csrf().then(load)}function close(){ $('aspiCommitteeModal').classList.remove('open') }
function addButton(){const nav=document.querySelector('nav');if(!nav||document.getElementById('aspiCommitteeOpen'))return;const b=document.createElement('button');b.id='aspiCommitteeOpen';b.type='button';b.className='sidebar-item';b.innerHTML='<i class="fa-solid fa-people-group"></i><span>সাংগঠনিক কমিটি</span>';b.onclick=open;nav.appendChild(b)}
$('aspiCClose').onclick=close;$('aspiCRefresh').onclick=load;$('aspiCReset').onclick=reset;$('aspiCForm').onsubmit=async e=>{e.preventDefault();try{const old=items.find(x=>+x.id===+$('aspiCId').value);let image=old?.image_url||'';if($('aspiCImage').files[0])image=await upload($('aspiCImage').files[0]);const body={csrf_token:token,id:+$('aspiCId').value||0,name_bn:$('aspiCBn').value.trim(),name_en:$('aspiCEn').value.trim(),designation_bn:$('aspiDgBn').value.trim(),designation_en:$('aspiDgEn').value.trim(),image_url:image,status:$('aspiCStatus').checked?1:0,sort_order:+$('aspiCSort').value||0};const j=await json('../committee.php?action=save',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});if(j.status!=='success')throw Error(j.error||'Save failed');$('aspiCMsg').textContent='সংরক্ষণ সম্পন্ন হয়েছে।';reset();load()}catch(e){$('aspiCMsg').textContent=e.message}};
window.addEventListener('DOMContentLoaded',addButton);setTimeout(addButton,700);
})();
</script>
HTML;

$html = str_ireplace('</head>', $runtime . '</head>', $html, $count);
if ($count === 0) $html = $runtime . $html;
header('Content-Type: text/html; charset=utf-8');
echo $html;
