<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/../app/models/Media.php';

$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  // Profil fotoğrafı yükle
  $pp = handle_upload('profile_photo_file', 'profile');
  if ($pp) {
    try { Media::add($pp, $_FILES['profile_photo_file']['name'] ?? 'profile', $_FILES['profile_photo_file']['type'] ?? 'image', (int)($_FILES['profile_photo_file']['size'] ?? 0)); } catch (Throwable $t) {}
    Settings::set('profile_photo', $pp);
    $_POST['profile_photo'] = $pp;
  }

  $fields = [
    'site_name','site_description',
    'hero_title','hero_subtitle',
    'skills','highlights',
    'contact_email','contact_phone','contact_location',
    'social_linkedin','social_github',
    'google_analytics_id','gsc_meta','sitemap_auto','sitemap_ping','comments_enabled','comments_require_approval','contact_form_enabled','contact_form_store','contact_form_send_email','testimonials','contact_hours','contact_availability','contact_map_embed','brand_tagline','page_cache_enabled','page_cache_ttl','registration_enabled','registration_require_approval','comments_require_login',
  'tickets_enabled','debug_mode','profile_photo'
  ];

  // Text fields: only update if present in POST (prevent wiping on new versions)
  $textFields = [
    'site_name','site_description','hero_title','hero_subtitle','skills','highlights',
    'contact_email','contact_phone','contact_location',
    'social_linkedin','social_github',
    'google_analytics_id','gsc_meta',
    'testimonials','contact_hours','contact_availability','contact_map_embed',
    'brand_tagline','page_cache_ttl','profile_photo'
  ];
  foreach ($textFields as $k) {
    if (isset($_POST[$k])) Settings::set($k, trim((string)$_POST[$k]));
  }

  // Checkbox / switch fields: always set 1/0
  $checkFields = [
    'sitemap_auto','sitemap_ping',
    'comments_enabled','comments_require_approval','comments_require_login',
    'contact_form_enabled','contact_form_store','contact_form_send_email',
    'page_cache_enabled',
    'registration_enabled','registration_require_approval',
    'tickets_enabled',
    'debug_mode'
  ];
  foreach ($checkFields as $ck) {
    Settings::set($ck, isset($_POST[$ck]) ? '1' : '0');
  }
  $ok = 'Kaydedildi.';
  // Public page cache temizle
  if (function_exists('purge_all_cache')) { purge_all_cache(); }
}

$all = Settings::getAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Site Ayarları</h1>
    <div class="text-muted">Anasayfa, SEO ve iletişim bilgileri</div>
  </div>
  <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('/')) ?>" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>Siteyi aç</a>
</div>

<?php if ($ok): ?><div class="alert alert-success border-0" style="background: rgba(34,197,94,.18); color:#fff;"><?= e($ok) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card p-3">
        <div class="fw-bold mb-2"><i class="bi bi-globe2 me-1"></i>Genel</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Site Adı</label>
            <input class="form-control" name="site_name" value="<?= e($all['site_name'] ?? 'Furkan Cihan') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Üst Menü Slogan (Brand Tagline)</label>
            <input class="form-control" name="brand_tagline" value="<?= e($all['brand_tagline'] ?? 'Bilgi Sistemleri Uzmanı') ?>" placeholder="Bilgi Sistemleri Uzmanı">
          </div>


          <div class="col-md-6">
            <label class="form-label">Site Açıklaması (SEO)</label>
            <input class="form-control" name="site_description" value="<?= e($all['site_description'] ?? 'Bilgi Sistemleri Uzmanı') ?>">
          </div>
        </div>
      </div>

      <div class="card p-3 mt-3">
        <div class="fw-bold mb-2"><i class="bi bi-stars me-1"></i>Anasayfa</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Hero Başlık</label>
            <input class="form-control" name="hero_title" value="<?= e($all['hero_title'] ?? 'Bilgi Sistemleri Uzmanı') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Hero Alt Başlık</label>
            <input class="form-control" name="hero_subtitle" value="<?= e($all['hero_subtitle'] ?? 'Projeler, paylaşımlar ve teknik notlar.') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Uzmanlık Alanları (virgülle)</label>
            <input class="form-control" name="skills" value="<?= e($all['skills'] ?? 'Hastane Bilgi Sistemleri, Ağ & Sistem, ITIL, Siber Güvenlik, Entegrasyonlar, Raporlama') ?>">
            <div class="form-text text-muted">Örn: HIS, Active Directory, VMware, ITSM, SQL, HL7...</div>
          </div>
          <div class="col-12">
            <label class="form-label">Vitrin Maddeleri (virgülle)</label>
            <input class="form-control" name="highlights" value="<?= e($all['highlights'] ?? 'Operasyonel Süreç İyileştirme, Proje Yönetimi, Entegrasyon & Otomasyon, Güvenlik & Erişim Kontrolleri') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card p-3">
        <div class="fw-bold mb-2"><i class="bi bi-telephone me-1"></i>İletişim</div>
        <div class="mb-3">
          <label class="form-label">E-posta</label>
          <input class="form-control" name="contact_email" value="<?= e($all['contact_email'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Telefon</label>
          <input class="form-control" name="contact_phone" value="<?= e($all['contact_phone'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Konum</label>
          <input class="form-control" name="contact_location" value="<?= e($all['contact_location'] ?? '') ?>">
        </div>

