<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="h4 fw-bold mb-0">Kayıt Ol</div>
          <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('giris')) ?>">Giriş</a>
        </div>
        <div class="text-muted mb-3">Yeni hesap oluşturun.</div>

        <?php if (!empty($formMsg)): ?>
          <div class="alert alert-<?= e($formType ?? 'info') ?>"><?= e($formMsg) ?></div>
        <?php endif; ?>

        <?php if (User::registrationEnabled()): ?>
        <form method="post">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Kullanıcı adı</label>
              <input class="form-control form-control-lg" name="username" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ad Soyad</label>
              <input class="form-control form-control-lg" name="display_name">
            </div>
            <div class="col-12">
              <label class="form-label">E-posta</label>
              <input class="form-control form-control-lg" type="email" name="email" required>
            </div>
            <div class="col-12">
              <label class="form-label">Şifre</label>
              <input class="form-control form-control-lg" type="password" name="password" minlength="8" required>
              <div class="small text-muted mt-1">En az 8 karakter.</div>
            </div>
            <div class="col-12">
              <button class="btn btn-primary btn-lg w-100"><i class="bi bi-person-plus me-1"></i>Kayıt Ol</button>
            </div>
          </div>
        </form>
        <?php endif; ?>
      </div>
      <div class="text-center mt-3">
        <a class="text-decoration-none" href="<?= e(base_url('/')) ?>">← Siteye dön</a>
      </div>
    </div>
  </div>
</div>
