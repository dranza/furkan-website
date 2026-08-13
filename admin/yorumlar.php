<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/models/Comments.php';

Auth::requireLogin();

$tab = $_GET['tab'] ?? 'pending';
if (!in_array($tab, ['pending','approved','spam'], true)) $tab = 'pending';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  $action = (string)($_POST['action'] ?? '');

  if ($id > 0) {
    if ($action === 'approve') Comments::adminSetStatus($id, 'approved');
    if ($action === 'spam') Comments::adminSetStatus($id, 'spam');
    if ($action === 'pending') Comments::adminSetStatus($id, 'pending');
    if ($action === 'delete') Comments::adminDelete($id);
  }
  // Cache purge: blog detail + list
    try {
      $pdo = DB::pdo();
      $st = $pdo->prepare("SELECT b.slug FROM comments c JOIN blog_posts b ON b.id=c.post_id WHERE c.id=:id LIMIT 1");
      $st->execute([':id'=>$id]);
      $slug = (string)($st->fetchColumn() ?? '');
      if ($slug) { purge_page_cache('/blog/'.$slug); }
      purge_page_cache('/blog');
      purge_page_cache('/');
    } catch (Throwable $t) {}
    redirect(base_url('admin/yorumlar.php?tab='.$tab));
}

$counts = Comments::adminCounts();
$list = Comments::adminList($tab, 300);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Yorumlar</h1>
    <div class="text-muted">Moderasyon ve onay</div>
  </div>
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
  <a class="btn btn-sm <?= $tab==='pending'?'btn-primary':'btn-outline-light' ?>" href="<?= e(base_url('admin/yorumlar.php?tab=pending')) ?>">
    Bekleyen <span class="badge bg-dark ms-1"><?= (int)$counts['pending'] ?></span>
  </a>
  <a class="btn btn-sm <?= $tab==='approved'?'btn-primary':'btn-outline-light' ?>" href="<?= e(base_url('admin/yorumlar.php?tab=approved')) ?>">
    Onaylı <span class="badge bg-dark ms-1"><?= (int)$counts['approved'] ?></span>
  </a>
  <a class="btn btn-sm <?= $tab==='spam'?'btn-primary':'btn-outline-light' ?>" href="<?= e(base_url('admin/yorumlar.php?tab=spam')) ?>">
    Spam <span class="badge bg-dark ms-1"><?= (int)$counts['spam'] ?></span>
  </a>
</div>

<div class="card p-3">
  <?php if (!$list): ?><div class="text-muted">Kayıt yok.</div><?php endif; ?>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Tarih</th>
          <th>Yazan</th>
          <th>Yorum</th>
          <th>Yazı</th>
          <th class="text-end">İşlem</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($list as $c): ?>
        <tr>
          <td class="text-muted"><?= e(date('d.m.Y H:i', strtotime((string)$c['created_at']))) ?></td>
          <td>
            <div class="fw-semibold"><?= e($c['name']) ?></div>
            <div class="text-muted small"><?= e($c['email'] ?? '-') ?></div>
          </td>
          <td style="max-width:480px">
            <div class="text-truncate" style="max-width:480px"><?= e($c['content']) ?></div>
          </td>
          <td>
            <a class="text-decoration-none" href="<?= e(base_url('blog/'.$c['post_slug'])) ?>" target="_blank"><?= e($c['post_title']) ?></a>
          </td>
          <td class="text-end">
            <form method="post" class="d-inline">
              <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <?php if ($tab !== 'approved'): ?>
                <button name="action" value="approve" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i>Onayla</button>
              <?php endif; ?>
              <?php if ($tab !== 'spam'): ?>
                <button name="action" value="spam" class="btn btn-sm btn-warning text-dark"><i class="bi bi-shield-exclamation me-1"></i>Spam</button>
              <?php endif; ?>
              <?php if ($tab !== 'pending'): ?>
                <button name="action" value="pending" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left-right me-1"></i>Bekleyen</button>
              <?php endif; ?>
              <button name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')"><i class="bi bi-trash me-1"></i>Sil</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
