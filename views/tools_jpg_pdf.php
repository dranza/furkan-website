<?php if (!empty($error ?? null)): ?>
  <div class="container py-5">
    <div class="alert alert-warning mb-0"><?= e($error) ?></div>
    <div class="mt-3"><a class="btn btn-outline-secondary" href="<?= e(base_url('araclar')) ?>">← Araçlara dön</a></div>
  </div>
  <?php return; ?>
<?php endif; ?>

<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
      <h1 class="fw-bold mb-1"><?= e($pageTitle ?? 'JPG → PDF') ?></h1>
      <p class="text-muted mb-0"><?= e($pageDesc ?? '') ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('araclar')) ?>">← Araçlara dön</a>
  </div>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">Görselleri ekle</h5>
            <span class="badge bg-light text-muted border">Ücretsiz</span>
          </div>

          <input id="jpFiles" type="file" class="visually-hidden" accept="image/jpeg,image/png" multiple />
          <label id="jpDrop" for="jpFiles" class="border rounded-3 p-4 text-center w-100" style="cursor:pointer; border-style:dashed!important;">
            <div class="display-6">🖼️</div>
            <div class="fw-semibold">Buraya bırak</div>
            <div class="text-muted small">veya tıklayıp JPG/PNG seç</div>
            <div class="mt-2 small text-muted">Dosyalar sunucuya yüklenmez.</div>
          </label>

          <hr />

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small">Sayfa boyutu</label>
              <select id="jpPage" class="form-select">
                <option value="a4" selected>A4</option>
                <option value="letter">Letter</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small">Yön</label>
              <select id="jpOrient" class="form-select">
                <option value="portrait" selected>Dikey</option>
                <option value="landscape">Yatay</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small">Kenar boşluğu (mm)</label>
              <input id="jpMargin" type="number" class="form-control" value="8" min="0" max="30" />
            </div>
            <div class="col-6">
              <label class="form-label small">Yerleşim</label>
              <select id="jpFit" class="form-select">
                <option value="contain" selected>Oran koru (contain)</option>
                <option value="cover">Doldur (cover)</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small">Görsel kalitesi</label>
              <input id="jpQuality" type="range" class="form-range" min="0.6" max="1" step="0.05" value="0.9" />
              <div class="d-flex justify-content-between small text-muted"><span>Küçük dosya</span><span>Yüksek kalite</span></div>
            </div>
            <div class="col-12">
              <label class="form-label small">Çıktı dosya adı</label>
              <input id="jpOut" type="text" class="form-control" value="resimler.pdf" />
              <div class="form-text">.pdf uzantısı otomatik eklenir.</div>
            </div>
          </div>

          <button id="jpBuild" class="btn btn-primary w-100 mt-3" disabled>
            PDF Oluştur ve İndir
          </button>
          <div id="jpStatus" class="small text-muted mt-2"></div>

          <div class="d-flex gap-2 mt-3">
            <button id="jpDemo" class="btn btn-outline-secondary btn-sm">Demo ekle</button>
            <button id="jpClear" class="btn btn-outline-danger btn-sm" disabled>Temizle</button>
          </div>

          <div class="alert alert-info mt-3 mb-0">
            <div class="fw-semibold mb-1">İpucu</div>
            <div class="small">Sıralamak için listede sürükle-bırak yapabilirsin. Her görseli ayrı sayfa olarak ekler.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Liste ve Önizleme</h5>
            <span id="jpLib" class="badge bg-light text-muted border">Kütüphaneler: yükleniyor…</span>
          </div>
          <hr />

          <div id="jpEmpty" class="text-muted">Henüz görsel eklenmedi.</div>
          <div id="jpList" class="vstack gap-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<template id="jpItemTpl">
  <div class="border rounded-3 p-2 d-flex align-items-center gap-3 jp-item" style="background:#fff;">
    <div class="jp-handle" style="cursor:grab; width:34px; height:34px;" title="Sürükle">
      <div class="border rounded-3 d-flex align-items-center justify-content-center" style="width:34px; height:34px;">⋮⋮</div>
    </div>
    <img class="rounded-2 border" style="width:62px; height:62px; object-fit:cover;" alt="">
    <div class="flex-grow-1" style="min-width:0;">
      <div class="fw-semibold text-truncate jp-name"></div>
      <div class="small text-muted jp-meta"></div>
      <div class="d-flex flex-wrap gap-2 mt-1">
        <button class="btn btn-outline-secondary btn-sm jp-rot" type="button">Döndür</button>
        <button class="btn btn-outline-danger btn-sm jp-del" type="button">Kaldır</button>
      </div>
    </div>
  </div>
