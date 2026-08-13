<?php
  $symMap = ['TRY'=>'₺','USD'=>'$','EUR'=>'€'];
  $currency = (string)($offer['currency'] ?? 'TRY');
  $sym = $symMap[$currency] ?? '₺';
  // Site branding is intentionally not printed on offers.
  $siteName = Settings::get('site_name','') ?? '';
  $firmLogo = (string)($firm['logo_path'] ?? '');
  $firmName = (string)($firm['company_name'] ?? '');
  $firmTaxOffice = (string)($firm['tax_office'] ?? '');
  $firmTaxNo = (string)($firm['tax_no'] ?? '');
  $firmIban = (string)($firm['iban'] ?? '');
  $firmSig = (string)($firm['signature_path'] ?? '');
  $firmStamp = (string)($firm['stamp_path'] ?? '');
?>

<style>
  .offer-wrap{max-width:960px;margin:0 auto;}
  .offer-paper{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.06)}
  .offer-top{display:flex;gap:18px;align-items:flex-start;justify-content:space-between}
  .offer-brand{display:flex;gap:12px;align-items:center;min-width:0}
  .offer-logo{width:54px;height:54px;border-radius:14px;object-fit:cover;flex:0 0 auto}
  .offer-title{font-weight:800;letter-spacing:-.2px}
  .offer-muted{color:rgba(0,0,0,.55)}
  .offer-table th{font-size:.85rem;color:rgba(0,0,0,.55)}
  .offer-table td{vertical-align:middle}
  .mono{font-variant-numeric: tabular-nums;}
  @media print{
    nav, .no-print{display:none !important;}
    main.container{padding:0 !important;max-width:none !important;}
    .offer-paper{border:none;box-shadow:none;border-radius:0}
    body{background:#fff !important;}
  }
</style>

<div class="offer-wrap">
  <div class="d-flex justify-content-between align-items-center gap-2 mb-3 no-print">
    <div>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('araclar/teklif-olustur')) ?>"><i class="bi bi-arrow-left"></i> Teklifler</a>
    </div>
    <div class="d-flex gap-2">
      <?php if ($isOwner): ?>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('araclar/teklif-olustur/duzenle?id='.(int)$offer['id'])) ?>"><i class="bi bi-pencil"></i> Düzenle</a>
      <?php endif; ?>
      <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Yazdır / PDF</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard?.writeText(window.location.href)"><i class="bi bi-link-45deg"></i> Linki kopyala</button>
    </div>
  </div>

  <div class="offer-paper p-4 p-md-5">
    <div class="offer-top mb-4">
      <div class="offer-brand">
        <?php if ($firmLogo): ?>
          <img class="offer-logo" src="<?= e(base_url($firmLogo)) ?>" alt="Logo" onerror="this.style.display='none'">
        <?php endif; ?>
        <div class="min-w-0">
          <div class="offer-title h4 mb-0 text-truncate" style="max-width: 55vw;"><?= e((string)($offer['title'] ?? 'Teklif')) ?></div>
          <div class="offer-muted small text-truncate" style="max-width: 55vw;">
            <?= $firmName ? e($firmName) : '' ?>
            <?php if ($firmTaxOffice || $firmTaxNo || $firmIban): ?>
              <span class="ms-2">•</span>
              <span class="ms-2"><?= e(trim(($firmTaxOffice?('VD: '.$firmTaxOffice):'') . ($firmTaxNo?(' / '.$firmTaxNo):''))) ?></span>
              <?php if ($firmIban): ?><span class="ms-2">IBAN: <?= e($firmIban) ?></span><?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="text-end">
        <div class="fw-semibold">Teklif No</div>
        <div class="mono"><?= e((string)($offer['offer_no'] ?? '')) ?></div>
        <div class="offer-muted small"><?= e(date('d.m.Y', strtotime((string)$offer['created_at']))) ?></div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="fw-semibold mb-1">Teklif Veren</div>
        <?php if ($firmName): ?>
          <div class="fw-semibold"><?= e($firmName) ?></div>
        <?php elseif (!empty($owner['display_name'])): ?>
          <div class="fw-semibold"><?= e((string)$owner['display_name']) ?></div>
        <?php endif; ?>
        <?php if (!empty($owner['email'])): ?><div class="offer-muted small"><?= e((string)$owner['email']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-6">
        <div class="fw-semibold mb-1">Müşteri</div>
        <?php if (!empty($offer['customer_company'])): ?><div><?= e((string)$offer['customer_company']) ?></div><?php endif; ?>
        <?php if (!empty($offer['customer_name'])): ?><div class="offer-muted"><?= e((string)$offer['customer_name']) ?></div><?php endif; ?>
        <?php if (!empty($offer['customer_email'])): ?><div class="offer-muted small"><?= e((string)$offer['customer_email']) ?></div><?php endif; ?>
        <?php if (!empty($offer['customer_phone'])): ?><div class="offer-muted small"><?= e((string)$offer['customer_phone']) ?></div><?php endif; ?>
        <?php if (!empty($offer['customer_address'])): ?><div class="offer-muted small" style="white-space:pre-wrap"><?= e((string)$offer['customer_address']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table offer-table">
        <thead>
          <tr>
            <th>Ürün / Hizmet</th>
            <th class="text-end">Adet</th>
            <th class="text-end">Birim</th>
            <th class="text-end">İndirim</th>
            <th class="text-end">KDV</th>
            <th class="text-end">Toplam</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach (($totals['items'] ?? []) as $it): ?>
          <tr>
            <td class="min-w-0">
              <div class="fw-semibold text-truncate" style="max-width: 420px;" title="<?= e((string)$it['name']) ?>"><?= e((string)$it['name']) ?></div>
            </td>
            <td class="text-end mono"><?= e(number_format((float)$it['qty'], 3, ',', '.')) ?></td>
            <td class="text-end mono"><?= e(number_format((float)$it['unit_price'], 2, ',', '.')) ?> <?= e($sym) ?></td>
            <td class="text-end mono">-<?= e(number_format((float)$it['_line_discount'], 2, ',', '.')) ?> <?= e($sym) ?></td>
            <td class="text-end mono"><?= e(number_format((float)$it['_line_vat'], 2, ',', '.')) ?> <?= e($sym) ?></td>
            <td class="text-end mono fw-semibold"><?= e(number_format((float)$it['_line_total'], 2, ',', '.')) ?> <?= e($sym) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="row justify-content-end">
      <div class="col-md-6 col-lg-5">
        <div class="border rounded-4 p-3">
          <div class="d-flex justify-content-between mb-1"><span class="offer-muted">Ara toplam</span><span class="mono"><?= e(number_format((float)$totals['sub_total'], 2, ',', '.')) ?> <?= e($sym) ?></span></div>
          <div class="d-flex justify-content-between mb-1"><span class="offer-muted">KDV toplam</span><span class="mono"><?= e(number_format((float)$totals['vat_total'], 2, ',', '.')) ?> <?= e($sym) ?></span></div>
          <div class="d-flex justify-content-between mb-1"><span class="offer-muted">Satır indirimleri</span><span class="mono">-<?= e(number_format((float)$totals['items_discount_total'], 2, ',', '.')) ?> <?= e($sym) ?></span></div>
          <div class="d-flex justify-content-between mb-2"><span class="offer-muted">Genel indirim</span><span class="mono">-<?= e(number_format((float)$totals['offer_discount_total'], 2, ',', '.')) ?> <?= e($sym) ?></span></div>
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold">Genel Toplam</div>
            <div class="fs-4 fw-bold mono"><?= e(number_format((float)$totals['grand_total'], 2, ',', '.')) ?> <?= e($sym) ?></div>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($offer['notes'])): ?>
      <div class="mt-4">
        <div class="fw-semibold mb-1">Notlar</div>
        <div class="offer-muted" style="white-space:pre-wrap"><?= e((string)$offer['notes']) ?></div>
      </div>
    <?php endif; ?>

    

    <div class="row mt-5">
      <div class="col-6 text-center">
        <div class="fw-semibold mb-2">Yetkili İmza</div>
        <?php if ($firmSig): ?>
          <img src="<?= e(base_url($firmSig)) ?>" style="height:90px;max-width:260px;object-fit:contain">
        <?php else: ?>
          <div style="height:90px;border-bottom:1px solid #ccc"></div>
        <?php endif; ?>
      </div>
      <div class="col-6 text-center">
        <div class="fw-semibold mb-2">Kaşe</div>
        <?php if ($firmStamp): ?>
          <img src="<?= e(base_url($firmStamp)) ?>" style="height:90px;max-width:260px;object-fit:contain">
        <?php else: ?>
          <div style="height:90px;border-bottom:1px solid #ccc"></div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
