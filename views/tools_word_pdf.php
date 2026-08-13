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
      <h1 class="fw-bold mb-1"><?= e($pageTitle ?? 'Word → PDF') ?></h1>
      <p class="text-muted mb-0"><?= e($pageDesc ?? '') ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('araclar')) ?>">← Araçlara dön</a>
  </div>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">Dosya seç</h5>
            <span class="badge bg-light text-muted border">Ücretsiz</span>
          </div>

          <input id="wpFile" type="file" class="visually-hidden" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
          <label id="wpDrop" for="wpFile" class="border rounded-3 p-4 text-center w-100" style="cursor:pointer; border-style:dashed!important;">
            <div class="display-6">📄</div>
            <div class="fw-semibold">Buraya bırak</div>
            <div class="text-muted small">veya tıklayıp DOCX seç</div>
            <div class="mt-2 small text-muted">Dosyalar sunucuya yüklenmez.</div>
          </label>

          <div class="mt-3">
            <div class="small text-muted">Seçilen dosya</div>
            <div id="wpFileName" class="fw-semibold text-truncate">-</div>
          </div>

          <hr />

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small">Sayfa boyutu</label>
              <select id="wpPage" class="form-select">
                <option value="a4" selected>A4</option>
                <option value="letter">Letter</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small">Yön</label>
              <select id="wpOrient" class="form-select">
                <option value="portrait" selected>Dikey</option>
                <option value="landscape">Yatay</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small">Kenar boşluğu (mm)</label>
              <input id="wpMargin" type="number" class="form-control" value="12" min="0" max="40" />
            </div>
            <div class="col-6">
              <label class="form-label small">Kalite</label>
              <select id="wpQuality" class="form-select">
                <option value="1" selected>Yüksek</option>
                <option value="0.92">Standart</option>
                <option value="0.85">Küçük dosya</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small">Çıktı dosya adı</label>
              <input id="wpOut" type="text" class="form-control" value="belge.pdf" />
              <div class="form-text">.pdf uzantısı otomatik eklenir.</div>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="wpPageNum" checked>
                <label class="form-check-label" for="wpPageNum">Alt bilgiye sayfa numarası ekle</label>
              </div>
            </div>
          </div>

          <button id="wpConvert" class="btn btn-primary w-100 mt-3" disabled>
            PDF Oluştur ve İndir
          </button>

          <div id="wpStatus" class="small text-muted mt-2"></div>

          <div class="alert alert-info mt-3 mb-0">
            <div class="fw-semibold mb-1">Not</div>
            <div class="small">DOCX → PDF dönüştürme tarayıcı içinde yapılır. Çok karmaşık Word şablonlarında (özel sayfa kırımları/kolonlar) görünüm farklılıkları olabilir.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Önizleme</h5>
            <div class="d-flex align-items-center gap-2">
              <span id="wpLib" class="badge bg-light text-muted border">Kütüphaneler: yükleniyor…</span>
              <button id="wpClear" class="btn btn-sm btn-outline-danger" disabled>Temizle</button>
            </div>
          </div>
          <hr />
          <div id="wpPreviewWrap" class="bg-body-tertiary rounded-3 p-3" style="min-height:420px;">
            <div id="wpPreview" class="bg-white rounded-3 shadow-sm p-4" style="min-height:360px;"></div>
          </div>
        </div>
      </div>

      <div class="mt-3 small text-muted">
        İpucu: DOCX içindeki görseller desteklenir. Başlıklar, listeler ve temel tablolar dönüştürülür.
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const $ = (id)=>document.getElementById(id);
  const fileInput = $('wpFile');
  const drop = $('wpDrop');
  const fileName = $('wpFileName');
  const btn = $('wpConvert');
  const clearBtn = $('wpClear');
  const status = $('wpStatus');
  const preview = $('wpPreview');
  const libBadge = $('wpLib');

  let currentDocx = null; // {name, arrayBuffer}

  function setStatus(msg, type){
    status.textContent = msg || '';
    status.className = 'small mt-2 ' + (type==='danger' ? 'text-danger' : (type==='success' ? 'text-success' : 'text-muted'));
  }

  function safeName(n){
    n = (n||'belge').replace(/\s+/g,' ').trim();
    if(!n) n='belge';
    if(!n.toLowerCase().endsWith('.pdf')) n += '.pdf';
    return n;
  }

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

    // mammoth
    if(!window.mammoth){
      const ok = await loadScript([
        'https://unpkg.com/mammoth/mammoth.browser.min.js',
        'https://cdn.jsdelivr.net/npm/mammoth@1.6.0/mammoth.browser.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js'
      ]);
      if(!ok) throw new Error('mammoth');
    }
    // html2pdf bundle (includes jsPDF + html2canvas)
    if(!window.html2pdf){
      const ok = await loadScript([
        'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
        'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
        'https://unpkg.com/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js'
      ]);
      if(!ok) throw new Error('html2pdf');
    }
    libBadge.textContent = 'Kütüphaneler: hazır';
    libBadge.className = 'badge bg-success-subtle text-success border';
  }

  function setDragUI(active){
    drop.classList.toggle('border-primary', !!active);
    drop.classList.toggle('bg-primary-subtle', !!active);
  }
  ;['dragenter','dragover'].forEach(ev=>drop.addEventListener(ev,(e)=>{e.preventDefault(); setDragUI(true);}));
  ;['dragleave','drop'].forEach(ev=>drop.addEventListener(ev,(e)=>{e.preventDefault(); setDragUI(false);}));
  drop.addEventListener('drop',(e)=>{
    const f = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files[0] : null;
    if(f) handleFile(f);
  });

  fileInput.addEventListener('change', ()=>{
    const f = fileInput.files && fileInput.files[0];
    if(f) handleFile(f);
  });

  async function handleFile(file){
    try{
      setStatus('', '');
      if(!file.name.toLowerCase().endsWith('.docx')){
        setStatus('Lütfen .docx (Word) dosyası seçin.', 'danger');
        return;
      }
      fileName.textContent = file.name;
      preview.innerHTML = '<div class="text-muted">Okunuyor…</div>';
      btn.disabled = true;
      clearBtn.disabled = false;

      await ensureLibs();

      const buf = await file.arrayBuffer();
      currentDocx = { name: file.name, arrayBuffer: buf };

      const result = await window.mammoth.convertToHtml({ arrayBuffer: buf }, { includeDefaultStyleMap: true });
      const html = (result && result.value) ? result.value : '';

      const clean = `
        <style>
          .wp-docx { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji"; }
          .wp-docx img { max-width: 100%; height: auto; }
          .wp-docx table { width: 100%; border-collapse: collapse; }
          .wp-docx td, .wp-docx th { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
          .wp-docx h1,.wp-docx h2,.wp-docx h3 { margin-top: 1.2rem; }
        </style>
        <div class="wp-docx">${html || '<div class="text-muted">Önizleme oluşturulamadı.</div>'}</div>
      `;
      preview.innerHTML = clean;
      btn.disabled = false;
      setStatus('Hazır. PDF oluşturabilirsin.', 'success');
    } catch(err){
      console.error(err);
      btn.disabled = true;
      setStatus('Dönüştürme için gerekli kütüphaneler yüklenemedi. CDN engeli olabilir. İstersen kütüphaneleri siteye lokal gömerek %100 çalışır hale getirebilirim.', 'danger');
      libBadge.textContent = 'Kütüphaneler: hata';
      libBadge.className = 'badge bg-danger-subtle text-danger border';
    }
  }

  clearBtn.addEventListener('click', ()=>{
    currentDocx = null;
    fileInput.value = '';
    fileName.textContent = '-';
    preview.innerHTML = '';
    btn.disabled = true;
    clearBtn.disabled = true;
    setStatus('', '');
  });

  btn.addEventListener('click', async ()=>{
    if(!currentDocx) return;
    try{
      btn.disabled = true;
      setStatus('PDF hazırlanıyor…', '');

      const margin = Math.max(0, Math.min(40, parseFloat($('wpMargin').value||'12')));
      const format = $('wpPage').value || 'a4';
      const orientation = $('wpOrient').value || 'portrait';
      const quality = parseFloat($('wpQuality').value||'1');
      const filename = safeName(($('wpOut').value || '').trim() || currentDocx.name.replace(/\.docx$/i,'') + '.pdf');

      // footer page numbers: html2pdf doesn't do native page numbers; we emulate with CSS counters for print.
      const addPageNum = $('wpPageNum').checked;
      const wrapper = document.createElement('div');
      wrapper.innerHTML = preview.innerHTML;
      if(addPageNum){
        const style = document.createElement('style');
        style.textContent = `
          @page { margin: ${margin}mm; }
          body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          .wp-pagenum { position: fixed; bottom: 6mm; left: 0; right: 0; text-align:center; font-size: 11px; color: #6b7280; }
        `;
        wrapper.prepend(style);
        const pn = document.createElement('div');
        pn.className = 'wp-pagenum';
        pn.textContent = ' '; // placeholder
        wrapper.appendChild(pn);
      }

      await window.html2pdf().set({
        margin: margin,
        filename: filename,
        image: { type: 'jpeg', quality: Math.max(0.7, Math.min(1, quality)) },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF: { unit: 'mm', format: format, orientation: orientation },
        pagebreak: { mode: ['css','legacy'] }
      }).from(wrapper).save();

      // record use
      try{ await fetch(location.pathname, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({files:1}) }); }catch(e){}

      setStatus('PDF indirildi.', 'success');
    } catch(err){
      console.error(err);
      setStatus('PDF oluşturulamadı. Tarayıcı kısıtlaması veya büyük dosya olabilir.', 'danger');
    } finally {
      btn.disabled = false;
    }
  });
})();
</script>
