<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../app/models/HomeBlock.php';

Auth::requireRole(['admin','editor']);

$tab = $_GET['tab'] ?? 'services';
$tabs = ['services'=>'Hizmet Kartları','stats'=>'Vurgu KPI','process'=>'Çalışma Modeli','tech'=>'Teknoloji'];
if (!isset($tabs[$tab])) $tab = 'services';

$ok = '';
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'save_titles') {
    $pairs = [
      'home_services_title','home_services_subtitle',
      'home_stats_title','home_stats_subtitle',
      'home_process_title','home_process_subtitle',
      'home_tech_title','home_tech_subtitle'
    ];
    foreach ($pairs as $k) {
      Settings::set($k, trim((string)($_POST[$k] ?? '')));
    }
    $ok = 'Başlıklar kaydedildi.';
    try { purge_page_cache('/'); } catch (Throwable $t) {}
  } elseif ($action === 'create') {
    $res = HomeBlock::create([
      'section'=>$tab,
      'title'=>$_POST['title'] ?? '',
      'body'=>$_POST['body'] ?? '',
      'icon'=>$_POST['icon'] ?? 'bi-stars',
      'link_url'=>$_POST['link_url'] ?? '',
      'sort_order'=>$_POST['sort_order'] ?? 0,
      'is_active'=>!empty($_POST['is_active']) ? 1 : 0,
    ]);
    if (!empty($res['ok'])) { $ok='Kayıt eklendi.'; try { purge_page_cache('/'); } catch (Throwable $t) {} }
    else $err = (string)($res['error'] ?? 'Hata');
  } elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $res = HomeBlock::update($id, [
      'title'=>$_POST['title'] ?? '',
      'body'=>$_POST['body'] ?? '',
      'icon'=>$_POST['icon'] ?? '',
      'link_url'=>$_POST['link_url'] ?? '',
      'sort_order'=>$_POST['sort_order'] ?? 0,
      'is_active'=>!empty($_POST['is_active']) ? 1 : 0,
    ]);
    if (!empty($res['ok'])) { $ok='Güncellendi.'; try { purge_page_cache('/'); } catch (Throwable $t) {} }
    else $err = (string)($res['error'] ?? 'Hata');
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { HomeBlock::delete($id); $ok='Silindi.'; try { purge_page_cache('/'); } catch (Throwable $t) {} }
  }
}

$rows = HomeBlock::adminAll($tab);

