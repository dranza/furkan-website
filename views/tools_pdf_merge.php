<div class="container py-5">
  <style>
    /* Prevent long filenames from overflowing (especially on narrow widths) */
    .merge-item-main{min-width:0;}
    .merge-item-title{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  </style>
  <div class="row align-items-end g-3 mb-4">
    <div class="col-lg-8">
      <h1 class="fw-bold mb-1">PDF Birleştirme</h1>
      <p class="text-muted mb-0">PDF dosyalarını <strong>tarayıcı içinde</strong> birleştir. Dosyalar sunucuya yüklenmez. Sürükle-bırak ile sıralayabilir, sayfa aralığı seçebilir ve önizleme alabilirsin.</p>
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
              <div class="fw-semibold">Dosyaları ekle</div>
              <div class="small text-muted">Birden fazla PDF seçebilir veya sürükleyip bırakabilirsin.</div>
            </div>
            <div class="badge text-bg-light border">Ücretsiz</div>
          </div>

          <!-- Use a <label> so clicking always opens the file picker (works even on Safari/iOS). -->
          <label id="dropZone" for="fileInput" class="mt-3 p-4 text-center rounded border d-block" style="border-style: dashed!important; cursor:pointer;">
            <div class="display-6">📎</div>
            <div class="fw-semibold">Buraya bırak</div>
            <div class="small text-muted">veya tıklayıp PDF seç</div>
          </label>
          <!-- Keep the input in the DOM (not display:none) to allow programmatic access reliably. -->
          <input id="fileInput" type="file" accept="application/pdf" multiple class="visually-hidden" aria-hidden="true">

          <hr class="my-4">

          <div class="vstack gap-3">
            <div>
              <label class="form-label fw-semibold">Çıktı dosya adı</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-file-earmark-pdf"></i></span>
                <input id="outName" class="form-control" value="birlesik.pdf" placeholder="birlesik.pdf">
              </div>
              <div class="form-text">.pdf uzantısı otomatik eklenir.</div>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="insertBlank">
              <label class="form-check-label" for="insertBlank">Her dosya arasına 1 boş sayfa ekle</label>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="keepMeta" checked>
              <label class="form-check-label" for="keepMeta">Başlık/Yazar gibi PDF meta bilgilerini koru (varsa)</label>
            </div>

            <button id="mergeBtn" class="btn btn-primary btn-lg" type="button" disabled>
              <i class="bi bi-union me-1"></i>Birleştir ve indir
            </button>

            <div class="progress d-none" id="progWrap" style="height: 10px;">
              <div class="progress-bar" id="progBar" role="progressbar" style="width: 0%"></div>
            </div>

            <div class="small text-muted" id="statusText">İpucu: Listedeki kartları sürükleyerek sırayı değiştirebilirsin.</div>

            <div class="alert alert-info small mb-0">
              <div class="fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i>Gizlilik</div>
              Bu araç <strong>tarayıcıda</strong> çalışır. PDF’ler sunucuya yüklenmez.
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <div class="fw-semibold">Birleştirme listesi</div>
              <div class="small text-muted">Sırala • Aralık seç • Gereksiz dosyayı kaldır</div>
            </div>
            <div class="d-flex gap-2">
              <button id="clearBtn" class="btn btn-outline-danger btn-sm" type="button" disabled><i class="bi bi-trash3 me-1"></i>Temizle</button>
              <button id="demoBtn" class="btn btn-outline-secondary btn-sm" type="button"><i class="bi bi-lightning-charge me-1"></i>Demo</button>
            </div>
          </div>

          <div class="mt-3" id="listEmpty">
            <div class="p-4 rounded border text-center text-muted" style="border-style:dashed!important;">
              Henüz dosya eklenmedi.
            </div>
          </div>

          <div class="vstack gap-3 mt-3" id="fileList" aria-live="polite"></div>

          <div class="mt-3 small text-muted">
            <div class="d-flex gap-2 flex-wrap">
              <span class="badge text-bg-light border">Aralık örnekleri: <code>1-3</code>, <code>1,3,5</code>, <code>2-4,7</code>, <code>all</code></span>
              <span class="badge text-bg-light border">Önizleme: ilk sayfa küçük resim</span>
            </div>
          </div>

        </div>
      </div>

      <div class="card shadow-sm mt-4">
        <div class="card-body">
          <div class="fw-semibold mb-2">Sık sorulanlar</div>
          <div class="accordion" id="faqAcc">
            <div class="accordion-item">
              <h2 class="accordion-header" id="f1h">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f1" aria-expanded="false" aria-controls="f1">PDF’ler sunucuya gidiyor mu?</button>
              </h2>
              <div id="f1" class="accordion-collapse collapse" aria-labelledby="f1h" data-bs-parent="#faqAcc">
                <div class="accordion-body">Hayır. Birleştirme işlemi tarayıcıda yapılır. Dosyalar sadece cihazında işlenir.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="f2h">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f2" aria-expanded="false" aria-controls="f2">Büyük PDF’lerde takılma olursa?</button>
              </h2>
              <div id="f2" class="accordion-collapse collapse" aria-labelledby="f2h" data-bs-parent="#faqAcc">
                <div class="accordion-body">Çok büyük PDF’lerde işlem süresi cihazına bağlıdır. Daha az dosya seçmek veya aralık kısıtlamak yardımcı olur.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php endif; ?>
</div>

<?php if (empty($error ?? null)): ?>
<!-- Libraries -->
<!--
  NOTE: We intentionally do NOT use SRI (integrity=...) here.
  Some hosting setups/CDNs rewrite files which can break SRI and prevent libraries from loading,
  resulting in "file selected but nothing happens".
-->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js" crossorigin="anonymous"></script>

<script>
// Robust CDN loader (tries multiple mirrors). If all fail, the tool shows a clear error message.
(function(){
  function loadScript(url){
    return new Promise((resolve, reject)=>{
      const s=document.createElement('script');
      s.src=url; s.async=true; s.defer=true;
      s.onload=()=>resolve(url);
      s.onerror=()=>reject(new Error('Yüklenemedi: '+url));
      document.head.appendChild(s);
    });
  }
  async function ensureSortable(){
    if (window.Sortable) return;
    const urls=[
      'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
      'https://unpkg.com/sortablejs@1.15.2/Sortable.min.js',
      'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js'
    ];
    let lastErr;
    for (const u of urls){
      try { await loadScript(u); if (window.Sortable) return; } catch(e){ lastErr=e; }
    }
    throw lastErr || new Error('Sortable yüklenemedi');
  }
  async function ensurePDFLib(){
    if (window.PDFLib) return;
    const urls=[
      'https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js',
      'https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js',
      'https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js'
    ];
    let lastErr;
    for (const u of urls){
      try { await loadScript(u); if (window.PDFLib) return; } catch(e){ lastErr=e; }
    }
    throw lastErr || new Error('PDFLib yüklenemedi');
  }
  window.__pdfMergeEnsureLibs = async function(){
    await ensureSortable();
    await ensurePDFLib();
  };
})();
</script>
<script>
(async function(){
  // Hard fail early if required libs didn't load (common cause: blocked CDN / broken SRI / CSP).
  if (!window.PDFLib || !window.PDFLib.PDFDocument) {
    const statusText = document.getElementById('statusText');
    const mergeBtn = document.getElementById('mergeBtn');
    const clearBtn = document.getElementById('clearBtn');
    if (mergeBtn) mergeBtn.disabled = true;
    if (clearBtn) clearBtn.disabled = true;
    if (statusText) {
      statusText.textContent = 'Gerekli kütüphaneler yüklenemedi (PDF-LIB). Adblock/CSP/CDN engeli olabilir.';
    }
    console.error('PDF-LIB failed to load. window.PDFLib is missing.');
    return;
  }
  if (!window.Sortable) {
    console.warn('SortableJS failed to load. Drag & drop ordering will be disabled.');
  }

  const { PDFDocument } = window.PDFLib;
  // pdf.js global can vary by build/version. Prefer the standard `window.pdfjsLib`.
  // If it's unavailable, we gracefully disable thumbnail generation.
  const pdfjsLib = null; // preview uses browser's built-in PDF renderer (no pdf.js needed)

  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');
  const fileList = document.getElementById('fileList');
  const listEmpty = document.getElementById('listEmpty');
  const mergeBtn = document.getElementById('mergeBtn');
  const clearBtn = document.getElementById('clearBtn');
  const demoBtn = document.getElementById('demoBtn');
  const outName = document.getElementById('outName');
  const insertBlank = document.getElementById('insertBlank');
  const keepMeta = document.getElementById('keepMeta');
  const progWrap = document.getElementById('progWrap');
  const progBar = document.getElementById('progBar');
  const statusText = document.getElementById('statusText');

  /** @type {{id:string,file:File,name:string,size:number,range:string,pages:number|null,thumbUrl:string|null}[]} */
  let items = [];

  const fmtBytes = (b)=>{
    if (!Number.isFinite(b) || b<=0) return '0 B';
    const u=['B','KB','MB','GB'];
    let i=0; let n=b;
    while(n>=1024 && i<u.length-1){n/=1024;i++;}
    return (i===0? n.toFixed(0): n.toFixed(1))+' '+u[i];
  };

  function setProgress(p){
    progWrap.classList.remove('d-none');
    const v = Math.max(0, Math.min(100, p));
    progBar.style.width = v+'%';
    progBar.setAttribute('aria-valuenow', String(v));
    if (v>=100) {
      setTimeout(()=>{ progWrap.classList.add('d-none'); progBar.style.width='0%'; }, 800);
    }
  }

  function slugId(){
    return Math.random().toString(16).slice(2)+Date.now().toString(16);
  }

  function normalizeOutName(){
    let n = (outName.value || '').trim();
    if (!n) n = 'birlesik.pdf';
    if (!/\.pdf$/i.test(n)) n += '.pdf';
    outName.value = n;
    return n;
  }

  function updateButtons(){
    const has = items.length>0;
    mergeBtn.disabled = !has;
    clearBtn.disabled = !has;
    listEmpty.style.display = has ? 'none' : '';
  }

  function parseRanges(spec, maxPages){
    spec = (spec || '').trim().toLowerCase();
    if (!spec || spec==='all' || spec==='*') {
      return Array.from({length:maxPages}, (_,i)=>i);
    }
    // Accept: 1-3,5,7-9
    const parts = spec.split(',').map(s=>s.trim()).filter(Boolean);
    const set = new Set();
    for (const part of parts){
      const m = part.match(/^([0-9]+)\s*-\s*([0-9]+)$/);
      if (m){
        let a = parseInt(m[1],10), b = parseInt(m[2],10);
        if (!Number.isFinite(a)||!Number.isFinite(b)) continue;
        if (a>b) [a,b]=[b,a];
        a = Math.max(1, Math.min(maxPages, a));
        b = Math.max(1, Math.min(maxPages, b));
        for (let i=a;i<=b;i++) set.add(i-1);
        continue;
      }
      const one = parseInt(part,10);
      if (Number.isFinite(one) && one>=1 && one<=maxPages) set.add(one-1);
    }
    const arr = Array.from(set);
    arr.sort((x,y)=>x-y);
    return arr.length ? arr : Array.from({length:maxPages}, (_,i)=>i);
  }

  function makeThumbUrl(file){
  try{
    const url = URL.createObjectURL(file);
    // hide viewer UI where supported
    return url + '#page=1&toolbar=0&navpanes=0&scrollbar=0';
  }catch(e){
    return null;
  }
}

  async function countPages(file){
    try{
      const ab = await file.arrayBuffer();
      const doc = await PDFDocument.load(ab, { ignoreEncryption: true });
      return doc.getPageCount();
    } catch(e){
      return null;
    }
  }

  function render(){
    fileList.innerHTML='';

    for (const it of items){
      const card = document.createElement('div');
      card.className='p-3 rounded border';
      card.dataset.id = it.id;

      card.innerHTML = `
        <div class="d-flex align-items-start gap-3">
          <div class="rounded border bg-light" style="width:84px;height:110px;display:flex;align-items:center;justify-content:center;overflow:hidden;flex:0 0 auto;">
            ${it.thumbUrl ? `<embed src="${it.thumbUrl}" type="application/pdf" style="width:100%;height:100%;" />` : `<div class="text-muted small">Önizleme</div>`}
          </div>
          <div class="flex-grow-1 merge-item-main">
            <div class="d-flex align-items-start justify-content-between gap-2">
              <div class="merge-item-main">
                <div class="fw-semibold merge-item-title" title="${escapeAttr(it.name)}">${escapeHtml(it.name)}</div>
                <div class="small text-muted">${fmtBytes(it.size)}${it.pages ? ` • ${it.pages} sayfa` : ''}</div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm handle" type="button" title="Sürükle">
                  <i class="bi bi-grip-vertical"></i>
                </button>
                <button class="btn btn-outline-danger btn-sm rm" type="button" title="Kaldır">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>

            <div class="row g-2 mt-2 align-items-end">
              <div class="col-md-7">
                <label class="form-label small text-muted mb-1">Sayfa aralığı</label>
                <input class="form-control form-control-sm range" value="${escapeAttr(it.range)}" placeholder="all veya 1-3,5">
              </div>
              <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Not</label>
                <div class="small text-muted">Boş bırakılırsa: <code>all</code></div>
              </div>
            </div>
          </div>
        </div>
      `;

      card.querySelector('.rm').addEventListener('click', ()=>{
        items = items.filter(x=>x.id!==it.id);
        render();
      });
      card.querySelector('.range').addEventListener('input', (e)=>{
        it.range = (e.target.value || 'all');
      });

      fileList.appendChild(card);
    }

    updateButtons();
  }

  function escapeHtml(s){
    return String(s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }
  function escapeAttr(s){
    return escapeHtml(s).replace(/\n/g,' ');
  }

  async function addFiles(fileListObj){
    const pdfs = Array.from(fileListObj || []).filter(f=>/\.pdf$/i.test(f.name) || f.type==='application/pdf');
    if (!pdfs.length) return;

    statusText.textContent = 'Dosyalar hazırlanıyor…';
    setProgress(5);

    for (let i=0;i<pdfs.length;i++){
      const f = pdfs[i];
      const id = slugId();
      const blobUrl = URL.createObjectURL(f);
      const it = { id, file:f, name:f.name, size:f.size, range:'all', pages:null, blobUrl, thumbUrl: (blobUrl + '#page=1&toolbar=0&navpanes=0&scrollbar=0') };
      items.push(it);

      // async enrich: pages + thumbnail
      (async ()=>{
        const [pg, th] = await Promise.all([countPages(f), makeThumb(f)]);
        it.pages = pg;
        it.thumbUrl = th;
        render();
      })();

      setProgress(5 + Math.round((i+1)/pdfs.length*25));
    }

    render();
    statusText.textContent = 'Hazır. Dosya sırasını sürükle-bırak ile değiştirebilirsin.';
    setProgress(30);
    setTimeout(()=>setProgress(100), 300);
  }

  async function doMerge(){
    if (!items.length) return;
    normalizeOutName();

    mergeBtn.disabled = true;
    statusText.textContent = 'Birleştiriliyor…';
    setProgress(1);

    try{
      const merged = await PDFDocument.create();
      const keep = !!keepMeta.checked;
      let metaSet = false;

      for (let idx=0; idx<items.length; idx++){
        const it = items[idx];
        setProgress(5 + Math.round((idx/items.length)*70));

        const bytes = await it.file.arrayBuffer();
        const srcDoc = await PDFDocument.load(bytes, { ignoreEncryption: true });

        if (keep && !metaSet){
          try{
            const title = srcDoc.getTitle?.();
            const author = srcDoc.getAuthor?.();
            const subject = srcDoc.getSubject?.();
            const keywords = srcDoc.getKeywords?.();
            if (title) merged.setTitle(title);
            if (author) merged.setAuthor(author);
            if (subject) merged.setSubject(subject);
            if (keywords) merged.setKeywords(keywords);
            metaSet = true;
          } catch(e) {}
        }

        const totalPages = srcDoc.getPageCount();
        const wantedIdx = parseRanges(it.range, totalPages);
        const pages = await merged.copyPages(srcDoc, wantedIdx);
        for (const p of pages) merged.addPage(p);

        if (insertBlank.checked && idx < items.length-1) {
          merged.addPage([595.28, 841.89]); // A4
        }
      }

      setProgress(85);
      const outBytes = await merged.save({ useObjectStreams: true, addDefaultPage: false });
      const blob = new Blob([outBytes], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);

      const a = document.createElement('a');
      a.href = url;
      a.download = normalizeOutName();
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);

      setProgress(100);
      statusText.textContent = 'İndirildi ✅';

      // Record usage stats
      try {
        await fetch('<?= e(base_url('araclar/pdf-birlestirme')) ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ files: items.length })
        });
      } catch(e) {}

    } catch(err){
      console.error(err);
      statusText.textContent = 'Hata: PDF birleştirilemedi. Dosya şifreli/bozuk olabilir.';
      alert('PDF birleştirilemedi. Dosya şifreli/bozuk olabilir.');
      setProgress(100);
    } finally {
      mergeBtn.disabled = false;
    }
  }

  // Sortable (drag & drop ordering)
  if (window.Sortable) {
    new Sortable(fileList, {
      animation: 150,
      handle: '.handle',
      onEnd: ()=>{
        const order = Array.from(fileList.children).map(el=>el.dataset.id);
        items.sort((a,b)=>order.indexOf(a.id)-order.indexOf(b.id));
        render();
      }
    });
  }

  // Dropzone interactions
  // Click-to-select is handled by the <label for="fileInput"> in the HTML for maximum browser compatibility.
  fileInput.addEventListener('change', (e)=> addFiles(e.target.files));

  ;['dragenter','dragover'].forEach(ev=>{
    dropZone.addEventListener(ev, (e)=>{
      e.preventDefault(); e.stopPropagation();
      dropZone.classList.add('border-primary');
    });
  });
  ;['dragleave','drop'].forEach(ev=>{
    dropZone.addEventListener(ev, (e)=>{
      e.preventDefault(); e.stopPropagation();
      dropZone.classList.remove('border-primary');
    });
  });
  dropZone.addEventListener('drop', (e)=>{
    addFiles(e.dataTransfer.files);
  });

  mergeBtn.addEventListener('click', doMerge);
  clearBtn.addEventListener('click', ()=>{
    if (!confirm('Listedeki tüm dosyalar silinsin mi?')) return;
    items.forEach(x=>{ if(x.blobUrl) { try{ URL.revokeObjectURL(x.blobUrl);}catch(e){} } });
    items = [];
    render();
    statusText.textContent = 'Liste temizlendi.';
  });

  demoBtn.addEventListener('click', async ()=>{
    alert('Demo: Kendi PDF dosyalarını ekleyerek deneyebilirsin.');
  });

  updateButtons();
})();
</script>
<?php endif; ?>
