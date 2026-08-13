<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$active = trim(explode('/', trim($path,'/'))[0] ?? '');
?>
<?php
$settings = class_exists('Settings') ? Settings::getAll() : [];
$siteName = $settings['site_name'] ?? (app_config()['app']['site_name'] ?? 'Site');
$metaTitle = $metaTitle ?? $siteName;
$metaDesc  = $metaDesc  ?? ($settings['site_description'] ?? 'Bilgi Sistemleri Uzmanı - Blog & Projeler');
$canonical = $canonical ?? null;

$ogTitle = $metaTitle;
$ogDesc  = $metaDesc;
$ogUrl   = $canonical ?: base_url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($metaTitle) ?></title>
  <meta name="description" content="<?= e($metaDesc) ?>">
  <?php if ($canonical): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>

  <?php if (!empty($noindex)): ?>
    <meta name="robots" content="noindex,follow">
  <?php endif; ?>

  <!-- Open Graph -->
  <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
  <meta property="og:title" content="<?= e($ogTitle) ?>">
  <meta property="og:description" content="<?= e($ogDesc) ?>">
  <meta property="og:url" content="<?= e($ogUrl) ?>">
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <?php if (!empty($ogImage)): ?>
    <meta property="og:image" content="<?= e($ogImage) ?>">
  <?php endif; ?>

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($ogTitle) ?>">
  <meta name="twitter:description" content="<?= e($ogDesc) ?>">
  <?php if (!empty($ogImage)): ?><meta name="twitter:image" content="<?= e($ogImage) ?>"><?php endif; ?>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
  <?php if (!empty($settings['gsc_meta'])): ?>
    <meta name="google-site-verification" content="<?= e(str_replace('google-site-verification=','',$settings['gsc_meta'])) ?>">
  <?php endif; ?>
  <?php if (!empty($settings['google_analytics_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($settings['google_analytics_id']) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);} 
      gtag('js', new Date());
      gtag('config', '<?= e($settings['google_analytics_id']) ?>');
    </script>
  <?php endif; ?>
<?php if (!empty($jsonLd)):
    $jsonArr = is_array($jsonLd) && array_keys($jsonLd) === range(0, count($jsonLd)-1) ? $jsonLd : [$jsonLd];
    foreach ($jsonArr as $obj): ?>
  <script type="application/ld+json"><?= json_encode($obj, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
  <?php endforeach; endif; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom sticky-top">
  <div class="container">
    <?php
  $brandPhoto = trim((string)($settings['profile_photo'] ?? ''));
  $brandSrc = '';
  if ($brandPhoto !== '') {
    if (preg_match('~^https?://~', $brandPhoto)) {
      $brandSrc = $brandPhoto;
    } else {
      $brandSrc = base_url($brandPhoto);
      $fp = __DIR__ . '/../../' . ltrim($brandPhoto,'/');
      if (is_file($fp)) { $brandSrc .= (strpos($brandSrc,'?')===false?'?':'&') . 'v=' . (int)@filemtime($fp); }
    }
  }
  $brandTagline = $settings['brand_tagline'] ?? 'Bilgi Sistemleri Uzmanı';
?>
<a class="navbar-brand brand" href="<?= e(base_url('/')) ?>">
  <?php if ($brandSrc): ?>
    <img class="brand-logo" style="width:36px;height:36px;border-radius:999px;object-fit:cover;flex:0 0 auto;" src="<?= e($brandSrc) ?>" alt="Logo" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
    <span class="brand-monogram" style="display:none;"><?= e(mb_strtoupper(mb_substr($siteName,0,1))) ?></span>
  <?php else: ?>
    <span class="brand-monogram"><?= e(mb_strtoupper(mb_substr($siteName,0,1))) ?></span>
  <?php endif; ?>
  <span class="brand-text">
    <span class="brand-name"><?= e($siteName) ?></span>
    <span class="brand-sub"><?= e($brandTagline) ?></span>
  </span>
</a>
    <!-- Mobile: Offcanvas (iOS Safari uyumlu) -->
    <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#navOffcanvas" aria-controls="navOffcanvas" aria-label="Menü">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Desktop: classic collapse -->
    <div id="nav" class="collapse navbar-collapse d-none d-lg-flex">
      <ul class="navbar-nav ms-auto gap-2 align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/')) ?>">Anasayfa</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('hakkimda')) ?>">Hakkımda</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('projeler')) ?>">Projeler</a></li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="<?= e(base_url('araclar')) ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Araçlar
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/teklif-olustur')) ?>"><i class="bi bi-receipt-cutoff me-1"></i>Teklif Oluştur</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-kucultme')) ?>">PDF Küçültme</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-birlestirme')) ?>">PDF Birleştirme</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/word-pdf')) ?>">Word → PDF</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/jpg-pdf')) ?>">JPG → PDF</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-bol')) ?>">PDF Böl</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-sayfa-sil')) ?>">PDF Sayfa Sil</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-dondur')) ?>">PDF Sayfa Döndür</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-filigran')) ?>">PDF Filigran</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/resim-sikistir')) ?>">Resim Sıkıştır / Dönüştür</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-sayfa-cikar')) ?>">PDF Sayfa Çıkar</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-sayfa-numarasi')) ?>">PDF Sayfa Numarası</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-metadata')) ?>">PDF Metadata</a></li>
                <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-imza')) ?>">PDF İmza Ekle</a></li>
              </ul>
            </li>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('blog')) ?>">Blog</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('dokumanlar')) ?>">Dökümanlar</a></li>
              <?php if (UserAuth::check()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('destek')) ?>">Destek</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('iletisim')) ?>">İletişim</a></li>
        <li class="nav-item d-none d-lg-block"><span class="text-muted">•</span></li>
        <li class="nav-item">
          <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('admin/login.php')) ?>">Admin</a>
        </li>
      <li class="nav-item ms-lg-2">
  <?php if (UserAuth::check()): ?>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('profil')) ?>"><i class="bi bi-person-circle me-1"></i><?= e(UserAuth::name()) ?></a>
  <?php else: ?>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('giris')) ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Giriş</a>
  <?php endif; ?>
