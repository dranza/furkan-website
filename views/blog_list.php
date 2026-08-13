<?php
$cat = $filters['category'] ?? '';
$tag = $filters['tag'] ?? '';
$q   = $filters['q'] ?? '';
?>
<div class="mb-3">
  <h1 class="h3 section-title mb-1">Blog</h1>
  <div class="section-subtitle">Paylaşımlar ve teknik yazılar</div>
</div>

<div class="bg-white border rounded-4 shadow-sm p-3 mb-3">
  <form class="row g-2 align-items-end" method="get" action="<?= e(base_url('blog')) ?>">
    <div class="col-md-4">
      <label class="form-label small text-muted mb-1">Ara</label>
      <input class="form-control" name="q" placeholder="Başlık / içerik ara..." value="<?= e($q) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Kategori</label>
      <select class="form-select" name="cat">
        <option value="">Tümü</option>
        <?php foreach (($categories ?? []) as $c): ?>
          <option value="<?= e($c) ?>" <?= $c===$cat?'selected':'' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Etiket</label>
      <input class="form-control" name="tag" placeholder="örn: hl7" value="<?= e($tag) ?>">
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-primary">Filtrele</button>
    </div>
  </form>

  <?php if (!empty($topTags)): ?>
    <div class="mt-3 d-flex flex-wrap gap-2">
      <?php foreach ($topTags as $t): ?>
        <?php $tname = $t['tag']; ?>
        <a class="badge bg-light text-dark border text-decoration-none"
           href="<?= e(base_url('blog?tag='.urlencode($tname).($cat?'&cat='.urlencode($cat):'').($q?'&q='.urlencode($q):''))) ?>">
          #<?= e($tname) ?> (<?= (int)$t['count'] ?>)
        </a>
      <?php endforeach; ?>
      <?php if ($cat || $tag || $q): ?>
        <a class="badge bg-white border text-decoration-none" href="<?= e(base_url('blog')) ?>">Temizle</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php if (!$data['items']): ?>
  <div class="bg-white border rounded-4 shadow-sm p-4">Sonuç bulunamadı.</div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($data['items'] as $b): ?>
      <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
          <?php if (!empty($b['cover_image'])): ?>
            <img src="<?= e(base_url($b['cover_image'])) ?>" class="card-img-top" alt="<?= e($b['title']) ?>">
          <?php endif; ?>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="text-muted small"><?= e(date('d.m.Y', strtotime((string)$b['published_at']))) ?></div>
              <?php if (!empty($b['category'])): ?><span class="badge bg-light text-dark border"><?= e($b['category']) ?></span><?php endif; ?>
            </div>
            <h2 class="h6 fw-bold"><?= e($b['title']) ?></h2>
            <?php if (!empty($b['tags'])): ?><div class="small text-muted"><?= e($b['tags']) ?></div><?php endif; ?>
            <a href="<?= e(base_url('blog/'.$b['slug'])) ?>" class="stretched-link"></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php
    $totalPages = (int)ceil($data['total'] / $data['per']);
    $p = $data['page'];
    $qs = [];
    if ($cat) $qs['cat'] = $cat;
    if ($tag) $qs['tag'] = $tag;
    if ($q) $qs['q'] = $q;
  ?>
  <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
          <?php $qs2 = $qs; $qs2['page'] = $i; ?>
          <li class="page-item <?= $i===$p?'active':'' ?>">
            <a class="page-link" href="<?= e(base_url('blog?'.http_build_query($qs2))) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>
