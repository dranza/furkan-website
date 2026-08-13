<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <div>
      <div class="h3 fw-bold mb-1">Destek Talepleri</div>
      <div class="text-muted">Yeni bir ticket oluşturabilir veya mevcut ticket'larını görüntüleyebilirsin.</div>
    </div>
    <a class="btn btn-outline-primary" href="<?= e(base_url('profil')) ?>"><i class="bi bi-person-circle me-1"></i>Profil</a>
  </div>

  <?php $sf = $statusFilter ?? 'all'; $counts = $counts ?? ['all'=>0,'open'=>0,'pending'=>0,'closed'=>0]; $q = $q ?? ''; $qParam = $q!=='' ? ('&q=' . urlencode($q)) : ''; ?>
<div class="d-flex flex-wrap gap-2 mb-3 ticket-filter-bar">
  <a class="btn btn-sm <?= $sf==='all'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('destek' . ($q!==''?('?q=' . urlencode($q)):'') )) ?>">
    Tümü <span class="badge bg-primary ms-1"><?= (int)$counts['all'] ?></span>
  </a>
  <a class="btn btn-sm <?= $sf==='open'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('destek?status=open' . $qParam)) ?>">
    Açık <span class="badge bg-primary ms-1"><?= (int)$counts['open'] ?></span>
  </a>
  <a class="btn btn-sm <?= $sf==='pending'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('destek?status=pending' . $qParam)) ?>">
    Beklemede <span class="badge bg-primary ms-1"><?= (int)$counts['pending'] ?></span>
  </a>
  <a class="btn btn-sm <?= $sf==='closed'?'btn-primary':'btn-outline-primary' ?>" href="<?= e(base_url('destek?status=closed' . $qParam)) ?>">
    Kapalı <span class="badge bg-primary ms-1"><?= (int)$counts['closed'] ?></span>
  </a>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <form class="d-flex gap-2 flex-grow-1" method="get" action="<?= e(base_url('destek')) ?>">
    <input type="hidden" name="status" value="<?= e($sf) ?>">
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Ticket ara (Konu, kategori, öncelik, #ID)">
      <button class="btn btn-outline-primary">Ara</button>
      <?php if ($q !== ''): ?>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('destek?status=' . urlencode($sf))) ?>">Temizle</a>
      <?php endif; ?>
    </div>
  </form>
  <div class="text-muted small">Toplam sonuç: <b><?= (int)($total ?? count($tickets ?? [])) ?></b></div>
</div>

<?php if (!empty($formMsg)): ?>
    <div class="alert alert-<?= e($formType ?? 'info') ?>"><?= e($formMsg) ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card p-3">
        <div class="fw-bold mb-2"><i class="bi bi-plus-circle me-1"></i>Yeni Ticket</div>
        <form method="post">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <label class="form-label">Konu</label>
          <input class="form-control mb-2" name="subject" placeholder="Örn: HL7 entegrasyon sorunu" required>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Kategori</label>
              <select class="form-select mb-2" name="category">
                <option value="entegrasyon">Entegrasyon</option>
                <option value="raporlama">Raporlama</option>
                <option value="itsm">ITSM</option>
                <option value="guvenlik">Güvenlik</option>
                <option value="sistem">Sistem</option>
                <option value="diger" selected>Diğer</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Öncelik</label>
              <select class="form-select mb-2" name="priority">
                <option value="low">Düşük</option>
                <option value="normal" selected>Normal</option>
                <option value="high">Yüksek</option>
                <option value="urgent">Acil</option>
              </select>
            </div>
          </div>

          <label class="form-label">Mesaj</label>
          <textarea class="form-control mb-2" name="message" rows="5" placeholder="Detayları yaz..." required></textarea>

          <button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Gönder</button>
        </form>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card p-3">
        <div class="fw-bold mb-2"><i class="bi bi-inbox me-1"></i>Ticket Listesi</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Konu</th>
                <th>Durum</th>
                <th class="text-end">Güncelleme</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): ?>
                <tr>
                  <td><?= (int)$t['id'] ?></td>
                  <td>
                    <a class="text-decoration-none fw-bold" href="<?= e(base_url('destek/' . (int)$t['id'])) ?>"><?= e($t['subject']) ?></a>
                    <div class="text-muted small"><?= e(($t['category'] ?? '') . ' • ' . ($t['priority'] ?? '')) ?></div>
                    <?php if (!empty($t['last_message'])): ?>
                      <div class="text-muted small"><?= e(mb_strimwidth((string)$t['last_message'], 0, 90, '…', 'UTF-8')) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php $s=(string)$t['status']; ?>
                    <?php if ($s==='open'): ?><span class="badge bg-success">Açık</span><?php endif; ?>
                    <?php if ($s==='pending'): ?><span class="badge bg-warning text-dark">Beklemede</span><?php endif; ?>
                    <?php if ($s==='closed'): ?><span class="badge bg-secondary">Kapalı</span><?php endif; ?>
                  </td>
                  <td class="text-end text-muted small"><?= e($t['updated_at'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($tickets)): ?>
                <tr><td colspan="4" class="text-muted">Henüz ticket yok.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        

<?php
  $page = (int)($page ?? 1);
  $pages = (int)($pages ?? 1);
  $base = base_url('destek');
  $qsBase = 'status=' . urlencode((string)$sf) . ($q!=='' ? ('&q=' . urlencode($q)) : '');
?>

<?php if ($pages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-end mb-0">
      <li class="page-item <?= $page<=1?'disabled':'' ?>">
        <a class="page-link" href="<?= e($base . '?' . $qsBase . '&page=' . max(1, $page-1)) ?>">Önce</a>
      </li>
      <?php
        $start = max(1, $page - 2);
        $end = min($pages, $page + 2);
        if ($start > 1) {
          echo '<li class="page-item"><a class="page-link" href="' . e($base . '?' . $qsBase . '&page=1') . '">1</a></li>';
          if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        for ($i=$start; $i<=$end; $i++) {
          $active = $i===$page ? 'active' : '';
          echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . e($base . '?' . $qsBase . '&page=' . $i) . '">' . $i . '</a></li>';
        }
        if ($end < $pages) {
          if ($end < $pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
          echo '<li class="page-item"><a class="page-link" href="' . e($base . '?' . $qsBase . '&page=' . $pages) . '">' . $pages . '</a></li>';
        }
      ?>
      <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
        <a class="page-link" href="<?= e($base . '?' . $qsBase . '&page=' . min($pages, $page+1)) ?>">Sonra</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

</div>
      </div>
      <div class="small text-muted mt-2">Not: Bu alan sadece giriş yapan kullanıcılar içindir.</div>
    </div>
  </div>
</div>