<div class="card p-3 mt-3">
  <div class="fw-bold mb-2"><i class="bi bi-envelope-paper me-1"></i>İletişim Formu</div>

  <div class="form-check form-switch mb-2">
    <?php $v = ($all['contact_form_enabled'] ?? '1'); ?>
    <input class="form-check-input" type="checkbox" role="switch" name="contact_form_enabled" value="1" <?= $v==='1'?'checked':'' ?>>
    <label class="form-check-label">Form aktif</label>
  </div>

  <div class="form-check form-switch mb-2">
    <?php $v = ($all['contact_form_store'] ?? '1'); ?>
    <input class="form-check-input" type="checkbox" role="switch" name="contact_form_store" value="1" <?= $v==='1'?'checked':'' ?>>
    <label class="form-check-label">Mesajları admin panele kaydet</label>
  </div>

  <div class="form-check form-switch">
    <?php $v = ($all['contact_form_send_email'] ?? '1'); ?>
    <input class="form-check-input" type="checkbox" role="switch" name="contact_form_send_email" value="1" <?= $v==='1'?'checked':'' ?>>
    <label class="form-check-label">E-posta bildirimi gönder</label>
  </div>

  <div class="small text-muted mt-2">
    Not: “Kaydet” kapalıysa mesajlar sadece e-posta ile iletilebilir (eğer açık ise).
  </div>
</div>

<hr class="my-3">
<div class="fw-bold mb-2"><i class="bi bi-clock-history me-1"></i>İletişim Ek Bilgiler</div>
<label class="form-label">Çalışma saatleri</label>
<input class="form-control mb-2" name="contact_hours" value="<?= e($all['contact_hours'] ?? 'Hafta içi 09:00 - 18:00') ?>" placeholder="Hafta içi 09:00 - 18:00">

<label class="form-label">Uygunluk rozeti</label>
<select class="form-select mb-2" name="contact_availability">
  <?php $av = $all['contact_availability'] ?? 'available'; ?>
  <option value="available" <?= $av==='available'?'selected':'' ?>>Müsait</option>
  <option value="busy" <?= $av==='busy'?'selected':'' ?>>Yoğun</option>
  <option value="offline" <?= $av==='offline'?'selected':'' ?>>Offline</option>
</select>

<label class="form-label">Google Maps Embed (iframe)</label>
<textarea class="form-control" name="contact_map_embed" rows="4" placeholder="&lt;iframe src=&quot;...&quot; ...&gt;&lt;/iframe&gt;"><?= e($all['contact_map_embed'] ?? '') ?></textarea>
<div class="small text-muted mt-1">Google Maps &gt; Paylaş &gt; Harita yerleştir (Embed a map) kodunu buraya yapıştır.</div>

      </div>

      <div class="card p-3 mt-3">
        <div class="fw-bold mb-2"><i class="bi bi-bar-chart me-1"></i>Entegrasyon</div>
        <div class="mb-3">
          <label class="form-label">Google Analytics Measurement ID (opsiyonel)</label>
          <input class="form-control" name="google_analytics_id" value="<?= e($all['google_analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
        </div>
        <div class="mb-3">
          <label class="form-label">Search Console doğrulama meta (opsiyonel)</label>
          <input class="form-control" name="gsc_meta" value="<?= e($all['gsc_meta'] ?? '') ?>" placeholder="google-site-verification=...">
        </div>
        <div class="fw-bold mb-2 mt-4"><i class="bi bi-diagram-3 me-1"></i>SEO & Sitemap</div>
<div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" name="sitemap_auto" value="1" <?= !empty($all['sitemap_auto']) && $all['sitemap_auto']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Sitemap otomatik güncellensin</label>
</div>
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" name="sitemap_ping" value="1" <?= !empty($all['sitemap_ping']) && $all['sitemap_ping']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Ping ile arama motorlarına haber ver</label>
  <div class="form-text text-muted">Google/Bing ping yapılır (destek durumu arama motoruna göre değişebilir).</div>
</div>
<div class="d-flex gap-2">
  <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('sitemap.xml')) ?>" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>Sitemap'i Aç</a>
  <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('admin/seo-sitemap.php')) ?>"><i class="bi bi-arrow-repeat me-1"></i>Şimdi Güncelle & Ping</a>
</div>

<hr class="my-3">
<div class="fw-bold mb-2"><i class="bi bi-chat-dots me-1"></i>Yorumlar</div>

