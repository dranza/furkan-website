<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../app/models/Download.php';
require_once __DIR__ . '/../app/models/Sitemap.php';

Auth::requireRole(['admin','editor']);

$id = (int)($_GET['id'] ?? 0);
$item = $id ? Download::adminGet($id) : null;

$ok=''; $err='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $res = Download::adminUpsert($_POST, $_FILES['file'] ?? null);
  if (!empty($res['ok'])) {
    $ok = 'Kaydedildi.';
    try { purge_pages(['/','/dokumanlar']); } catch (Throwable $t) {}
    // ✅ SEO & Sitemap entegrasyonu
    if ((Settings::get('sitemap_auto','0') ?? '0') === '1') {
      try { Sitemap::generate(true); } catch (Throwable $t) {}
      if ((Settings::get('sitemap_ping','0') ?? '0') === '1') {
        try { Sitemap::ping(); } catch (Throwable $t) {}
      }
    }
    $id = (int)$res['id'];
    $item = Download::adminGet($id);
  } else {
    $err = (string)($res['error'] ?? 'Hata');
  }
}

$allowedSetting = strtolower(trim((string)(Settings::get('downloads_allowed_ext','') ?? '')));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1"><?= $id ? 'Dökümanı Düzenle' : 'Yeni Döküman' ?></h1>
    <div class="text-muted">SEO uyumlu indirme sayfası + istatistik</div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('admin/dokumanlar.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Geri</a>
    <?php if ($item): ?>
      <a class="btn btn-outline-light btn-sm" target="_blank" href="<?= e(base_url('dokuman/'.($item['slug'] ?? ''))) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Gör</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="card p-3">
  <form method="post" enctype="multipart/form-data" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$id ?>">

    <div class="col-12">
      <label class="form-label">Başlık</label>
      <input class="form-control" name="title" value="<?= e($item['title'] ?? '') ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Slug (opsiyonel)</label>
      <input class="form-control" name="slug" value="<?= e($item['slug'] ?? '') ?>" placeholder="otomatik üretilebilir">
    </div>

    <div class="col-md-6">
      <label class="form-label">Durum</label>
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" name="is_public" <?= !isset($item) || !empty($item['is_public']) ? 'checked':'' ?>>
        <label class="form-check-label">Yayında (public)</label>
      </div>
    </div>

    <div class="col-12">
      <label class="form-label">Açıklama (SEO)</label>
      <textarea class="form-control" name="description" rows="4"><?= e($item['description'] ?? '') ?></textarea>
    </div>

    <div class="col-md-6">
      <label class="form-label">Kategori</label>
      <input class="form-control" name="category_name" value="<?= e($item['category_name'] ?? '') ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Kategori Slug (opsiyonel)</label>
      <input class="form-control" name="category_slug" value="<?= e($item['category_slug'] ?? '') ?>">
    </div>

    <div class="col-12">
      <label class="form-label">Etiketler (virgülle)</label>
      <input class="form-control" name="tags" value="<?= e($item['tags'] ?? '') ?>">
    </div>

    <div class="col-12">
      <label class="form-label">Dosya <?= $id ? '(değiştirmek istersen yükle)' : '' ?></label>
      <input class="form-control" type="file" name="file" <?= $id ? '' : 'required' ?>>
      <div class="form-text text-muted">
        Uzantı modu: <b><?= e($allowedSetting ?: '-') ?></b>. <b>all</b> ise (PHP/JS/HTML/SVG hariç) çoğu uzantıya izin verilir.
      </div>
    </div>

    <?php if ($item): ?>
    <div class="col-12">
      <div class="p-2 rounded-3" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);">
        <div class="small text-muted">Mevcut dosya:</div>
        <div class="fw-semibold"><?= e($item['original_name'] ?? '') ?></div>
        <div class="small text-muted">İndirme: <?= (int)($item['download_count'] ?? 0) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="col-12 d-grid mt-2">
      <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Kaydet</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
