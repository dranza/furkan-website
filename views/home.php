<?php
$siteName = Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan';
$heroTitle = Settings::get('hero_title','Bilgi Sistemleri Uzmanı') ?? 'Bilgi Sistemleri Uzmanı';
$heroSubtitle = Settings::get('hero_subtitle','Hastanede yürüttüğüm projeler, paylaşımlar ve teknik notlar.') ?? '';

$skills = array_filter(array_map('trim', explode(',', Settings::get('skills','') ?? '')));
$highlights = array_filter(array_map('trim', explode(',', Settings::get('highlights','') ?? '')));

$linkedin = Settings::get('social_linkedin','') ?? '';
$github = Settings::get('social_github','') ?? '';
$profilePhoto = Settings::get('profile_photo','') ?? '';
?>

<div class="hero rounded-4 p-4 p-md-5">
  <div class="row g-4 align-items-center position-relative" style="z-index:1;">
    <div class="col-lg-7">
      <span class="badge bg-light text-dark border mb-3">Hastane • Bilgi Sistemleri • Proje & Operasyon</span>
      <h1 class="display-6 fw-bold mb-2"><?= e($heroTitle) ?></h1>
      <p class="lead mb-4"><?= e($heroSubtitle) ?></p>

      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-primary" href="<?= e(base_url('projeler')) ?>">Projeler</a>
        <a class="btn btn-outline-primary" href="<?= e(base_url('blog')) ?>">Blog</a>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('hakkimda')) ?>">Hakkımda</a>
        <?php if ($linkedin): ?><a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= e($linkedin) ?>">LinkedIn</a><?php endif; ?>
        <?php if ($github): ?><a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= e($github) ?>">GitHub</a><?php endif; ?>
      </div>

      <?php if (!empty($homeStats)): ?>
<div class="row g-2 mt-4">
  <?php foreach (array_slice($homeStats,0,3) as $s): ?>
    <div class="col-sm-4">
      <div class="kpi p-3">
        <div class="num"><?= e($s['title'] ?? '') ?></div>
        <div class="lbl"><?= e($s['body'] ?? '') ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($skills): ?>
        <div class="mt-4">
          <div class="small text-muted mb-2">Uzmanlık Alanları</div>
          <div class="d-flex gap-2 flex-wrap">
            <?php foreach (array_slice($skills, 0, 10) as $s): ?>
              <span class="badge bg-light text-dark border"><?= e($s) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <div class="col-lg-5">
      <div class="glass p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <?php
  $pp = trim((string)(Settings::get('profile_photo','') ?? ''));
  $ppSrc = '';
  if ($pp !== '') {
    if (preg_match('~^https?://~', $pp)) {
      $ppSrc = $pp;
    } else {
      $ppSrc = base_url($pp);
      $fp = __DIR__ . '/../' . ltrim($pp,'/');
      if (is_file($fp)) { $ppSrc .= (strpos($ppSrc,'?')===false?'?':'&') . 'v=' . (int)@filemtime($fp); }
    }
  }
?>
<?php if ($ppSrc): ?>
  <img class="avatar-img" style="width:56px;height:56px;border-radius:999px;object-fit:cover;flex:0 0 auto;" src="<?= e($ppSrc) ?>" alt="Profil" loading="lazy"
       onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
  <div class="avatar" style="display:none;"><?= e(mb_strtoupper(mb_substr($siteName,0,1))) ?></div>
<?php else: ?>
  <div class="avatar"><?= e(mb_strtoupper(mb_substr($siteName,0,1))) ?></div>
<?php endif; ?>
  <div>
    <div class="fw-bold"><?= e($siteName) ?></div>
    <div class="text-muted small">Bilgi Sistemleri • Hastane</div>
          </div>
        </div>

        <?php if ($highlights): ?>
          <div class="small text-muted mb-2">Vitrin</div>
          <div class="vstack gap-2">
            <?php foreach (array_slice($highlights,0,5) as $h): ?>
              <div class="highlight"><i class="bi bi-check2-circle me-2"></i><?= e($h) ?></div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-muted">Admin panelden “Vitrin Maddeleri” ekleyerek burayı zenginleştirebilirsin.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Neler yapıyorum -->
