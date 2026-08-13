<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="fw-bold mb-1">PDF Sayfa Döndür</h1>
      <div class="text-muted">PDF sayfalarını 90/180/270 derece döndür. Dosyalar sunucuya yüklenmez.</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('araclar')) ?>">← Araçlara dön</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-warning"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="fw-semibold">Dosya seç</h5>
          <label id="dropZone" for="pdfInput" class="border rounded-4 p-4 w-100 text-center" style="cursor:pointer; border-style:dashed;">
            <div class="fs-1">🔄</div>
            <div class="fw-semibold">Buraya bırak</div>
            <div class="text-muted small">veya tıklayıp PDF seç</div>
          </label>
          <input id="pdfInput" type="file" accept="application/pdf" class="visually-hidden" />

          <div class="mt-3">
            <label class="form-label fw-semibold">Döndürme</label>
            <select id="deg" class="form-select">
              <option value="90">90°</option>
              <option value="180">180°</option>
              <option value="270">270°</option>
            </select>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Hangi sayfalar?</label>
            <select id="target" class="form-select">
              <option value="all">Tüm sayfalar</option>
              <option value="spec">Seçtiğim sayfalar (örn: 1,3,5-7)</option>
            </select>
          </div>

          <div id="specWrap" class="mt-3 d-none">
            <input id="spec" class="form-control" placeholder="1,3,5-7" />
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Çıktı dosya adı</label>
            <input id="outName" class="form-control" value="dondurulmus.pdf" />
          </div>

          <button id="btnRun" class="btn btn-primary w-100 mt-3" disabled>Döndür ve indir</button>
          <button id="btnClear" class="btn btn-outline-danger w-100 mt-2" disabled>Temizle</button>

          <div class="mt-3 small text-muted" id="fileInfo">Henüz dosya seçilmedi.</div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="fw-semibold mb-1">Durum</h5>
          <div id="status" class="text-muted">Hazır.</div>
          <hr />
          <div class="text-muted small">İpucu: Seçili sayfalar için: <span class="badge text-bg-light border">1,3,5-7</span></div>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <div class="fw-semibold">Gizlilik</div>
        <div class="small">Bu araç tarayıcıda çalışır. Dosyaların sunucuya yüklenmez.</div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const $ = (id)=>document.getElementById(id);
  const pdfInput=$('pdfInput');
  const dropZone=$('dropZone');
  const deg=$('deg');
  const target=$('target');
  const specWrap=$('specWrap');
  const spec=$('spec');
  const outName=$('outName');
  const btnRun=$('btnRun');
  const btnClear=$('btnClear');
  const status=$('status');
  const fileInfo=$('fileInfo');

  let file=null;
  let pdfLibReady=false;

  function loadScript(src){
    return new Promise((resolve,reject)=>{ const s=document.createElement('script'); s.src=src; s.async=true; s.onload=()=>resolve(); s.onerror=()=>reject(); document.head.appendChild(s); });
  }
  async function ensurePdfLib(){
    if (pdfLibReady) return true;
    const cdns=[
      'https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js',
      'https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js',
      'https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js'
    ];
    for (const u of cdns){ try{ await loadScript(u); if (window.PDFLib){ pdfLibReady=true; return true; } }catch(e){} }
    return false;
  }

  function parseSpec(specStr, maxPages){
    const s=(specStr||'').trim();
    if (!s) return [];
    const parts=s.split(',').map(x=>x.trim()).filter(Boolean);
    const set=new Set();
    for (const p of parts){
      const m=p.match(/^([0-9]+)\s*-\s*([0-9]+)$/);
      if (m){
        let a=parseInt(m[1],10), b=parseInt(m[2],10);
        if (a>b) [a,b]=[b,a];
        for (let i=a;i<=b;i++){ if (i>=1 && i<=maxPages) set.add(i); }
      } else {
        const n=parseInt(p,10);
        if (!isNaN(n) && n>=1 && n<=maxPages) set.add(n);
      }
    }
    return Array.from(set).sort((a,b)=>a-b);
  }

  function setEnabled(){
    btnRun.disabled = !file || !pdfLibReady;
    btnClear.disabled = !file;
  }

  function setFile(f){
    file=f;
    if (!file){ fileInfo.textContent='Henüz dosya seçilmedi.'; status.textContent='Hazır.'; return; }
    fileInfo.textContent = `${file.name} • ${(file.size/1024/1024).toFixed(2)} MB`;
    status.textContent='Hazır. Ayarları seçip butona bas.';
  }

  target.addEventListener('change', ()=>{ specWrap.classList.toggle('d-none', target.value!=='spec'); });

  ;['dragenter','dragover'].forEach(ev=>dropZone.addEventListener(ev,(e)=>{e.preventDefault(); dropZone.classList.add('bg-light');}));
  ;['dragleave','drop'].forEach(ev=>dropZone.addEventListener(ev,(e)=>{e.preventDefault(); dropZone.classList.remove('bg-light');}));
  dropZone.addEventListener('drop',(e)=>{ const f=e.dataTransfer?.files?.[0]; if (f){ pdfInput.files=e.dataTransfer.files; setFile(f); setEnabled(); } });
  pdfInput.addEventListener('change',()=>{ const f=pdfInput.files?.[0]; if (f){ setFile(f); setEnabled(); } });
  btnClear.addEventListener('click',()=>{ file=null; pdfInput.value=''; spec.value=''; status.textContent='Hazır.'; setFile(null); setEnabled(); });

  function download(blob, filename){
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a'); a.href=url; a.download=filename; document.body.appendChild(a); a.click(); a.remove();
    setTimeout(()=>URL.revokeObjectURL(url), 5000);
  }

  async function record(pagesCount){
    try{ await fetch('<?= e(base_url('araclar/pdf-dondur')) ?>', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({pages: pagesCount})}); }catch(e){}
  }

  btnRun.addEventListener('click', async ()=>{
    if (!file) return;
    btnRun.disabled=true;
    status.textContent='İşleniyor…';

    const ok=await ensurePdfLib();
    if (!ok){ status.textContent='PDF motoru yüklenemedi. Reklam engelleyiciyi kapatıp yenile.'; setEnabled(); return; }

    try{
      const bytes=await file.arrayBuffer();
      const doc=await PDFLib.PDFDocument.load(bytes);
      const total=doc.getPageCount();
      const degVal=parseInt(deg.value,10);

      let targets=[];
      if (target.value==='all'){
        targets = Array.from({length: total}, (_,i)=>i);
      } else {
        const pagesList=parseSpec(spec.value, total);
        if (!pagesList.length) throw new Error('Döndürülecek sayfaları gir.');
        targets = pagesList.map(n=>n-1);
      }

      targets.forEach(i=>{
        const page=doc.getPage(i);
        const cur=page.getRotation().angle || 0;
        page.setRotation(PDFLib.degrees((cur + degVal) % 360));
      });

      const outBytes=await doc.save();
      const blob=new Blob([outBytes], {type:'application/pdf'});
      const name=(outName.value||'dondurulmus.pdf').trim() || 'dondurulmus.pdf';
      download(blob, name.endsWith('.pdf')?name:(name+'.pdf'));

      status.textContent=`Tamamlandı. Döndürülen sayfa sayısı: ${targets.length}`;
      record(total);
    } catch(err){
      console.error(err);
      status.textContent = err?.message || 'İşlem başarısız.';
    } finally {
      setEnabled();
    }
  });

  ensurePdfLib().then(()=>setEnabled());
})();
</script>
