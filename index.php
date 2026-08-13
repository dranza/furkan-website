<?php
declare(strict_types=1);

require_once __DIR__ . '/app/core/Bootstrap.php';
$noindex = false; // default

$cfg = app_config();
if (!$cfg) {
  // Not installed
  if (strpos($_SERVER['REQUEST_URI'] ?? '', '/install') !== 0) {
    header('Location: /install/');
    exit;
  }
}

date_default_timezone_set(($cfg['app']['timezone'] ?? 'Europe/Istanbul'));

require_once __DIR__ . '/app/models/Settings.php';
$debugMode = (Settings::get('debug_mode','0') ?? '0') === '1';
if ($debugMode) {
  @ini_set('display_errors', '1');
  error_reporting(E_ALL);
} else {
  @ini_set('display_errors', '0');
}


require_once __DIR__ . '/app/models/About.php';
require_once __DIR__ . '/app/models/Blog.php';
require_once __DIR__ . '/app/models/HomeBlock.php';
require_once __DIR__ . '/app/models/Projects.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/models/Ticket.php';
require_once __DIR__ . '/app/models/Skill.php';
require_once __DIR__ . '/app/models/Certification.php';
require_once __DIR__ . '/app/models/Download.php';
require_once __DIR__ . '/app/models/Timeline.php';
require_once __DIR__ . '/app/models/Sitemap.php';
require_once __DIR__ . '/app/models/Comments.php';
require_once __DIR__ . '/app/models/Contact.php';
require_once __DIR__ . '/app/models/Analytics.php';
require_once __DIR__ . '/app/models/Offer.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// ---- Local analytics (KVKK-friendly, no external services) ----
try {
  // log even when page cache hits
  Analytics::logView($uri);
} catch (Throwable $t) {}

// ===== PAGE_CACHE (simple file cache for guests) =====
$cacheEnabled = ((Settings::get('page_cache_enabled','1') ?: '1') === '1');
$cacheTtl = (int)(Settings::get('page_cache_ttl','300') ?? '300'); // seconds
$cacheDir = __DIR__ . '/storage/cache';
if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }

$canCache = $cacheEnabled
  && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
  && empty($_GET)
  && !(strpos($uri, '/admin') === 0)
  && empty($_SESSION['user_id'])
  && empty($_SESSION['admin_id']);

$cacheKey = '';
$cacheFile = '';
if ($canCache) {
  $cacheKey = md5($uri);
  $cacheFile = $cacheDir . '/' . $cacheKey . '.html';
  if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    readfile($cacheFile);
    exit;
  }
}

function render_cached(string $view, array $vars=[]): void {
  global $canCache, $cacheFile;
  ob_start();
  render($view, $vars);
  $html = ob_get_clean();
  if ($canCache && $cacheFile) { @file_put_contents($cacheFile, $html); }
  echo $html;
}
// ================================================

$uri = rtrim($uri, '/');
if ($uri === '') $uri = '/';

// SEO: sitemap auto update (30 dk throttle)
try { Sitemap::maybeAutoGenerate(); } catch (Throwable $t) {}

function render(string $view, array $vars=[]): void {
  extract($vars);
  require __DIR__ . '/views/partials/head.php';
  require __DIR__ . '/views/' . $view . '.php';
  require __DIR__ . '/views/partials/foot.php';
}

