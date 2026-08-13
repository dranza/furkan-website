<div class="container py-5">
  <div class="row align-items-end g-3 mb-4">
    <div class="col-lg-8">
      <h1 class="fw-bold mb-1">PDF Metadata Düzenle</h1>
      <p class="text-muted mb-0">PDF başlık, yazar, konu ve anahtar kelimeleri düzenle. İşlem <strong>tarayıcı içinde</strong> yapılır.</p>
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
              <div class="small text-muted">Dosya sunucuya yüklenmez.</div>
            </div>
            <span class="badge text-bg-light border">Ücretsiz</span>
          </div>

          <label for="pdfInput" class="mt-3 p-4 text-center rounded border d-block" style="border-style:dashed!important; cursor:pointer;">
            <div class="display-6">📄</div>
            <div class="fw-semibold">Tıklayıp PDF seç</div>
            <div class="small text-muted">veya sürükleyip bırak</div>
          </label>
          <input id="pdfInput" type="file" accept="application/pdf" class="visually-hidden" aria-hidden="true">

          <hr class="my-4">

          <div class="vstack gap-3">
            <div>
              <label class="form-label fw-semibold">Başlık (Title)</label>
              <input id="metaTitle" class="form-control" placeholder="Örn: Rapor 2026">
            </div>
            <div>
              <label class="form-label fw-semibold">Yazar (Author)</label>
              <input id="metaAuthor" class="form-control" placeholder="Örn: Furkan Cihan">
            </div>
            <div>
              <label class="form-label fw-semibold">Konu (Subject)</label>
              <input id="metaSubject" class="form-control" placeholder="Örn: Bilgi İşlem">
            </div>
            <div>
              <label class="form-label fw-semibold">Anahtar Kelimeler (Keywords)</label>
              <input id="metaKeywords" class="form-control" placeholder="Örn: IT, güvenlik, rapor">
              <div class="form-text">Virgülle ayırabilirsin.</div>
            </div>
            <div>
              <label class="form-label fw-semibold">Çıktı adı</label>
              <input id="outName" class="form-control" value="metadata-duzenlenmis.pdf">
            </div>

            <button id="runBtn" class="btn btn-primary btn-lg" type="button" disabled><i class="bi bi-pencil-square me-1"></i>Metadata uygula ve indir</button>
            <div class="progress d-none" id="progWrap" style="height:10px;"><div class="progress-bar" id="progBar" style="width:0%"></div></div>
            <div class="small text-muted" id="statusText">PDF seçince araç aktif olur.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold">Önizleme</div>
          <div class="small text-muted" id="fileMeta">Henüz dosya seçilmedi.</div>
          <div class="mt-3" id="previewWrap" style="display:none;">
            <div class="ratio ratio-4x3 rounded border overflow-hidden">
              <embed id="pdfPreview" type="application/pdf" src="" style="width:100%;height:100%;" />
            </div>
            <div class="small text-muted mt-2">Not: Bu önizleme mevcut PDF’i gösterir. Metadata uygulanmış çıktı indirildikten sonra görülür.</div>
          </div>
        </div>
      </div>

      <div class="alert alert-info small mt-4 mb-0">
        <div class="fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i>Gizlilik</div>
        İşlem cihazında yapılır. Dosya sunucuya yüklenmez.
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>

