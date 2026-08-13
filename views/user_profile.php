<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <div class="h4 fw-bold mb-0">Profil</div>
            <div class="text-muted">Merhaba, <?= e(UserAuth::name()) ?></div>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-light" href="<?= e(base_url('destek')) ?>"><i class="bi bi-life-preserver me-1"></i>Destek</a>
            <a class="btn btn-outline-light" href="<?= e(base_url('cikis')) ?>"><i class="bi bi-box-arrow-right me-1"></i>Çıkış</a>
          </div>
        </div>

        <?php if (!empty($formMsg)): ?>
          <div class="alert alert-<?= e($formType ?? 'info') ?>"><?= e($formMsg) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-2">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <div class="mb-3">
            <label class="form-label">Görünen Ad</label>
            <input class="form-control form-control-lg" name="display_name" value="<?= e(UserAuth::name()) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Yeni Şifre (opsiyonel)</label>
            <input class="form-control form-control-lg" type="password" name="new_password" minlength="8" placeholder="********">
            <div class="small text-muted mt-1">Boş bırakırsanız şifre değişmez.</div>
          </div>
          <button class="btn btn-primary btn-lg"><i class="bi bi-save me-1"></i>Kaydet</button>
        </form>

        <hr class="my-4">

        <?php
          $tc = $ticketCounts ?? ['all'=>0,'open'=>0,'pending'=>0,'closed'=>0];
          $openCount = (int)($tc['open'] ?? 0);
          $allCount  = (int)($tc['all'] ?? 0);
        ?>

        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
          <div>
            <div class="fw-bold">Benim Ticketlarım</div>
            <div class="text-muted small">Destek taleplerini buradan yönetebilirsin.</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill" style="background:rgba(99,102,241,.14);border:1px solid rgba(99,102,241,.24);color:#4f46e5;">Açık: <?= (int)$openCount ?></span>
            <span class="badge rounded-pill bg-light text-dark border">Toplam: <?= (int)$allCount ?></span>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('destek')) ?>">Ticketlarımı Gör</a>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="badge bg-light text-dark border">Açık: <?= (int)($tc['open'] ?? 0) ?></span>
          <span class="badge bg-light text-dark border">Beklemede: <?= (int)($tc['pending'] ?? 0) ?></span>
          <span class="badge bg-light text-dark border">Kapalı: <?= (int)($tc['closed'] ?? 0) ?></span>
        </div>

        <div class="fw-bold mt-3">Yorumlarım</div>
        <div class="text-muted small mb-2">Gönderdiğin blog yorumları (onay durumuyla)</div>

<div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Blog</th>
                <th>Durum</th>
                <th class="text-end">Tarih</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($myComments ?? []) as $c): ?>
                <tr>
                  <td>
                    <a class="text-decoration-none fw-bold" href="<?= e(base_url('blog/' . ($c['post_slug'] ?? ''))) ?>" target="_blank"><?= e($c['post_title'] ?? 'Blog') ?></a>
                    <div class="text-muted small"><?= e(mb_strimwidth((string)($c['content'] ?? ''), 0, 90, '…', 'UTF-8')) ?></div>
                  </td>
                  <td>
                    <?php $s=(string)($c['status'] ?? 'pending'); ?>
                    <?php if ($s==='approved'): ?><span class="badge bg-success">Onaylandı</span><?php endif; ?>
                    <?php if ($s==='pending'): ?><span class="badge bg-warning text-dark">Onay Bekliyor</span><?php endif; ?>
                    <?php if ($s==='rejected'): ?><span class="badge bg-danger">Reddedildi</span><?php endif; ?>
                  </td>
                  <td class="text-end text-muted small"><?= e($c['created_at'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($myComments)): ?>
                <tr><td colspan="3" class="text-muted">Henüz yorum yok.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>
