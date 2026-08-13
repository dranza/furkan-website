<div class="container py-5">
  <div class="row align-items-end g-3 mb-4">
    <div class="col-lg-8">
      <h1 class="fw-bold mb-1">PDF Sayfa Çıkar</h1>
      <p class="text-muted mb-0">PDF içinden seçtiğin sayfaları <strong>tarayıcı içinde</strong> yeni bir PDF olarak çıkar. Dosyalar sunucuya yüklenmez.</p>
    </div>
    <div class="col-lg-4 text-lg-end">
      <a class="btn btn-outline-secondary" href="<?= e(base_url('araclar')) ?>"><i class="bi bi-arrow-left me-1"></i>Araçlara dön</a>
    </div>
  </div>

  <?php if (!empty($error ?? null)): ?>
    <div class="alert alert-warning d-flex align-items-start gap-2">
      <i class="bi bi-exclamation-triangle"></i>
      <div>
        <div class="fw-semibold">Araç şu an kullanılamıyor</div>
        <div class="small text-muted"><?= e((string)$error) ?></div>
      </div>
    </div>
  <?php else: ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-semibold">PDF seç</div>
              <div class="small text-muted">Sürükle-bırak veya tıklayıp seç.</div>
            </div>
            <span class="badge text-bg-light border">Ücretsiz</span>
          </div>

          <label id="dropZone" for="pdfInput" class="mt-3 p-4 text-center rounded border d-block" style="border-style:dashed!important; cursor:pointer;">
            <div class="display-6">📎</div>
            <div class="fw-semibold">Buraya bırak</div>
            <div class="small text-muted">veya tıklayıp PDF seç</div>
          </label>
          <input id="pdfInput" type="file" accept="application/pdf" class="visually-hidden" aria-hidden="true">

          <hr class="my-4">

          <div class="vstack gap-3">
            <div>
              <label class="form-label fw-semibold">Çıkarılacak sayfalar</label>
              <input id="ranges" class="form-control" placeholder="Örn: 1-3, 5, 8-10" value="1">
              <div class="form-text">Destek: <code>1-3</code>, <code>1,3,5</code>, <code>2-4,7</code>, <code>all</code></div>
            </div>
            <div>
              <label class="form-label fw-semibold">Çıktı dosya adı</label>
              <input id="outName" class="form-control" value="cikarilan-sayfalar.pdf">
            </div>
            <button id="runBtn" class="btn btn-primary btn-lg" type="button" disabled><i class="bi bi-scissors me-1"></i>Çıkar ve indir</button>
            <div class="progress d-none" id="progWrap" style="height:10px;">
              <div class="progress-bar" id="progBar" role="progressbar" style="width:0%"></div>
            </div>
            <div class="small text-muted" id="statusText">PDF seçince araç aktif olur.</div>

            <div class="alert alert-info small mb-0">
              <div class="fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i>Gizlilik</div>
              Bu araç tarayıcıda çalışır. PDF cihazından dışarı çıkmaz.
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold">Seçili dosya</div>
          <div class="small text-muted" id="fileMeta">Henüz dosya seçilmedi.</div>
          <div class="mt-3" id="previewWrap" style="display:none;">
            <div class="ratio ratio-4x3 rounded border overflow-hidden">
              <embed id="pdfPreview" type="application/pdf" src="" style="width:100%;height:100%;" />
            </div>
            <div class="small text-muted mt-2">Önizleme tarayıcının PDF görüntüleyicisiyle açılır.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>

<?php if (empty($error ?? null)): ?>
<script>
// Minimal robust loader for PDF-LIB (tries multiple mirrors)
(function(){
  function loadScript(url){
    return new Promise((resolve,reject)=>{
      const s=document.createElement('script');
      s.src=url; s.async=true; s.defer=true;
      s.onload=()=>resolve(url);
      s.onerror=()=>reject(new Error('Yüklenemedi: '+url));
      document.head.appendChild(s);
    });
  }
  async function ensurePDFLib(){
    if (window.PDFLib && window.PDFLib.PDFDocument) return;
    const urls=[
      'https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js',
      'https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js',
      'https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js'
    ];
    let last;
    for (const u of urls){
      try{ await loadScript(u); if (window.PDFLib && window.PDFLib.PDFDocument) return; }catch(e){ last=e; }
    }
    throw last || new Error('PDFLib yüklenemedi');
  }
  window.__ensurePDFLib = ensurePDFLib;
})();
</script>