if ($uri === '/' ) {
  $metaTitle = Settings::get('home_title', Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $metaDesc  = Settings::get('home_desc', Settings::get('site_description','') ?? '');
$canonical = base_url('/');
$ogType = 'website';
$profilePhoto = Settings::get('profile_photo','') ?? '';
$ogImage = $profilePhoto ? base_url($profilePhoto) : null;
$jsonLd = [
  [
    "@context"=>"https://schema.org",
    "@type"=>"WebSite",
    "name"=> (string)Settings::get('site_name','Furkan Cihan'),
    "url"=> base_url('/'),
    "potentialAction"=>[
      "@type"=>"SearchAction",
      "target"=> base_url('/blog') . "?q={search_term_string}",
      "query-input"=>"required name=search_term_string"
    ]
  ],
  [
    "@context"=>"https://schema.org",
    "@type"=>"Person",
    "name"=> (string)Settings::get('site_name','Furkan Cihan'),
    "jobTitle"=> (string)Settings::get('hero_title','Bilgi Sistemleri Uzmanı'),
    "url"=> base_url('/'),
    "image"=> $ogImage,
    "sameAs"=> array_values(array_filter([
      Settings::get('social_linkedin','') ?? '',
      Settings::get('social_github','') ?? ''
    ]))
  ]
];
  $blogs = Blog::latest(6);
  $featuredProjects = Projects::featured(6);
  $projects = Projects::latest(6);

  $homeServices = HomeBlock::bySection('services');
  $homeStats = HomeBlock::bySection('stats');
  $homeProcess = HomeBlock::bySection('process');
  $homeTech = HomeBlock::bySection('tech');

  render_cached('home', compact('blogs','projects','featuredProjects','homeServices','homeStats','homeProcess','homeTech','metaTitle','metaDesc','canonical','ogType','ogImage','jsonLd','noindex'));
  exit;
}

if ($uri === '/hakkimda') {
  $metaTitle = 'Hakkımda - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $metaDesc = Settings::get('about_desc', Settings::get('site_description','') ?? '');
  $aboutText = About::getText();
  $education = Timeline::educations();
  $skills = Skill::all();
  $certs = Certification::all();
$canonical = base_url('hakkimda');
$ogType = 'profile';
$ogImage = (Settings::get('profile_photo','') ? base_url(Settings::get('profile_photo','')) : null);
$jsonLd = [
  [
    "@context"=>"https://schema.org",
    "@type"=>"BreadcrumbList",
    "itemListElement"=>[
      ["@type"=>"ListItem","position"=>1,"name"=>"Anasayfa","item"=>base_url('/')],
      ["@type"=>"ListItem","position"=>2,"name"=>"Hakkımda","item"=>base_url('hakkimda')]
    ]
  ]
];

  $experience = Timeline::experiences();
  render_cached('about', compact('aboutText','education','experience','skills','certs','metaTitle','metaDesc','canonical','ogType','ogImage','jsonLd','noindex'));
  exit;
}


if ($uri === '/giris') {
  $metaTitle = 'Giriş - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $canonical = base_url('giris');
  $ogType = 'website';
  $formMsg = null; $formType = 'info';
  $noindex = true;

  UserAuth::start();
  if (UserAuth::check()) redirect(base_url('profil'));

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::checkOrExit($_POST['_csrf'] ?? null);
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    if (UserAuth::login($u, $p)) {
      redirect(base_url('profil'));
    } else {
      $formMsg = 'Giriş başarısız (hesap onaylı olmayabilir).';
      $formType = 'danger';
    }
  }

  render('user_login', compact('metaTitle','canonical','ogType','formMsg','formType','noindex'));
  exit;
}

if ($uri === '/kayit') {
  $metaTitle = 'Kayıt - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $canonical = base_url('kayit');
  $ogType = 'website';
  $formMsg = null; $formType = 'info';
  $noindex = true;

  if (!User::registrationEnabled()) {
    $formMsg = 'Kayıt şu an kapalı.';
    $formType = 'warning';
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && User::registrationEnabled()) {
    CSRF::checkOrExit($_POST['_csrf'] ?? null);
    $r = User::create(
      (string)($_POST['username'] ?? ''),
      (string)($_POST['email'] ?? ''),
      (string)($_POST['password'] ?? ''),
      (string)($_POST['display_name'] ?? '')
    );
    if (!empty($r['ok'])) {
      if (!empty($r['active'])) {
        $formMsg = 'Kayıt tamamlandı. Giriş yapabilirsiniz.';
        $formType = 'success';
      } else {
        $formMsg = 'Kayıt alındı. Admin onayından sonra giriş yapabilirsiniz.';
        $formType = 'success';
      }
    } else {
      $formMsg = (string)($r['error'] ?? 'Kayıt başarısız.');
      $formType = 'danger';
    }
  }

  render('user_register', compact('metaTitle','canonical','ogType','formMsg','formType','noindex'));
  exit;
}

