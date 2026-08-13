<?php
// variables: $item, $top
?>
<div class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <a class="text-decoration-none" href="<?= e(base_url('dokumanlar')) ?>">← Dökümanlara dön</a>

      <div class="mt-3 card p-4">
        <h1 class="h3 fw-bold mb-1"><?= e($item['title'] ?? '') ?></h1>
        <div class="text-muted mb-3">
          <?php if (!empty($item['category_name'])): ?>
            <span class="badge bg-secondary me-1"><?= e($item['category_name']) ?></span>
          <?php endif; ?>
          <span class="badge bg-dark me-1"><?= e(pathinfo((string)($item['original_name'] ?? ''), PATHINFO_EXTENSION)) ?></span>
          <span class="badge bg-dark me-1"><?= e(Download::formatBytes((int)($item['size_bytes'] ?? 0))) ?></span>
          <span class="badge" style="background:rgba(99,102,241,.20);border:1px solid rgba(99,102,241,.35);color:#c7d2fe;"><?= (int)($item['download_count'] ?? 0) ?> indirme</span>
        </div>

        <?php if (!empty($item['description'])): ?>
          <div class="text-muted mb-4" style="white-space:pre-line;"><?= e($item['description']) ?></div>
        <?php endif; ?>

        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-primary" href="<?= e(base_url('indir/'.($item['slug'] ?? ''))) ?>"><i class="bi bi-download me-1"></i>İndir</a>
          <a class="btn btn-outline-light" href="<?= e(base_url('dokumanlar')) ?>"><i class="bi bi-collection me-1"></i>Tüm Dökümanlar</a>
        </div>

        <?php if (!empty($item['tags'])): ?>
          <div class="mt-4">
            <div class="small text-muted mb-2">Etiketler</div>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach (array_filter(array_map('trim', explode(',', (string)$item['tags']))) as $tg): ?>
                <span class="badge bg-dark"><?= e($tg) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="mt-3 small text-muted">
        <i class="bi bi-shield-check me-1"></i>Dosyalar indirme endpoint’i üzerinden “attachment” olarak servis edilir.
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-3">
        <div class="fw-bold mb-2">En Çok İndirilenler</div>
        <?php foreach (($top ?? []) as $t): ?>
          <a class="d-flex justify-content-between align-items-center text-decoration-none py-2 border-bottom" style="border-color:rgba(255,255,255,.10)!important;" href="<?= e(base_url('dokuman/'.($t['slug'] ?? ''))) ?>">
            <div class="me-2">
              <div class="fw-semibold"><?= e($t['title'] ?? '') ?></div>
              <div class="text-muted small"><?= e(Download::formatBytes((int)($t['size_bytes'] ?? 0))) ?></div>
            </div>
            <span class="badge" style="background:rgba(99,102,241,.20);border:1px solid rgba(99,102,241,.35);color:#c7d2fe;"><?= (int)($t['download_count'] ?? 0) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
