<?php
require_once __DIR__ . '/_layout_top.php';

$ok = '';
if (($_GET['delete'] ?? '') !== '') {
  CSRF::checkOrExit($_GET['_csrf'] ?? null);
  $toDel = Projects::adminGet((int)$_GET['delete']);
  Projects::adminDelete((int)$_GET['delete']);
  $paths = ['/'];
  if (!empty($toDel['slug'])) $paths[] = '/proje/'.$toDel['slug'];
  $paths[] = '/projeler';
  try { purge_pages($paths); } catch (Throwable $t) {}
  if (!empty($toDel['slug'])) { try { purge_page_cache('/proje/'.$toDel['slug']); } catch (Throwable $t) {} }
  try { purge_page_cache('/'); purge_page_cache('/projeler'); } catch (Throwable $t) {}
  $ok = 'Silindi.';
}

$items = Projects::adminAll();
?>
<h1 class="h4 fw-bold mb-4 d-flex justify-content-between align-items-center">
  Projeler
  <a class="btn btn-primary" href="<?= e(base_url('admin/proje-duzenle.php')) ?>">Yeni Proje</a>
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
        <?php foreach ($items as $p): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($p['title']) ?> <?php if ((int)($p['featured'] ?? 0)===1): ?><span class="badge bg-info">featured</span><?php endif; ?></div>
              <div class="small text-muted">/proje/<?= e($p['slug']) ?></div>
            </td>
            <td><span class="badge <?= $p['status']==='published'?'bg-success':'bg-secondary' ?>"><?= e($p['status']) ?></span></td>
            <td class="text-muted small"><?= e($p['published_at'] ? date('d.m.Y', strtotime((string)$p['published_at'])) : '-') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('admin/proje-duzenle.php?id='.$p['id'])) ?>">Düzenle</a>
              <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Silinsin mi?')" href="<?= e(base_url('admin/projeler.php?delete='.$p['id'].'&_csrf='.CSRF::token())) ?>">Sil</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
