<?php
require_once __DIR__ . '/_layout_top.php';

Auth::requireLogin();

$pdo = DB::pdo();
$scheduledBlogs = $pdo->query("SELECT id,title,slug,published_at,status FROM blog_posts WHERE published_at IS NOT NULL AND published_at > NOW() ORDER BY published_at ASC LIMIT 100")->fetchAll();
$scheduledProjects = $pdo->query("SELECT id,title,slug,published_at,status FROM projects WHERE published_at IS NOT NULL AND published_at > NOW() ORDER BY published_at ASC LIMIT 100")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Takvim</h1>
    <div class="text-muted">Planlı yayınlar</div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-journal-text me-1"></i>Planlı Blog</div>
      <?php if (!$scheduledBlogs): ?><div class="text-muted">Yok.</div><?php endif; ?>
      <div class="vstack gap-2">
        <?php foreach ($scheduledBlogs as $b): ?>
          <div class="border rounded-4 p-2">
            <div class="d-flex justify-content-between">
              <div class="fw-semibold"><?= e($b['title']) ?></div>
              <div class="small text-muted"><?= e(date('d.m.Y H:i', strtotime((string)$b['published_at']))) ?></div>
            </div>
            <div class="small text-muted">/blog/<?= e($b['slug']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-kanban me-1"></i>Planlı Proje</div>
      <?php if (!$scheduledProjects): ?><div class="text-muted">Yok.</div><?php endif; ?>
      <div class="vstack gap-2">
        <?php foreach ($scheduledProjects as $p): ?>
          <div class="border rounded-4 p-2">
            <div class="d-flex justify-content-between">
              <div class="fw-semibold"><?= e($p['title']) ?></div>
              <div class="small text-muted"><?= e(date('d.m.Y H:i', strtotime((string)$p['published_at']))) ?></div>
            </div>
            <div class="small text-muted">/proje/<?= e($p['slug']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
