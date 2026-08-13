<?php
// tools_teklif_public.php
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h1 class="h4 fw-bold mb-0">Teklif Oluştur</h1>
      <div class="text-muted">Üyelik gerekmez • Logo isteğe bağlı • Yazdır/PDF indir</div>
    </div>
    <button class="btn btn-primary" type="button" onclick="window.print()">
      <i class="bi bi-printer me-1"></i> Yazdır / PDF
    </button>
  </div>

  <!-- EDITOR (sadece ekranda) -->
  <div class="card p-3 p-md-4 mb-4 d-print-none">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Logo (isteğe bağlı)</label>
        <input class="form-control" type="file" id="logoInput" accept="image/*">
      </div>
      <div class="col-md-4">
        <label class="form-label">Müşteri / Firma Adı</label>
        <input class="form-control" id="customerName" placeholder="(opsiyonel)">
      </div>
      <div class="col-md-4">
        <label class="form-label">Teklif Numarası</label>
        <input class="form-control" id="offerNo" placeholder="(opsiyonel)">
      </div>

      <div class="col-md-3">
        <label class="form-label">Teklif Tarihi</label>
        <input class="form-control" id="offerDate" type="date">
      </div>
      <div class="col-md-3">
        <label class="form-label">Geçerlilik Tarihi</label>
        <input class="form-control" id="validDate" type="date">
      </div>
      <div class="col-md-3">
        <label class="form-label">Vergi Dairesi</label>
        <input class="form-control" id="taxOffice" placeholder="(opsiyonel)">
      </div>
      <div class="col-md-3">
        <label class="form-label">Vergi Numarası</label>
        <input class="form-control" id="taxNo" placeholder="(opsiyonel)">
      </div>

      <div class="col-md-4">
        <label class="form-label">IBAN</label>
        <input class="form-control" id="iban" placeholder="(opsiyonel)">
      </div>
      <div class="col-md-4">
        <label class="form-label">KDV Oranı (%)</label>
        <input class="form-control" id="vatRate" type="number" step="0.01" placeholder="(opsiyonel)">
      </div>
      <div class="col-md-4">
        <label class="form-label">Genel İndirim (₺)</label>
        <input class="form-control" id="globalDiscount" type="number" step="0.01" placeholder="(opsiyonel)">
      </div>
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-semibold">Kalemler</div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" type="button" id="addRowBtn">
          <i class="bi bi-plus"></i> Satır Ekle
        </button>
        <button class="btn btn-outline-danger btn-sm" type="button" id="clearBtn">
          <i class="bi bi-trash"></i> Temizle
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0" id="editorTable">
        <thead>
          <tr>
            <th style="width:70px">No</th>
            <th>Ürün Adı</th>
            <th style="width:120px" class="text-end">Miktar</th>
            <th style="width:160px" class="text-end">B. Fiyat</th>
            <th style="width:160px" class="text-end">İndirim (₺)</th>
          </tr>
        </thead>
        <tbody id="editorBody"></tbody>
      </table>
      <div class="form-text mt-2">
        İpucu: PDF örneğine benzemesi için tablo otomatik <b>14 satıra</b> tamamlanır.
      </div>
    </div>
  </div>

  <!-- PRINT AREA -->
  <div class="offer-print">
    <div class="offer-header">
      <div class="offer-logo-wrap">
        <img id="logoPreview" class="offer-logo" alt="" />
      </div>
      <div class="offer-title">FİYAT TEKLİFİ</div>
    </div>

    <div class="offer-meta">
      <div>
        <div class="offer-customer" id="pCustomer"></div>
        <div class="offer-tax">
          <div>Vergi Numarası <span id="pTaxNo"></span></div>
          <div>Vergi Dairesi <span id="pTaxOffice"></span></div>
          <div>IBAN <span id="pIban"></span></div>
        </div>
      </div>
      <div class="offer-meta-right">
        <div>Teklif Numarası <span id="pOfferNo"></span></div>
        <div>Teklif Tarihi <span id="pOfferDate"></span></div>
        <div>Geçerlilik Tarihi <span id="pValidDate"></span></div>
      </div>
    </div>

    <table class="offer-table">
      <thead>
        <tr>
          <th style="width:60px">No</th>
          <th style="width:90px">Miktar</th>
          <th>Ürün Adı</th>
          <th style="width:160px" class="r">B. Fiyat</th>
          <th style="width:160px" class="r">Tutar</th>
        </tr>
      </thead>
      <tbody id="printBody"></tbody>
    </table>

    <div class="offer-totals">
      <div class="totals-left"></div>
      <div class="totals-right">
        <div class="trow"><span>Ara Toplam</span><span id="pSub">₺0,00</span></div>
        <div class="trow"><span>KDV %<span id="pVatRate">0</span></span><span id="pVat">₺0,00</span></div>
        <div class="trow"><span>Genel İndirim</span><span id="pDisc">₺0,00</span></div>
        <div class="trow grand"><span>GENEL TOPLAM</span><span id="pGrand">₺0,00</span></div>
      </div>
    </div>
  </div>
