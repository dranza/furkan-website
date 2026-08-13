<?php
require_once __DIR__ . '/../app/core/Bootstrap.php';
require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Blog.php';
require_once __DIR__ . '/../app/models/Projects.php';
require_once __DIR__ . '/../app/models/Timeline.php';
require_once __DIR__ . '/../app/models/Media.php';
require_once __DIR__ . '/../app/models/Skill.php';
require_once __DIR__ . '/../app/models/Certification.php';
// Mesaj modeli bu projede Contact.php içerisinde tutulur.
require_once __DIR__ . '/../app/models/Contact.php';
require_once __DIR__ . '/../app/models/Ticket.php';
require_once __DIR__ . '/../app/models/Tool.php';

Auth::requireLogin();

// Badges
$msgNewCount = 0;
$ticketOpenCount = 0;
try { $msgNewCount = (int)Contact::countByStatus('new'); } catch (Throwable $t) { $msgNewCount = 0; }
try { $ticketOpenCount = (int)Ticket::countByStatus('open'); } catch (Throwable $t) { $ticketOpenCount = 0; }

$siteName = Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan';
$profilePhoto = Settings::get('profile_photo','') ?? '';
$current = basename($_SERVER['PHP_SELF'] ?? '');

// (duplicated badge block removed)

function nav_item(string $href, string $label, string $icon, string $current, int $badge = 0): string {
  $active = basename($_SERVER['PHP_SELF'] ?? '');
  $active = str_replace('.php','',$active);
  $me = str_replace('.php','',basename($href));
  $isActive = ($active === $me) ? 'active' : '';
  $u = base_url('admin/' . $href);

  $badgeHtml = '';
  if ($badge > 0) {
    $badgeHtml = '<span class="ms-auto badge" style="background:rgba(99,102,241,.20);border:1px solid rgba(99,102,241,.35);color:#c7d2fe;">'.$badge.'</span>';
  }

  return '<a class="nav-link '.$isActive.'" href="'.e($u).'" style="display:flex;align-items:center;gap:.5rem;">'
    . '<span><i class="bi '.$icon.' me-2"></i>'.$label.'</span>'
    . $badgeHtml
    . '</a>';
}

function purge_page_cache(string $uri): void {
  $cacheDir = __DIR__ . '/../storage/cache';
  $key = md5($uri);
  $file = $cacheDir . '/' . $key . '.html';
  if (is_file($file)) { @unlink($file); }
}

function purge_pages(array $paths): void {
  foreach ($paths as $p) {
    try { purge_page_cache((string)$p); } catch (Throwable $t) {}
  }
}

function purge_all_cache(): void {
  $cacheDir = __DIR__ . '/../storage/cache';
  if (!is_dir($cacheDir)) return;
  foreach (glob($cacheDir.'/*.html') as $f) { @unlink($f); }
}

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Admin • <?= e($siteName) ?></title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
</head>
<body class="admin">
<div class="container-fluid">
  <div class="row">
    <aside class="col-md-3 col-lg-2 sidebar p-3">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <div class="brand"><?= e($siteName) ?></div>
          <div class="small text-muted">Admin Panel</div>
        </div>
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/')) ?>" title="Siteyi aç"><i class="bi bi-box-arrow-up-right"></i></a>
      </div>

      <div class="pill mb-3 d-flex align-items-center gap-2">
        <?php if ($profilePhoto): ?>
          <img src="<?= e(preg_match('~^https?://~',$profilePhoto)?$profilePhoto:base_url($profilePhoto)) ?>" alt="Profil" style="width:26px;height:26px;border-radius:10px;object-fit:cover;border:1px solid rgba(255,255,255,.18);">
        <?php else: ?>
          <i class="bi bi-person-circle"></i>
        <?php endif; ?>
        <span><?= e($_SESSION['admin_user'] ?? '') ?></span>
        <span class="badge" style="background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.14);"><?= e($_SESSION['admin_role'] ?? 'admin') ?></span>
      </div>

      <div class="nav flex-column nav-pills gap-1">
        <?= nav_item('index.php','Dashboard','bi-grid-1x2', $current) ?>
        <?= nav_item('anasayfa.php','Anasayfa','bi-house', $current) ?>
        <?= nav_item('analytics.php','Analytics','bi-graph-up', $current) ?>
        <?= nav_item('takvim.php','Takvim','bi-calendar3', $current) ?>
        <?= nav_item('araclar.php','Araçlar','bi-wrench-adjustable', $current) ?>
        <?= nav_item('teklifler.php','Teklifler','bi-receipt', $current) ?>
        <?php if (Auth::isAdmin()): ?>
        <?= nav_item('site-ayarlar.php','Site Ayarları','bi-sliders', $current) ?>
        <?php endif; ?>
        <?= nav_item('hakkimda.php','Hakkımda','bi-person-badge', $current) ?>
                <?= nav_item('egitim-deneyim.php','Eğitim & Deneyim','bi-mortarboard', $current) ?>
        <?= nav_item('blog.php','Blog','bi-journal-text', $current) ?>
        <?= nav_item('yorumlar.php','Yorumlar','bi-chat-dots', $current) ?>
        <?= nav_item('mesajlar.php','Mesajlar','bi-envelope', $current, (int)$msgNewCount) ?>
        <?= nav_item('tickets.php','Ticketler','bi-life-preserver', $current, (int)$ticketOpenCount) ?>
        <?= nav_item('projeler.php','Projeler','bi-kanban', $current) ?>
        <?= nav_item('dokumanlar.php','Dökümanlar','bi-folder2-open', $current) ?>
        <?= nav_item('medya.php','Medya','bi-images', $current) ?>
        <?php if (Auth::isAdmin()): ?>
        <?= nav_item('guvenlik.php','Güvenlik','bi-shield-lock', $current) ?>
        <?php endif; ?>
        <?php if (Auth::isAdmin()): ?>
        <?= nav_item('yedek.php','Yedek','bi-download', $current) ?>
        <?= nav_item('kullanicilar.php','Kullanıcılar','bi-people', $current) ?>
        <?php endif; ?>
        <hr class="my-3">
        <a class="nav-link text-danger" href="<?= e(base_url('admin/cikis.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Çıkış</a>
      </div>

      <div class="small text-muted mt-4">
        <div class="mb-1"><i class="bi bi-robot me-1"></i>SEO: sitemap.xml aktif</div>
        <div><i class="bi bi-shield-check me-1"></i>CSRF + şifre hash</div>
      </div>
    </aside>

    <main class="col-md-9 col-lg-10 p-4">
      <div class="topbar p-3 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <div class="small text-muted">Yönetim</div>
          <div class="h5 mb-0 fw-bold"><?= e($siteName) ?></div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/blog-duzenle.php')) ?>"><i class="bi bi-plus-lg me-1"></i>Yeni Blog</a>
          <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/proje-duzenle.php')) ?>"><i class="bi bi-plus-lg me-1"></i>Yeni Proje</a>
          <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('sitemap.xml')) ?>" target="_blank"><i class="bi bi-diagram-3 me-1"></i>Sitemap</a>
        </div>
      </div>