<div class="mt-5">
  <div class="d-flex justify-content-between align-items-end mb-2">
    <div>
      <h2 class="h4 section-title mb-1"><?= e(Settings::get('home_services_title','Neler Yapıyorum?') ?? 'Neler Yapıyorum?') ?></h2>
      <div class="section-subtitle"><?= e(Settings::get('home_services_subtitle','Hastane ortamında uçtan uca BT operasyonu ve proje teslimi') ?? '') ?></div>
    </div>
    <a class="text-decoration-none fw-semibold" href="<?= e(base_url('hakkimda')) ?>">Profil →</a>
  </div>

    <?php
    $serviceCards = $homeServices ?? [];
    if (!$serviceCards) {
      $serviceCards = [
        ['icon'=>'bi-hospital','title'=>'Hastane Bilgi Sistemleri','body'=>'HIS süreçleri, rol & yetki, kritik süreçler ve kesintisiz hizmet.','link_url'=>''],
        ['icon'=>'bi-shield-lock','title'=>'Güvenlik & Erişim','body'=>'AD/LDAP, MFA, erişim kontrolleri, loglama, güvenlik hardening.','link_url'=>''],
        ['icon'=>'bi-diagram-3','title'=>'Entegrasyon & Otomasyon','body'=>'HL7/FHIR, API, entegrasyonlar, raporlama ve otomasyon senaryoları.','link_url'=>''],
        ['icon'=>'bi-kanban','title'=>'Proje & Süreç','body'=>'ITSM, süreç iyileştirme, standartlaştırma ve dokümantasyon.','link_url'=>''],
      ];
    }
  ?>
  <div class="row g-3">
    <?php foreach (array_slice($serviceCards, 0, 8) as $c): ?>
      <?php
        $icon = $c['icon'] ?? ($c['i'] ?? 'bi-stars');
        $title = $c['title'] ?? ($c['t'] ?? '');
        $body = $c['body'] ?? ($c['d'] ?? '');
        $link = $c['link_url'] ?? '';
        $href = $link ? (preg_match('~^https?://~', $link) ? $link : base_url($link)) : '';
      ?>
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 service-card">
          <div class="card-body">
            <div class="icon-chip mb-3"><i class="bi <?= e($icon) ?>"></i></div>
            <div class="fw-bold mb-1"><?= e($title) ?></div>
            <div class="text-muted"><?= e($body) ?></div>
            <?php if ($href): ?><a class="stretched-link" href="<?= e($href) ?>"></a><?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- KPI / Süreç / Teknoloji -->
<?php
  $process = $homeProcess ?? [];
  $tech = $homeTech ?? [];
?>
<div class="mt-5">
  <div class="d-flex justify-content-between align-items-end mb-2">
    <div>
      <h2 class="h4 section-title mb-1"><?= e(Settings::get('home_process_title','Çalışma Modeli') ?? 'Çalışma Modeli') ?></h2>
      <div class="section-subtitle"><?= e(Settings::get('home_process_subtitle','Analizden devreye almaya, dokümantasyondan izlemeye') ?? '') ?></div>
    </div>
  </div>

  <div class="row g-3">
    <?php foreach (array_slice($process,0,4) as $p): ?>
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 service-card">
          <div class="card-body">
            <div class="icon-chip mb-3"><i class="bi <?= e($p['icon'] ?? 'bi-clipboard2-check') ?>"></i></div>
            <div class="fw-bold mb-1"><?= e($p['title'] ?? '') ?></div>
            <div class="text-muted"><?= e($p['body'] ?? '') ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if (!empty($tech)): ?>
<div class="mt-5">
  <div class="d-flex justify-content-between align-items-end mb-2">
    <div>
      <h2 class="h4 section-title mb-1"><?= e(Settings::get('home_tech_title','Teknoloji & Platformlar') ?? 'Teknoloji & Platformlar') ?></h2>
      <div class="section-subtitle"><?= e(Settings::get('home_tech_subtitle','Saha deneyimiyle kullandığım araçlar ve yaklaşımlar') ?? '') ?></div>
    </div>
    <a class="text-decoration-none fw-semibold" href="<?= e(base_url('hakkimda')) ?>">Detay →</a>
  </div>

  <div class="row g-3">
    <?php foreach (array_slice($tech,0,12) as $t): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 service-card">
          <div class="card-body">
            <div class="d-flex align-items-center gap-2">
              <div class="icon-chip"><i class="bi <?= e($t['icon'] ?? 'bi-cpu') ?>"></i></div>
              <div>
                <div class="fw-bold"><?= e($t['title'] ?? '') ?></div>
                <div class="text-muted small"><?= e($t['body'] ?? '') ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Öne çıkan projeler -->