if ($uri === '/profil') {
  UserAuth::start();
  if (!UserAuth::check()) redirect(base_url('giris'));

  $metaTitle = 'Profil - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $canonical = base_url('profil');
  $ogType = 'website';
  $noindex = true;

  $myComments = Comments::listForUser(UserAuth::id(), 50);
  // Profile ticket summary counts
  $ticketCounts = Ticket::countsForUser(UserAuth::id());

  $formMsg = null; $formType='info';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::checkOrExit($_POST['_csrf'] ?? null);
    $r = User::updateProfile(UserAuth::id(), (string)($_POST['display_name'] ?? ''), (string)($_POST['new_password'] ?? ''));
    if (!empty($r['ok'])) { $formMsg='Profil güncellendi.'; $formType='success'; $_SESSION['user_name'] = (string)($_POST['display_name'] ?? $_SESSION['user_name']); }
    else { $formMsg=(string)($r['error'] ?? 'Güncellenemedi.'); $formType='danger'; }
  }

  // User-specific page: do NOT cache
  render('user_profile', compact('metaTitle','canonical','ogType','formMsg','formType','noindex','myComments','ticketCounts'));
  exit;
}

if ($uri === '/cikis') {
  UserAuth::logout();
  redirect(base_url('/'));
}


if ($uri === '/blog') {
  $page = (int)($_GET['page'] ?? 1);
  $filters = [
    'category' => trim((string)($_GET['cat'] ?? '')),
    'tag' => trim((string)($_GET['tag'] ?? '')),
    'q' => trim((string)($_GET['q'] ?? '')),
  ];
  $filters = array_filter($filters, fn($v) => $v !== '');
  $data = Blog::list(max(1,$page), 10, $filters);
  $categories = Blog::categories();
  $topTags = Blog::topTags(18);
  $metaTitle = 'Blog - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $metaDesc = Settings::get('blog_desc', Settings::get('site_description','') ?? '');
  $canonical = base_url('blog');
  $noindex = !empty($filters);
  $ogType = 'website';
  $ogImage = null;
  $jsonLd = [
    "@context"=>"https://schema.org",
    "@type"=>"BreadcrumbList",
    "itemListElement"=>[
      ["@type"=>"ListItem","position"=>1,"name"=>"Anasayfa","item"=>base_url('/')],
      ["@type"=>"ListItem","position"=>2,"name"=>"Blog","item"=>base_url('blog')]
    ]
  ];

  render_cached('blog_list', ['data'=>$data,'categories'=>$categories,'topTags'=>$topTags,'filters'=>$filters,'metaTitle'=>$metaTitle,'metaDesc'=>$metaDesc,'canonical'=>$canonical,'ogType'=>$ogType,'ogImage'=>$ogImage,'jsonLd'=>$jsonLd,'noindex'=>$noindex]);
  exit;
}


