<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h1 class="h4 fw-bold mb-0">Firma Profili</h1>
          <div class="text-muted">Logo / Ünvan / Vergi / IBAN / İmza / Kaşe</div>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-outline-secondary" href="<?= e(base_url('araclar/teklif-olustur')) ?>"><i class="bi bi-receipt me-1"></i>Teklifler</a>
          <a class="btn btn-outline-secondary" href="<?= e(base_url('profil')) ?>"><i class="bi bi-person me-1"></i>Profil</a>
        </div>
      </div>

      <?php if (!empty($msg)): ?>
        <div class="alert alert-<?= e($msgType ?? 'info') ?>"><?= e($msg) ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="card p-3 p-md-4">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Firma Ünvanı</label>
            <input class="form-control" name="company_name" value="<?= e((string)($profile['company_name'] ?? '')) ?>" placeholder="(İsteğe bağlı)">
          </div>
          <div class="col-md-3">
            <label class="form-label">Vergi Dairesi</label>
            <input class="form-control" name="tax_office" value="<?= e((string)($profile['tax_office'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Vergi No</label>
            <input class="form-control" name="tax_no" value="<?= e((string)($profile['tax_no'] ?? '')) ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">IBAN</label>
            <input class="form-control" name="iban" value="<?= e((string)($profile['iban'] ?? '')) ?>" placeholder="TR..">
          </div>
          <div class="col-md-3">
            <label class="form-label">Telefon</label>
            <input class="form-control" name="phone" value="<?= e((string)($profile['phone'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">E-posta</label>
            <input class="form-control" name="email" value="<?= e((string)($profile['email'] ?? '')) ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Adres</label>
            <textarea class="form-control" name="address" rows="3"><?= e((string)($profile['address'] ?? '')) ?></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label">Teklif No Şablonu</label>
            <input class="form-control" name="offer_no_template" value="<?= e((string)($profile['offer_no_template'] ?? 'TKF-{YYYY}-{SEQ4}')) ?>">
            <div class="form-text">Örnek: <code>FC-{YYYY}-{SEQ4}</code> / <code>TEKLIF-{YY}-{MM}-{SEQ3}</code></div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Varsayılan Para</label>
            <select class="form-select" name="default_currency">
              <?php foreach (['TRY','USD','EUR'] as $c): ?>
                <option value="<?= e($c) ?>" <?= (($profile['default_currency'] ?? 'TRY')===$c)?'selected':'' ?>><?= e($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Varsayılan KDV (%)</label>
            <input class="form-control" type="number" step="0.01" min="0" max="50" name="default_vat_rate" value="<?= e((string)($profile['default_vat_rate'] ?? '20.00')) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Logo</label>
            <input class="form-control" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp">
            <?php if (!empty($profile['logo_path'])): ?>
              <div class="small text-muted mt-1">Mevcut: <a href="/<?= e($profile['logo_path']) ?>" target="_blank">görüntüle</a></div>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">İmza</label>
            <input class="form-control" type="file" name="signature" accept=".png,.jpg,.jpeg,.webp">
            <?php if (!empty($profile['signature_path'])): ?>
              <div class="small text-muted mt-1">Mevcut: <a href="/<?= e($profile['signature_path']) ?>" target="_blank">görüntüle</a></div>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Kaşe</label>
            <input class="form-control" type="file" name="stamp" accept=".png,.jpg,.jpeg,.webp">
            <?php if (!empty($profile['stamp_path'])): ?>
              <div class="small text-muted mt-1">Mevcut: <a href="/<?= e($profile['stamp_path']) ?>" target="_blank">görüntüle</a></div>
            <?php endif; ?>
          </div>
        </div>

        <button class="btn btn-primary mt-4"><i class="bi bi-save me-1"></i>Kaydet</button>
      </form>
    </div>
  </div>
</div>
