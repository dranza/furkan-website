<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/core/Bootstrap.php';

if (!app_config()) { redirect(base_url('install/')); }

require_once __DIR__ . '/../app/models/Settings.php';

Auth::start();
function ip_hash(): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  return hash('sha256', $ip . '|' . (app_config()['app']['secret'] ?? ''));
}

function too_many_attempts(string $scope='admin', int $max=8, int $minutes=10): bool {
  $pdo = DB::pdo();
  $cut = date('Y-m-d H:i:s', time() - $minutes*60);
  $st = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_hash=:ip AND scope=:s AND created_at >= :cut");
  $st->execute([':ip'=>ip_hash(), ':s'=>$scope, ':cut'=>$cut]);
  return (int)$st->fetchColumn() >= $max;
}

function record_attempt(string $scope='admin'): void {
  $pdo = DB::pdo();
  $st = $pdo->prepare("INSERT INTO login_attempts (ip_hash, scope, created_at) VALUES (:ip,:s,NOW())");
  $st->execute([':ip'=>ip_hash(), ':s'=>$scope]);
}

$error = '';
$totpEnabled = (Settings::get('totp_enabled','0') ?? '0') === '1';
$totpSecret = Settings::get('totp_secret','') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (too_many_attempts('admin', 8, 10)) {
    $error = 'Çok fazla deneme yapıldı. 10 dakika sonra tekrar deneyin.';
  } else {

  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $u = trim((string)($_POST['username'] ?? ''));
  $p = (string)($_POST['password'] ?? '');
  if (Auth::login($u, $p)) {
    if ($totpEnabled) {
      $otp = (string)($_POST['otp'] ?? '');
      if (!$totpSecret || !TOTP::verify($totpSecret, $otp)) {
        Auth::logout();
        record_attempt('admin');
        $error = '2FA kodu hatalı.';
      } else {
        redirect(base_url('admin/index.php'));
      }
    } else {
      redirect(base_url('admin/index.php'));
    }
  } else {
    record_attempt('admin');
    $error = 'Kullanıcı adı veya şifre hatalı.';
  }
  }
}

$siteName = Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan';
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Admin Giriş • <?= e($siteName) ?></title>
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
              <div class="small text-muted">Admin Panel</div>
              <div class="h4 fw-bold mb-0"><?= e($siteName) ?></div>
            </div>
            <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/')) ?>" title="Siteye dön">
              <i class="bi bi-box-arrow-up-right"></i>
            </a>
          </div>

          <?php if ($error): ?><div class="alert alert-danger border-0" style="background: rgba(220,38,38,.18); color:#fff;"><?= e($error) ?></div><?php endif; ?>

          <form method="post" class="mt-2">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">

            <div class="mb-3">
              <label class="form-label">Kullanıcı Adı</label>
              <div class="input-group">
                <span class="input-group-text" style="background:rgba(255,255,255,.06); border-color:rgba(255,255,255,.12); color:#fff;"><i class="bi bi-person"></i></span>
                <input class="form-control" name="username" autocomplete="username" required>
              </div>
            </div>

            <?php if ($totpEnabled): ?>
            <div class="mb-3">
              <label class="form-label">2FA Kodu</label>
              <div class="input-group">
                <span class="input-group-text" style="background:rgba(255,255,255,.06); border-color:rgba(255,255,255,.12); color:#fff;"><i class="bi bi-shield-lock"></i></span>
                <input class="form-control" name="otp" inputmode="numeric" placeholder="123456" autocomplete="one-time-code" required>
              </div>
              <div class="form-text text-muted">Authenticator uygulamasındaki 6 haneli kodu gir.</div>
            </div>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">Şifre</label>
              <div class="input-group">
                <span class="input-group-text" style="background:rgba(255,255,255,.06); border-color:rgba(255,255,255,.12); color:#fff;"><i class="bi bi-lock"></i></span>
                <input class="form-control" name="password" type="password" autocomplete="current-password" required>
              </div>
            </div>

            <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Giriş</button>

            <div class="small text-muted mt-3 d-flex justify-content-between">
              <span><i class="bi bi-shield-check me-1"></i>Güvenli oturum</span>
              <a href="<?= e(base_url('/')) ?>" class="text-decoration-none">Site</a>
            </div>
          </form>
        </div>

        <div class="small text-muted mt-3 text-center">
          İpucu: İlk kurulumdan sonra <strong>/install</strong> klasörünü silin.
        </div>
      </div>
    </div>
  </div>
</body>
</html>