if (preg_match('#^/blog/([a-z0-9\-]+)$#', $uri, $m)) {
  $post = Blog::bySlug($m[1]);
  // Yorum gönderimi
  if ($post && $_SERVER['REQUEST_METHOD'] === 'POST' && Comments::enabled()) {
    try { CSRF::check($_POST['_csrf'] ?? null); } catch (Throwable $t) { /* ignore */ }
    // CSRF (public): oturum kaybı olursa engellemesin
    if (!empty($_POST['_csrf']) && !CSRF::check($_POST['_csrf'])) {
      // token geçersizse devam etme yerine sessizce yenile
      // (yorumlar moderasyon + rate limit ile korunuyor)
    }
    $requireLogin = (Settings::get('comments_require_login','0') ?? '0') === '1';
UserAuth::start();
$uid = UserAuth::check() ? UserAuth::id() : null;
$name = UserAuth::check() ? UserAuth::name() : (string)($_POST['name'] ?? '');
$email = UserAuth::check() ? '' : (string)($_POST['email'] ?? '');

if ($requireLogin && !$uid) {
  $_SESSION['comment_err'] = 'Yorum yapmak için giriş yapmalısınız.';
  header('Location: ' . base_url('blog/' . $post['slug']) . '?comment=err#yorumlar');
  exit;
}

$r = Comments::submit((int)$post['id'], $name, $email, (string)($_POST['content'] ?? ''), (string)($_POST['company'] ?? ''), $uid);
    if ($r['ok']) {
      $q = ($r['status'] === 'pending') ? 'pending' : 'ok';
      header('Location: ' . base_url('blog/' . $post['slug']) . '?comment=' . $q . '#yorumlar');
      exit;
    } else {
      $_SESSION['comment_error'] = (string)($r['error'] ?? 'Hata');
      header('Location: ' . base_url('blog/' . $post['slug']) . '?comment=err#yorumlar');
      exit;
    }
  }

  if (!$post) { http_response_code(404); render('404', ['metaTitle'=>'404 - Sayfa Bulunamadı']); exit; }
  $metaTitle = ($post['meta_title'] ?: $post['title']) . ' - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $metaDesc = $post['meta_desc'] ?: Settings::get('site_description','') ?? '';
  $canonical = base_url('blog/' . $post['slug']);
  $ogType = 'article';
  $ogImage = !empty($post['cover_image']) ? base_url($post['cover_image']) : (Settings::get('profile_photo','') ? base_url(Settings::get('profile_photo','')) : null);
  $jsonLd = [
    [
      "@context"=>"https://schema.org",
          "@type"=>"BlogPosting",
          "headline"=>(string)$post['title'],
          "description"=>(string)$metaDesc,
          "datePublished"=> date('c', strtotime((string)$post['published_at'])),
          "dateModified"=> $post['updated_at'] ? date('c', strtotime((string)$post['updated_at'])) : date('c', strtotime((string)$post['published_at'])),
          "mainEntityOfPage"=> ["@type"=>"WebPage","@id"=>$canonical],
          "author"=> ["@type"=>"Person","name"=>(string)Settings::get('site_name','Furkan Cihan'),"url"=>base_url('/')],
          "publisher"=> ["@type"=>"Organization","name"=>(string)Settings::get('site_name','Furkan Cihan')],
          "image"=> $ogImage,
          "keywords"=> (string)($post['tags'] ?? '')
    ],
    [
      "@context"=>"https://schema.org",
      "@type"=>"BreadcrumbList",
      "itemListElement"=>[
        ["@type"=>"ListItem","position"=>1,"name"=>"Anasayfa","item"=>base_url('/')],
        ["@type"=>"ListItem","position"=>2,"name"=>"Blog","item"=>base_url('blog')],
        ["@type"=>"ListItem","position"=>3,"name"=>(string)$post['title'],"item"=>$canonical]
      ]
    ]
  ];
  render_cached('blog_detail', compact('post','metaTitle','metaDesc','canonical','ogType','ogImage','jsonLd','noindex'));
  exit;
}

if ($uri === '/projeler') {
  $page = (int)($_GET['page'] ?? 1);
  $filters = [
    'tech' => trim((string)($_GET['tech'] ?? '')),
    'q' => trim((string)($_GET['q'] ?? '')),
  ];
  $filters = array_filter($filters, fn($v) => $v !== '');
  $data = Projects::list(max(1,$page), 10, $filters);
  $topTech = Projects::topTechnologies(18);
  $metaTitle = 'Projeler - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $metaDesc = Settings::get('projects_desc', Settings::get('site_description','') ?? '');
  $canonical = base_url('projeler');
  $noindex = !empty($filters);
  $ogType = 'website';
  $ogImage = null;
  $jsonLd = [
    "@context"=>"https://schema.org",
    "@type"=>"BreadcrumbList",
    "itemListElement"=>[
      ["@type"=>"ListItem","position"=>1,"name"=>"Anasayfa","item"=>base_url('/')],
      ["@type"=>"ListItem","position"=>2,"name"=>"Projeler","item"=>base_url('projeler')]
    ]
  ];

  render('project_list', ['data'=>$data,'topTech'=>$topTech,'filters'=>$filters,'metaTitle'=>$metaTitle,'metaDesc'=>$metaDesc,'canonical'=>$canonical,'ogType'=>$ogType,'ogImage'=>$ogImage,'jsonLd'=>$jsonLd,'noindex'=>$noindex]);
  exit;
}