</div>

<style>
.offer-print{background:#fff; padding:24px; border:1px solid #e6e6e6; border-radius:10px;}
.offer-header{display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;}
.offer-title{font-weight:800; letter-spacing:.6px; font-size:22px;}
.offer-logo{max-height:64px; max-width:240px; object-fit:contain; display:none;}
.offer-meta{display:flex; justify-content:space-between; gap:16px; margin-bottom:14px;}
.offer-customer{font-weight:700; font-size:16px; margin-bottom:6px; min-height:20px;}
.offer-tax{font-size:12px; color:#333; display:grid; gap:2px;}
.offer-meta-right{font-size:12px; color:#333; display:grid; gap:2px; text-align:right;}
.offer-table{width:100%; border-collapse:collapse; font-size:13px;}
.offer-table th, .offer-table td{border:1px solid #222; padding:8px;}
.offer-table thead th{font-weight:700;}
.offer-table .r{text-align:right;}
.offer-totals{display:flex; justify-content:space-between; margin-top:14px;}
.totals-right{min-width:320px; font-size:13px;}
.trow{display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #ddd;}
.trow.grand{font-weight:800; font-size:14px; border-bottom:none; padding-top:10px;}
@media print{
  .d-print-none{display:none !important;}
  body{background:#fff;}
  .offer-print{border:none; padding:0;}
  @page{size:A4; margin:12mm;}
}
</style>

<script>
(function(){
  const fmtTRY = (n)=> {
    n = isFinite(n) ? n : 0;
    return new Intl.NumberFormat('tr-TR',{style:'currency',currency:'TRY'}).format(n);
  };

  const editorBody = document.getElementById('editorBody');
  const printBody  = document.getElementById('printBody');

  // default date today
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth()+1).padStart(2,'0');
  const dd = String(today.getDate()).padStart(2,'0');
  const offerDateEl = document.getElementById('offerDate');
  if (offerDateEl) offerDateEl.value = `${yyyy}-${mm}-${dd}`;

  function renumber(){
    [...editorBody.children].forEach((tr,i)=>{
      tr.querySelector('.rowNo').textContent = (i+1);
    });
  }

  function addRow(data={}){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="rowNo"></td>
      <td><input class="form-control form-control-sm name" placeholder="(opsiyonel)" value="${(data.name||'').replaceAll('"','&quot;')}"></td>
      <td><input class="form-control form-control-sm qty text-end" type="number" step="0.01" value="${(data.qty ?? 1)}"></td>
      <td><input class="form-control form-control-sm price text-end" type="number" step="0.01" value="${(data.price ?? 0)}"></td>
      <td><input class="form-control form-control-sm disc text-end" type="number" step="0.01" value="${(data.disc ?? 0)}"></td>
    `;
    editorBody.appendChild(tr);
    tr.querySelectorAll('input').forEach(inp=>inp.addEventListener('input', sync));
    renumber();
    sync();
  }

  function sync(){
    const setText = (id,val)=>document.getElementById(id).textContent = val ? val : '';
    setText('pCustomer', (document.getElementById('customerName').value||'').trim());
    setText('pOfferNo', (document.getElementById('offerNo').value||'').trim());
    setText('pTaxOffice', (document.getElementById('taxOffice').value||'').trim());
    setText('pTaxNo', (document.getElementById('taxNo').value||'').trim());
    setText('pIban', (document.getElementById('iban').value||'').trim());

    const od = document.getElementById('offerDate').value;
    const vd = document.getElementById('validDate').value;
    setText('pOfferDate', od ? od.split('-').reverse().join('.') : '');
    setText('pValidDate', vd ? vd.split('-').reverse().join('.') : '');

    const rows = [...editorBody.querySelectorAll('tr')].slice(0,14).map((tr,i)=> {
      const name = (tr.querySelector('.name').value||'').trim();
      const qty  = parseFloat(tr.querySelector('.qty').value||'0')||0;
      const price= parseFloat(tr.querySelector('.price').value||'0')||0;
      const disc = parseFloat(tr.querySelector('.disc').value||'0')||0;
      const line = Math.max(0, (qty*price) - disc);
      return {no:i+1, qty, name, price, disc, line};
    });

    printBody.innerHTML = '';
    let sub = 0;
    rows.forEach(r=>{
      sub += r.line;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.no}</td>
        <td>${r.qty ? r.qty : ''}</td>
        <td>${r.name}</td>
        <td class="r">${r.price ? fmtTRY(r.price) : ''}</td>
        <td class="r">${fmtTRY(r.line)}</td>
      `;
      printBody.appendChild(tr);
    });

    for(let i=rows.length;i<14;i++){
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${i+1}</td><td></td><td></td><td class="r"></td><td class="r"></td>`;
      printBody.appendChild(tr);
    }

    const vatRate = parseFloat(document.getElementById('vatRate').value||'0')||0;
    const globalDisc = parseFloat(document.getElementById('globalDiscount').value||'0')||0;

    const base = Math.max(0, sub - globalDisc);
    const vat = Math.max(0, base * (vatRate/100));
    const grand = base + vat;

    document.getElementById('pVatRate').textContent = vatRate ? vatRate : 0;
    document.getElementById('pSub').textContent = fmtTRY(sub);
    document.getElementById('pDisc').textContent = fmtTRY(globalDisc);
    document.getElementById('pVat').textContent = fmtTRY(vat);
    document.getElementById('pGrand').textContent = fmtTRY(grand);
  }

  document.getElementById('addRowBtn').addEventListener('click', ()=>addRow({qty:1,price:0,disc:0}));
  document.getElementById('clearBtn').addEventListener('click', ()=>{
    editorBody.innerHTML='';
    addRow({qty:1,price:0,disc:0});
    document.getElementById('customerName').value='';
    document.getElementById('offerNo').value='';
    document.getElementById('validDate').value='';
    document.getElementById('taxOffice').value='';
    document.getElementById('taxNo').value='';
    document.getElementById('iban').value='';
    document.getElementById('vatRate').value='';
    document.getElementById('globalDiscount').value='';
    const logoPreview = document.getElementById('logoPreview');
    logoPreview.src=''; logoPreview.style.display='none';
    document.getElementById('logoInput').value='';
    sync();
  });

  ['customerName','offerNo','offerDate','validDate','taxOffice','taxNo','iban','vatRate','globalDiscount']
    .forEach(id=>document.getElementById(id).addEventListener('input', sync));

  // logo
  const logoInput = document.getElementById('logoInput');
  const logoPreview = document.getElementById('logoPreview');
  logoInput.addEventListener('change', ()=>{
    const f = logoInput.files && logoInput.files[0];
    if (!f) { logoPreview.style.display='none'; sync(); return; }
    const rd = new FileReader();
    rd.onload = ()=>{ logoPreview.src = rd.result; logoPreview.style.display='block'; };
    rd.readAsDataURL(f);
  });

  // init
  addRow({qty:1,price:0,disc:0});
  sync();
})();
</script>
