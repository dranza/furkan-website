<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="fw-bold mb-1">PDF Filigran</h1>
      <div class="text-muted">PDF’e yazı filigranı ekle. Konum, opaklık, boyut ve açı ayarlarıyla. Dosyalar sunucuya yüklenmez.</div>
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
            <div class="fs-1">🏷️</div>
            <div class="fw-semibold">Buraya bırak</div>
            <div class="text-muted small">veya tıklayıp PDF seç</div>
          </label>
          <input id="pdfInput" type="file" accept="application/pdf" class="visually-hidden" />

          <div class="mt-3">
            <label class="form-label fw-semibold">Filigran metni</label>
            <input id="wmText" class="form-control" value="FurkanCihan.com.tr" />
          </div>

          <div class="row g-2 mt-2">
            <div class="col-6">
              <label class="form-label fw-semibold">Boyut</label>
              <input id="fontSize" type="number" class="form-control" value="42" min="8" max="200" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Opaklık</label>
              <input id="opacity" type="number" step="0.05" class="form-control" value="0.15" min="0.05" max="1" />
            </div>
          </div>

          <div class="row g-2 mt-2">
            <div class="col-6">
              <label class="form-label fw-semibold">Açı</label>
              <input id="angle" type="number" class="form-control" value="-30" min="-180" max="180" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Konum</label>
              <select id="pos" class="form-select">
                <option value="center">Orta</option>
                <option value="diagonal">Çapraz (sayfaya yay)</option>
                <option value="bottom">Alt orta</option>
                <option value="top">Üst orta</option>
              </select>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Çıktı dosya adı</label>
            <input id="outName" class="form-control" value="filigranli.pdf" />
          </div>

          <button id="btnRun" class="btn btn-primary w-100 mt-3" disabled>Uygula ve indir</button>
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
          <div class="text-muted small">
            İpucu: “Çapraz” modu özellikle kurumsal filigran için idealdir.
          </div>
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
  const $=(id)=>document.getElementById(id);
  const pdfInput=$('pdfInput');
  const dropZone=$('dropZone');
  const wmText=$('wmText');
  const fontSize=$('fontSize');
  const opacity=$('opacity');
  const angle=$('angle');
  const pos=$('pos');
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

  ;['dragenter','dragover'].forEach(ev=>dropZone.addEventListener(ev,(e)=>{e.preventDefault(); dropZone.classList.add('bg-light');}));
  ;['dragleave','drop'].forEach(ev=>dropZone.addEventListener(ev,(e)=>{e.preventDefault(); dropZone.classList.remove('bg-light');}));
  dropZone.addEventListener('drop',(e)=>{ const f=e.dataTransfer?.files?.[0]; if (f){ pdfInput.files=e.dataTransfer.files; setFile(f); setEnabled(); } });
  pdfInput.addEventListener('change',()=>{ const f=pdfInput.files?.[0]; if (f){ setFile(f); setEnabled(); } });
  btnClear.addEventListener('click',()=>{ file=null; pdfInput.value=''; status.textContent='Hazır.'; setFile(null); setEnabled(); });

  function download(blob, filename){
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a'); a.href=url; a.download=filename; document.body.appendChild(a); a.click(); a.remove();
    setTimeout(()=>URL.revokeObjectURL(url), 5000);
  }

  async function record(pagesCount){
    try{ await fetch('<?= e(base_url('araclar/pdf-filigran')) ?>', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({pages: pagesCount})}); }catch(e){}
  }

  function clamp(n,min,max){ return Math.max(min, Math.min(max, n)); }

  btnRun.addEventListener('click', async ()=>{
    if (!file) return;
    btnRun.disabled=true;
    status.textContent='İşleniyor…';

    const ok=await ensurePdfLib();
    if (!ok){ status.textContent='PDF motoru yüklenemedi. Reklam engelleyiciyi kapatıp yenile.'; setEnabled(); return; }

    try{
      const text=(wmText.value||'').trim();
      if (!text) throw new Error('Filigran metni boş olamaz.');
      const size=clamp(parseInt(fontSize.value||'42',10)||42, 8, 200);
      const op=clamp(parseFloat(opacity.value||'0.15')||0.15, 0.05, 1);
      const ang=clamp(parseInt(angle.value||'-30',10)||-30, -180, 180);

      const bytes=await file.arrayBuffer();
      const doc=await PDFLib.PDFDocument.load(bytes);
      const pages=doc.getPages();
      const total=pages.length;

      const font = await doc.embedFont(PDFLib.StandardFonts.Helvetica);

      pages.forEach((page)=>{
        const {width, height} = page.getSize();

        const drawOne = (x,y,rot)=>{
          page.drawText(text, {
            x, y,
            size,
            font,
            color: PDFLib.rgb(0.2,0.2,0.2),
            opacity: op,
            rotate: PDFLib.degrees(rot)
          });
        };

        if (pos.value==='center'){
          const tw = font.widthOfTextAtSize(text, size);
          drawOne((width-tw)/2, height/2, ang);
          return;
        }
        if (pos.value==='bottom'){
          const tw = font.widthOfTextAtSize(text, size);
          drawOne((width-tw)/2, 24, 0);
          return;
        }
        if (pos.value==='top'){
          const tw = font.widthOfTextAtSize(text, size);
          drawOne((width-tw)/2, height - size - 24, 0);
          return;
        }

        // diagonal: repeat in a grid
        const stepX = Math.max(180, size*6);
        const stepY = Math.max(140, size*4);
        for (let x=-width; x<width*2; x+=stepX){
          for (let y=-height; y<height*2; y+=stepY){
            drawOne(x, y, ang);
          }
        }
      });

      const outBytes=await doc.save();
      const blob=new Blob([outBytes], {type:'application/pdf'});
      const name=(outName.value||'filigranli.pdf').trim() || 'filigranli.pdf';
      download(blob, name.endsWith('.pdf')?name:(name+'.pdf'));

      status.textContent=`Tamamlandı. Filigran uygulanan sayfa: ${total}`;
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