if (preg_match('#^/proje/([a-z0-9\-]+)$#', $uri, $m)) {
  $project = Projects::bySlug($m[1]);
  if (!$project) { http_response_code(404); render('404', ['metaTitle'=>'404 - Sayfa Bulunamadı']); exit; }
  $metaTitle = ($project['meta_title'] ?: $project['title']) . ' - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $metaDesc = $project['meta_desc'] ?: Settings::get('site_description','') ?? '';
  $canonical = base_url('proje/' . $project['slug']);
  $ogType = 'article';
  $ogImage = !empty($project['cover_image']) ? base_url($project['cover_image']) : (Settings::get('profile_photo','') ? base_url(Settings::get('profile_photo','')) : null);
  $jsonLd = [
    [
      "@context"=>"https://schema.org",
          "@type"=>"CreativeWork",
          "name"=>(string)$project['title'],
          "description"=>(string)$metaDesc,
          "datePublished"=> date('c', strtotime((string)$project['published_at'])),
          "dateModified"=> $project['updated_at'] ? date('c', strtotime((string)$project['updated_at'])) : date('c', strtotime((string)$project['published_at'])),
          "url"=> $canonical,
          "image"=> $ogImage,
          "keywords"=> (string)($project['technologies'] ?? ''),
          "author"=> ["@type"=>"Person","name"=>(string)Settings::get('site_name','Furkan Cihan'),"url"=>base_url('/')]
    ],
    [
      "@context"=>"https://schema.org",
      "@type"=>"BreadcrumbList",
      "itemListElement"=>[
        ["@type"=>"ListItem","position"=>1,"name"=>"Anasayfa","item"=>base_url('/')],
        ["@type"=>"ListItem","position"=>2,"name"=>"Projeler","item"=>base_url('projeler')],
        ["@type"=>"ListItem","position"=>3,"name"=>(string)$project['title'],"item"=>$canonical]
      ]
    ]
  ];
  render_cached('project_detail', compact('project','metaTitle','metaDesc','canonical','ogType','ogImage','jsonLd','noindex'));
  exit;
}

// Dokümanlar (indirilebilir dosyalar)
if ($uri === '/dokumanlar') {
  require_once __DIR__ . '/app/models/Download.php';
  $q = trim((string)($_GET['q'] ?? ''));
  $cat = trim((string)($_GET['cat'] ?? ''));
  $items = Download::searchPublic($q, $cat, 50);
  $cats = Download::categories();
  $top = Download::top(8);
  $pageTitle = 'Dokümanlar';
  $pageDesc = 'Paylaştığım dokümanlar, araçlar ve indirilebilir içerikler.';
  render('downloads', compact('items','cats','top','q','cat','pageTitle','pageDesc'));
  exit;
}
if (preg_match('~^/dokumanlar/([a-z0-9\-]+)$~', $uri, $mm)) {
  require_once __DIR__ . '/app/models/Download.php';
  $slug = $mm[1];
  $item = Download::bySlug($slug);
  if (!$item || (int)$item['is_public'] !== 1) { http_response_code(404); render('404'); exit; }
  Download::incrementViews((int)$item['id']);
  $pageTitle = (string)$item['title'];
  $pageDesc = (string)($item['description'] ?? '');
  render('download_detail', compact('item','pageTitle','pageDesc'));
  exit;
}
if (preg_match('~^/indir/([a-z0-9\-]+)$~', $uri, $mm)) {
  require_once __DIR__ . '/app/models/Download.php';
  $slug = $mm[1];
  $item = Download::bySlug($slug);
  if (!$item || (int)$item['is_public'] !== 1) { http_response_code(404); render('404'); exit; }

  $rel = (string)$item['file_path'];
  $path = __DIR__ . '/' . ltrim($rel, '/');
  if (!is_file($path)) { http_response_code(404); render('404'); exit; }

  Download::incrementDownloads((int)$item['id']);

  $downloadName = (string)($item['original_name'] ?: ($item['slug'] . '.' . pathinfo($rel, PATHINFO_EXTENSION)));
  $mime = (string)($item['mime'] ?: 'application/octet-stream');

  header('Content-Description: File Transfer');
  header('Content-Type: ' . $mime);
  header('Content-Disposition: attachment; filename="' . str_replace('"','', $downloadName) . '"');
  header('Content-Length: ' . filesize($path));
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: private, max-age=0, must-revalidate');
  header('Pragma: public');
  readfile($path);
  exit;
}

