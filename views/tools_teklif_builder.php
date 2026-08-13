<?php
  $csrf = CSRF::token();
  $d = $default ?? [];
  $items = $d['items'] ?? [
    ['name'=>'', 'qty'=>1, 'unit_price'=>0, 'discount_percent'=>0, 'discount_amount'=>0, 'vat_rate'=>($d['vat_rate'] ?? 20)]
  ];
  if (empty($items)) $items = [['name'=>'','qty'=>1,'unit_price'=>0,'discount_percent'=>0,'discount_amount'=>0,'vat_rate'=>($d['vat_rate'] ?? 20)]];
  $currency = (string)($d['currency'] ?? 'TRY');
  $currencySymbol = ['TRY'=>'₺','USD'=>'$','EUR'=>'€'][$currency] ?? '₺';
  $editingId = isset($offer['id']) ? (int)$offer['id'] : 0;
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
  <div>
    <h1 class="fw-bold mb-1"><?= e($pageTitle ?? 'Teklif') ?></h1>
    <div class="text-muted">Ürünleri ekle, indirimleri gir, teklif toplamlarını anlık gör.</div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= e(base_url('araclar/teklif-olustur')) ?>"><i class="bi bi-arrow-left me-1"></i>Teklifler</a>
    <?php if ($editingId): ?>
      <a class="btn btn-outline-primary" href="<?= e(base_url('teklif/' . ($offer['public_code'] ?? ''))) ?>"><i class="bi bi-eye me-1"></i>Görüntüle</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" id="offerForm">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="items_json" id="items_json" value="[]">

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Teklif başlığı</label>
              <input class="form-control" name="title" value="<?= e((string)($d['title'] ?? '')) ?>" placeholder="Örn: Bakım Hizmeti Teklifi">
            </div>
            <div class="col-md-3">
              <label class="form-label">Para birimi</label>
              <select class="form-select" name="currency" id="currency">
                <?php foreach (['TRY'=>'TRY (₺)','USD'=>'USD ($)','EUR'=>'EUR (€)'] as $k=>$lbl): ?>
                  <option value="<?= e($k) ?>" <?= $currency===$k?'selected':'' ?>><?= e($lbl) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Genel KDV (%)</label>
              <input class="form-control" type="number" step="0.01" min="0" max="50" name="vat_rate" id="vat_rate" value="<?= e((string)($d['vat_rate'] ?? 20)) ?>">
              <div class="form-text">Kalemlerde ayrı KDV girebilirsin.</div>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">Ürün / Hizmet Kalemleri</div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRow"><i class="bi bi-plus"></i> Kalem ekle</button>
          </div>

          <div class="table-responsive">
            <table class="table align-middle" id="itemsTable">
              <thead>
                <tr>
                  <th style="min-width:240px;">Ürün adı</th>
                  <th class="text-nowrap">Adet</th>
                  <th class="text-nowrap">Birim fiyat</th>
                  <th class="text-nowrap">İnd.%</th>
                  <th class="text-nowrap">İnd.Tutar</th>
                  <th class="text-nowrap">KDV%</th>
                  <th class="text-end text-nowrap">Satır</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><input class="form-control name" value="<?= e((string)($it['name'] ?? '')) ?>" placeholder="Örn: Bakım hizmeti"></td>
                  <td><input class="form-control qty" type="number" step="0.001" min="0.001" value="<?= e((string)($it['qty'] ?? 1)) ?>"></td>
                  <td><div class="input-group"><span class="input-group-text cur"><?= e($currencySymbol) ?></span><input class="form-control unit" type="number" step="0.01" min="0" value="<?= e((string)($it['unit_price'] ?? 0)) ?>"></div></td>
                  <td><input class="form-control dPct" type="number" step="0.01" min="0" max="100" value="<?= e((string)($it['discount_percent'] ?? 0)) ?>"></td>
                  <td><div class="input-group"><span class="input-group-text cur"><?= e($currencySymbol) ?></span><input class="form-control dAmt" type="number" step="0.01" min="0" value="<?= e((string)($it['discount_amount'] ?? 0)) ?>"></div></td>
                  <td><input class="form-control vRate" type="number" step="0.01" min="0" max="50" value="<?= e((string)($it['vat_rate'] ?? ($d['vat_rate'] ?? 20))) ?>"></td>
                  <td class="text-end"><span class="fw-semibold lineTotal">0</span> <span class="text-muted curSym"><?= e($currencySymbol) ?></span></td>
                  <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger del"><i class="bi bi-x"></i></button></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label class="form-label">Notlar</label>
              <textarea class="form-control" name="notes" rows="4" placeholder="Ödeme koşulları, teslim süresi, kapsam..."><?= e((string)($d['notes'] ?? '')) ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Genel indirim (toplamdan düşer)</label>
              <div class="input-group">
                <span class="input-group-text cur"><?= e($currencySymbol) ?></span>
                <input class="form-control" type="number" step="0.01" min="0" name="discount_total" id="discount_total" value="<?= e((string)($d['discount_total'] ?? 0)) ?>">
              </div>
              <input type="hidden" name="is_public" value="1">
              <div class="small text-muted mt-2">Teklifler otomatik olarak herkese açıktır. Linki müşterinle paylaşabilirsin.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-body">
          <div class="fw-semibold mb-2">Teklif Ayarları</div>
          <?php $st = (string)($d['status'] ?? 'draft'); ?>
          <div class="mb-2">
            <label class="form-label">Durum</label>
            <select class="form-select" name="status">
              <option value="draft" <?= $st==='draft'?'selected':'' ?>>Taslak</option>
              <option value="sent" <?= $st==='sent'?'selected':'' ?>>Gönderildi</option>
              <option value="approved" <?= $st==='approved'?'selected':'' ?>>Onaylandı</option>
              <option value="rejected" <?= $st==='rejected'?'selected':'' ?>>Reddedildi</option>
            </select>
            <div class="form-text">Takip için teklifi durumlandırabilirsiniz.</div>
          </div>

          <div class="alert alert-light border small mb-0">
            <div class="fw-semibold mb-1">Logo / IBAN / İmza / Kaşe otomatik basılır</div>
            <a href="<?= e(base_url('firma-profili')) ?>">Firma profilini düzenle</a>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="fw-semibold mb-2">Müşteri Bilgileri</div>
          <div class="mb-2">
            <label class="form-label">Firma/Şirket</label>
            <input class="form-control" name="customer_company" value="<?= e((string)($d['customer_company'] ?? '')) ?>" placeholder="Müşteri şirketi">
          </div>
          <div class="mb-2">
            <label class="form-label">Yetkili kişi</label>
            <input class="form-control" name="customer_name" value="<?= e((string)($d['customer_name'] ?? '')) ?>" placeholder="Ad Soyad">
          </div>
          <div class="mb-2">
            <label class="form-label">E-posta</label>
            <input class="form-control" name="customer_email" value="<?= e((string)($d['customer_email'] ?? '')) ?>" placeholder="mail@...">
          </div>
          <div class="mb-2">
            <label class="form-label">Telefon</label>
            <input class="form-control" name="customer_phone" value="<?= e((string)($d['customer_phone'] ?? '')) ?>" placeholder="0(5xx)...">
          </div>
          <div class="mb-2">
            <label class="form-label">Adres</label>
            <textarea class="form-control" name="customer_address" rows="3" placeholder="Adres..."><?= e((string)($d['customer_address'] ?? '')) ?></textarea>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="fw-semibold mb-3">Özet</div>
          <div class="d-flex justify-content-between mb-1"><span class="text-muted">Ara toplam</span><span><span id="subTotal">0</span> <span class="curSym"><?= e($currencySymbol) ?></span></span></div>
          <div class="d-flex justify-content-between mb-1"><span class="text-muted">KDV toplam</span><span><span id="vatTotal">0</span> <span class="curSym"><?= e($currencySymbol) ?></span></span></div>
          <div class="d-flex justify-content-between mb-1"><span class="text-muted">Satır indirimleri</span><span>-<span id="itemDisc">0</span> <span class="curSym"><?= e($currencySymbol) ?></span></span></div>
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">Genel indirim</span><span>-<span id="offerDisc">0</span> <span class="curSym"><?= e($currencySymbol) ?></span></span></div>
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold">Genel Toplam</div>
            <div class="fs-4 fw-bold"><span id="grandTotal">0</span> <span class="curSym"><?= e($currencySymbol) ?></span></div>
          </div>
          <button class="btn btn-primary w-100 mt-3" type="submit"><i class="bi bi-check2-circle me-1"></i><?= $editingId ? 'Kaydet' : 'Teklifi oluştur' ?></button>
          <div class="small text-muted mt-2">Oluşturduktan sonra “Yazdır / PDF” ile PDF’e çevirebilirsin.</div>
        </div>
      </div>
    </div>
  </div>