</li>
<?php if (!UserAuth::check() && (Settings::get('registration_enabled','0') ?? '0') === '1'): ?>
<li class="nav-item">
  <a class="btn btn-sm btn-primary" href="<?= e(base_url('kayit')) ?>"><i class="bi bi-person-plus me-1"></i>Kayıt</a>
</li>
<?php endif; ?>
<?php if (UserAuth::check()): ?>
<li class="nav-item">
  <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('cikis')) ?>"><i class="bi bi-box-arrow-right"></i></a>
</li>
<?php endif; ?>

      </ul>

    </div>
  </div>
</nav>

<!-- Offcanvas mobile menu -->
<div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="navOffcanvas" aria-labelledby="navOffcanvasLabel">
  <div class="offcanvas-header">
    <div class="d-flex align-items-center gap-2" id="navOffcanvasLabel">
      <?php if ($brandSrc): ?>
        <img class="brand-logo" style="width:34px;height:34px;border-radius:999px;object-fit:cover;flex:0 0 auto;" src="<?= e($brandSrc) ?>" alt="Logo" loading="lazy" onerror="this.style.display='none';">
      <?php else: ?>
        <span class="brand-monogram" style="width:34px;height:34px;display:grid;place-items:center;">
          <?= e(mb_strtoupper(mb_substr($siteName,0,1))) ?>
        </span>
      <?php endif; ?>
      <div class="min-w-0">
        <div class="fw-semibold text-truncate" style="max-width: 65vw;"><?= e($siteName) ?></div>
        <div class="small text-muted text-truncate" style="max-width: 65vw;"><?= e($brandTagline) ?></div>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="navbar-nav gap-1">
      <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/')) ?>">Anasayfa</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e(base_url('hakkimda')) ?>">Hakkımda</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e(base_url('projeler')) ?>">Projeler</a></li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Araçlar</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/teklif-olustur')) ?>">Teklif Oluştur</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-kucultme')) ?>">PDF Küçültme</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-birlestirme')) ?>">PDF Birleştirme</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/word-pdf')) ?>">Word → PDF</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/jpg-pdf')) ?>">JPG → PDF</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-bol')) ?>">PDF Böl</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-sayfa-sil')) ?>">PDF Sayfa Sil</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-dondur')) ?>">PDF Sayfa Döndür</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-filigran')) ?>">PDF Filigran</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/resim-sikistir')) ?>">Resim Sıkıştır / Dönüştür</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-sayfa-cikar')) ?>">PDF Sayfa Çıkar</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-sayfa-numarasi')) ?>">PDF Sayfa Numarası</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-metadata')) ?>">PDF Metadata</a></li>
          <li><a class="dropdown-item" href="<?= e(base_url('araclar/pdf-imza')) ?>">PDF İmza Ekle</a></li>
        </ul>
      </li>

      <li class="nav-item"><a class="nav-link" href="<?= e(base_url('blog')) ?>">Blog</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e(base_url('dokumanlar')) ?>">Dökümanlar</a></li>
      <?php if (UserAuth::check()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('destek')) ?>">Destek</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link" href="<?= e(base_url('iletisim')) ?>">İletişim</a></li>

      <li><hr class="dropdown-divider"></li>
      <li class="nav-item"><a class="btn btn-outline-primary w-100 mb-2" href="<?= e(base_url('admin/login.php')) ?>">Admin</a></li>
      <?php if (UserAuth::check()): ?>
        <li class="nav-item"><a class="btn btn-outline-primary w-100 mb-2" href="<?= e(base_url('profil')) ?>"><i class="bi bi-person-circle me-1"></i><?= e(UserAuth::name()) ?></a></li>
        <li class="nav-item"><a class="btn btn-outline-secondary w-100" href="<?= e(base_url('cikis')) ?>"><i class="bi bi-box-arrow-right me-1"></i>Çıkış</a></li>
      <?php else: ?>
        <li class="nav-item"><a class="btn btn-outline-primary w-100 mb-2" href="<?= e(base_url('giris')) ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Giriş</a></li>
        <?php if ((Settings::get('registration_enabled','0') ?? '0') === '1'): ?>
          <li class="nav-item"><a class="btn btn-primary w-100" href="<?= e(base_url('kayit')) ?>"><i class="bi bi-person-plus me-1"></i>Kayıt</a></li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
  </div>
</div>
<main class="container py-4">