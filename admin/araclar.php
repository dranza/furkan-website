<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/core/CSRF.php';
require_once __DIR__ . '/../app/models/Tool.php';

$flash = null;
$error = null;

// Ensure registry exists (robust: don't white-screen on DB privilege issues)
try {
  Tool::ensure('pdf_compress', 'PDF Küçültme');
  Tool::ensure('pdf_merge', 'PDF Birleştirme');
  Tool::ensure('word_pdf', 'Word → PDF');
  Tool::ensure('jpg_pdf', 'JPG → PDF');
  Tool::ensure('pdf_split', 'PDF Böl');
  Tool::ensure('pdf_delete_pages', 'PDF Sayfa Sil');
  Tool::ensure('pdf_rotate', 'PDF Sayfa Döndür');
  Tool::ensure('pdf_watermark', 'PDF Filigran');
  Tool::ensure('img_compress', 'Resim Sıkıştır / Dönüştür');
  Tool::ensure('pdf_extract', 'PDF Sayfa Çıkar');
  Tool::ensure('pdf_pagenum', 'PDF Sayfa Numarası');
  Tool::ensure('pdf_metadata', 'PDF Metadata');
  Tool::ensure('pdf_sign', 'PDF İmza Ekle');
  Tool::ensure('word_pdf', 'Word → PDF');
  Tool::ensure('jpg_pdf', 'JPG → PDF');
} catch (Throwable $t) {
  $error = 'Araç altyapısı oluşturulamadı (veritabanı hatası olabilir): ' . $t->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $action = (string)($_POST['action'] ?? '');
  $slug = (string)($_POST['tool_slug'] ?? 'pdf_compress');

  $allowedSlugs = ['pdf_compress','pdf_merge','word_pdf','jpg_pdf','pdf_split','pdf_delete_pages','pdf_rotate','pdf_watermark','img_compress','pdf_extract','pdf_pagenum','pdf_metadata','pdf_sign'];
  if (!in_array($slug, $allowedSlugs, true)) $slug = 'pdf_compress';

  try {
    if ($action === 'save_tool') {
      $status = (string)($_POST['status'] ?? 'active');
      $maintMsg = trim((string)($_POST['maintenance_message'] ?? ''));
      Tool::setStatus($slug, $status, $maintMsg);

      // Purge cached public pages
      $purge = ['/araclar'];
      if ($slug === 'pdf_compress') $purge[] = '/araclar/pdf-kucultme';
      if ($slug === 'pdf_merge') $purge[] = '/araclar/pdf-birlestirme';
      if ($slug === 'word_pdf') $purge[] = '/araclar/word-pdf';
      if ($slug === 'jpg_pdf') $purge[] = '/araclar/jpg-pdf';
      if ($slug === 'pdf_split') $purge[] = '/araclar/pdf-bol';
      if ($slug === 'pdf_delete_pages') $purge[] = '/araclar/pdf-sayfa-sil';
      if ($slug === 'pdf_rotate') $purge[] = '/araclar/pdf-dondur';
      if ($slug === 'pdf_watermark') $purge[] = '/araclar/pdf-filigran';
      if ($slug === 'img_compress') $purge[] = '/araclar/resim-sikistir';
      if ($slug === 'pdf_extract') $purge[] = '/araclar/pdf-sayfa-cikar';
      if ($slug === 'pdf_pagenum') $purge[] = '/araclar/pdf-sayfa-numarasi';
      if ($slug === 'pdf_metadata') $purge[] = '/araclar/pdf-metadata';
      if ($slug === 'pdf_sign') $purge[] = '/araclar/pdf-imza';
      if ($slug === 'word_pdf') $purge[] = '/araclar/word-pdf';
      if ($slug === 'jpg_pdf') $purge[] = '/araclar/jpg-pdf';

      if (function_exists('purge_pages')) {
        purge_pages($purge);
      } elseif (function_exists('purge_page_cache')) {
        foreach ($purge as $p) purge_page_cache($p);
      }

      $flash = 'Ayarlar güncellendi.';
    } elseif ($action === 'reset_stats') {
      Tool::resetUsage($slug);
      $flash = 'Kullanım istatistikleri sıfırlandı.';
    }
  } catch (Throwable $t) {
    $error = 'İşlem yapılamadı: ' . $t->getMessage();
  }
}

