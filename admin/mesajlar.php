<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/models/Contact.php';

Auth::requireRole(['admin','editor']);

$tab = $_GET['tab'] ?? 'new';
if (!in_array($tab, ['new','read','archived'], true)) $tab = 'new';

$q = trim((string)($_GET['q'] ?? ''));

$view = (string)($_GET['view'] ?? '');
$id = (int)($_GET['id'] ?? 0);

$counts = Contact::adminCounts();

// diagnostics (DB name)
$dbName = '-';
try { $dbName = (string)(DB::pdo()->query("SELECT DATABASE()")->fetchColumn()); } catch (Throwable $t) {}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  // CSV export for current filter
  $rows = Contact::adminSearchList($tab, $q, 500);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="mesajlar_'.$tab.'_'.date('Ymd_His').'.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['id','name','email','status','tags','created_at','message','admin_note']);
  foreach ($rows as $r) {
    fputcsv($out, [
      $r['id'] ?? '',
      $r['name'] ?? '',
      $r['email'] ?? '',
      $r['status'] ?? '',
      $r['tags'] ?? '',
      $r['created_at'] ?? '',
      $r['message'] ?? '',
      $r['admin_note'] ?? '',
    ]);
  }
  fclose($out);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);

  $action = (string)($_POST['action'] ?? '');
  $singleId = (int)($_POST['id'] ?? 0);

  // bulk ids
  $ids = $_POST['ids'] ?? [];
  if (!is_array($ids)) $ids = [];
  $ids = array_values(array_filter(array_map('intval', $ids), fn($v)=>$v>0));

  if ($action === 'test_insert') {
    $r = Contact::submit('Admin Test', '', 'Test mesajı (admin panel) - ' . date('Y-m-d H:i:s'), '');
    $_SESSION['flash_ok'] = !empty($r['ok']) ? 'Test mesajı oluşturuldu.' : 'Test başarısız.';
    redirect(base_url('admin/mesajlar.php?tab='.$tab));
  }

  if ($action === 'update_meta' && $singleId > 0) {
    Contact::adminUpdateMeta($singleId, (string)($_POST['tags'] ?? ''), (string)($_POST['admin_note'] ?? ''));
    if (isset($_POST['status']) && in_array($_POST['status'], ['new','read','archived'], true)) {
      Contact::adminSetStatus($singleId, (string)$_POST['status']);
    }
    $_SESSION['flash_ok'] = 'Güncellendi.';
    redirect(base_url('admin/mesajlar.php?tab='.$tab.'&view=detail&id='.$singleId));
  }

  // helper to run status changes
  $apply = function(int $mid, string $a) {
    if ($a === 'read') Contact::adminSetStatus($mid, 'read');
    if ($a === 'archive') Contact::adminSetStatus($mid, 'archived');
    if ($a === 'new') Contact::adminSetStatus($mid, 'new');
    if ($a === 'delete') { Auth::requireRole(['admin']); Contact::adminDelete($mid); }
  };

  if ($action === 'bulk' && !empty($ids)) {
    $bulkAction = (string)($_POST['bulk_action'] ?? '');
    if (!in_array($bulkAction, ['read','archive','new','delete'], true)) $bulkAction = 'read';
    foreach ($ids as $mid) { $apply($mid, $bulkAction); }
    $_SESSION['flash_ok'] = 'Toplu işlem tamamlandı.';
    redirect(base_url('admin/mesajlar.php?tab='.$tab.'&q='.urlencode($q)));
  }

  if ($singleId > 0 && in_array($action, ['read','archive','new','delete'], true)) {
    $apply($singleId, $action);
    $_SESSION['flash_ok'] = 'İşlem tamamlandı.';
    redirect(base_url('admin/mesajlar.php?tab='.$tab.'&q='.urlencode($q)));
  }
}

$flash = $_SESSION['flash_ok'] ?? '';
unset($_SESSION['flash_ok']);