$home_services_title = Settings::get('home_services_title','Neler Yapıyorum?') ?? '';
$home_services_subtitle = Settings::get('home_services_subtitle','Hastane ortamında uçtan uca BT operasyonu ve proje teslimi') ?? '';
$home_stats_title = Settings::get('home_stats_title','Öne Çıkanlar') ?? '';
$home_stats_subtitle = Settings::get('home_stats_subtitle','Kritik sistemlerde güven, görünürlük ve süreklilik') ?? '';
$home_process_title = Settings::get('home_process_title','Çalışma Modeli') ?? '';
$home_process_subtitle = Settings::get('home_process_subtitle','Analizden devreye almaya, dokümantasyondan izlemeye') ?? '';
$home_tech_title = Settings::get('home_tech_title','Teknoloji & Platformlar') ?? '';
$home_tech_subtitle = Settings::get('home_tech_subtitle','Saha deneyimiyle kullandığım araçlar ve yaklaşımlar') ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Anasayfa İçerikleri</h1>
    <div class="text-muted">Anasayfadaki kartlar, KPI’lar ve bölümler</div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-light btn-sm" target="_blank" href="<?= e(base_url('/')) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Anasayfa</a>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="card p-3 mb-3">
  <div class="fw-bold mb-2">Bölüm Başlıkları</div>
  <form method="post" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
    <input type="hidden" name="action" value="save_titles">

    <div class="col-md-6">
      <label class="form-label">Hizmet Başlığı</label>
      <input class="form-control" name="home_services_title" value="<?= e($home_services_title) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Hizmet Alt Başlığı</label>
      <input class="form-control" name="home_services_subtitle" value="<?= e($home_services_subtitle) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">KPI Başlığı</label>
      <input class="form-control" name="home_stats_title" value="<?= e($home_stats_title) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">KPI Alt Başlığı</label>
      <input class="form-control" name="home_stats_subtitle" value="<?= e($home_stats_subtitle) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Süreç Başlığı</label>
      <input class="form-control" name="home_process_title" value="<?= e($home_process_title) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Süreç Alt Başlığı</label>
      <input class="form-control" name="home_process_subtitle" value="<?= e($home_process_subtitle) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Teknoloji Başlığı</label>
      <input class="form-control" name="home_tech_title" value="<?= e($home_tech_title) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Teknoloji Alt Başlığı</label>
      <input class="form-control" name="home_tech_subtitle" value="<?= e($home_tech_subtitle) ?>">
    </div>

    <div class="col-12 d-grid mt-2">
      <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Kaydet</button>
    </div>
  </form>
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
  <?php foreach ($tabs as $k=>$lbl): ?>
    <a class="btn btn-sm <?= $tab===$k?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('admin/anasayfa.php?tab='.$k)) ?>"><?= e($lbl) ?></a>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="fw-bold mb-2">Yeni Ekle (<?= e($tabs[$tab]) ?>)</div>
      <form method="post" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="create">

        <div class="col-12">
          <label class="form-label">Başlık</label>
          <input class="form-control" name="title" required>
        </div>

        <div class="col-12">
          <label class="form-label">Açıklama</label>
          <textarea class="form-control" name="body" rows="3"></textarea>
        </div>

        <div class="col-6">
          <label class="form-label">İkon (Bootstrap Icons)</label>
          <input class="form-control" name="icon" value="bi-stars" placeholder="Örn: bi-shield-lock">
        </div>
        <div class="col-6">
          <label class="form-label">Sıra</label>
          <input class="form-control" name="sort_order" type="number" value="0">
        </div>

        <div class="col-12">
          <label class="form-label">Link (opsiyonel)</label>
          <input class="form-control" name="link_url" placeholder="/projeler veya https://...">
        </div>

        <div class="col-12">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" checked>
            <span class="form-check-label">Aktif</span>
          </label>
        </div>

        <div class="col-12 d-grid">
          <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ekle</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card p-3">
      <div class="fw-bold mb-2">Kayıtlar</div>
      <?php if (!$rows): ?><div class="text-muted">Kayıt yok.</div><?php endif; ?>

      <?php foreach ($rows as $r): ?>
        <div class="border rounded-4 p-3 mb-2" style="border-color:rgba(255,255,255,.10)!important;">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold"><?= e($r['title'] ?? '') ?></div>
            <form method="post" onsubmit="return confirm('Silinsin mi?')">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-danger"><i class="bi bi-trash me-1"></i>Sil</button>
            </form>
          </div>

          <form method="post" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

            <div class="col-12">
              <label class="form-label">Başlık</label>
              <input class="form-control" name="title" value="<?= e($r['title'] ?? '') ?>" required>
            </div>

            <div class="col-12">
              <label class="form-label">Açıklama</label>
              <textarea class="form-control" name="body" rows="3"><?= e($r['body'] ?? '') ?></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">İkon</label>
              <input class="form-control" name="icon" value="<?= e($r['icon'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Link</label>
              <input class="form-control" name="link_url" value="<?= e($r['link_url'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Sıra</label>
              <input class="form-control" type="number" name="sort_order" value="<?= (int)($r['sort_order'] ?? 0) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <label class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_active" <?= !empty($r['is_active'])?'checked':'' ?>>
                <span class="form-check-label">Aktif</span>
              </label>
            </div>

            <div class="col-12 d-flex justify-content-end">
              <button class="btn btn-sm btn-primary"><i class="bi bi-save2 me-1"></i>Kaydet</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
