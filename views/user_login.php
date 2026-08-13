<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
      <div class="card p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="h4 fw-bold mb-0">Giriş</div>
          <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('kayit')) ?>">Kayıt</a>
        </div>
        <div class="text-muted mb-3">Hesabınızla giriş yapın.</div>

        <?php if (!empty($formMsg)): ?>
          <div class="alert alert-<?= e($formType ?? 'info') ?>"><?= e($formMsg) ?></div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <div class="mb-3">
            <label class="form-label">Kullanıcı adı veya e-posta</label>
            <input class="form-control form-control-lg" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Şifre</label>
            <input class="form-control form-control-lg" type="password" name="password" required>
          </div>
          <button class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Giriş</button>
        </form>
      </div>
      <div class="text-center mt-3">
        <a class="text-decoration-none" href="<?= e(base_url('/')) ?>">← Siteye dön</a>
      </div>
    </div>
  </div>
</div>
