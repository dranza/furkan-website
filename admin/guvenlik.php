<?php
require_once __DIR__ . '/_layout_top.php';

$ok = '';
$err = '';

$enabled = Settings::get('totp_enabled','') === '1';
$secret  = Settings::get('totp_secret','') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);

  $action = (string)($_POST['action'] ?? '');
  if ($action === 'generate') {
    $secret = TOTP::generateSecret();
    Settings::set('totp_secret', $secret);
    $ok = 'Yeni secret üretildi. Uygulamana ekleyip doğruladıktan sonra aktif et.';
  } elseif ($action === 'enable') {
    $code = (string)($_POST['code'] ?? '');
    if (!$secret) { $err = 'Önce secret üretin.'; }
    elseif (!TOTP::verify($secret, $code, 1, 30)) { $err = 'Kod hatalı. Secret doğru mu kontrol edin.'; }
    else {
      Settings::set('totp_enabled', '1');
      $enabled = true;
      $ok = '2 adımlı doğrulama aktif edildi.';
    }
  } elseif ($action === 'disable') {
    Settings::set('totp_enabled', '0');
    $enabled = false;
    $ok = '2 adımlı doğrulama kapatıldı.';
  }
}

$siteName = Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan';
$issuer = preg_replace('/[^a-zA-Z0-9\-\_\. ]/', '', (string)$siteName);
$label  = rawurlencode($issuer . ':' . (string)($_SESSION['admin_user'] ?? 'admin'));
$issuerEnc = rawurlencode($issuer);
$secretEnc = rawurlencode((string)$secret);
$otpauth = $secret ? "otpauth://totp/{$label}?secret={$secretEnc}&issuer={$issuerEnc}&period=30&digits=6" : '';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Güvenlik</h1>
    <div class="text-muted">Admin girişine 2 adımlı doğrulama (TOTP) ekle</div>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold"><i class="bi bi-shield-lock me-1"></i>2 Adımlı Doğrulama</div>
        <span class="badge <?= $enabled ? 'bg-success' : 'bg-secondary' ?>"><?= $enabled ? 'Aktif' : 'Kapalı' ?></span>
      </div>

      <ol class="text-muted">
        <li>“Secret üret” ile anahtarı oluştur.</li>
        <li>Authenticator uygulamana manuel ekle (Secret / otpauth URI).</li>
        <li>Uygulamadan gelen kodu girip “Aktif et”.</li>
      </ol>

      <form method="post" class="d-flex gap-2 flex-wrap">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <button class="btn btn-outline-light" name="action" value="generate" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Secret üret</button>
        <?php if ($enabled): ?>
          <button class="btn btn-danger" name="action" value="disable" type="submit"><i class="bi bi-x-circle me-1"></i>Kapat</button>
        <?php endif; ?>
      </form>

      <hr class="my-3">

      <div class="small text-muted mb-2">Secret</div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <code style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);padding:8px 10px;border-radius:12px;"><?= e($secret ?: '-') ?></code>
        <?php if ($secret): ?>
          <button class="btn btn-sm btn-outline-light" type="button" onclick="navigator.clipboard.writeText('<?= e($secret) ?>');alert('Kopyalandı')"><i class="bi bi-clipboard me-1"></i>Secret kopyala</button>
        <?php endif; ?>
      </div>

      <?php if ($otpauth): ?>
        <div class="small text-muted mt-3 mb-1">otpauth URI (manuel ekleme)</div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <code style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);padding:8px 10px;border-radius:12px;max-width:100%;overflow:auto;"><?= e($otpauth) ?></code>
          <button class="btn btn-sm btn-outline-light" type="button" onclick="navigator.clipboard.writeText('<?= e($otpauth) ?>');alert('Kopyalandı')"><i class="bi bi-clipboard me-1"></i>URI kopyala</button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-check2-circle me-1"></i>Aktif Et</div>
      <div class="text-muted mb-3">Uygulamadaki 6 haneli kodu gir.</div>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="enable">
        <div class="mb-3">
          <label class="form-label">Kod</label>
          <input class="form-control" name="code" inputmode="numeric" placeholder="123456">
        </div>
        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-shield-check me-1"></i>Aktif et</button>
      </form>
      <div class="text-muted small mt-3">
        Kod tutmuyorsa cihaz saatini otomatik yapmayı deneyin.
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
