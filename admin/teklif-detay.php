<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/models/Offer.php';
require_once __DIR__ . '/../app/models/FirmProfile.php';

$pdo = DB::pdo();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo 'Geçersiz.'; exit; }

$st = $pdo->prepare("SELECT o.*, u.username, u.display_name FROM offers o JOIN users u ON u.id=o.user_id WHERE o.id=? LIMIT 1");
$st->execute([$id]);
$offer = $st->fetch(PDO::FETCH_ASSOC);
if (!$offer) { echo 'Bulunamadı.'; exit; }

$offer['items'] = Offer::items((int)$offer['id']);
$firm = FirmProfile::get((int)$offer['user_id']);

$msg = null; $msgType='info';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $ns = (string)($_POST['status'] ?? 'draft');
  if (!in_array($ns, ['draft','sent','approved','rejected'], true)) $ns = 'draft';

  $pdo->prepare("UPDATE offers SET status=?,
      sent_at = IF(?='sent' AND (sent_at IS NULL), NOW(), sent_at),
      decided_at = IF((?='approved' OR ?='rejected') AND (decided_at IS NULL), NOW(), decided_at)
    WHERE id=?")
    ->execute([$ns,$ns,$ns,$ns,(int)$offer['id']]);

  $msg='Durum güncellendi.'; $msgType='success';

  $st = $pdo->prepare("SELECT o.*, u.username, u.display_name FROM offers o JOIN users u ON u.id=o.user_id WHERE o.id=? LIMIT 1");
  $st->execute([$id]);
  $offer = $st->fetch(PDO::FETCH_ASSOC);
  $offer['items'] = Offer::items((int)$offer['id']);
  $firm = FirmProfile::get((int)$offer['user_id']);
}

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
    <h1 class="h4 fw-bold mb-1">Teklif Detayı</h1>
    <div class="text-muted"><?= e((string)$offer['offer_no']) ?> • <?= st_badge((string)($offer['status'] ?? 'draft')) ?></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('admin/teklifler.php')) ?>"><i class="bi bi-arrow-left"></i> Liste</a>
    <a class="btn btn-primary btn-sm" target="_blank" href="<?= e(base_url('teklif/'.($offer['public_code'] ?? ''))) ?>"><i class="bi bi-printer"></i> Yazdır/PDF</a>
  </div>
</div>

<?php if ($msg): ?>
  <div class="alert alert-<?= e($msgType) ?>"><?= e($msg) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold mb-2"><?= e((string)($offer['title'] ?? '')) ?></div>
        <div class="text-muted small mb-3">
          Firma: <?= e((string)($firm['company_name'] ?? '')) ?> • @<?= e((string)($offer['username'] ?? '')) ?>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Kalem</th><th class="text-end">Adet</th><th class="text-end">Birim</th><th class="text-end">KDV%</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($offer['items'] ?? []) as $it): ?>
                <tr>
                  <td><?= e((string)$it['name']) ?></td>
                  <td class="text-end"><?= e((string)$it['qty']) ?></td>
                  <td class="text-end"><?= e((string)$it['unit_price']) ?></td>
                  <td class="text-end"><?= e((string)$it['vat_rate']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($offer['items'])): ?>
                <tr><td colspan="4" class="text-center text-muted">Kalem yok.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="small text-muted">Not: Toplamlar teklif görüntüleme sayfasında otomatik hesaplanır.</div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold mb-2">Durum</div>
        <form method="post">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <select class="form-select" name="status">
            <?php foreach (['draft'=>'Taslak','sent'=>'Gönderildi','approved'=>'Onaylandı','rejected'=>'Reddedildi'] as $k=>$v): ?>
              <option value="<?= e($k) ?>" <?= ((string)($offer['status'] ?? 'draft')===$k)?'selected':'' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary w-100 mt-2"><i class="bi bi-save me-1"></i>Güncelle</button>
        </form>

        <hr>
        <div class="small text-muted">
          Oluşturma: <?= e((string)($offer['created_at'] ?? '')) ?><br>
          Gönderim: <?= e((string)($offer['sent_at'] ?? '-')) ?><br>
          Karar: <?= e((string)($offer['decided_at'] ?? '-')) ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