</form>

<template id="rowTpl">
  <tr>
    <td><input class="form-control name" value="" placeholder="Örn: Bakım hizmeti"></td>
    <td><input class="form-control qty" type="number" step="0.001" min="0.001" value="1"></td>
    <td><div class="input-group"><span class="input-group-text cur"><?= e($currencySymbol) ?></span><input class="form-control unit" type="number" step="0.01" min="0" value="0"></div></td>
    <td><input class="form-control dPct" type="number" step="0.01" min="0" max="100" value="0"></td>
    <td><div class="input-group"><span class="input-group-text cur"><?= e($currencySymbol) ?></span><input class="form-control dAmt" type="number" step="0.01" min="0" value="0"></div></td>
    <td><input class="form-control vRate" type="number" step="0.01" min="0" max="50" value="<?= e((string)($d['vat_rate'] ?? 20)) ?>"></td>
    <td class="text-end"><span class="fw-semibold lineTotal">0</span> <span class="text-muted curSym"><?= e($currencySymbol) ?></span></td>
    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger del"><i class="bi bi-x"></i></button></td>
  </tr>
</template>

<script>
(() => {
  const fmt = (n) => {
    const x = Number(n||0);
    return x.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  };

  const symMap = {TRY:'₺', USD:'$', EUR:'€'};
  const currencySel = document.getElementById('currency');
  const itemsTable = document.getElementById('itemsTable').querySelector('tbody');
  const tpl = document.getElementById('rowTpl');
  const addRowBtn = document.getElementById('addRow');
  const vatDefault = document.getElementById('vat_rate');
  const discountTotal = document.getElementById('discount_total');
  const itemsJson = document.getElementById('items_json');
  const form = document.getElementById('offerForm');

  const setCurrencyUI = () => {
    const sym = symMap[currencySel.value] || '₺';
    document.querySelectorAll('.cur, .curSym').forEach(el => el.textContent = sym);
  };

  const calc = () => {
    let sub = 0, vatT = 0, itemDisc = 0;
    const sym = symMap[currencySel.value] || '₺';

    itemsTable.querySelectorAll('tr').forEach(tr => {
      const qty = Number(tr.querySelector('.qty')?.value || 0);
      const unit = Number(tr.querySelector('.unit')?.value || 0);
      const dPct = Number(tr.querySelector('.dPct')?.value || 0);
      const dAmt = Number(tr.querySelector('.dAmt')?.value || 0);
      const vRate = Number(tr.querySelector('.vRate')?.value || vatDefault.value || 0);
      const lineGross = Math.max(0, qty) * Math.max(0, unit);
      const d = Math.min(lineGross, (lineGross * (Math.max(0, Math.min(100,dPct))/100)) + Math.max(0,dAmt));
      const net = Math.max(0, lineGross - d);
      const v = net * (Math.max(0, Math.min(50,vRate)) / 100);
      const total = net + v;
      tr.querySelector('.lineTotal').textContent = fmt(total);
      tr.querySelectorAll('.curSym').forEach(el => el.textContent = sym);
      sub += net;
      vatT += v;
      itemDisc += d;
    });

    const offerDisc = Math.max(0, Number(discountTotal.value||0));
    const grandBefore = sub + vatT;
    const grand = Math.max(0, grandBefore - offerDisc);

    document.getElementById('subTotal').textContent = fmt(sub);
    document.getElementById('vatTotal').textContent = fmt(vatT);
    document.getElementById('itemDisc').textContent = fmt(itemDisc);
    document.getElementById('offerDisc').textContent = fmt(offerDisc);
    document.getElementById('grandTotal').textContent = fmt(grand);
  };

  const bindRow = (tr) => {
    tr.querySelectorAll('input').forEach(i => i.addEventListener('input', calc));
    tr.querySelector('.del')?.addEventListener('click', () => {
      if (itemsTable.querySelectorAll('tr').length <= 1) {
        // keep at least one row
        tr.querySelector('.name').value = '';
        tr.querySelector('.qty').value = 1;
        tr.querySelector('.unit').value = 0;
        tr.querySelector('.dPct').value = 0;
        tr.querySelector('.dAmt').value = 0;
        tr.querySelector('.vRate').value = vatDefault.value || 20;
      } else {
        tr.remove();
      }
      calc();
    });
  };

  itemsTable.querySelectorAll('tr').forEach(bindRow);

  addRowBtn.addEventListener('click', () => {
    const tr = tpl.content.firstElementChild.cloneNode(true);
    // default VAT
    tr.querySelector('.vRate').value = vatDefault.value || 20;
    itemsTable.appendChild(tr);
    bindRow(tr);
    calc();
    tr.querySelector('.name')?.focus();
  });

  currencySel.addEventListener('change', () => { setCurrencyUI(); calc(); });
  vatDefault.addEventListener('input', () => {
    // update empty VAT fields only
    itemsTable.querySelectorAll('tr').forEach(tr => {
      const v = tr.querySelector('.vRate');
      if (v && (v.value === '' || Number.isNaN(Number(v.value)))) v.value = vatDefault.value;
    });
    calc();
  });
  discountTotal.addEventListener('input', calc);

  form.addEventListener('submit', (e) => {
    const rows = [];
    itemsTable.querySelectorAll('tr').forEach(tr => {
      rows.push({
        name: tr.querySelector('.name')?.value || '',
        qty: tr.querySelector('.qty')?.value || 1,
        unit_price: tr.querySelector('.unit')?.value || 0,
        discount_percent: tr.querySelector('.dPct')?.value || 0,
        discount_amount: tr.querySelector('.dAmt')?.value || 0,
        vat_rate: tr.querySelector('.vRate')?.value || vatDefault.value || 0,
      });
    });
    itemsJson.value = JSON.stringify(rows);
  });

  setCurrencyUI();
  calc();
})();
</script>