function tool_block(string $slug, string $title, string $publicUrl, ?string $error=null): array {
  $tool = ['slug'=>$slug,'name'=>$title,'status'=>'active','maintenance_message'=>''];
  $daily = [];
  $totals = ['total_uses'=>0,'today'=>0];

  try {
    $tool = Tool::get($slug) ?: $tool;
    if (($tool['status'] ?? '') === 'disabled' && trim((string)($tool['maintenance_message'] ?? '')) !== '') {
      $tool['status'] = 'maintenance';
    }

    $today = (new DateTime('now'))->format('Y-m-d');
    $from = (string)($_GET['from_'.$slug] ?? (new DateTime('-14 days'))->format('Y-m-d'));
    $to   = (string)($_GET['to_'.$slug] ?? $today);

    $daily = Tool::usageDaily($slug, $from, $to);
    $totals = Tool::usageTotals($slug) ?: $totals;

  } catch (Throwable $t) {
    // keep local, don't overwrite page-level error
  }

  return compact('tool','daily','totals','title','publicUrl');
}

$block1 = tool_block('pdf_compress','PDF Küçültme', base_url('araclar/pdf-kucultme'), $error);
$block2 = tool_block('pdf_merge','PDF Birleştirme', base_url('araclar/pdf-birlestirme'), $error);
$block3 = tool_block('word_pdf','Word → PDF', base_url('araclar/word-pdf'), $error);
$block4 = tool_block('jpg_pdf','JPG → PDF', base_url('araclar/jpg-pdf'), $error);
$block5 = tool_block('pdf_split','PDF Böl', base_url('araclar/pdf-bol'), $error);
$block6 = tool_block('pdf_delete_pages','PDF Sayfa Sil', base_url('araclar/pdf-sayfa-sil'), $error);
$block7 = tool_block('pdf_rotate','PDF Sayfa Döndür', base_url('araclar/pdf-dondur'), $error);
$block8 = tool_block('pdf_watermark','PDF Filigran', base_url('araclar/pdf-filigran'), $error);
$block9 = tool_block('img_compress','Resim Sıkıştır / Dönüştür', base_url('araclar/resim-sikistir'), $error);
$block10 = tool_block('pdf_extract','PDF Sayfa Çıkar', base_url('araclar/pdf-sayfa-cikar'), $error);
$block11 = tool_block('pdf_pagenum','PDF Sayfa Numarası', base_url('araclar/pdf-sayfa-numarasi'), $error);
$block12 = tool_block('pdf_metadata','PDF Metadata', base_url('araclar/pdf-metadata'), $error);
$block13 = tool_block('pdf_sign','PDF İmza Ekle', base_url('araclar/pdf-imza'), $error);

?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h3 mb-1" style="color:#e5e7eb;">Araçlar</h1>
    <div class="text-muted">Site içi araçları yönet, mod belirle ve günlük kullanım istatistiklerini gör.</div>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert alert-success"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php
$blocks = [
  $block1,
  $block2,
  $block3,
  $block4,
  $block5,
  $block6,
  $block7,
  $block8,
  $block9,
  $block10,
  $block11,
  $block12,
  $block13,
];
?>

