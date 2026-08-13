<?php
// variables: $items, $top, $cats, $q, $cat
?>
<div class="container py-5">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
      <h1 class="display-6 fw-bold mb-1">Dökümanlar</h1>
      <div class="text-muted">Araçlar, dokümanlar ve paylaşımlar • SEO uyumlu indirme sayfaları</div>
    </div>
    <form class="d-flex gap-2" method="get" action="<?= e(base_url('dokumanlar')) ?>">
      <input class="form-control" name="q" value="<?= e($q ?? '') ?>" placeholder="Ara (başlık, etiket...)">
      <button class="btn btn-primary"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <?php if (!empty($cat)): ?>
        <div class="mb-3">
          <a class="text-decoration-none" href="<?= e(base_url('dokumanlar')) ?>">← Tüm dökümanlar</a>
          <span class="ms-2 badge" style="background:rgba(99,102,241,.20);border:1px solid rgba(99,102,241,.35);color:#c7d2fe;"><?= e($cat) ?></span>
        </div>
      <?php endif; ?>

      <div class="row g-3">
        <?php foreach (($items ?? []) as $it): ?>
          <div class="col-md-6">
            <a class="text-decoration-none" href="<?= e(base_url('dokuman/'.($it['slug'] ?? ''))) ?>">
              <div class="card h-100 service-card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="fw-bold"><?= e($it['title'] ?? '') ?></div>
                    <span class="badge" style="background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.14);"><?= (int)($it['download_count'] ?? 0) ?> indirme</span>
                  </div>
                  <div class="text-muted small mt-1"><?= e(mb_strimwidth((string)($it['description'] ?? ''), 0, 120, '…', 'UTF-8')) ?></div>
                  <div class="d-flex gap-2 mt-3 flex-wrap">
                    <?php if (!empty($it['category_name'])): ?>
                      <span class="badge bg-secondary"><?= e($it['category_name']) ?></span>
                    <?php endif; ?>
                    <span class="badge bg-dark"><?= e(pathinfo((string)($it['original_name'] ?? ''), PATHINFO_EXTENSION)) ?></span>
                    <span class="badge bg-dark"><?= e(Download::formatBytes((int)($it['size_bytes'] ?? 0))) ?></span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
          <div class="text-muted">Kayıt bulunamadı.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-3 mb-3">
        <div class="fw-bold mb-2">En Çok İndirilenler</div>
        <?php foreach (($top ?? []) as $t): ?>
          <a class="d-flex justify-content-between align-items-center text-decoration-none py-2 border-bottom" style="border-color:rgba(255,255,255,.10)!important;" href="<?= e(base_url('dokuman/'.($t['slug'] ?? ''))) ?>">
            <div class="me-2">
              <div class="fw-semibold"><?= e($t['title'] ?? '') ?></div>
              <div class="text-muted small"><?= e(Download::formatBytes((int)($t['size_bytes'] ?? 0))) ?> • <?= e(pathinfo((string)($t['original_name'] ?? ''), PATHINFO_EXTENSION)) ?></div>
            </div>
            <span class="badge" style="background:rgba(99,102,241,.20);border:1px solid rgba(99,102,241,.35);color:#c7d2fe;"><?= (int)($t['download_count'] ?? 0) ?></span>
          </a>
        <?php endforeach; ?>
        <?php if (empty($top)): ?><div class="text-muted small">Henüz veri yok.</div><?php endif; ?>
      </div>

      <div class="card p-3">
        <div class="fw-bold mb-2">Kategoriler</div>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach (($cats ?? []) as $c): ?>
            <a class="badge bg-dark text-decoration-none" href="<?= e(base_url('dokumanlar?cat='.urlencode((string)($c['category_slug'] ?? '')))) ?>">
              <?= e($c['category_name'] ?? '') ?> (<?= (int)($c['cnt'] ?? 0) ?>)
            </a>
          <?php endforeach; ?>
          <?php if (empty($cats)): ?><div class="text-muted small">Kategori yok.</div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
