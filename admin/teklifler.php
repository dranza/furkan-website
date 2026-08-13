<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/models/Offer.php';

$pdo = DB::pdo();

$q = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? 'all');

$where = [];
$params = [];

if ($status !== 'all') {
  $where[] = "o.status = ?";
  $params[] = $status;
}

if ($q !== '') {
  $where[] = "(o.offer_no LIKE ? OR o.title LIKE ? OR o.customer_company LIKE ? OR o.customer_name LIKE ? OR u.username LIKE ? OR u.display_name LIKE ?)";
  $like = '%' . $q . '%';
  array_push($params, $like, $like, $like, $like, $like, $like);
}

$sql = "SELECT o.*, u.username, u.display_name
        FROM offers o
        JOIN users u ON u.id=o.user_id";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY o.id DESC LIMIT 300";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

function st_badge(string $s): string {
  return match($s) {
    'sent' => '<span class="badge bg-info text-dark">Gönderildi</span>',
    'approved' => '<span class="badge bg-success">Onaylandı</span>',
    'rejected' => '<span class="badge bg-danger">Reddedildi</span>',
    default => '<span class="badge bg-secondary">Taslak</span>'
  };
}
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h1 class="h4 fw-bold mb-1">Teklifler</h1>
    <div class="text-muted">Tüm firmaların teklifleri • filtrele • detay</div>
  </div>
</div>

<form class="row g-2 mb-3">
  <div class="col-md-6">
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Teklif no, başlık, müşteri, kullanıcı...">
  </div>
  <div class="col-md-3">
    <select class="form-select" name="status">
      <option value="all" <?= $status==='all'?'selected':'' ?>>Tümü</option>
      <option value="draft" <?= $status==='draft'?'selected':'' ?>>Taslak</option>
      <option value="sent" <?= $status==='sent'?'selected':'' ?>>Gönderildi</option>
      <option value="approved" <?= $status==='approved'?'selected':'' ?>>Onaylandı</option>
      <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Reddedildi</option>
    </select>
  </div>
  <div class="col-md-3 d-grid">
    <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrele</button>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Teklif No</th>
          <th>Başlık</th>
          <th>Firma (Kullanıcı)</th>
          <th>Müşteri</th>
          <th>Durum</th>
          <th class="text-end">Tarih</th>
          <th class="text-end">İşlem</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-muted">#<?= (int)$r['id'] ?></td>
            <td class="fw-semibold"><?= e((string)$r['offer_no']) ?></td>
            <td><?= e((string)$r['title']) ?></td>
            <td>
              <?= e((string)($r['display_name'] ?? '')) ?>
              <div class="small text-muted">@<?= e((string)($r['username'] ?? '')) ?></div>
            </td>
            <td>
              <?= e((string)($r['customer_company'] ?? '')) ?>
              <div class="small text-muted"><?= e((string)($r['customer_name'] ?? '')) ?></div>
            </td>
            <td><?= st_badge((string)($r['status'] ?? 'draft')) ?></td>
            <td class="text-end text-muted"><?= e((string)($r['created_at'] ?? '')) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('admin/teklif-detay.php?id='.(int)$r['id'])) ?>"><i class="bi bi-eye"></i></a>
              <a class="btn btn-sm btn-outline-light" target="_blank" href="<?= e(base_url('teklif/'.($r['public_code'] ?? ''))) ?>"><i class="bi bi-box-arrow-up-right"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Kayıt yok.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