<?php if (!empty($featuredProjects)): ?>
  <div class="d-flex justify-content-between align-items-end mt-5 mb-2">
    <div>
      <h2 class="h4 section-title mb-1">Öne Çıkan Projeler</h2>
      <div class="section-subtitle">Seçili çalışmalar • etki, entegrasyon ve sürdürülebilirlik</div>
    </div>
    <a class="text-decoration-none fw-semibold" href="<?= e(base_url('projeler')) ?>">Tümü →</a>
  </div>

  <div class="row g-3">
    <?php foreach (array_slice($featuredProjects, 0, 3) as $fp): ?>
      <div class="col-lg-4">
        <div class="card h-100 featured-card">
          <?php if (!empty($fp['cover_image'])): ?>
            <img src="<?= e(base_url($fp['cover_image'])) ?>" class="card-img-top" alt="<?= e($fp['title']) ?>">
          <?php endif; ?>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge badge-featured"><i class="bi bi-pin-angle-fill me-1"></i>Öne Çıkan</span>
              <span class="small text-muted"><?= e($fp['published_at'] ? date('d.m.Y', strtotime((string)$fp['published_at'])) : '') ?></span>
            </div>
            <h3 class="h6 fw-bold mb-2"><?= e($fp['title']) ?></h3>
            <?php if (!empty($fp['summary'])): ?>
              <p class="mb-0 text-muted"><?= e(mb_substr((string)$fp['summary'], 0, 120)) ?><?= mb_strlen((string)$fp['summary'])>120?'…':'' ?></p>
            <?php else: ?>
              <p class="mb-0 text-muted">Detayları görmek için tıklayın.</p>
            <?php endif; ?>
            <a class="stretched-link" href="<?= e(base_url('proje/'.$fp['slug'])) ?>"></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Son projeler -->
<div class="d-flex justify-content-between align-items-end mt-5 mb-2">
  <div>
    <h2 class="h4 section-title mb-1">Son Projeler</h2>
    <div class="section-subtitle">Hastane bilgi sistemleri, süreç iyileştirme ve otomasyon</div>
  </div>
  <a class="text-decoration-none fw-semibold" href="<?= e(base_url('projeler')) ?>">Tümü →</a>
</div>

<?php if (empty($projects)): ?>
  <div class="card"><div class="card-body">Henüz proje yok. Admin panelden ekleyebilirsin.</div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach (array_slice($projects,0,3) as $p): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <?php if (!empty($p['cover_image'])): ?>
            <img src="<?= e(base_url($p['cover_image'])) ?>" class="card-img-top" alt="<?= e($p['title']) ?>">
          <?php endif; ?>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="small text-muted"><?= e($p['published_at'] ? date('d.m.Y', strtotime((string)$p['published_at'])) : '') ?></div>
              <?php if (!empty($p['technologies'])): ?><span class="badge bg-light text-dark border"><?= e($p['technologies']) ?></span><?php endif; ?>
            </div>
            <h3 class="h6 fw-bold mb-1"><?= e($p['title']) ?></h3>
            <div class="small-muted">Detaylar →</div>
            <a class="stretched-link" href="<?= e(base_url('proje/'.$p['slug'])) ?>"></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Son blog -->
<div class="d-flex justify-content-between align-items-end mt-5 mb-2">
  <div>
    <h2 class="h4 section-title mb-1">Son Blog Yazıları</h2>
    <div class="section-subtitle">Teknik notlar, çözümler ve saha tecrübeleri</div>
  </div>
  <a class="text-decoration-none fw-semibold" href="<?= e(base_url('blog')) ?>">Tümü →</a>
</div>

<?php if (empty($blogs)): ?>
  <div class="card"><div class="card-body">Henüz blog yazısı yok. Admin panelden ekleyebilirsin.</div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach (array_slice($blogs,0,3) as $b): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <?php if (!empty($b['cover_image'])): ?>
            <img src="<?= e(base_url($b['cover_image'])) ?>" class="card-img-top" alt="<?= e($b['title']) ?>">
          <?php endif; ?>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="small text-muted"><?= e($b['published_at'] ? date('d.m.Y', strtotime((string)$b['published_at'])) : '') ?></div>
              <?php if (!empty($b['category'])): ?><span class="badge bg-light text-dark border"><?= e($b['category']) ?></span><?php endif; ?>
            </div>
            <h3 class="h6 fw-bold mb-1"><?= e($b['title']) ?></h3>
            <div class="small-muted">Okumak için tıkla</div>
            <a class="stretched-link" href="<?= e(base_url('blog/'.$b['slug'])) ?>"></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Yaklaşım / Değerler -->
