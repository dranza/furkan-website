<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="fw-bold mb-1">Resim Sıkıştır / Dönüştür</h1>
      <div class="text-muted">JPG/PNG/WebP görselleri tarayıcıda sıkıştır ve format dönüştür. Çoklu dosya + sürükle-bırak.</div>
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
          <h5 class="fw-semibold">Dosyaları ekle</h5>

          <label id="dropZone" for="imgInput" class="border rounded-4 p-4 w-100 text-center" style="cursor:pointer; border-style:dashed;">
            <div class="fs-1">🖼️</div>
            <div class="fw-semibold">Buraya bırak</div>
            <div class="text-muted small">veya tıklayıp görsel seç</div>
          </label>
          <input id="imgInput" type="file" accept="image/*" multiple class="visually-hidden" />

          <div class="mt-3">
            <label class="form-label fw-semibold">Çıktı formatı</label>
            <select id="format" class="form-select">
              <option value="image/jpeg">JPG</option>
              <option value="image/webp">WebP</option>
              <option value="image/png">PNG</option>
            </select>
            <div class="form-text">PNG seçersen kalite ayarı etkisiz olabilir (PNG kayıpsızdır).</div>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Kalite</label>
            <input id="quality" type="range" class="form-range" min="0.1" max="1" step="0.05" value="0.8" />
            <div class="d-flex justify-content-between small text-muted"><span>En küçük</span><span id="qLabel">0.80</span><span>En iyi</span></div>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Maks. genişlik</label>
            <input id="maxW" type="number" class="form-control" value="0" min="0" />
            <div class="form-text">0 = orijinal boyut. Örn: 1920</div>
          </div>

          <button id="btnRun" class="btn btn-primary w-100 mt-3" disabled>Sıkıştır ve indir</button>
          <button id="btnClear" class="btn btn-outline-danger w-100 mt-2" disabled>Temizle</button>

          <div class="alert alert-info mt-3 mb-0">
            <div class="fw-semibold">Gizlilik</div>
            <div class="small">Görseller tarayıcı içinde işlenir. Sunucuya yüklenmez.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-2">
            <h5 class="fw-semibold mb-0">Liste</h5>
            <span class="small text-muted" id="count">0 dosya</span>
          </div>
          <hr />
          <div id="list" class="vstack gap-2"></div>
          <div id="status" class="text-muted small mt-3">Henüz dosya eklenmedi.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const $=(id)=>document.getElementById(id);
  const imgInput=$('imgInput');
  const dropZone=$('dropZone');
  const format=$('format');
  const quality=$('quality');
  const qLabel=$('qLabel');
  const maxW=$('maxW');
  const btnRun=$('btnRun');
  const btnClear=$('btnClear');
  const list=$('list');
  const status=$('status');
  const count=$('count');

  let files=[];

  quality.addEventListener('input', ()=>{ qLabel.textContent = Number(quality.value).toFixed(2); });

  function setEnabled(){
    btnRun.disabled = files.length===0;
    btnClear.disabled = files.length===0;
    count.textContent = `${files.length} dosya`;
  }

  function human(n){
    if (n>1024*1024) return (n/1024/1024).toFixed(2)+' MB';
    return (n/1024).toFixed(0)+' KB';
  }

  function extForMime(m){
    if (m==='image/webp') return 'webp';
    if (m==='image/png') return 'png';
    return 'jpg';
  }

  function baseName(name){
    return (name||'image').replace(/\.[a-z0-9]+$/i,'');
  }

  function addFiles(newFiles){
    for (const f of newFiles){
      if (!f.type.startsWith('image/')) continue;
      files.push(f);
    }
    render();
  }

  function render(){
    list.innerHTML='';
    if (!files.length){ status.textContent='Henüz dosya eklenmedi.'; setEnabled(); return; }
    status.textContent='Hazır. “Sıkıştır ve indir”e bas.';

    files.forEach((f, idx)=>{
      const row=document.createElement('div');
      row.className='d-flex align-items-center justify-content-between border rounded-3 p-2 gap-2';
      row.innerHTML = `
        <div class="d-flex align-items-center gap-2" style="min-width:0;">
          <img src="" alt="" class="rounded" style="width:44px;height:44px;object-fit:cover;background:#f2f2f2" />
          <div style="min-width:0;">
            <div class="fw-semibold text-truncate" title="${f.name}">${f.name}</div>
            <div class="small text-muted">${human(f.size)} • ${f.type || 'image'}</div>
          </div>
        </div>
        <button class="btn btn-sm btn-outline-danger">Kaldır</button>
      `;
      const img=row.querySelector('img');
      const btn=row.querySelector('button');
      btn.addEventListener('click', ()=>{ files.splice(idx,1); render(); });

      const url=URL.createObjectURL(f);
      img.src=url;
      setTimeout(()=>URL.revokeObjectURL(url), 5000);

      list.appendChild(row);
    });
    setEnabled();
  }

  function download(blob, filename){
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a'); a.href=url; a.download=filename; document.body.appendChild(a); a.click(); a.remove();
    setTimeout(()=>URL.revokeObjectURL(url), 5000);
  }

  async function record(n){
    try{ await fetch('<?= e(base_url('araclar/resim-sikistir')) ?>', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({files: n})}); }catch(e){}
  }

  async function fileToImage(file){
    return new Promise((resolve,reject)=>{
      const img=new Image();
      img.onload=()=>resolve(img);
      img.onerror=reject;
      img.src=URL.createObjectURL(file);
    });
  }

  async function imageToBlob(img, mime, q, maxWidth){
    const w=img.naturalWidth||img.width;
    const h=img.naturalHeight||img.height;
    let tw=w, th=h;
    if (maxWidth && maxWidth>0 && w>maxWidth){
      const r=maxWidth/w;
      tw=Math.round(w*r);
      th=Math.round(h*r);
    }

    const canvas=document.createElement('canvas');
    canvas.width=tw; canvas.height=th;
    const ctx=canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, tw, th);

    return new Promise((resolve)=>{
      canvas.toBlob((b)=>resolve(b), mime, q);
    });
  }

  btnRun.addEventListener('click', async ()=>{
    if (!files.length) return;
    btnRun.disabled=true;
    status.textContent='İşleniyor…';

    const mime=format.value;
    const q=parseFloat(quality.value)||0.8;
    const mw=parseInt(maxW.value||'0',10)||0;

    let done=0;
    for (const f of files){
      try{
        const img=await fileToImage(f);
        const blob=await imageToBlob(img, mime, q, mw);
        const filename = `${baseName(f.name)}.${extForMime(mime)}`;
        download(blob, filename);
        done++;
      } catch(err){
        console.error(err);
      }
    }

    status.textContent=`Tamamlandı. İndirilen: ${done} dosya.`;
    record(done || files.length);
    btnRun.disabled=false;
  });

  btnClear.addEventListener('click', ()=>{ files=[]; imgInput.value=''; render(); });

  ;['dragenter','dragover'].forEach(ev=>dropZone.addEventListener(ev,(e)=>{e.preventDefault(); dropZone.classList.add('bg-light');}));
  ;['dragleave','drop'].forEach(ev=>dropZone.addEventListener(ev,(e)=>{e.preventDefault(); dropZone.classList.remove('bg-light');}));
  dropZone.addEventListener('drop',(e)=>{ const fs=[...(e.dataTransfer?.files||[])]; if (fs.length) addFiles(fs); });
  imgInput.addEventListener('change', ()=>{ const fs=[...(imgInput.files||[])]; if (fs.length) addFiles(fs); });

  render();
})();
</script>