// Araçlar
if ($uri === '/araclar') {
  $pageTitle = 'Araçlar';
  $pageDesc = 'Ücretsiz online araçlar: PDF küçültme ve daha fazlası.';
  render('tools_index', compact('pageTitle','pageDesc'));
  exit;
}
if ($uri === '/araclar/pdf-kucultme') {
  require __DIR__ . '/tools/pdf-kucultme.php';
  exit;
}

if ($uri === '/araclar/pdf-birlestirme') {
  require __DIR__ . '/tools/pdf-birlestirme.php';
  exit;
}

if ($uri === '/araclar/word-pdf') {
  require __DIR__ . '/tools/word-pdf.php';
  exit;
}

if ($uri === '/araclar/jpg-pdf') {
  require __DIR__ . '/tools/jpg-pdf.php';
  exit;
}


if ($uri === '/araclar/pdf-bol') {
  require __DIR__ . '/tools/pdf-bol.php';
  exit;
}

if ($uri === '/araclar/pdf-sayfa-sil') {
  require __DIR__ . '/tools/pdf-sayfa-sil.php';
  exit;
}

if ($uri === '/araclar/pdf-dondur') {
  require __DIR__ . '/tools/pdf-dondur.php';
  exit;
}

if ($uri === '/araclar/pdf-filigran') {
  require __DIR__ . '/tools/pdf-filigran.php';
  exit;
}

if ($uri === '/araclar/resim-sikistir') {
  require __DIR__ . '/tools/resim-sikistir.php';
  exit;
}

// Yeni araçlar (PDF Pro)
if ($uri === '/araclar/pdf-sayfa-cikar') {
  require __DIR__ . '/tools/pdf-sayfa-cikar.php';
  exit;
}

if ($uri === '/araclar/pdf-sayfa-numarasi') {
  require __DIR__ . '/tools/pdf-sayfa-numarasi.php';
  exit;
}

if ($uri === '/araclar/pdf-metadata') {
  require __DIR__ . '/tools/pdf-metadata.php';
  exit;
}

if ($uri === '/araclar/pdf-imza') {
  require __DIR__ . '/tools/pdf-imza.php';
  exit;
}

// Teklif Oluştur (Herkese Açık)
if ($uri === '/araclar/teklif-olustur') {
  require __DIR__ . '/tools/teklif-public.php';
  exit;
}

// Eski firma teklif paneli route'ları kapatıldı (üyelik zorunlu değil)
if ($uri === '/araclar/teklif-olustur/yeni' || $uri === '/araclar/teklif-olustur/duzenle') {
  redirect(base_url('araclar/teklif-olustur'));
}

// Eski public offer view route'u kapatıldı (anonim teklif DB kaydı yok)
if (preg_match('#^/teklif/([A-Za-z0-9]{8,20})$#', $uri, $m)) {
  http_response_code(404);
  echo "Teklif bulunamadı.";
  exit;
}


