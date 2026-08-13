<?php
// IMPORTANT:
// This page performs redirects (POST actions + CSV export). We MUST run those
// before outputting any HTML. Therefore, _layout_top.php is included later.
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../app/models/Ticket.php';

Auth::requireRole(['admin','editor']);

$tab = $_GET['status'] ?? 'all';
if (!in_array($tab, ['all','open','pending','closed'], true)) $tab = 'all';

$q = trim((string)($_GET['q'] ?? ''));

$view = (string)($_GET['view'] ?? '');
$id = (int)($_GET['id'] ?? 0);

$pageErr = '';
try { $counts = Ticket::adminCounts(); } catch (Throwable $t) { $counts = ['all'=>0,'open'=>0,'pending'=>0,'closed'=>0]; $pageErr = $t->getMessage(); }

// --- CSV export (must run before any HTML output) ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $rows = Ticket::adminSearchList($tab, $q, 800);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="ticketler_'.$tab.'_'.date('Ymd_His').'.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['id','subject','category','priority','status','tags','user','email','created_at','updated_at','last_message']);
  foreach ($rows as $r) {
    fputcsv($out, [
      $r['id'] ?? '',
      $r['subject'] ?? '',
      $r['category'] ?? '',
      $r['priority'] ?? '',
      $r['status'] ?? '',
      $r['tags'] ?? '',
      ($r['display_name'] ?: $r['username']) ?? '',
      $r['email'] ?? '',
      $r['created_at'] ?? '',
      $r['updated_at'] ?? '',
      $r['last_message'] ?? '',
    ]);
  }
  fclose($out);
  exit;
}

// --- POST actions (must run before any HTML output) ---
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);

  $action = (string)($_POST['action'] ?? '');
  $singleId = (int)($_POST['id'] ?? 0);

  $ids = $_POST['ids'] ?? [];
  if (!is_array($ids)) $ids = [];
  $ids = array_values(array_filter(array_map('intval', $ids), fn($v)=>$v>0));

  $apply = function(int $tid, string $a) {
    if ($a === 'open') Ticket::adminSetStatus($tid, 'open');
    if ($a === 'pending') Ticket::adminSetStatus($tid, 'pending');
    if ($a === 'closed') Ticket::adminSetStatus($tid, 'closed');
    if ($a === 'delete') { Auth::requireRole(['admin']); Ticket::adminDelete($tid); }
  };

  if ($action === 'bulk' && !empty($ids)) {
    $bulkAction = (string)($_POST['bulk_action'] ?? 'pending');
    if (!in_array($bulkAction, ['open','pending','closed','delete'], true)) $bulkAction = 'pending';
    foreach ($ids as $tid) { $apply($tid, $bulkAction); }
    $_SESSION['flash_ok'] = 'Toplu işlem tamamlandı.';
    $_SESSION['flash_success'] = 'Seçilen ticket(ler) güncellendi.';
    redirect(base_url('admin/tickets.php?' . http_build_query(['status'=>$tab,'q'=>$q])));
  }

  if ($action === 'reply' && $singleId > 0) {
    $msg = (string)($_POST['message'] ?? '');
    $setStatus = (string)($_POST['set_status'] ?? '');
    $r = Ticket::adminReply($singleId, $msg, $setStatus);
    if (!empty($r['ok'])) { $_SESSION['flash_ok'] = 'Yanıt gönderildi.'; } else { $_SESSION['flash_err'] = (string)($r['error'] ?? 'Gönderilemedi'); }
    $_SESSION['flash_success'] = 'Yanıt gönderildi.';
    redirect(base_url('admin/tickets.php?' . http_build_query(['status'=>$tab,'view'=>'detail','id'=>$singleId])));
  }

  if ($action === 'update_meta' && $singleId > 0) {
    Ticket::adminUpdateMeta($singleId, (string)($_POST['tags'] ?? ''), (string)($_POST['admin_note'] ?? ''));
    if (isset($_POST['status']) && in_array($_POST['status'], ['open','pending','closed'], true)) {
      Ticket::adminSetStatus($singleId, (string)$_POST['status']);
    }
    $_SESSION['flash_ok'] = 'Güncellendi.';
    $_SESSION['flash_success'] = 'Ticket güncellendi.';
    redirect(base_url('admin/tickets.php?' . http_build_query(['status'=>$tab,'view'=>'detail','id'=>$singleId])));
  }

  if ($singleId > 0 && in_array($action, ['open','pending','closed','delete'], true)) {
    $apply($singleId, $action);
    $_SESSION['flash_ok'] = 'İşlem tamamlandı.';
    $_SESSION['flash_success'] = 'Ticket silindi.';
    redirect(base_url('admin/tickets.php?' . http_build_query(['status'=>$tab,'q'=>$q])));
  }
}