</template>

<script>
(function(){
  const $ = (id)=>document.getElementById(id);
  const filesInput = $('jpFiles');
  const drop = $('jpDrop');
  const list = $('jpList');
  const empty = $('jpEmpty');
  const buildBtn = $('jpBuild');
  const clearBtn = $('jpClear');
  const demoBtn = $('jpDemo');
  const status = $('jpStatus');
  const libBadge = $('jpLib');
  const tpl = $('jpItemTpl');

  /** items: { id, file, url, w, h, rotate } */
  let items = [];
  let sortable = null;

  function setStatus(msg, type){
    status.textContent = msg || '';
    status.className = 'small mt-2 ' + (type==='danger' ? 'text-danger' : (type==='success' ? 'text-success' : 'text-muted'));
  }
  function safeName(n){
    n = (n||'resimler').replace(/\s+/g,' ').trim();
    if(!n) n='resimler';
    if(!n.toLowerCase().endsWith('.pdf')) n += '.pdf';
    return n;
  }
  function setDragUI(active){
    drop.classList.toggle('border-primary', !!active);
    drop.classList.toggle('bg-primary-subtle', !!active);
  }
  ;['dragenter','dragover'].forEach(ev=>drop.addEventListener(ev,(e)=>{e.preventDefault(); setDragUI(true);}));
  ;['dragleave','drop'].forEach(ev=>drop.addEventListener(ev,(e)=>{e.preventDefault(); setDragUI(false);}));
  drop.addEventListener('drop',(e)=>{
    const fs = e.dataTransfer && e.dataTransfer.files ? Array.from(e.dataTransfer.files) : [];
    if(fs.length) addFiles(fs);
  });
  filesInput.addEventListener('change', ()=>{
    const fs = filesInput.files ? Array.from(filesInput.files) : [];
    if(fs.length) addFiles(fs);
    filesInput.value = '';
  });

  async function loadScript(urls){
    for (const url of urls) {
      try {
        await new Promise((resolve, reject)=>{
          const s = document.createElement('script');
          s.src = url;
          s.async = true;
          s.onload = ()=>resolve(true);
          s.onerror = ()=>reject(new Error('load fail'));
          document.head.appendChild(s);
        });
        return true;
      } catch(e) {}
    }
    return false;
  }
  async function ensureLibs(){
    libBadge.textContent = 'Kütüphaneler: yükleniyor…';

    if(!window.jspdf || !window.jspdf.jsPDF){
      const ok = await loadScript([
        'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
        'https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js',
        'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js'
      ]);
      if(!ok) throw new Error('jspdf');
    }
    if(!window.Sortable){
      const ok = await loadScript([
        'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js',
        'https://unpkg.com/sortablejs@1.15.2/Sortable.min.js',
        'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js'
      ]);
      // Sortable olmazsa da çalışır; sadece sürükle-sırala kapanır
      if(!ok) console.warn('Sortable load failed');
    }

    libBadge.textContent = 'Kütüphaneler: hazır';
    libBadge.className = 'badge bg-success-subtle text-success border';

    if(window.Sortable && !sortable){
      sortable = new window.Sortable(list, {
        animation: 150,
        handle: '.jp-handle',
        onEnd: ()=>syncOrderFromDOM()
      });
    }
  }

  function syncOrderFromDOM(){
    const ids = Array.from(list.querySelectorAll('.jp-item')).map(el=>el.dataset.id);
    items.sort((a,b)=> ids.indexOf(a.id) - ids.indexOf(b.id));
  }

  function render(){
    empty.style.display = items.length ? 'none' : 'block';
    buildBtn.disabled = items.length === 0;
    clearBtn.disabled = items.length === 0;

    list.innerHTML = '';
    for(const it of items){
      const node = tpl.content.firstElementChild.cloneNode(true);
      node.dataset.id = it.id;
      const img = node.querySelector('img');
      img.src = it.url;
      img.style.transform = `rotate(${it.rotate||0}deg)`;
      node.querySelector('.jp-name').textContent = it.file.name;
      node.querySelector('.jp-meta').textContent = `${Math.round(it.file.size/1024)} KB • ${it.w}×${it.h}px`;

      node.querySelector('.jp-del').addEventListener('click', ()=>{
        removeItem(it.id);
      });
      node.querySelector('.jp-rot').addEventListener('click', ()=>{
        it.rotate = ((it.rotate||0) + 90) % 360;
        render();
      });

      list.appendChild(node);
    }
  }

  function removeItem(id){
    const idx = items.findIndex(x=>x.id===id);
    if(idx>=0){
      URL.revokeObjectURL(items[idx].url);
      items.splice(idx,1);
      render();
    }
  }

  async function fileToDim(file){
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.decoding = 'async';
    img.loading = 'eager';
    const p = new Promise((resolve, reject)=>{
      img.onload = ()=>resolve({w: img.naturalWidth, h: img.naturalHeight, url});
      img.onerror = ()=>reject(new Error('img load'));
    });
    img.src = url;
    return p;
  }

  async function addFiles(fs){
    try{
      await ensureLibs();
      setStatus('', '');
      const only = fs.filter(f=>/^image\/(jpeg|png)$/i.test(f.type) || /\.(jpe?g|png)$/i.test(f.name));
      if(!only.length){
        setStatus('Lütfen JPG/PNG görsel seçin.', 'danger');
        return;
      }

      setStatus('Görseller hazırlanıyor…', '');
      for(const f of only){
        const dim = await fileToDim(f);
        items.push({ id: String(Date.now()) + Math.random().toString(16).slice(2), file: f, url: dim.url, w: dim.w, h: dim.h, rotate: 0 });
      }
      render();
      setStatus('Hazır. PDF oluşturabilirsin.', 'success');
    } catch(err){
      console.error(err);
      setStatus('Gerekli kütüphaneler yüklenemedi. CDN engeli olabilir. İstersen kütüphaneleri siteye lokal gömerek %100 çalışır hale getirebilirim.', 'danger');
      libBadge.textContent = 'Kütüphaneler: hata';
      libBadge.className = 'badge bg-danger-subtle text-danger border';
    }
  }

  clearBtn.addEventListener('click', ()=>{
    for(const it of items){ try{ URL.revokeObjectURL(it.url); }catch(e){} }
    items = [];
    render();
    setStatus('', '');
  });

  demoBtn.addEventListener('click', async ()=>{
    // simple demo with generated canvases
    function makeBlob(text){
      const c = document.createElement('canvas');
      c.width = 1200; c.height = 1600;
      const ctx = c.getContext('2d');
      ctx.fillStyle = '#ffffff'; ctx.fillRect(0,0,c.width,c.height);
      ctx.fillStyle = '#111827'; ctx.font = '64px system-ui';
      ctx.fillText(text, 80, 160);
      ctx.fillStyle = '#6b7280'; ctx.font = '40px system-ui';
      ctx.fillText('JPG → PDF demo sayfası', 80, 240);
      return new Promise((res)=>c.toBlob(res, 'image/jpeg', 0.92));
    }
    const b1 = await makeBlob('Demo 1');
    const b2 = await makeBlob('Demo 2');
    const f1 = new File([b1], 'demo-1.jpg', {type:'image/jpeg'});
    const f2 = new File([b2], 'demo-2.jpg', {type:'image/jpeg'});
    addFiles([f1,f2]);
  });

  buildBtn.addEventListener('click', async ()=>{
    if(!items.length) return;
    try{
      await ensureLibs();
      syncOrderFromDOM();

      buildBtn.disabled = true;
      setStatus('PDF oluşturuluyor…', '');

      const margin = Math.max(0, Math.min(30, parseFloat($('jpMargin').value||'8')));
      const format = $('jpPage').value || 'a4';
      const orientation = $('jpOrient').value || 'portrait';
      const fit = $('jpFit').value || 'contain';
      const quality = Math.max(0.6, Math.min(1, parseFloat($('jpQuality').value||'0.9')));
      const filename = safeName(($('jpOut').value||'').trim());

      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF({ unit: 'mm', format, orientation, compress: true });

      // page size in mm
      const pageW = pdf.internal.pageSize.getWidth();
      const pageH = pdf.internal.pageSize.getHeight();
      const boxW = pageW - margin*2;
      const boxH = pageH - margin*2;

      for (let i=0;i<items.length;i++){
        const it = items[i];
        if(i>0) pdf.addPage();

        const imgData = await fileToDataURL(it.file, quality);
        const dims = await imgDimsFromDataURL(imgData);

        // swap dimensions on 90/270
        const rot = (it.rotate||0) % 360;
        const iw = (rot===90||rot===270) ? dims.h : dims.w;
        const ih = (rot===90||rot===270) ? dims.w : dims.h;
        const scaleContain = Math.min(boxW/iw, boxH/ih);
        const scaleCover = Math.max(boxW/iw, boxH/ih);
        const s = (fit==='cover') ? scaleCover : scaleContain;
        const w = iw * s;
        const h = ih * s;
        const x = margin + (boxW - w)/2;
        const y = margin + (boxH - h)/2;

        if(rot){
          // jsPDF rotate around center
          const cx = x + w/2;
          const cy = y + h/2;
          pdf.saveGraphicsState();
          pdf.rotate(rot, { origin: [cx, cy] });
          pdf.addImage(imgData, 'JPEG', x, y, w, h, undefined, 'FAST');
          pdf.restoreGraphicsState();
        } else {
          pdf.addImage(imgData, 'JPEG', x, y, w, h, undefined, 'FAST');
        }
      }

      pdf.save(filename);

      // record use
      try{ await fetch(location.pathname, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({files: items.length}) }); }catch(e){}

      setStatus('PDF indirildi.', 'success');
    } catch(err){
      console.error(err);
      setStatus('PDF oluşturulamadı. Görsel çok büyük olabilir veya tarayıcı bellek limiti aşılmış olabilir.', 'danger');
    } finally {
      buildBtn.disabled = false;
    }
  });

  async function fileToDataURL(file, quality){
    // Convert to JPEG for predictable output size
    const img = await loadImageFromFile(file);
    const canvas = document.createElement('canvas');
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
    return canvas.toDataURL('image/jpeg', quality);
  }
  function loadImageFromFile(file){
    return new Promise((resolve, reject)=>{
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = ()=>{ URL.revokeObjectURL(url); resolve(img); };
      img.onerror = ()=>{ URL.revokeObjectURL(url); reject(new Error('img')); };
      img.src = url;
    });
  }
  function imgDimsFromDataURL(dataUrl){
    return new Promise((resolve, reject)=>{
      const img = new Image();
      img.onload = ()=>resolve({w: img.naturalWidth, h: img.naturalHeight});
      img.onerror = ()=>reject(new Error('dims')); 
      img.src = dataUrl;
    });
  }

  // init
  ensureLibs().catch(()=>{});
})();
</script>
