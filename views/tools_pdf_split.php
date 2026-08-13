<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="fw-bold mb-1">PDF Böl</h1>
      <div class="text-muted">PDF dosyanı sayfalara veya seçtiğin aralıklara göre böl. Dosyalar sunucuya yüklenmez.</div>
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
            <div class="fs-1">📄</div>
            <div class="fw-semibold">Buraya bırak</div>
            <div class="text-muted small">veya tıklayıp PDF seç</div>
          </label>
          <input id="pdfInput" type="file" accept="application/pdf" class="visually-hidden" />

          <div class="mt-3">
            <label class="form-label fw-semibold">Bölme modu</label>
            <select id="mode" class="form-select">
              <option value="each">Her sayfayı ayrı PDF</option>
              <option value="ranges">Aralıklara göre böl (örn: 1-3; 4-4; 5-7)</option>
            </select>
          </div>

          <div id="rangesWrap" class="mt-3 d-none">
            <label class="form-label fw-semibold">Aralıklar</label>
            <input id="ranges" class="form-control" placeholder="1-3; 4; 5-7" />
            <div class="form-text">Noktalı virgülle ayır. Tek sayfa için “4” yazabilirsin.</div>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Çıktı dosya adı</label>
            <input id="baseName" class="form-control" value="bolunmus" />
            <div class="form-text">Her çıktı otomatik numaralandırılır.</div>
          </div>

          <button id="btnSplit" class="btn btn-primary w-100 mt-3" disabled>PDF'i Böl</button>

          <div class="mt-3 small text-muted">
            <div>• Gizlilik: Dosyalar tarayıcı içinde işlenir.</div>
            <div>• Büyük PDF’lerde işlem sürebilir. Sekmeyi kapatma.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-2">
            <h5 class="fw-semibold mb-0">Çıktılar</h5>
            <button id="btnClear" class="btn btn-sm btn-outline-danger" disabled>Temizle</button>
          </div>
          <div class="text-muted small mt-1" id="fileInfo">Henüz dosya seçilmedi.</div>
          <hr />

          <div id="outList" class="vstack gap-2"></div>

          <div id="hint" class="text-muted small mt-3">PDF seçip “PDF'i Böl” butonuna bas.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const $ = (id) => document.getElementById(id);
  const pdfInput = $('pdfInput');
  const dropZone = $('dropZone');
  const mode = $('mode');
  const rangesWrap = $('rangesWrap');
  const ranges = $('ranges');
  const btnSplit = $('btnSplit');
  const btnClear = $('btnClear');
  const outList = $('outList');
  const fileInfo = $('fileInfo');
  const hint = $('hint');
  const baseName = $('baseName');

  let file = null;
  let pdfLibReady = false;

  function toast(msg, type='primary'){
    const el = document.createElement('div');
    el.className = `alert alert-${type} py-2 px-3`; el.textContent = msg;
    outList.prepend(el);
    setTimeout(()=>el.remove(), 3500);
  }

  function loadScript(src){
    return new Promise((resolve, reject)=>{
      const s=document.createElement('script');
      s.src=src; s.async=true;
      s.onload=()=>resolve();
      s.onerror=()=>reject(new Error('failed '+src));
      document.head.appendChild(s);
    });
  }

  async function ensurePdfLib(){
    if (pdfLibReady) return true;
    const cdns = [
      'https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js',
      'https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js',
      'https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js'
    ];
    for (const url of cdns){
      try { await loadScript(url); pdfLibReady = !!window.PDFLib; if (pdfLibReady) return true; } catch(e) {}
    }
    return false;
  }

  function parseRanges(str, maxPages){
    const parts = (str||'').split(';').map(s=>s.trim()).filter(Boolean);
    const out=[];
    for (const p of parts){
      const m = p.match(/^([0-9]+)\s*-\s*([0-9]+)$/);
      if (m){
        let a=parseInt(m[1],10), b=parseInt(m[2],10);
        if (a<1||b<1) continue;
        if (a>b) [a,b]=[b,a];
        a=Math.min(a,maxPages); b=Math.min(b,maxPages);
        out.push({from:a, to:b});
        continue;
      }
      const n = parseInt(p,10);
      if (!isNaN(n) && n>=1){
        out.push({from:Math.min(n,maxPages), to:Math.min(n,maxPages)});
      }
    }
    return out;
  }

  function setEnabled(){
    const ok = !!file && pdfLibReady;
    btnSplit.disabled = !ok;
    btnClear.disabled = !file && outList.children.length===0;
  }

  mode.addEventListener('change', ()=>{
    rangesWrap.classList.toggle('d-none', mode.value !== 'ranges');
  });

  function setFile(f){
    file = f;
    outList.innerHTML='';
    hint.textContent='';
    if (!file){
      fileInfo.textContent='Henüz dosya seçilmedi.';
      setEnabled();
      return;
    }
    fileInfo.textContent = `${file.name} • ${(file.size/1024/1024).toFixed(2)} MB`;
    setEnabled();
  }

  // Drag-drop
  ;['dragenter','dragover'].forEach(ev=>{
    dropZone.addEventListener(ev, (e)=>{ e.preventDefault(); dropZone.classList.add('bg-light'); });
  });
  ;['dragleave','drop'].forEach(ev=>{
    dropZone.addEventListener(ev, (e)=>{ e.preventDefault(); dropZone.classList.remove('bg-light'); });
  });
  dropZone.addEventListener('drop', (e)=>{
    const f = e.dataTransfer?.files?.[0];
    if (f) { pdfInput.files = e.dataTransfer.files; setFile(f); }
  });

  pdfInput.addEventListener('change', ()=>{
    const f = pdfInput.files?.[0];
    if (f) setFile(f);
  });

  btnClear.addEventListener('click', ()=>{
    file=null; pdfInput.value='';
    outList.innerHTML='';
    hint.textContent='PDF seçip “PDF\'i Böl” butonuna bas.';
    setEnabled();
  });

  async function record(outputs, pages){
    try{ await fetch('<?= e(base_url('araclar/pdf-bol')) ?>', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({outputs, pages})}); }catch(e){}
  }

  function downloadBlob(blob, filename){
    const url = URL.createObjectURL(blob);
    const a=document.createElement('a');
    a.href=url; a.download=filename;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(()=>URL.revokeObjectURL(url), 5000);
  }

  btnSplit.addEventListener('click', async ()=>{
    if (!file) return;
    btnSplit.disabled=true;
    outList.innerHTML='';
    hint.textContent='İşleniyor…';

    const ok = await ensurePdfLib();
    if (!ok){ toast('PDF motoru yüklenemedi. Lütfen reklam engelleyiciyi kapatıp yenile.', 'danger'); hint.textContent=''; setEnabled(); return; }

    try {
      const bytes = await file.arrayBuffer();
      const srcDoc = await PDFLib.PDFDocument.load(bytes);
      const total = srcDoc.getPageCount();

      let groups=[];
      if (mode.value === 'each'){
        groups = Array.from({length: total}, (_,i)=>({from:i+1,to:i+1}));
      } else {
        groups = parseRanges(ranges.value, total);
        if (!groups.length) throw new Error('Aralıklar boş veya hatalı');
      }

      let idx=0;
      for (const g of groups){
        idx++;
        const out = await PDFLib.PDFDocument.create();
        const pageIdx = [];
        for (let p=g.from; p<=g.to; p++) pageIdx.push(p-1);
        const copied = await out.copyPages(srcDoc, pageIdx);
        copied.forEach(pg=>out.addPage(pg));
        const outBytes = await out.save();
        const blob = new Blob([outBytes], {type:'application/pdf'});
        const fname = `${(baseName.value||'bolunmus').trim() || 'bolunmus'}_${idx}.pdf`;

        const row = document.createElement('div');
        row.className='d-flex align-items-center justify-content-between border rounded-3 p-2';
        row.innerHTML = `<div class="text-truncate" style="min-width:0;"><div class="fw-semibold text-truncate" title="${fname}">${fname}</div><div class="small text-muted">Sayfalar: ${g.from}-${g.to} • ${(blob.size/1024).toFixed(0)} KB</div></div>`;
        const btn = document.createElement('button');
        btn.className='btn btn-sm btn-outline-primary';
        btn.textContent='İndir';
        btn.addEventListener('click', ()=>downloadBlob(blob, fname));
        row.appendChild(btn);
        outList.appendChild(row);

        // auto-download first 1 output for convenience
        if (idx===1) downloadBlob(blob, fname);
      }

      hint.textContent='';
      toast('Bölme tamamlandı.', 'success');
      record(groups.length, total);

    } catch (err){
      console.error(err);
      toast(err?.message || 'İşlem başarısız.', 'danger');
      hint.textContent='';
    } finally {
      setEnabled();
    }
  });

  // init
  ensurePdfLib().then(()=>setEnabled());
})();
</script>
