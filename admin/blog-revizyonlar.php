<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/models/Revisions.php';

$postId = (int)($_GET['id'] ?? 0);
$post = $postId ? Blog::adminGet($postId) : null;
if (!$post) { echo "<div class='alert alert-danger'>Kayıt bulunamadı.</div>"; require __DIR__.'/_layout_bottom.php'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $revId = (int)($_POST['rev_id'] ?? 0);
  $rev = Revisions::blogGet($revId);
  if ($rev && (int)$rev['post_id'] === $postId) {
    Blog::adminSave([
      'id' => $postId,
      'title' => $rev['title'],
      'slug' => $rev['slug'],
      'category' => $rev['category'],
      'content' => $rev['content'],
      'cover_image' => $rev['cover_image'],
      'tags' => $rev['tags'],
      'meta_title' => $rev['meta_title'],
      'meta_desc' => $rev['meta_desc'],
      'status' => $rev['status'],
      'published_at' => $rev['published_at'],
    ]);
    redirect(base_url('admin/blog-duzenle.php?id='.$postId.'&restored=1'));
  }
}

$list = Revisions::blogList($postId);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Revizyonlar</h1>
    <div class="text-muted"><?= e($post['title']) ?></div>
  </div>
  <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('admin/blog-duzenle.php?id='.$postId)) ?>"><i class="bi bi-arrow-left me-1"></i>Geri</a>
</div>

<div class="card p-3">
  <?php if (!$list): ?><div class="text-muted">Revizyon yok.</div><?php endif; ?>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Tarih</th><th>Kaydeden</th><th>Durum</th><th>Yayın</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($list as $r): ?>
          <tr>
            <td><?= e(date('d.m.Y H:i', strtotime((string)$r['created_at']))) ?></td>
            <td class="text-muted"><?= e($r['saved_by'] ?? '-') ?></td>
            <td><?= e($r['status'] ?? '-') ?></td>
            <td class="text-muted"><?= e($r['published_at'] ? date('d.m.Y H:i', strtotime((string)$r['published_at'])) : '-') ?></td>
            <td class="text-end">
              <form method="post" onsubmit="return confirm('Bu revizyona dönülsün mü?')">
                <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
                <input type="hidden" name="rev_id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-counterclockwise me-1"></i>Geri Al</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="text-muted small mt-2">Son 20 revizyon saklanır.</div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
