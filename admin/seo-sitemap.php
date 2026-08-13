<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/models/Sitemap.php';

Auth::requireLogin();

$done = false;
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  Sitemap::generate(true);
  $result = ((Settings::get('sitemap_ping','0') ?? '0') === '1') ? Sitemap::ping() : null;
  $done = true;
}

$lastGen = Settings::get('sitemap_last_generated','-') ?? '-';
$lastPing = Settings::get('sitemap_last_ping','-') ?? '-';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">SEO & Sitemap</h1>
    <div class="text-muted">Sitemap oluşturma ve ping</div>
  </div>
  <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('admin/site-ayarlar.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Geri</a>
</div>

<?php if ($done): ?>
  <div class="alert alert-success">Sitemap güncellendi.<?= $result ? ' Ping denendi.' : '' ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-diagram-3 me-1"></i>Durum</div>
      <div class="text-muted">Son oluşturma: <span class="text-white"><?= e($lastGen) ?></span></div>
      <div class="text-muted">Son ping: <span class="text-white"><?= e($lastPing) ?></span></div>
      <div class="mt-3 d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('sitemap.xml')) ?>" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>Sitemap'i Aç</a>
        <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('robots.txt')) ?>" target="_blank"><i class="bi bi-robot me-1"></i>robots.txt</a>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-arrow-repeat me-1"></i>Şimdi Güncelle</div>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <button class="btn btn-primary"><i class="bi bi-lightning-charge me-1"></i>Güncelle & Ping</button>
      </form>
      <?php if ($result): ?>
        <hr class="my-3">
        <div class="fw-semibold mb-2">Ping Sonucu</div>
        <pre class="small mb-0"><?= e(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
      <?php endif; ?>
      <div class="form-text text-muted mt-2">Ping endpointleri arama motorlarının güncel politikalarına bağlıdır.</div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
