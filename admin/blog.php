<?php
require_once __DIR__ . '/_layout_top.php';

$ok = '';
if (($_GET['delete'] ?? '') !== '') {
  // deletion via GET is not ideal, but we enforce csrf through token query
  CSRF::checkOrExit($_GET['_csrf'] ?? null);
  $toDel = Blog::adminGet((int)$_GET['delete']);
  Blog::adminDelete((int)$_GET['delete']);
  $paths = ['/'];
  if (!empty($toDel['slug'])) $paths[] = '/blog/'.$toDel['slug'];
  $paths[] = '/blog';
  try { purge_pages($paths); } catch (Throwable $t) {}
  if (!empty($toDel['slug'])) { try { purge_page_cache('/blog/'.$toDel['slug']); } catch (Throwable $t) {} }
  try { purge_page_cache('/'); purge_page_cache('/blog'); } catch (Throwable $t) {}
  $ok = 'Silindi.';
}

$items = Blog::adminAll();
?>
<h1 class="h4 fw-bold mb-4 d-flex justify-content-between align-items-center">
  Blog
  <a class="btn btn-primary" href="<?= e(base_url('admin/blog-duzenle.php')) ?>">Yeni Yazı</a>
</h1>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Başlık</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
      <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="4" class="text-muted">Kayıt yok.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $b): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($b['title']) ?> <?php if (!empty($b['category'])): ?><span class="badge bg-info"><?= e($b['category']) ?></span><?php endif; ?></div>
              <div class="small text-muted">/blog/<?= e($b['slug']) ?></div>
            </td>
            <td><span class="badge <?= $b['status']==='published'?'bg-success':'bg-secondary' ?>"><?= e($b['status']) ?></span></td>
            <td class="text-muted small"><?= e($b['published_at'] ? date('d.m.Y', strtotime((string)$b['published_at'])) : '-') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('admin/blog-duzenle.php?id='.$b['id'])) ?>">Düzenle</a>
              <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Silinsin mi?')" href="<?= e(base_url('admin/blog.php?delete='.$b['id'].'&_csrf='.CSRF::token())) ?>">Sil</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