$list = Contact::adminSearchList($tab, $q, 300);
$detail = null;
if ($view === 'detail' && $id > 0) {
  $detail = Contact::adminGet($id);
  // auto mark read when opened
  if ($detail && ($detail['status'] ?? '') === 'new') {
    Contact::adminSetStatus($id, 'read');
    $detail['status'] = 'read';
  }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Mesajlar</h1>
    <div class="text-muted">İletişim formu kayıtları</div>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/mesajlar.php?tab='.$tab.'&q='.urlencode($q).'&export=csv')) ?>">
      <i class="bi bi-filetype-csv me-1"></i>CSV
    </a>
    <form method="post" class="d-inline">
      <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
      <input type="hidden" name="action" value="test_insert">
      <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Test Mesajı</button>
    </form>
  </div>
</div>

<div class="alert alert-info d-flex flex-wrap gap-3 align-items-center">
  <div><strong>DB:</strong> <?= e($dbName) ?></div>
  <div><strong>Yeni:</strong> <?= (int)$counts['new'] ?></div>
  <div><strong>Okundu:</strong> <?= (int)$counts['read'] ?></div>
  <div><strong>Arşiv:</strong> <?= (int)$counts['archived'] ?></div>
</div>

<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="d-flex gap-2 flex-wrap mb-3">
      <a class="btn btn-sm <?= $tab==='new'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('admin/mesajlar.php?tab=new&q='.urlencode($q))) ?>">
        Yeni <span class="badge bg-primary ms-1"><?= (int)$counts['new'] ?></span>
      </a>
      <a class="btn btn-sm <?= $tab==='read'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('admin/mesajlar.php?tab=read&q='.urlencode($q))) ?>">
        Okundu <span class="badge bg-primary ms-1"><?= (int)$counts['read'] ?></span>
      </a>
      <a class="btn btn-sm <?= $tab==='archived'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('admin/mesajlar.php?tab=archived&q='.urlencode($q))) ?>">
        Arşiv <span class="badge bg-primary ms-1"><?= (int)$counts['archived'] ?></span>
      </a>
    </div>

    <div class="card p-3 mb-3">
      <form method="get" class="d-flex gap-2">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Ara: isim, e-posta, mesaj, etiket...">
        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
      </form>
      <div class="small text-muted mt-2">İpucu: Etiket alanında “ITSM, HL7, Güvenlik” gibi notlar tutabilirsin.</div>
    </div>

    <div class="card p-3">
      <?php if (!$list): ?><div class="text-muted">Kayıt yok.</div><?php endif; ?>

      <?php if ($list): ?>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="bulk">

        <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
          <select class="form-select form-select-sm" name="bulk_action" style="max-width: 200px;">
            <option value="read">Seçilenleri Okundu</option>
            <option value="archive">Seçilenleri Arşiv</option>
            <option value="new">Seçilenleri Yeni</option>
            <?php if (Auth::isAdmin()): ?><option value="delete">Seçilenleri Sil</option><?php endif; ?>
          </select>
          <button class="btn btn-sm btn-primary">Uygula</button>
          <div class="ms-auto small text-muted"><?= count($list) ?> kayıt</div>
        </div>

        <div class="vstack gap-2">
          <?php foreach ($list as $m): ?>
            <label class="border rounded-4 p-3 d-block" style="cursor:pointer">
              <div class="d-flex align-items-start gap-2">
                <input class="form-check-input mt-1" type="checkbox" name="ids[]" value="<?= (int)$m['id'] ?>">
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                      <div class="fw-bold"><?= e($m['name']) ?> <span class="text-muted fw-normal"><?= e($m['email'] ?? '') ?></span></div>
                      <div class="small text-muted"><?= e(date('d.m.Y H:i', strtotime((string)$m['created_at']))) ?>
                        <?php if (!empty($m['tags'])): ?> • <span class="badge bg-primary"><?= e($m['tags']) ?></span><?php endif; ?>
                      </div>
                    </div>
                    <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/mesajlar.php?tab='.$tab.'&q='.urlencode($q).'&view=detail&id='.(int)$m['id'])) ?>">
                      Detay <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                  </div>
                  <div class="mt-2 text-muted" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= e($m['message']) ?>
                  </div>
                </div>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card p-3">
      <?php if (!$detail): ?>
        <div class="text-muted">Detay görmek için soldan bir mesaj seç.</div>
      <?php else: ?>
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <div class="h5 fw-bold mb-0"><?= e($detail['name'] ?? '') ?></div>
            <div class="text-muted"><?= e($detail['email'] ?? '') ?></div>
            <div class="small text-muted mt-1">
              <?= e(date('d.m.Y H:i', strtotime((string)($detail['created_at'] ?? 'now')))) ?>
              • Durum: <span class="badge bg-primary"><?= e($detail['status'] ?? '') ?></span>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
              <button class="btn btn-sm btn-success" name="action" value="read"><i class="bi bi-check2 me-1"></i>Okundu</button>
            </form>
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
              <button class="btn btn-sm btn-warning text-dark" name="action" value="archive"><i class="bi bi-archive me-1"></i>Arşiv</button>
            </form>
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
              <button class="btn btn-sm btn-outline-primary" name="action" value="new"><i class="bi bi-arrow-counterclockwise me-1"></i>Yeni</button>
            </form>
            <?php if (Auth::isAdmin()): ?>
            <form method="post" onsubmit="return confirm('Silinsin mi?')">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
              <button class="btn btn-sm btn-danger" name="action" value="delete"><i class="bi bi-trash me-1"></i>Sil</button>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <hr class="my-3">
        <div class="rounded-4 p-3" style="background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); white-space:pre-wrap;">
          <?= e($detail['message'] ?? '') ?>
        </div>

        <hr class="my-3">
        <form method="post" class="row g-2">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <input type="hidden" name="action" value="update_meta">
          <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">

          <div class="col-12">
            <label class="form-label">Etiketler (virgülle)</label>
            <input class="form-control" name="tags" value="<?= e($detail['tags'] ?? '') ?>" placeholder="Örn: entegrasyon, HL7, raporlama">
          </div>

          <div class="col-12">
            <label class="form-label">Admin Notu</label>
            <textarea class="form-control" name="admin_note" rows="4" placeholder="Bu mesajla ilgili not..."><?= e($detail['admin_note'] ?? '') ?></textarea>
          </div>

          <div class="col-6">
            <label class="form-label">Durum</label>
            <select class="form-select" name="status">
              <option value="new" <?= ($detail['status'] ?? '')==='new'?'selected':'' ?>>new</option>
              <option value="read" <?= ($detail['status'] ?? '')==='read'?'selected':'' ?>>read</option>
              <option value="archived" <?= ($detail['status'] ?? '')==='archived'?'selected':'' ?>>archived</option>
            </select>
          </div>

          <div class="col-6 d-grid align-items-end">
            <button class="btn btn-primary mt-4"><i class="bi bi-save2 me-1"></i>Kaydet</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
