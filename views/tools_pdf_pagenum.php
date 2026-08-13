<div class="container py-5">
  <div class="row align-items-end g-3 mb-4">
    <div class="col-lg-8">
      <h1 class="fw-bold mb-1">PDF Sayfa Numarası Ekle</h1>
      <p class="text-muted mb-0">PDF’e sayfa numarası ekle. Konum, başlangıç numarası ve biçim ayarlarıyla <strong>tarayıcı içinde</strong> çıktı al.</p>
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
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label fw-semibold">Başlangıç</label>
                <input id="startNum" type="number" min="1" class="form-control" value="1">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold">Yazı boyutu</label>
                <input id="fontSize" type="number" min="6" max="48" class="form-control" value="10">
              </div>
            </div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label fw-semibold">Konum</label>
                <select id="pos" class="form-select">
                  <option value="bottom-center" selected>Alt Orta</option>
                  <option value="bottom-right">Alt Sağ</option>
                  <option value="bottom-left">Alt Sol</option>
                  <option value="top-right">Üst Sağ</option>
                  <option value="top-left">Üst Sol</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold">Biçim</label>
                <select id="format" class="form-select">
                  <option value="n" selected>1</option>
                  <option value="n_of_total">1 / 10</option>
                  <option value="page_n_of_total">Sayfa 1 / 10</option>
                </select>
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold">Uygulanacak sayfalar</label>
              <input id="ranges" class="form-control" value="all" placeholder="Örn: all veya 2-10">
              <div class="form-text">Örn: <code>all</code>, <code>2-10</code>, <code>1,3,5</code></div>
            </div>
            <div>
              <label class="form-label fw-semibold">Çıktı adı</label>
              <input id="outName" class="form-control" value="numarali.pdf">
            </div>
            <button id="runBtn" class="btn btn-primary btn-lg" type="button" disabled><i class="bi bi-123 me-1"></i>Numara ekle ve indir</button>
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
            <div class="small text-muted mt-2">Not: Bu önizleme mevcut PDF’i gösterir. Numara eklenmiş çıktı indirildikten sonra görülür.</div>
          </div>
        </div>
      </div>

      <div class="alert alert-info small mt-4 mb-0">
        <div class="fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i>Gizlilik</div>
        İşlem cihazında yapılır. Dosyan sunucuya yüklenmez.
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
  const pdfInput = document.getElementById('pdfInput');
  const runBtn = document.getElementById('runBtn');
  const statusText = document.getElementById('statusText');
  const fileMeta = document.getElementById('fileMeta');
  const previewWrap = document.getElementById('previewWrap');
  const pdfPreview = document.getElementById('pdfPreview');
  const rangesEl = document.getElementById('ranges');
  const startNumEl = document.getElementById('startNum');
  const fontSizeEl = document.getElementById('fontSize');
  const posEl = document.getElementById('pos');
  const formatEl = document.getElementById('format');
  const outNameEl = document.getElementById('outName');
  const progWrap = document.getElementById('progWrap');
  const progBar = document.getElementById('progBar');
  let file = null;

  function setProgress(p){ progWrap.classList.remove('d-none'); progBar.style.width = Math.max(0,Math.min(100,p))+'%'; }
  function hideProgress(){ progWrap.classList.add('d-none'); progBar.style.width='0%'; }
  function normalizeOutName(name){ name=(name||'').trim()||'numarali.pdf'; if(!name.toLowerCase().endsWith('.pdf')) name+='.pdf'; return name; }
  function downloadBytes(bytes, name){
    const blob=new Blob([bytes],{type:'application/pdf'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a'); a.href=url; a.download=name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(()=>URL.revokeObjectURL(url), 5000);
  }
  function parseRanges(str, pageCount){
    const s=(str||'').trim().toLowerCase();
    if(!s || s==='all') return Array.from({length:pageCount},(_,i)=>i);
    const out=new Set();
    const parts=s.split(/\s*,\s*/).filter(Boolean);
    for(const part of parts){
      const m=part.match(/^(\d+)(?:\s*-\s*(\d+))?$/);
      if(!m) continue;
      const a=Math.max(1,parseInt(m[1],10));
      const b=m[2]?Math.max(1,parseInt(m[2],10)):a;
      const start=Math.min(a,b), end=Math.max(a,b);
      for(let p=start;p<=end;p++){ if(p>=1 && p<=pageCount) out.add(p-1); }
    }
    return Array.from(out).sort((x,y)=>x-y);
  }
  function textFor(n,total){
    const f=formatEl.value;
    if(f==='n') return String(n);
    if(f==='n_of_total') return `${n} / ${total}`;
    return `Sayfa ${n} / ${total}`;
  }
  function coords(pos,w,h,fontSize){
    const margin=18;
    const yTop=h - margin - fontSize;
    const yBottom=margin;
    if(pos==='bottom-right') return {x:w - margin, y:yBottom, align:'right'};
    if(pos==='bottom-left') return {x:margin, y:yBottom, align:'left'};
    if(pos==='top-right') return {x:w - margin, y:yTop, align:'right'};
    if(pos==='top-left') return {x:margin, y:yTop, align:'left'};
    return {x:w/2, y:yBottom, align:'center'};
  }

  pdfInput.addEventListener('change', ()=>{
    file = pdfInput.files && pdfInput.files[0] ? pdfInput.files[0] : null;
    if(!file){ runBtn.disabled=true; fileMeta.textContent='Henüz dosya seçilmedi.'; previewWrap.style.display='none'; return; }
    runBtn.disabled=false;
    fileMeta.textContent = file.name + ' • ' + Math.round(file.size/1024) + ' KB';
    const url=URL.createObjectURL(file);
    pdfPreview.src=url;
    previewWrap.style.display='block';
    statusText.textContent='Hazır. Ayarları seçip indir.';
  });

  const dz = document.querySelector('label[for="pdfInput"]');
  ['dragenter','dragover'].forEach(ev=>dz.addEventListener(ev,(e)=>{e.preventDefault(); dz.classList.add('border-primary');}));
  ['dragleave','drop'].forEach(ev=>dz.addEventListener(ev,(e)=>{e.preventDefault(); dz.classList.remove('border-primary');}));
  dz.addEventListener('drop',(e)=>{
    const f=e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files[0] : null;
    if(!f) return;
    if (f.type !== 'application/pdf' && !f.name.toLowerCase().endsWith('.pdf')) { statusText.textContent='Lütfen PDF dosyası seç.'; return; }
    const dt=new DataTransfer(); dt.items.add(f); pdfInput.files=dt.files; pdfInput.dispatchEvent(new Event('change'));
  });

  runBtn.addEventListener('click', async ()=>{
    if(!file) return;
    runBtn.disabled=true;
    statusText.textContent='İşleniyor…';
    setProgress(10);
    try{
      await window.__ensurePDFLib();
      setProgress(25);
      const buf=await file.arrayBuffer();
      const pdf=await PDFLib.PDFDocument.load(buf);
      const pageCount=pdf.getPageCount();
      const idxs=parseRanges(rangesEl.value, pageCount);
      if(!idxs.length) throw new Error('Seçim boş.');
      const fontSize=Math.max(6, Math.min(48, parseInt(fontSizeEl.value||'10',10)));
      const startNum=Math.max(1, parseInt(startNumEl.value||'1',10));
      const helv = await pdf.embedFont(PDFLib.StandardFonts.Helvetica);
      setProgress(55);
      for (let i=0;i<idxs.length;i++){
        const pageIndex = idxs[i];
        const page = pdf.getPage(pageIndex);
        const {width,height} = page.getSize();
        const n = startNum + i;
        const text = textFor(n, idxs.length);
        const c = coords(posEl.value, width, height, fontSize);
        const textWidth = helv.widthOfTextAtSize(text, fontSize);
        const x = (c.align==='center') ? (c.x - textWidth/2) : (c.align==='right' ? (c.x - textWidth) : c.x);
        page.drawText(text, {x, y:c.y, size:fontSize, font:helv, color: PDFLib.rgb(0.2,0.2,0.2)});
        if (i % 5 === 0) setProgress(55 + Math.round((i/idxs.length)*35));
      }
      const bytes=await pdf.save();
      setProgress(95);
      downloadBytes(bytes, normalizeOutName(outNameEl.value));
      setProgress(100);
      try { await fetch(location.href, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({files:1})}); } catch(e) {}
      statusText.textContent='Tamamlandı. Dosya indirildi.';
    }catch(e){
      console.error(e);
      statusText.textContent = (e && e.message) ? e.message : 'İşlem başarısız.';
    }finally{
      runBtn.disabled = !file;
      setTimeout(hideProgress, 800);
    }
  });
})();
</script>
<?php endif; ?>