if ($uri === '/destek') {
  UserAuth::start();
  if (!UserAuth::check()) redirect(base_url('giris'));

  if (((Settings::get('tickets_enabled','1') ?: '1') !== '1')) {
    http_response_code(404); echo "Destek sistemi kapalı."; exit;
  }

  $metaTitle = 'Destek Talepleri - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $canonical = base_url('destek');
  $ogType = 'website';
  $noindex = true;

  $statusFilter = (string)($_GET['status'] ?? 'all');
  $q = trim((string)($_GET['q'] ?? ''));
  $page = max(1, (int)($_GET['page'] ?? 1));
  $perPage = 12;

  // Flash messages (shown as toast in footer)
  $flashSuccess = $_SESSION['flash_success'] ?? '';
  $flashErr = $_SESSION['flash_err'] ?? '';
  unset($_SESSION['flash_success'], $_SESSION['flash_err']);

  $formMsg = null; $formType='info';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::checkOrExit($_POST['_csrf'] ?? null);
    $r = Ticket::create(
      UserAuth::id(),
      (string)($_POST['subject'] ?? ''),
      (string)($_POST['category'] ?? 'diger'),
      (string)($_POST['priority'] ?? 'normal'),
      (string)($_POST['message'] ?? '')
    );
    if (!empty($r['ok'])) {
      $_SESSION['flash_success'] = 'Ticket oluşturuldu.';
      redirect(base_url('destek/' . (int)$r['id']));
    } else {
      $formMsg = (string)($r['error'] ?? 'Ticket oluşturulamadı.');
      $formType = 'danger';
    }
  }

  $counts = Ticket::countsForUser(UserAuth::id());
  $paged = Ticket::listForUserPaged(UserAuth::id(), $page, $perPage, $statusFilter, $q);
  $tickets = $paged['items'] ?? [];
  $total = (int)($paged['total'] ?? 0);
  $pages = (int)ceil($total / $perPage);

  // Kullanıcıya özel sayfa: cache'lenirse sayaçlar ve admin mesajları güncel görünmez.
  render('tickets', compact('metaTitle','canonical','ogType','tickets','counts','statusFilter','formMsg','formType','noindex','q','page','pages','total','flashSuccess','flashErr'));
  exit;
}


if (preg_match("~^/destek/(\d+)$~", $uri, $mm)) {
  UserAuth::start();
  if (!UserAuth::check()) redirect(base_url('giris'));

  if (((Settings::get('tickets_enabled','1') ?: '1') !== '1')) {
    http_response_code(404); echo "Destek sistemi kapalı."; exit;
  }

  $ticketId = (int)$mm[1];
  $ticket = Ticket::getForUser($ticketId, UserAuth::id());
  if (!$ticket) { http_response_code(404); echo "Ticket bulunamadı."; exit; }

  $metaTitle = 'Ticket #' . $ticketId . ' - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $canonical = base_url('destek/' . $ticketId);
  $ogType = 'website';
  $noindex = true;

  // Flash messages (shown as toast in footer)
  $flashSuccess = $_SESSION['flash_success'] ?? '';
  $flashErr = $_SESSION['flash_err'] ?? '';
  unset($_SESSION['flash_success'], $_SESSION['flash_err']);

  $formMsg=null; $formType='info';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::checkOrExit($_POST['_csrf'] ?? null);

    $action = (string)($_POST['action'] ?? 'reply');

    if ($action === 'close') {
      $r = Ticket::userSetStatus($ticketId, UserAuth::id(), 'closed');
      if (!empty($r['ok'])) { $_SESSION['flash_success'] = 'Ticket kapatıldı.'; redirect(base_url('destek/' . $ticketId)); }
      $formMsg = (string)($r['error'] ?? 'İşlem başarısız.');
      $formType = 'danger';
    }
    elseif ($action === 'reopen') {
      $r = Ticket::userSetStatus($ticketId, UserAuth::id(), 'open');
      if (!empty($r['ok'])) { $_SESSION['flash_success'] = 'Ticket tekrar açıldı.'; redirect(base_url('destek/' . $ticketId)); }
      $formMsg = (string)($r['error'] ?? 'İşlem başarısız.');
      $formType = 'danger';
    }
    else {
      $r = Ticket::userReply($ticketId, UserAuth::id(), (string)($_POST['reply'] ?? ''));
      if (!empty($r['ok'])) {
        $_SESSION['flash_success'] = 'Yanıt gönderildi.';
        redirect(base_url('destek/' . $ticketId . '#son'));
      } else {
        $formMsg = (string)($r['error'] ?? 'Yanıt gönderilemedi.');
        $formType = 'danger';
      }
    }
  }

  // Refresh ticket after status change
  $ticket = Ticket::getForUser($ticketId, UserAuth::id());
  $messages = Ticket::messages($ticketId);

  // Admin mesajlarında görünen avatar (üst menüdeki profil fotoğrafı ile aynı)
  $adminPhoto = (string)(Settings::get('profile_photo','') ?? '');
  // Kullanıcıya özel sayfa: cache'lenirse yeni mesajlar görünmez.
  render('ticket_detail', compact('metaTitle','canonical','ogType','ticket','messages','formMsg','formType','noindex','adminPhoto','flashSuccess','flashErr'));
  exit;
}

