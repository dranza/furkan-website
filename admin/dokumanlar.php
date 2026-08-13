<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../app/models/Download.php';
require_once __DIR__ . '/../app/models/Sitemap.php';

Auth::requireRole(['admin','editor']);

$ok=''; $err='';

if (isset($_GET['delete']) && Auth::isAdmin()) {
  CSRF::checkOrExit($_GET['_csrf'] ?? null);
  $id = (int)$_GET['delete'];
  if ($id) {
    Download::adminDelete($id);
    $ok = 'Silindi.';
    try { purge_pages(['/','/dokumanlar']); } catch (Throwable $t) {}
    // ✅ SEO & Sitemap entegrasyonu
    if ((Settings::get('sitemap_auto','0') ?? '0') === '1') {
      try { Sitemap::generate(true); } catch (Throwable $t) {}
      if ((Settings::get('sitemap_ping','0') ?? '0') === '1') {
        try { Sitemap::ping(); } catch (Throwable $t) {}
      }
    }
  }
}

$rows = Download::adminAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Dökümanlar</h1>
    <div class="text-muted">Dosya yükleme, indirme istatistikleri ve SEO sayfaları</div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/dokuman-duzenle.php')) ?>"><i class="bi bi-plus-lg me-1"></i>Yeni Döküman</a>
    <a class="btn btn-outline-light btn-sm" target="_blank" href="<?= e(base_url('dokumanlar')) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Sayfa</a>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="fw-bold">Tüm Dökümanlar</div>
    <div class="small text-muted">Toplam: <?= count($rows) ?></div>
  </div>

  <div class="table-responsive">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Başlık</th>
          <th>Dosya</th>
          <th>Kategori</th>
          <th class="text-center">İndirme</th>
          <th>Durum</th>
          <th class="text-end" style="width:220px;">İşlem</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($r['title'] ?? '') ?></div>
            <div class="text-muted small">/dokuman/<?= e($r['slug'] ?? '') ?></div>
          </td>
          <td class="text-muted">
            <div><?= e($r['original_name'] ?? '') ?></div>
            <div class="small"><?= e(Download::formatBytes((int)($r['size_bytes'] ?? 0))) ?></div>
          </td>
          <td><?= e($r['category_name'] ?? '') ?></td>
          <td class="text-center"><span class="badge" style="background:rgba(99,102,241,.20);border:1px solid rgba(99,102,241,.35);color:#c7d2fe;"><?= (int)($r['download_count'] ?? 0) ?></span></td>
          <td>
            <?php if (!empty($r['is_public'])): ?>
              <span class="badge bg-success">Yayında</span>
            <?php else: ?>
              <span class="badge bg-secondary">Gizli</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-light" target="_blank" href="<?= e(base_url('dokuman/'.($r['slug'] ?? ''))) ?>"><i class="bi bi-eye me-1"></i>Gör</a>
            <a class="btn btn-sm btn-primary" href="<?= e(base_url('admin/dokuman-duzenle.php?id='.(int)$r['id'])) ?>"><i class="bi bi-pencil-square me-1"></i>Düzenle</a>
            <?php if (Auth::isAdmin()): ?>
            <a class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')" href="<?= e(base_url('admin/dokumanlar.php?delete='.(int)$r['id'].'&_csrf='.urlencode(CSRF::token()))) ?>">
              <i class="bi bi-trash me-1"></i>Sil
            </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
        <tr><td colspan="6" class="text-muted">Kayıt yok.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
