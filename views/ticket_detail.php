<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <div>
      <div class="h3 fw-bold mb-1">Ticket #<?= (int)$ticket['id'] ?> — <?= e($ticket['subject']) ?></div>
      <?php
  $st = (string)($ticket['status'] ?? 'open');
  $statusBadge = $st==='open' ? '<span class="badge bg-success">Açık</span>' : ($st==='pending' ? '<span class="badge bg-warning text-dark">Beklemede</span>' : '<span class="badge bg-secondary">Kapalı</span>');
  $prio = (string)($ticket['priority'] ?? 'normal');
  $prioBadge = $prio==='urgent' ? '<span class="badge bg-danger">Acil</span>' : ($prio==='high' ? '<span class="badge bg-warning text-dark">Yüksek</span>' : ($prio==='low' ? '<span class="badge bg-light text-dark border">Düşük</span>' : '<span class="badge bg-primary">Normal</span>'));
?>
<div class="text-muted">Durum: <?= $statusBadge ?> • Kategori: <span class="badge bg-light text-dark border"><?= e($ticket['category']) ?></span> • Öncelik: <?= $prioBadge ?></div>
    </div>
    <div class="d-flex gap-2">
  <?php if (($ticket['status'] ?? '') !== 'closed'): ?>
    <form method="post" class="m-0">
      <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
      <input type="hidden" name="action" value="close">
      <button class="btn btn-outline-secondary" onclick="return confirm('Ticket kapatılsın mı?')"><i class="bi bi-check2-circle me-1"></i>Kapat</button>
    </form>
  <?php else: ?>
    <form method="post" class="m-0">
      <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
      <input type="hidden" name="action" value="reopen">
      <button class="btn btn-outline-success"><i class="bi bi-arrow-repeat me-1"></i>Tekrar Aç</button>
    </form>
  <?php endif; ?>
  <a class="btn btn-outline-primary" href="<?= e(base_url('destek')) ?>"><i class="bi bi-arrow-left me-1"></i>Geri dön</a>
</div>
  </div>

  <?php if (!empty($formMsg)): ?>
    <div class="alert alert-<?= e($formType ?? 'info') ?>"><?= e($formMsg) ?></div>
  <?php endif; ?>

  <div class="card p-3">
    <?php foreach ($messages as $m): ?>
      <?php $isAdmin = ($m['sender_role'] ?? '') === 'admin'; ?>
      <div class="d-flex gap-2 mb-3">
        <div class="flex-shrink-0">
          <?php
            $photo = '';
            if ($isAdmin) {
              $photo = (string)($adminPhoto ?? '');
            }
            $photoUrl = '';
            if ($photo !== '') {
              $photoUrl = preg_match('~^https?://~', $photo) ? $photo : base_url(ltrim($photo,'/'));
            }
          ?>
          <div class="rounded-circle d-flex align-items-center justify-content-center overflow-hidden" style="width:38px;height:38px;background:<?= $isAdmin ? '#0d6efd22' : '#19875422' ?>;border:1px solid rgba(0,0,0,.08);">
            <?php if ($isAdmin && $photoUrl): ?>
              <img src="<?= e($photoUrl) ?>" alt="Admin" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
              <i class="bi <?= $isAdmin ? 'bi-shield-check' : 'bi-person' ?>"></i>
            <?php endif; ?>
          </div>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold"><?= $isAdmin ? 'Admin' : 'Sen' ?></div>
            <div class="text-muted small"><?= e($m['created_at'] ?? '') ?></div>
          </div>
          <div class="mt-1" style="white-space:pre-wrap;"><?= e($m['message'] ?? '') ?></div>
        </div>
      </div>
    <?php endforeach; ?>
    <div id="son"></div>

    <hr class="my-3">

    <?php if (($ticket['status'] ?? '') === 'closed'): ?>
      <div class="alert alert-secondary mb-0">Bu ticket kapatıldı. Yeni talep için <a href="<?= e(base_url('destek')) ?>">yeni ticket</a> aç.</div>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <label class="form-label">Yanıt yaz</label>
        <textarea class="form-control mb-2" name="reply" rows="4" required></textarea>
        <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Gönder</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="container pb-5" style="max-width:980px;">
  <a class="btn btn-link p-0" href="<?= e(base_url('destek')) ?>"><i class="bi bi-arrow-left me-1"></i>Destek listesine dön</a>
</div>