<div class="mt-5">
  <div class="d-flex justify-content-between align-items-end mb-2">
    <div>
      <h2 class="h4 section-title mb-1">Çalışma Yaklaşımım</h2>
      <div class="section-subtitle">Güvenilir, ölçülebilir ve sürdürülebilir çıktılar</div>
    </div>
  </div>

  <?php
    $values = [
      ['i'=>'bi-clipboard2-check','t'=>'Dokümantasyon','d'=>'Süreçleri görünür kılan, devredilebilir dokümantasyon.'],
      ['i'=>'bi-speedometer2','t'=>'Performans','d'=>'Kritik sistemlerde izlenebilirlik ve optimizasyon.'],
      ['i'=>'bi-shield-check','t'=>'Güvenlik','d'=>'Erişim, log, hardening ve risk azaltma yaklaşımı.'],
      ['i'=>'bi-arrow-repeat','t'=>'İyileştirme','d'=>'Sürekli iyileştirme: küçük adım, büyük etki.'],
    ];
  ?>
  <div class="row g-3">
    <?php foreach ($values as $v): ?>
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 value-card">
          <div class="card-body">
            <div class="icon-chip mb-3"><i class="bi <?= e($v['i']) ?>"></i></div>
            <div class="fw-bold mb-1"><?= e($v['t']) ?></div>
            <div class="text-muted"><?= e($v['d']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Mini Timeline -->
<?php
  $timeline = Timeline::all();
  $timelinePreview = array_slice($timeline, 0, 6);
?>
<?php if (!empty($timelinePreview)): ?>
  <div class="mt-5">
    <div class="d-flex justify-content-between align-items-end mb-2">
      <div>
        <h2 class="h4 section-title mb-1">Kısa Zaman Çizelgesi</h2>
        <div class="section-subtitle">Eğitim ve çalışma deneyiminden öne çıkanlar</div>
      </div>
      <a class="text-decoration-none fw-semibold" href="<?= e(base_url('hakkimda')) ?>">Detay →</a>
    </div>

    <div class="card p-3 p-md-4 timeline-card">
      <div class="row g-3">
        <?php foreach ($timelinePreview as $t): ?>
          <div class="col-md-6">
            <div class="d-flex gap-3 align-items-start timeline-item">
              <div class="dot"></div>
              <div>
                <div class="fw-bold"><?= e($t['title']) ?></div>
                <div class="text-muted small"><?= e($t['subtitle'] ?? '') ?></div>
                <div class="text-muted small"><?= e($t['date_range'] ?? '') ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Referans / Yorumlar (Admin: testimonials) -->
<?php
  $testimonialsRaw = (string)(Settings::get('testimonials','') ?? '');
  $testimonials = [];
  foreach (preg_split("/\r?\n/", trim($testimonialsRaw)) as $line) {
    $line = trim($line);
    if (!$line) continue;
    $parts = array_map('trim', explode('|', $line));
    $testimonials[] = [
      'name' => $parts[0] ?? '',
      'title' => $parts[1] ?? '',
      'text' => $parts[2] ?? ''
    ];
  }
?>
<?php if (!empty($testimonials)): ?>
  <div class="mt-5">
    <div class="d-flex justify-content-between align-items-end mb-2">
      <div>
        <h2 class="h4 section-title mb-1">Geri Bildirimler</h2>
        <div class="section-subtitle">Birlikte çalıştığım kişilerden kısa notlar</div>
      </div>
    </div>

    <div class="row g-3">
      <?php foreach (array_slice($testimonials,0,3) as $r): ?>
        <div class="col-lg-4">
          <div class="card h-100 quote-card">
            <div class="card-body">
              <div class="quote-mark"><i class="bi bi-quote"></i></div>
              <div class="fw-semibold mb-3" style="white-space:pre-wrap;"><?= e($r['text']) ?></div>
              <div class="d-flex align-items-center gap-2 mt-auto">
                <div class="mini-avatar"><?= e(mb_strtoupper(mb_substr($r['name'],0,1))) ?></div>
                <div>
                  <div class="fw-bold"><?= e($r['name']) ?></div>
                  <div class="text-muted small"><?= e($r['title']) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- CTA -->
<div class="cta mt-5 rounded-4 p-4 p-md-5">
  <div class="row g-3 align-items-center">
    <div class="col-lg-8">
      <h2 class="h4 fw-bold mb-2">Birlikte çalışalım</h2>
      <div class="text-muted">Hastane bilgi sistemleri, güvenlik, entegrasyon ve süreç iyileştirme konularında iletişime geçebilirsin.</div>
    </div>
    <div class="col-lg-4 text-lg-end">
      <a class="btn btn-primary" href="<?= e(base_url('iletisim')) ?>"><i class="bi bi-envelope me-1"></i>İletişim</a>
      <a class="btn btn-outline-secondary ms-2" href="<?= e(base_url('blog')) ?>"><i class="bi bi-journal-text me-1"></i>Blog</a>
    </div>
  </div>
</div>
