<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/../app/models/Media.php';

$ok = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $path = handle_upload('file', 'media');
  if ($path) {
    $orig = $_FILES['file']['name'] ?? '';
    $mime = $_FILES['file']['type'] ?? '';
    $size = (int)($_FILES['file']['size'] ?? 0);
    Media::add($path, (string)$orig, (string)$mime, $size);
    $ok = 'Yüklendi.';
  } else {
    $err = 'Dosya yüklenemedi. (Sadece jpg, png, webp, gif)';
  }
}

$items = Media::latest(120);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Medya Kütüphanesi</h1>
    <div class="text-muted">Yüklenen görselleri yönet ve linkini kopyala</div>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-upload me-1"></i>Yeni Görsel Yükle</div>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <div class="mb-3">
          <label class="form-label">Dosya</label>
          <input class="form-control" type="file" name="file" accept="image/*" required>
          <div class="form-text">Öneri: 1280×720 (kapak) / 1200×1200 (kare)</div>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-cloud-upload me-1"></i>Yükle</button>
      </form>
      <hr class="my-3">
      <div class="text-muted small">İpucu: Blog/Proje editöründe “Medya seç” ile hızlıca kapak belirleyebilirsin.</div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold"><i class="bi bi-images me-1"></i>Görseller</div>
        <div class="text-muted small"><?= count($items) ?> kayıt</div>
      </div>

      <?php if (!$items): ?>
        <div class="text-muted">Henüz görsel yok.</div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($items as $m): ?>
            <div class="col-6 col-md-4 col-lg-3">
              <div class="card p-2">
                <img src="<?= e(base_url($m['file_path'])) ?>" class="w-100 rounded-3" style="aspect-ratio:1/1; object-fit:cover;" alt="">
                <div class="small text-muted mt-2 text-truncate"><?= e($m['original_name'] ?? '') ?></div>
                <div class="d-grid mt-2">
                  <button class="btn btn-sm btn-outline-light" type="button" onclick="copyText('<?= e($m['file_path']) ?>')">
                    <i class="bi bi-clipboard me-1"></i>Link kopyala
                  </button>
                </div>
                <div class="small text-muted mt-1 text-truncate"><?= e($m['file_path']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function copyText(t){
  navigator.clipboard.writeText(t).then(() => {
    alert('Kopyalandı: ' + t);
  });
}
</script>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