<script>
(async function(){
  const pdfInput = document.getElementById('pdfInput');
  const runBtn = document.getElementById('runBtn');
  const rangesEl = document.getElementById('ranges');
  const outNameEl = document.getElementById('outName');
  const statusText = document.getElementById('statusText');
  const fileMeta = document.getElementById('fileMeta');
  const previewWrap = document.getElementById('previewWrap');
  const pdfPreview = document.getElementById('pdfPreview');
  const progWrap = document.getElementById('progWrap');
  const progBar = document.getElementById('progBar');
  let file = null;

  function setProgress(p){
    progWrap.classList.remove('d-none');
    progBar.style.width = Math.max(0, Math.min(100, p)) + '%';
  }
  function hideProgress(){
    progWrap.classList.add('d-none');
    progBar.style.width='0%';
  }
  function normalizeOutName(name){
    name = (name || '').trim() || 'cikarilan-sayfalar.pdf';
    if (!name.toLowerCase().endsWith('.pdf')) name += '.pdf';
    return name;
  }
  function downloadBytes(bytes, name){
    const blob = new Blob([bytes], {type:'application/pdf'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(()=>URL.revokeObjectURL(url), 5000);
  }
  function parseRanges(str, pageCount){
    const s = (str||'').trim().toLowerCase();
    if (!s || s === 'all') return Array.from({length: pageCount}, (_,i)=>i);
    const out = new Set();
    const parts = s.split(/\s*,\s*/).filter(Boolean);
    for (const part of parts){
      const m = part.match(/^(\d+)(?:\s*-\s*(\d+))?$/);
      if (!m) continue;
      const a = Math.max(1, parseInt(m[1],10));
      const b = m[2] ? Math.max(1, parseInt(m[2],10)) : a;
      const start = Math.min(a,b);
      const end = Math.max(a,b);
      for (let p=start; p<=end; p++){
        if (p>=1 && p<=pageCount) out.add(p-1);
      }
    }
    return Array.from(out).sort((x,y)=>x-y);
  }

  pdfInput.addEventListener('change', ()=>{
    file = pdfInput.files && pdfInput.files[0] ? pdfInput.files[0] : null;
    if (!file) {
      runBtn.disabled = true;
      fileMeta.textContent = 'Henüz dosya seçilmedi.';
      previewWrap.style.display='none';
      return;
    }
    runBtn.disabled = false;
    fileMeta.textContent = file.name + ' • ' + Math.round(file.size/1024) + ' KB';
    const url = URL.createObjectURL(file);
    pdfPreview.src = url;
    previewWrap.style.display='block';
    statusText.textContent = 'Hazır. Çıkarılacak sayfaları seçip “Çıkar ve indir”e bas.';
  });

  // Drag & drop
  const dropZone = document.getElementById('dropZone');
  ['dragenter','dragover'].forEach(ev=>dropZone.addEventListener(ev,(e)=>{e.preventDefault(); dropZone.classList.add('border-primary');}));
  ['dragleave','drop'].forEach(ev=>dropZone.addEventListener(ev,(e)=>{e.preventDefault(); dropZone.classList.remove('border-primary');}));
  dropZone.addEventListener('drop', (e)=>{
    const f = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files[0] : null;
    if (!f) return;
    if (f.type !== 'application/pdf' && !f.name.toLowerCase().endsWith('.pdf')) {
      statusText.textContent = 'Lütfen PDF dosyası seç.';
      return;
    }
    const dt = new DataTransfer();
    dt.items.add(f);
    pdfInput.files = dt.files;
    pdfInput.dispatchEvent(new Event('change'));
  });

  runBtn.addEventListener('click', async ()=>{
    if (!file) return;
    runBtn.disabled = true;
    statusText.textContent = 'İşleniyor…';
    setProgress(10);
    try {
      await window.__ensurePDFLib();
      setProgress(20);
      const buf = await file.arrayBuffer();
      const srcPdf = await PDFLib.PDFDocument.load(buf);
      const pageCount = srcPdf.getPageCount();
      const idxs = parseRanges(rangesEl.value, pageCount);
      if (!idxs.length) throw new Error('Seçim boş. Sayfa aralığını kontrol et.');
      const outPdf = await PDFLib.PDFDocument.create();
      setProgress(45);
      const copied = await outPdf.copyPages(srcPdf, idxs);
      copied.forEach(p=>outPdf.addPage(p));
      setProgress(75);
      const bytes = await outPdf.save();
      downloadBytes(bytes, normalizeOutName(outNameEl.value));
      setProgress(100);

      // record usage (best-effort)
      try { await fetch(location.href, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({files:1})}); } catch(e) {}

      statusText.textContent = 'Tamamlandı. Dosya indirildi.';
    } catch (e) {
      console.error(e);
      statusText.textContent = (e && e.message) ? e.message : 'İşlem başarısız.';
    } finally {
      runBtn.disabled = !file;
      setTimeout(hideProgress, 800);
    }
  });
})();
</script>
<?php endif; ?>
