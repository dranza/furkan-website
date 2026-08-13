<div class="row g-4">
  <div class="col-lg-7">
    <div class="bg-white border rounded-4 shadow-sm p-4">
      <h1 class="h3 section-title mb-1">Hakkımda</h1>
      <div class="text-muted mb-3"><?= e(Settings::get('about_short','Kısa özet alanı (admin panelden değiştirilebilir).') ?? '') ?></div>
      <div class="prose"><?= $aboutText ?></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="bg-white border rounded-4 shadow-sm p-4 mb-4">
      <h2 class="h6 fw-bold mb-3">Eğitim</h2>
      <?php if (!$education): ?>
        <div class="text-muted">Henüz eğitim bilgisi yok.</div>
      <?php else: ?>
        <ul class="mb-0">
          <?php foreach ($education as $ed): ?>
            <li class="mb-2">
              <div class="fw-semibold"><?= e($ed['university']) ?></div>
              <div class="small text-muted"><?= e($ed['department']) ?> • <?= e((string)$ed['start_year']) ?> - <?= e((string)$ed['end_year']) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="bg-white border rounded-4 shadow-sm p-4">
      <h2 class="h6 fw-bold mb-3">Deneyim</h2>
      <?php if (!$experience): ?>
        <div class="text-muted">Henüz deneyim bilgisi yok.</div>
      <?php else: ?>
        <ul class="mb-0">
          <?php foreach ($experience as $ex): ?>
            <li class="mb-2">
              <div class="fw-semibold"><?= e($ex['company']) ?></div>
              <div class="small text-muted"><?= e($ex['role']) ?> • <?= e((string)$ex['start_year']) ?> - <?= e((string)$ex['end_year']) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

<div class="bg-white border rounded-4 shadow-sm p-4 mb-4">
  <h2 class="h6 fw-bold mb-3">Beceriler</h2>
  <?php if (empty($skills)): ?>
    <div class="text-muted">Henüz beceri eklenmedi.</div>
  <?php else: ?>
    <div class="d-flex flex-column gap-3">
      <?php foreach ($skills as $s): ?>
        <div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-semibold"><?= e($s['name'] ?? '') ?></div>
            <?php if ((int)($s['level'] ?? 0) > 0): ?>
              <span class="badge bg-primary"><?= (int)$s['level'] ?>%</span>
            <?php endif; ?>
          </div>
          <?php if ((int)($s['level'] ?? 0) > 0): ?>
            <div class="progress mt-2" style="height:8px;">
              <div class="progress-bar" role="progressbar" style="width: <?= (int)$s['level'] ?>%"></div>
            </div>
          <?php endif; ?>
          <?php if (!empty($s['tags'])): ?>
            <div class="text-muted small mt-1"><?= e($s['tags']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="bg-white border rounded-4 shadow-sm p-4">
  <h2 class="h6 fw-bold mb-3">Sertifikalar</h2>
  <?php if (empty($certs)): ?>
    <div class="text-muted">Henüz sertifika eklenmedi.</div>
  <?php else: ?>
    <div class="d-flex flex-column gap-3">
      <?php foreach ($certs as $c): ?>
        <div class="d-flex gap-3 align-items-start">
          <?php if (!empty($c['logo_path'])): ?>
            <img src="<?= e(base_url($c['logo_path'])) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:12px;border:1px solid rgba(0,0,0,.08)">
          <?php else: ?>
            <div style="width:44px;height:44px;border-radius:12px;border:1px solid rgba(0,0,0,.08)" class="d-flex align-items-center justify-content-center text-muted">🏅</div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <div class="fw-semibold"><?= e($c['title'] ?? '') ?></div>
            <div class="text-muted small">
              <?= e($c['issuer'] ?? '') ?><?= ((int)($c['issue_year'] ?? 0) ? ' • ' . (int)$c['issue_year'] : '') ?>
            </div>
            <?php if (!empty($c['description'])): ?>
                  <div class="text-muted small mt-1"><?= e($c['description']) ?></div>
                <?php endif; ?>
                <?php if (!empty($c['file_path'])): ?>
                  <a class="small text-decoration-none d-inline-block mt-1" href="<?= e(base_url($c['file_path'])) ?>" target="_blank" rel="noopener">Dosya (PDF)</a>
                <?php endif; ?>
                <?php if (!empty($c['credential_url'])): ?>
              <a class="small text-decoration-none" href="<?= e($c['credential_url']) ?>" target="_blank" rel="noopener">Kimlik / Doğrulama</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

  </div>
</div>

<style>
.prose img{max-width:100%; height:auto;}
.prose h2{margin-top:1.2rem;}
.prose p{margin-bottom:.8rem;}
</style>
