<?php
require_once __DIR__ . '/_layout_top.php';

$pdo = DB::pdo();
$blogCount = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
$projCount = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$pubBlog   = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
$pubProj   = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status='published'")->fetchColumn();

$recentBlogs = $pdo->query("SELECT id,title,status,published_at,created_at FROM blog_posts ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentProj  = $pdo->query("SELECT id,title,status,published_at,created_at FROM projects ORDER BY created_at DESC LIMIT 5")->fetchAll();

function status_badge(string $s): string {
  if ($s === 'published') return '<span class="badge bg-success">published</span>';
  return '<span class="badge bg-secondary">draft</span>';
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Dashboard</h1>
    <div class="text-muted">Hızlı durum • içerik • SEO</div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('admin/site-ayarlar.php')) ?>"><i class="bi bi-sliders me-1"></i>Ayarlar</a>
    <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('admin/hakkimda.php')) ?>"><i class="bi bi-person-badge me-1"></i>Hakkımda</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted">Blog (toplam)</div>
      <div class="display-6 fw-bold mb-0"><?= $blogCount ?></div>
      <div class="text-muted small"><?= $pubBlog ?> yayınlandı</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted">Projeler (toplam)</div>
      <div class="display-6 fw-bold mb-0"><?= $projCount ?></div>
      <div class="text-muted small"><?= $pubProj ?> yayınlandı</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted">SEO</div>
      <div class="h5 fw-bold mb-1"><i class="bi bi-diagram-3 me-1"></i>sitemap.xml</div>
      <a class="btn btn-sm btn-outline-light" target="_blank" href="<?= e(base_url('sitemap.xml')) ?>">Aç</a>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted">Hızlı İşlemler</div>
      <div class="d-flex flex-column gap-2 mt-2">
        <a class="btn btn-sm btn-primary" href="<?= e(base_url('admin/blog-duzenle.php')) ?>"><i class="bi bi-plus-lg me-1"></i>Blog yaz</a>
        <a class="btn btn-sm btn-primary" href="<?= e(base_url('admin/proje-duzenle.php')) ?>"><i class="bi bi-plus-lg me-1"></i>Proje ekle</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold"><i class="bi bi-journal-text me-1"></i>Son Bloglar</div>
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/blog.php')) ?>">Yönet</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Başlık</th><th>Durum</th><th>Tarih</th></tr></thead>
          <tbody>
            <?php if (!$recentBlogs): ?><tr><td colspan="3" class="text-muted">Kayıt yok.</td></tr><?php endif; ?>
            <?php foreach ($recentBlogs as $b): ?>
              <tr>
                <td><a class="text-decoration-none" href="<?= e(base_url('admin/blog-duzenle.php?id='.$b['id'])) ?>"><?= e($b['title']) ?></a></td>
                <td><?= status_badge($b['status']) ?></td>
                <td class="text-muted small"><?= e(($b['published_at'] ?: $b['created_at']) ? date('d.m.Y', strtotime((string)($b['published_at'] ?: $b['created_at']))) : '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold"><i class="bi bi-kanban me-1"></i>Son Projeler</div>
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/projeler.php')) ?>">Yönet</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Başlık</th><th>Durum</th><th>Tarih</th></tr></thead>
          <tbody>
            <?php if (!$recentProj): ?><tr><td colspan="3" class="text-muted">Kayıt yok.</td></tr><?php endif; ?>
            <?php foreach ($recentProj as $p): ?>
              <tr>
                <td><a class="text-decoration-none" href="<?= e(base_url('admin/proje-duzenle.php?id='.$p['id'])) ?>"><?= e($p['title']) ?></a></td>
                <td><?= status_badge($p['status']) ?></td>
                <td class="text-muted small"><?= e(($p['published_at'] ?: $p['created_at']) ? date('d.m.Y', strtotime((string)($p['published_at'] ?: $p['created_at']))) : '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-8">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-check2-circle me-1"></i>Google’da öne çıkmak için kontrol listesi</div>
      <ul class="mb-0 text-muted">
        <li>Blog/Proje içeriklerinde SEO başlık ve açıklama gir</li>
        <li>Kapak görseli ekle (16:9 önerilir)</li>
        <li><a class="text-decoration-none" target="_blank" href="<?= e(base_url('sitemap.xml')) ?>">sitemap.xml</a> dosyasını Search Console’a gönder</li>
        <li>Blog yazılarını düzenli paylaş (haftada 1 ideal)</li>
      </ul>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-lightning-charge me-1"></i>Hızlı Notlar</div>
      <div class="text-muted small">“Site Ayarları”ndan sosyal linkleri (LinkedIn/GitHub) ekleyerek profilini güçlendirebilirsin.</div>
      <div class="mt-3 d-flex gap-2">
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/site-ayarlar.php')) ?>">Ayarlar</a>
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/blog.php')) ?>">Blog</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
