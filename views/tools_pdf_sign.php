<div class="container py-5">
  <div class="row align-items-end g-3 mb-4">
    <div class="col-lg-8">
      <h1 class="fw-bold mb-1">PDF İmza Ekle</h1>
      <p class="text-muted mb-0">PDF’e PNG/JPG imza ekle. Konum, boyut, opaklık ve sayfa aralığı seçerek <strong>tarayıcı içinde</strong> çıktı al.</p>
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
              <div class="fw-semibold">Dosyaları seç</div>
              <div class="small text-muted">Dosyalar sunucuya yüklenmez.</div>
            </div>
            <span class="badge text-bg-light border">Ücretsiz</span>
          </div>

          <div class="vstack gap-3 mt-3">
            <label for="pdfInput" class="p-4 text-center rounded border d-block" style="border-style:dashed!important; cursor:pointer;">
              <div class="display-6">📄</div>
              <div class="fw-semibold">Tıklayıp PDF seç</div>
              <div class="small text-muted">veya sürükleyip bırak</div>
            </label>
            <input id="pdfInput" type="file" accept="application/pdf" class="visually-hidden" aria-hidden="true">

            <label for="imgInput" class="p-4 text-center rounded border d-block" style="border-style:dashed!important; cursor:pointer;">
              <div class="display-6">✍️</div>
              <div class="fw-semibold">Tıklayıp İmza Görseli seç (PNG/JPG)</div>
              <div class="small text-muted">veya sürükleyip bırak</div>
            </label>
            <input id="imgInput" type="file" accept="image/png,image/jpeg" class="visually-hidden" aria-hidden="true">
          </div>

          <hr class="my-4">

          <div class="vstack gap-3">
            <div>
              <label class="form-label fw-semibold">Konum</label>
              <select id="pos" class="form-select">
                <option value="bottom-right" selected>Alt Sağ</option>
                <option value="bottom-left">Alt Sol</option>
                <option value="bottom-center">Alt Orta</option>
                <option value="top-right">Üst Sağ</option>
                <option value="top-left">Üst Sol</option>
                <option value="top-center">Üst Orta</option>
                <option value="center">Orta</option>
              </select>
            </div>

            <div class="row g-2">
              <div class="col-6">
                <label class="form-label fw-semibold">Genişlik (px)</label>
                <input id="sigWidth" type="number" min="20" max="2000" class="form-control" value="180">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold">Opaklık</label>
                <input id="opacity" type="number" min="0" max="1" step="0.05" class="form-control" value="1">
              </div>
            </div>

            <div class="row g-2">
              <div class="col-6">
                <label class="form-label fw-semibold">Döndür (°)</label>
                <input id="rotate" type="number" min="-180" max="180" class="form-control" value="0">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold">Kenar boşluğu (px)</label>
                <input id="margin" type="number" min="0" max="200" class="form-control" value="24">
              </div>
            </div>

            <div>
              <label class="form-label fw-semibold">Uygulanacak sayfalar</label>
              <input id="ranges" class="form-control" value="all" placeholder="Örn: all veya 1,3,5-7">
              <div class="form-text">Örn: <code>all</code>, <code>2-10</code>, <code>1,3,5-7</code></div>
            </div>

            <div>
              <label class="form-label fw-semibold">Çıktı adı</label>
              <input id="outName" class="form-control" value="imzali.pdf">
            </div>

            <button id="runBtn" class="btn btn-primary btn-lg" type="button" disabled><i class="bi bi-pen me-1"></i>İmza ekle ve indir</button>
            <div class="progress d-none" id="progWrap" style="height:10px;"><div class="progress-bar" id="progBar" style="width:0%"></div></div>
            <div class="small text-muted" id="statusText">PDF ve imza görseli seçince araç aktif olur.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold">Önizleme</div>
          <div class="small text-muted" id="fileMeta">Henüz dosya seçilmedi.</div>

          <div class="mt-3" id="previews" style="display:none;">
            <div class="row g-3">
              <div class="col-md-7">
                <div class="ratio ratio-4x3 rounded border overflow-hidden">
                  <embed id="pdfPreview" type="application/pdf" src="" style="width:100%;height:100%;" />
                </div>
              </div>
              <div class="col-md-5">
                <div class="rounded border p-2 bg-body-tertiary">
                  <div class="small text-muted mb-2">İmza görseli</div>
                  <div class="ratio ratio-4x3 rounded overflow-hidden bg-white border">
                    <img id="imgPreview" alt="İmza" style="width:100%;height:100%;object-fit:contain;" />
                  </div>
                </div>
              </div>
            </div>

            <div class="small text-muted mt-2">Not: Önizleme seçtiğin PDF ve imzayı gösterir. İmzalı çıktı indirdikten sonra görülür.</div>
          </div>
        </div>
      </div>

      <div class="alert alert-info small mt-4 mb-0">
        <div class="fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i>Gizlilik</div>
        İşlem cihazında yapılır. Dosyalar sunucuya yüklenmez.
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
  const imgInput=document.getElementById('imgInput');
  const runBtn=document.getElementById('runBtn');
  const statusText=document.getElementById('statusText');
  const fileMeta=document.getElementById('fileMeta');
  const previews=document.getElementById('previews');
  const pdfPreview=document.getElementById('pdfPreview');
  const imgPreview=document.getElementById('imgPreview');
  const posEl=document.getElementById('pos');
  const sigWidthEl=document.getElementById('sigWidth');
  const opacityEl=document.getElementById('opacity');
  const rotateEl=document.getElementById('rotate');
  const marginEl=document.getElementById('margin');
  const rangesEl=document.getElementById('ranges');
  const outNameEl=document.getElementById('outName');
  const progWrap=document.getElementById('progWrap');
  const progBar=document.getElementById('progBar');

  let pdfFile=null;
  let imgFile=null;
  let pdfUrl=null;
  let imgUrl=null;

  function setProgress(p){ progWrap.classList.remove('d-none'); progBar.style.width=Math.max(0,Math.min(100,p))+'%'; }
  function hideProgress(){ progWrap.classList.add('d-none'); progBar.style.width='0%'; }
  function normalizeOutName(name){ name=(name||'').trim()||'imzali.pdf'; if(!name.toLowerCase().endsWith('.pdf')) name+='.pdf'; return name; }
  function downloadBytes(bytes,name){
    const blob=new Blob([bytes],{type:'application/pdf'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a'); a.href=url; a.download=name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(()=>URL.revokeObjectURL(url),5000);
  }
  function parseRanges(str,pageCount){
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
  function updateState(){
    const ready=!!pdfFile && !!imgFile;
    runBtn.disabled=!ready;
    if(!ready){ statusText.textContent='PDF ve imza görseli seçince araç aktif olur.'; }
    else { statusText.textContent='Hazır. Ayarları seçip indir.'; }
    if(!pdfFile && !imgFile){ fileMeta.textContent='Henüz dosya seçilmedi.'; previews.style.display='none'; }
  }

  function attachDZ(labelFor, input){
    const dz=document.querySelector(`label[for="${labelFor}"]`);
    ['dragenter','dragover'].forEach(ev=>dz.addEventListener(ev,(e)=>{e.preventDefault(); dz.classList.add('border-primary');}));
    ['dragleave','drop'].forEach(ev=>dz.addEventListener(ev,(e)=>{e.preventDefault(); dz.classList.remove('border-primary');}));
    dz.addEventListener('drop',(e)=>{
      const f=e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files[0] : null;
      if(!f) return;
      // Some browsers block setting input.files. We still trigger processing below.
      if (e.dataTransfer.files && e.dataTransfer.files.length){
        try{ input.files = e.dataTransfer.files; }catch(err){}
      }
      const evt=new Event('change',{bubbles:true});
      input.dispatchEvent(evt);
      // Fallback: if files couldn't be assigned, manually handle
      if(!input.files || !input.files.length){
        if (input === pdfInput) { pdfFile = f; handlePDFPicked(); }
        if (input === imgInput) { imgFile = f; handleImgPicked(); }
      }
    });
  }
  attachDZ('pdfInput', pdfInput);
  attachDZ('imgInput', imgInput);

  function handlePDFPicked(){
    if (pdfUrl){ URL.revokeObjectURL(pdfUrl); pdfUrl=null; }
    if (pdfFile){ pdfUrl=URL.createObjectURL(pdfFile); pdfPreview.src=pdfUrl; }
    const parts=[];
    if (pdfFile) parts.push(`PDF: ${pdfFile.name} • ${Math.round(pdfFile.size/1024)} KB`);
    if (imgFile) parts.push(`İmza: ${imgFile.name} • ${Math.round(imgFile.size/1024)} KB`);
    fileMeta.textContent = parts.length? parts.join('  |  ') : 'Henüz dosya seçilmedi.';
    previews.style.display = (pdfFile || imgFile) ? 'block' : 'none';
    updateState();
  }
  function handleImgPicked(){
    if (imgUrl){ URL.revokeObjectURL(imgUrl); imgUrl=null; }
    if (imgFile){ imgUrl=URL.createObjectURL(imgFile); imgPreview.src=imgUrl; }
    else { imgPreview.removeAttribute('src'); }
    const parts=[];
    if (pdfFile) parts.push(`PDF: ${pdfFile.name} • ${Math.round(pdfFile.size/1024)} KB`);
    if (imgFile) parts.push(`İmza: ${imgFile.name} • ${Math.round(imgFile.size/1024)} KB`);
    fileMeta.textContent = parts.length? parts.join('  |  ') : 'Henüz dosya seçilmedi.';
    previews.style.display = (pdfFile || imgFile) ? 'block' : 'none';
    updateState();
  }

  pdfInput.addEventListener('change', ()=>{
    pdfFile = (pdfInput.files && pdfInput.files[0]) ? pdfInput.files[0] : pdfFile;
    handlePDFPicked();
  });
  imgInput.addEventListener('change', ()=>{
    imgFile = (imgInput.files && imgInput.files[0]) ? imgInput.files[0] : imgFile;
    handleImgPicked();
  });

  function placement(pos, pageW, pageH, imgW, imgH, margin){
    const xMid=(pageW-imgW)/2;
    const yMid=(pageH-imgH)/2;
    if(pos==='bottom-right') return {x:pageW-imgW-margin, y:margin};
    if(pos==='bottom-left') return {x:margin, y:margin};
    if(pos==='bottom-center') return {x:xMid, y:margin};
    if(pos==='top-right') return {x:pageW-imgW-margin, y:pageH-imgH-margin};
    if(pos==='top-left') return {x:margin, y:pageH-imgH-margin};
    if(pos==='top-center') return {x:xMid, y:pageH-imgH-margin};
    if(pos==='center') return {x:xMid, y:yMid};
    return {x:pageW-imgW-margin, y:margin};
  }

  runBtn.addEventListener('click', async ()=>{
    if(!pdfFile || !imgFile) return;
    runBtn.disabled=true;
    hideProgress();
    try{
      statusText.textContent='Kütüphaneler hazırlanıyor...';
      setProgress(8);
      await window.__ensurePDFLib();
      const { PDFDocument, degrees } = window.PDFLib;
      setProgress(18);

      statusText.textContent='Dosyalar okunuyor...';
      const pdfBytes = new Uint8Array(await pdfFile.arrayBuffer());
      const imgBytes = new Uint8Array(await imgFile.arrayBuffer());
      setProgress(35);

      statusText.textContent='PDF işleniyor...';
      const pdfDoc = await PDFDocument.load(pdfBytes, { ignoreEncryption: false });
      const isPng = (imgFile.type||'').toLowerCase().includes('png') || (imgFile.name||'').toLowerCase().endsWith('.png');
      const embeddedImg = isPng ? await pdfDoc.embedPng(imgBytes) : await pdfDoc.embedJpg(imgBytes);

      const pages = pdfDoc.getPages();
      const pageIdxs = parseRanges(rangesEl.value, pages.length);
      const wantW = Math.max(20, Math.min(2000, parseInt(sigWidthEl.value||'180',10)));
      const scale = wantW / embeddedImg.width;
      const drawW = embeddedImg.width * scale;
      const drawH = embeddedImg.height * scale;
      const opacity = Math.max(0, Math.min(1, parseFloat(opacityEl.value||'1')));
      const rot = parseFloat(rotateEl.value||'0');
      const margin = Math.max(0, Math.min(200, parseInt(marginEl.value||'24',10)));
      const pos = posEl.value;

      let i=0;
      for (const idx of pageIdxs){
        const p = pages[idx];
        const { width:pw, height:ph } = p.getSize();
        const { x, y } = placement(pos, pw, ph, drawW, drawH, margin);
        p.drawImage(embeddedImg, {
          x, y,
          width: drawW,
          height: drawH,
          opacity,
          rotate: degrees(rot || 0)
        });
        i++;
        const prog = 35 + Math.round((i / Math.max(1,pageIdxs.length)) * 55);
        setProgress(prog);
      }

      statusText.textContent='PDF hazırlanıyor...';
      const outBytes = await pdfDoc.save({ useObjectStreams: true });
      setProgress(95);

      downloadBytes(outBytes, normalizeOutName(outNameEl.value));
      statusText.textContent='Tamamlandı. İndiriliyor...';
      setProgress(100);

      try{ await fetch(location.pathname, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({files:1})}); }catch(e){}
      setTimeout(()=>hideProgress(), 900);
    } catch (e){
      console.error(e);
      statusText.textContent = 'Hata: ' + (e && e.message ? e.message : 'İşlem başarısız');
      hideProgress();
    } finally {
      runBtn.disabled = !(pdfFile && imgFile);
    }
  });

  updateState();
})();
</script>
<?php endif; ?>