<?php if (empty($error ?? null)): ?>
<script>
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
  const pdfInput=document.getElementById('pdfInput');
  const runBtn=document.getElementById('runBtn');
  const statusText=document.getElementById('statusText');
  const fileMeta=document.getElementById('fileMeta');
  const previewWrap=document.getElementById('previewWrap');
  const pdfPreview=document.getElementById('pdfPreview');
  const titleEl=document.getElementById('metaTitle');
  const authorEl=document.getElementById('metaAuthor');
  const subjectEl=document.getElementById('metaSubject');
  const keywordsEl=document.getElementById('metaKeywords');
  const outNameEl=document.getElementById('outName');
  const progWrap=document.getElementById('progWrap');
  const progBar=document.getElementById('progBar');
  let file=null;
  let url=null;

  function setProgress(p){ progWrap.classList.remove('d-none'); progBar.style.width=Math.max(0,Math.min(100,p))+'%'; }
  function hideProgress(){ progWrap.classList.add('d-none'); progBar.style.width='0%'; }
  function normalizeOutName(name){ name=(name||'').trim()||'metadata-duzenlenmis.pdf'; if(!name.toLowerCase().endsWith('.pdf')) name+='.pdf'; return name; }
  function downloadBytes(bytes,name){
    const blob=new Blob([bytes],{type:'application/pdf'});
    const u=URL.createObjectURL(blob);
    const a=document.createElement('a'); a.href=u; a.download=name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(()=>URL.revokeObjectURL(u),5000);
  }

  const dz=document.querySelector('label[for="pdfInput"]');
  ['dragenter','dragover'].forEach(ev=>dz.addEventListener(ev,(e)=>{e.preventDefault(); dz.classList.add('border-primary');}));
  ['dragleave','drop'].forEach(ev=>dz.addEventListener(ev,(e)=>{e.preventDefault(); dz.classList.remove('border-primary');}));
  dz.addEventListener('drop',(e)=>{
    const f=e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files[0] : null;
    if(!f) return;
    file=f;
    handlePicked();
  });

  function handlePicked(){
    if(!file){ runBtn.disabled=true; fileMeta.textContent='Henüz dosya seçilmedi.'; previewWrap.style.display='none'; return; }
    runBtn.disabled=false;
    fileMeta.textContent = file.name + ' • ' + Math.round(file.size/1024) + ' KB';
    if (url) URL.revokeObjectURL(url);
    url = URL.createObjectURL(file);
    pdfPreview.src=url;
    previewWrap.style.display='block';
    statusText.textContent='Hazır. Alanları düzenleyip indir.';
  }

  pdfInput.addEventListener('change', ()=>{
    file = (pdfInput.files && pdfInput.files[0]) ? pdfInput.files[0] : file;
    handlePicked();
  });

  runBtn.addEventListener('click', async ()=>{
    if(!file) return;
    runBtn.disabled=true;
    hideProgress();
    try{
      statusText.textContent='Kütüphaneler hazırlanıyor...';
      setProgress(10);
      await window.__ensurePDFLib();
      const { PDFDocument } = window.PDFLib;
      setProgress(20);

      statusText.textContent='PDF okunuyor...';
      const pdfBytes = new Uint8Array(await file.arrayBuffer());
      setProgress(40);

      statusText.textContent='Metadata uygulanıyor...';
      const pdfDoc = await PDFDocument.load(pdfBytes, { ignoreEncryption:false });
      const t=(titleEl.value||'').trim();
      const a=(authorEl.value||'').trim();
      const s=(subjectEl.value||'').trim();
      const k=(keywordsEl.value||'').split(',').map(x=>x.trim()).filter(Boolean);
      if (t) pdfDoc.setTitle(t);
      if (a) pdfDoc.setAuthor(a);
      if (s) pdfDoc.setSubject(s);
      if (k.length) pdfDoc.setKeywords(k);
      setProgress(70);

      const outBytes = await pdfDoc.save({ useObjectStreams:true });
      setProgress(95);
      downloadBytes(outBytes, normalizeOutName(outNameEl.value));
      statusText.textContent='Tamamlandı. İndiriliyor...';
      setProgress(100);

      try{ await fetch(location.pathname, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({files:1})}); }catch(e){}
      setTimeout(()=>hideProgress(), 900);
    } catch(e){
      console.error(e);
      statusText.textContent = 'Hata: ' + (e && e.message ? e.message : 'İşlem başarısız');
      hideProgress();
    } finally {
      runBtn.disabled = !file;
    }
  });
})();
</script>
<?php endif; ?>