// Now it's safe to output the admin layout.
require_once __DIR__ . '/_layout_top.php';

$flash = $_SESSION['flash_ok'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

$list = [];
try { $list = Ticket::adminSearchList($tab, $q, 400); } catch (Throwable $t) { $pageErr = $pageErr ?: $t->getMessage(); }

$detail = null;
$messages = [];
if ($view === 'detail' && $id > 0) {
  try {
    $detail = Ticket::adminGet($id);
    if ($detail) $messages = Ticket::messages($id);
  } catch (Throwable $t) { $pageErr = $pageErr ?: $t->getMessage(); $detail = null; $messages = []; }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Ticketler</h1>
    <div class="text-muted">Site kullanıcılarından gelen destek talepleri</div>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/tickets.php?status='.$tab.'&q='.urlencode($q).'&export=csv')) ?>">
      <i class="bi bi-filetype-csv me-1"></i>CSV
    </a>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert text-light" style="background:rgba(16,185,129,.18);border:1px solid rgba(16,185,129,.35);">
    <i class="bi bi-check-circle me-1"></i><?= e($flash) ?>
  </div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert text-light" style="background:rgba(239,68,68,.18);border:1px solid rgba(239,68,68,.35);">
    <i class="bi bi-exclamation-triangle me-1"></i><?= e($flashErr) ?>
  </div>
<?php endif; ?>
<?php if ($pageErr): ?>
  <div class="alert text-light" style="background:rgba(239,68,68,.18);border:1px solid rgba(239,68,68,.35);">
    <i class="bi bi-bug me-1"></i>Beklenmeyen hata: <?= e($pageErr) ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="d-flex gap-2 flex-wrap mb-3">
      <a class="btn btn-sm <?= $tab==='all'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('admin/tickets.php?status=all&q='.urlencode($q))) ?>">
        Tümü <span class="badge bg-primary ms-1"><?= (int)$counts['all'] ?></span>
      </a>
      <a class="btn btn-sm <?= $tab==='open'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('admin/tickets.php?status=open&q='.urlencode($q))) ?>">
        Açık <span class="badge bg-primary ms-1"><?= (int)$counts['open'] ?></span>
      </a>
      <a class="btn btn-sm <?= $tab==='pending'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('admin/tickets.php?status=pending&q='.urlencode($q))) ?>">
        Beklemede <span class="badge bg-primary ms-1"><?= (int)$counts['pending'] ?></span>
      </a>
      <a class="btn btn-sm <?= $tab==='closed'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('admin/tickets.php?status=closed&q='.urlencode($q))) ?>">
        Kapalı <span class="badge bg-primary ms-1"><?= (int)$counts['closed'] ?></span>
      </a>
    </div>

    <div class="card p-3 mb-3">
      <form method="get" class="d-flex gap-2">
        <input type="hidden" name="status" value="<?= e($tab) ?>">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Ara: konu, kullanıcı, e-posta, etiket...">
        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
      </form>
      <div class="small text-muted mt-2">İpucu: Ticket etiketleri ile “entegrasyon, HL7, raporlama” gibi sınıflandırma yapabilirsin.</div>
    </div>

    <div class="card p-3">
      <?php if (!$list): ?><div class="text-muted">Kayıt yok.</div><?php endif; ?>

      <?php if ($list): ?>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="bulk">

        <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
          <select class="form-select form-select-sm" name="bulk_action" style="max-width: 220px;">
            <option value="pending">Seçilenleri Beklemede</option>
            <option value="open">Seçilenleri Açık</option>
            <option value="closed">Seçilenleri Kapalı</option>
            <?php if (Auth::isAdmin()): ?><option value="delete">Seçilenleri Sil</option><?php endif; ?>
          </select>
          <button class="btn btn-sm btn-primary">Uygula</button>
          <div class="ms-auto small text-muted"><?= count($list) ?> kayıt</div>
        </div>

        <div class="vstack gap-2">
          <?php foreach ($list as $t): ?>
            <?php $userLabel = ($t['display_name'] ?: $t['username']) ?: '—'; ?>
            <label class="border rounded-4 p-3 d-block" style="cursor:pointer">
              <div class="d-flex align-items-start gap-2">
                <input class="form-check-input mt-1" type="checkbox" name="ids[]" value="<?= (int)$t['id'] ?>">
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                      <div class="fw-bold">#<?= (int)$t['id'] ?> — <?= e($t['subject'] ?? '') ?></div>
                      <div class="small text-muted">
                        <?= e($userLabel) ?><?= !empty($t['email']) ? ' • ' . e($t['email']) : '' ?>
                        • <?= e(($t['category'] ?? '') . ' • ' . ($t['priority'] ?? '')) ?>
                        • <span class="badge bg-primary"><?= e($t['status'] ?? '') ?></span>
                        <?php if (!empty($t['tags'])): ?> • <span class="badge bg-primary"><?= e($t['tags']) ?></span><?php endif; ?>
                      </div>
                    </div>
                    <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/tickets.php?status='.$tab.'&q='.urlencode($q).'&view=detail&id='.(int)$t['id'])) ?>">
                      Detay <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                  </div>
                  <?php if (!empty($t['last_message'])): ?>
                    <div class="mt-2 text-muted" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                      <?= e((string)$t['last_message']) ?>
                    </div>
                  <?php endif; ?>
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
        <div class="text-muted">Detay görmek için soldan bir ticket seç.</div>
      <?php else: ?>
        <?php $userLabel = ($detail['display_name'] ?: $detail['username']) ?: '—'; ?>
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <div class="h5 fw-bold mb-0">#<?= (int)$detail['id'] ?> — <?= e($detail['subject'] ?? '') ?></div>
            <div class="text-muted"><?= e($userLabel) ?><?= !empty($detail['email']) ? ' • ' . e($detail['email']) : '' ?></div>
            <div class="small text-muted mt-1">
              <?= e(date('d.m.Y H:i', strtotime((string)($detail['created_at'] ?? 'now')))) ?>
              • Durum: <span class="badge bg-primary"><?= e($detail['status'] ?? '') ?></span>
              • <?= e(($detail['category'] ?? '') . ' • ' . ($detail['priority'] ?? '')) ?>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
              <button class="btn btn-sm btn-outline-primary" name="action" value="open">Açık</button>
            </form>
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
              <button class="btn btn-sm btn-warning text-dark" name="action" value="pending">Beklemede</button>
            </form>
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
              <button class="btn btn-sm btn-success" name="action" value="closed">Kapalı</button>
            </form>
            <?php if (Auth::isAdmin()): ?>
            <form method="post" onsubmit="return confirm('Ticket ve tüm mesajları silinsin mi?')">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
              <button class="btn btn-sm btn-danger" name="action" value="delete"><i class="bi bi-trash me-1"></i>Sil</button>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <hr class="my-3">

        <div class="vstack gap-3">
          <?php foreach ($messages as $m): ?>
            <?php $isAdmin = ($m['sender_role'] ?? '') === 'admin'; ?>
            <div class="d-flex gap-2">
              <div class="flex-shrink-0">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:<?= $isAdmin ? '#0d6efd22' : '#19875422' ?>;border:1px solid rgba(0,0,0,.08);">
                  <i class="bi <?= $isAdmin ? 'bi-shield-check' : 'bi-person' ?>"></i>
                </div>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="fw-bold"><?= $isAdmin ? 'Admin' : 'Kullanıcı' ?></div>
                  <div class="text-muted small"><?= e($m['created_at'] ?? '') ?></div>
                </div>
                <div class="mt-1" style="white-space:pre-wrap;"><?= e($m['message'] ?? '') ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <hr class="my-3">

        <form method="post">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <input type="hidden" name="action" value="reply">
          <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">

          <div class="row g-2 align-items-end">
            <div class="col-md-8">
              <label class="form-label">Yanıt</label>
              <textarea class="form-control" name="message" rows="3" required></textarea>
            </div>
            <div class="col-md-2">
              <label class="form-label">Durum</label>
              <select class="form-select" name="set_status">
                <option value="">(değiştirme)</option>
                <option value="open">open</option>
                <option value="pending">pending</option>
                <option value="closed">closed</option>
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Gönder</button>
            </div>
          </div>
        </form>

        <hr class="my-3">

        <form method="post" class="row g-2">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <input type="hidden" name="action" value="update_meta">
          <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">

          <div class="col-12">
            <label class="form-label">Etiketler (virgülle)</label>
            <input class="form-control" name="tags" value="<?= e($detail['tags'] ?? '') ?>" placeholder="Örn: HL7, entegrasyon, raporlama">
          </div>

          <div class="col-12">
            <label class="form-label">Admin Notu</label>
            <textarea class="form-control" name="admin_note" rows="4" placeholder="Ticket ile ilgili not..."><?= e($detail['admin_note'] ?? '') ?></textarea>
          </div>

          <div class="col-6">
            <label class="form-label">Durum</label>
            <select class="form-select" name="status">
              <option value="open" <?= ($detail['status'] ?? '')==='open'?'selected':'' ?>>open</option>
              <option value="pending" <?= ($detail['status'] ?? '')==='pending'?'selected':'' ?>>pending</option>
              <option value="closed" <?= ($detail['status'] ?? '')==='closed'?'selected':'' ?>>closed</option>
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