<div class="fw-bold mb-2 mt-4"><i class="bi bi-folder2-open me-1"></i>Dökümanlar</div>
<label class="form-label">İzinli uzantılar (virgülle) veya <code>all</code></label>
<input class="form-control" name="downloads_allowed_ext" value="<?= e($all['downloads_allowed_ext'] ?? 'all') ?>" placeholder="all">
<div class="form-text text-muted">Güvenlik: PHP/JS/HTML/SVG gibi script uzantıları her zaman engellenir.</div>


<div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" name="comments_enabled" value="1" <?= !empty($all['comments_enabled']) && $all['comments_enabled']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Blog yorumları açık</label>
</div>
<div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" name="comments_require_approval" value="1" <?= !empty($all['comments_require_approval']) && $all['comments_require_approval']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Yorumlar onay sonrası yayınlansın</label>
</div>


        <hr class="my-3">
<div class="fw-bold mb-2"><i class="bi bi-speedometer2 me-1"></i>Performans</div>
<div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" name="page_cache_enabled" value="1" <?= !empty($all['page_cache_enabled']) && $all['page_cache_enabled']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Sayfa cache (ziyaretçiler için)</label>
</div>
<label class="form-label">Cache TTL (saniye)</label>
<input class="form-control mb-2" name="page_cache_ttl" value="<?= e($all['page_cache_ttl'] ?? '300') ?>" placeholder="300">

<hr class="my-3">
<div class="fw-bold mb-2"><i class="bi bi-person-plus me-1"></i>Kullanıcı Kayıt</div>
<div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" name="registration_enabled" value="1" <?= !empty($all['registration_enabled']) && $all['registration_enabled']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Kayıt ekranı açık</label>
</div>
<div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" name="registration_require_approval" value="1" <?= !empty($all['registration_require_approval']) && $all['registration_require_approval']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Kayıtlar onay gerektirir (Admin)</label>
</div>
<div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" name="comments_require_login" value="1" <?= !empty($all['comments_require_login']) && $all['comments_require_login']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Yorum için giriş zorunlu (opsiyonel)</label>
</div>

<hr class="my-3">
<div class="fw-bold mb-2"><i class="bi bi-bug me-1"></i>Debug</div>
<div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" name="debug_mode" value="1" <?= !empty($all['debug_mode']) && $all['debug_mode']=='1' ? 'checked' : '' ?>>
  <label class="form-check-label">Debug modu (geliştirme)</label>
</div>
<div class="small text-muted">Debug kapalıyken hatalar ekranda gösterilmez ve loglar <code>storage/php-error.log</code> içine yönlendirilir.</div>


      <div class="fw-bold mb-2"><i class="bi bi-share me-1"></i>Sosyal</div>
        <div class="mb-3">
          <label class="form-label">LinkedIn URL</label>
          <input class="form-control" name="social_linkedin" value="<?= e($all['social_linkedin'] ?? '') ?>" placeholder="https://www.linkedin.com/in/...">
        </div>
        <div class="mb-2">
          <label class="form-label">GitHub URL</label>
          <input class="form-control" name="social_github" value="<?= e($all['social_github'] ?? '') ?>" placeholder="https://github.com/...">
        </div>
        <div class="form-text text-muted">Bu linkler anasayfada buton olarak görünür.

<hr class="my-3">
<div class="fw-bold mb-2"><i class="bi bi-person-square me-1"></i>Profil Fotoğrafı</div>
<div class="text-muted small mb-2">Admin panel ve anasayfa profil alanında görünür.</div>

<div class="row g-2 align-items-end">
  <div class="col-md-6">
    <label class="form-label">Yükle</label>
    <input class="form-control" type="file" name="profile_photo_file" accept="image/*">
    <div class="form-text">Öneri: 400×400 (kare)</div>
  </div>
  <div class="col-md-6">
    <label class="form-label">Veya medya seç</label>
    <div class="d-flex gap-2">
      <input id="profile_photo" class="form-control" name="profile_photo" value="<?= e($s['profile_photo'] ?? '') ?>" placeholder="uploads/media/...">
      <button type="button" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#mediaModalProfile"><i class="bi bi-images"></i></button>
    </div>
    <div class="d-flex gap-2 mt-2">
      <button type="button" class="btn btn-outline-light btn-sm" onclick="clearProfilePhoto()"><i class="bi bi-x-lg me-1"></i>Temizle</button>
    </div>
  </div>
</div>

<?php if (!empty($s['profile_photo'])): ?>
  <div class="mt-2">
    <img src="<?= e(base_url($s['profile_photo'])) ?>" class="rounded-4 border" style="width:120px;height:120px;object-fit:cover;" alt="Profil">
  </div>
<?php endif; ?>
</div>
      </div>

      <div class="mt-3">
        <button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i>Kaydet</button>
      </div>
    </div>
  </div>
</form>

<script>
function clearProfilePhoto(){
  const i = document.getElementById('profile_photo');
  if(i){ i.value=''; }
  // Formu otomatik kaydetme yok; kullanıcı Kaydet'e basmalı.
}
</script>


<?php require_once __DIR__ . '/_layout_bottom.php'; ?>