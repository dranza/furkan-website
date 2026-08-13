<?php
$tech = $filters['tech'] ?? '';
$q    = $filters['q'] ?? '';
?>
<div class="mb-3">
  <h1 class="h3 section-title mb-1">Projeler</h1>
  <div class="section-subtitle">Yürütülen çalışmalar ve çıktılar</div>
</div>

<div class="bg-white border rounded-4 shadow-sm p-3 mb-3">
  <form class="row g-2 align-items-end" method="get" action="<?= e(base_url('projeler')) ?>">
    <div class="col-md-5">
      <label class="form-label small text-muted mb-1">Ara</label>
      <input class="form-control" name="q" placeholder="Proje adı / içerik ara..." value="<?= e($q) ?>">
    </div>
    <div class="col-md-5">
      <label class="form-label small text-muted mb-1">Teknoloji</label>
      <input class="form-control" name="tech" placeholder="örn: hl7, vmware, sql" value="<?= e($tech) ?>">
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-primary">Filtrele</button>
    </div>
  </form>

  <?php if (!empty($topTech)): ?>
    <div class="mt-3 d-flex flex-wrap gap-2">
      <?php foreach ($topTech as $t): ?>
        <?php $tname = $t['tech']; ?>
        <a class="badge bg-light text-dark border text-decoration-none"
           href="<?= e(base_url('projeler?tech='.urlencode($tname).($q?'&q='.urlencode($q):''))) ?>">
          <?= e($tname) ?> (<?= (int)$t['count'] ?>)
        </a>
      <?php endforeach; ?>
      <?php if ($tech || $q): ?>
        <a class="badge bg-white border text-decoration-none" href="<?= e(base_url('projeler')) ?>">Temizle</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php if (!$data['items']): ?>
  <div class="bg-white border rounded-4 shadow-sm p-4">Sonuç bulunamadı.</div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($data['items'] as $p): ?>
      <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
          <?php if (!empty($p['cover_image'])): ?>
            <img src="<?= e(base_url($p['cover_image'])) ?>" class="card-img-top" alt="<?= e($p['title']) ?>">
          <?php endif; ?>
          <div class="card-body">
            <div class="text-muted small mb-1"><?= e(date('d.m.Y', strtotime((string)$p['published_at']))) ?></div>
            <h2 class="h6 fw-bold"><?= e($p['title']) ?></h2>
            <?php if (!empty($p['technologies'])): ?><div class="small text-muted"><?= e($p['technologies']) ?></div><?php endif; ?>
            <p class="mt-2 mb-0 text-muted"><?= e(mb_substr((string)$p['summary'], 0, 140)) ?><?= mb_strlen((string)$p['summary'])>140?'…':'' ?></p>
            <a href="<?= e(base_url('proje/'.$p['slug'])) ?>" class="stretched-link"></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php
    $totalPages = (int)ceil($data['total'] / $data['per']);
    $pg = $data['page'];
    $qs = [];
    if ($tech) $qs['tech'] = $tech;
    if ($q) $qs['q'] = $q;
  ?>
  <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
          <?php $qs2 = $qs; $qs2['page'] = $i; ?>
          <li class="page-item <?= $i===$pg?'active':'' ?>">
            <a class="page-link" href="<?= e(base_url('projeler?'.http_build_query($qs2))) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>