if ($uri === '/iletisim') {
  $metaTitle = 'İletişim - ' . (Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan');
  $metaDesc = Settings::get('contact_desc', Settings::get('site_description','') ?? '');
  $email = Settings::get('contact_email','') ?? '';
  $phone = Settings::get('contact_phone','') ?? '';
  $location = Settings::get('contact_location','') ?? '';

  // UI extras
  $hours = Settings::get('contact_hours','Hafta içi 09:00 - 18:00') ?? 'Hafta içi 09:00 - 18:00';
  $availability = Settings::get('contact_availability','available') ?? 'available'; // available|busy|offline
  $mapEmbed = Settings::get('contact_map_embed','') ?? '';

  $canonical = base_url('iletisim');
  $ogType = 'website';
  $ogImage = (Settings::get('profile_photo','') ? base_url(Settings::get('profile_photo','')) : null);
  $jsonLd = [
    [
      "@context"=>"https://schema.org",
      "@type"=>"BreadcrumbList",
      "itemListElement"=>[
        ["@type"=>"ListItem","position"=>1,"name"=>"Anasayfa","item"=>base_url('/')],
        ["@type"=>"ListItem","position"=>2,"name"=>"İletişim","item"=>base_url('iletisim')]
      ]
    ]
  ];

  $formMsg = null;
  $formType = 'info';
  $contactEnabled = class_exists('Contact') && Contact::enabled();

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && $contactEnabled) {
    $t = $_POST['_csrf'] ?? null;
    if ($t && !CSRF::check((string)$t)) {
      $formMsg = 'Form süresi doldu. Sayfayı yenileyip tekrar deneyin.';
      $formType = 'danger';
    } else {
      $topics = $_POST['topics'] ?? [];
      if (!is_array($topics)) $topics = [];
      $topics = array_map('trim', array_filter($topics));
      $companyName = trim((string)($_POST['company_name'] ?? ''));
      $senderPhone = trim((string)($_POST['sender_phone'] ?? ''));
      $msg = (string)($_POST['message'] ?? '');

      $prefix = [];
      if ($companyName) $prefix[] = "Kurum: ".$companyName;
      if ($senderPhone) $prefix[] = "Telefon: ".$senderPhone;
      if ($topics) $prefix[] = "Konular: ".implode(', ', $topics);
      if ($prefix) $msg = implode("\n", $prefix) . "\n\n" . $msg;

      $r = Contact::submit(
        (string)($_POST['name'] ?? ''),
        (string)($_POST['email'] ?? ''),
        $msg,
        (string)($_POST['website'] ?? '') // honeypot
      );

      if (!empty($r['ok'])) { $formMsg = 'Mesajınız alındı. Teşekkürler!'; $formType = 'success'; }
      else { $formMsg = (string)($r['error'] ?? 'Gönderilemedi.'); $formType = 'danger'; }
    }
  }

  render_cached('contact', compact('email','phone','location','hours','availability','mapEmbed','contactEnabled','metaTitle','metaDesc','canonical','ogType','ogImage','jsonLd','noindex','formMsg','formType'));
  exit;
}



if ($uri === '/sitemap.xml') {
  require __DIR__ . '/sitemap.php';
  exit;
}

http_response_code(404);
render('404', ['metaTitle'=>'404 - Sayfa Bulunamadı', 'metaDesc'=>'']);
