<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/core/Bootstrap.php';
require_once __DIR__ . '/../app/models/Settings.php';

Auth::start();
$siteName = Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan';

if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_admin_id'])) {
  redirect(base_url('admin/login.php'));
}

$enabled = Settings::get('totp_enabled','') === '1';
$secret  = Settings::get('totp_secret','') ?? '';

if (!$enabled || !$secret) {
  // 2FA kapalıysa normal girişe dön
  $_SESSION['admin_id'] = (int)$_SESSION['2fa_admin_id'];
  $_SESSION['admin_user'] = (string)($_SESSION['2fa_admin_user'] ?? '');
  unset($_SESSION['2fa_pending'], $_SESSION['2fa_admin_id'], $_SESSION['2fa_admin_user']);
  redirect(base_url('admin/index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $code = (string)($_POST['code'] ?? '');
  if (TOTP::verify($secret, $code, 1, 30)) {
    $_SESSION['admin_id'] = (int)$_SESSION['2fa_admin_id'];
    $_SESSION['admin_user'] = (string)($_SESSION['2fa_admin_user'] ?? '');
    unset($_SESSION['2fa_pending'], $_SESSION['2fa_admin_id'], $_SESSION['2fa_admin_user']);
    redirect(base_url('admin/index.php'));
  } else {
    $error = 'Kod hatalı. Lütfen tekrar deneyin.';
  }
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>2 Adımlı Doğrulama • <?= e($siteName) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
</head>
<body class="admin">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card p-3 p-md-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <div class="small text-muted">Güvenlik</div>
              <div class="h4 fw-bold mb-0">2 Adımlı Doğrulama</div>
            </div>
            <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/')) ?>" title="Site"><i class="bi bi-box-arrow-up-right"></i></a>
          </div>

          <?php if ($error): ?><div class="alert alert-danger border-0" style="background: rgba(220,38,38,.18); color:#fff;"><?= e($error) ?></div><?php endif; ?>

          <div class="text-muted mb-3">
            Authenticator uygulamanızdaki <b>6 haneli</b> kodu girin.
          </div>

          <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
            <div class="mb-3">
              <label class="form-label">Doğrulama Kodu</label>
              <input class="form-control" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required>
            </div>
            <button class="btn btn-primary w-100"><i class="bi bi-shield-check me-1"></i>Doğrula</button>
          </form>

          <div class="small text-muted mt-3">
            Kod gelmiyorsa cihaz saatini otomatik yapmayı deneyin.
          </div>
        </div>

        <div class="text-center mt-3">
          <a class="text-decoration-none" href="<?= e(base_url('admin/cikis.php')) ?>">Çıkış yap</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