<div class="row g-4">
  <?php foreach ($blocks as $b):
    $tool = $b['tool'];
    $daily = $b['daily'];
    $totals = $b['totals'];
    $title = $b['title'];
    $publicUrl = $b['publicUrl'];
    $slug = (string)($tool['slug'] ?? '');
    $st = (string)($tool['status'] ?? 'active');
    $from = (string)($_GET['from_'.$slug] ?? (new DateTime('-14 days'))->format('Y-m-d'));
    $to = (string)($_GET['to_'.$slug] ?? (new DateTime('now'))->format('Y-m-d'));
  ?>

  <div class="col-lg-6">
    <div class="card" style="background:rgba(17,24,39,.55);border:1px solid rgba(148,163,184,.18);">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="small text-muted">Araç</div>
            <div class="h5 mb-0" style="color:#e5e7eb;"><?= e($title) ?></div>
          </div>
          <a class="btn btn-outline-light btn-sm" target="_blank" href="<?= e($publicUrl) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Aracı Aç</a>
        </div>

        <hr style="border-color:rgba(148,163,184,.2);">

        <form method="post" class="vstack gap-3">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <input type="hidden" name="action" value="save_tool">
          <input type="hidden" name="tool_slug" value="<?= e($slug) ?>">

          <div>
            <label class="form-label text-muted">Durum</label>
            <select class="form-select" name="status">
              <option value="active" <?= $st==='active'?'selected':'' ?>>Aktif</option>
              <option value="maintenance" <?= $st==='maintenance'?'selected':'' ?>>Bakımda</option>
              <option value="disabled" <?= $st==='disabled'?'selected':'' ?>>Pasif</option>
            </select>
            <div class="small text-muted mt-1">Bakımda/Pasif modunda ziyaretçiler aracı kullanamaz, bilgilendirme mesajı gösterilir.</div>
          </div>

          <div>
            <label class="form-label text-muted">Bakım Mesajı</label>
            <textarea class="form-control" name="maintenance_message" rows="3" placeholder="Örn: Araç kısa süreli bakımda. Lütfen daha sonra tekrar deneyin."><?= e((string)($tool['maintenance_message'] ?? '')) ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Kaydet</button>
            <a class="btn btn-outline-light" href="<?= e(base_url('admin/araclar.php')) ?>"><i class="bi bi-arrow-clockwise me-1"></i>Yenile</a>
          </div>
        </form>

        <hr style="border-color:rgba(148,163,184,.2);">

        <div class="row g-3">
          <div class="col-6">
            <div class="p-3 rounded" style="background:rgba(255,255,255,.06);border:1px solid rgba(148,163,184,.16);">
              <div class="small text-muted">Toplam İşlem</div>
              <div class="h4 mb-0" style="color:#e5e7eb;">
                <?= (int)($totals['total'] ?? ($totals['total_uses'] ?? 0)) ?>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="p-3 rounded" style="background:rgba(255,255,255,.06);border:1px solid rgba(148,163,184,.16);">
              <div class="small text-muted">Bugün</div>
              <div class="h4 mb-0" style="color:#e5e7eb;">
                <?= (int)($totals['today'] ?? 0) ?>
              </div>
            </div>
          </div>
        </div>

        <hr style="border-color:rgba(148,163,184,.2);" class="mt-4">

        <form class="row g-2 align-items-end" method="get">
          <div class="col-5">
            <label class="form-label text-muted">Başlangıç</label>
            <input class="form-control" type="date" name="<?= e('from_'.$slug) ?>" value="<?= e($from) ?>">
          </div>
          <div class="col-5">
            <label class="form-label text-muted">Bitiş</label>
            <input class="form-control" type="date" name="<?= e('to_'.$slug) ?>" value="<?= e($to) ?>">
          </div>
          <div class="col-2">
            <button class="btn btn-outline-light w-100" type="submit"><i class="bi bi-search"></i></button>
          </div>
        </form>

        <div class="table-responsive mt-3">
          <table class="table table-sm" style="color:#e5e7eb;">
            <thead>
              <tr>
                <th style="color:#94a3b8;">Tarih</th>
                <th style="color:#94a3b8;" class="text-end">İşlem</th>
                <th style="color:#94a3b8;" class="text-end">Dosya</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$daily): ?>
                <tr><td colspan="3" class="text-muted">Seçilen aralıkta kayıt yok.</td></tr>
              <?php else: ?>
                <?php foreach ($daily as $row): ?>
                  <tr>
                    <td><?= e((string)($row['day'] ?? $row['date'] ?? '')) ?></td>
                    <td class="text-end"><?= (int)($row['uses'] ?? 0) ?></td>
                    <td class="text-end"><?= (int)($row['files'] ?? 0) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <form method="post" class="mt-2" onsubmit="return confirm('İstatistikleri sıfırlamak istiyor musun?');">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <input type="hidden" name="action" value="reset_stats">
          <input type="hidden" name="tool_slug" value="<?= e($slug) ?>">
          <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash3 me-1"></i>İstatistikleri Sıfırla</button>
        </form>

        <div class="small text-muted mt-2">Not: PDF Birleştirme aracı tarayıcıda çalışır; istatistikler indirme tamamlanınca artar.</div>
      </div>
    </div>
  </div>

  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
