<?php $csrf = CSRF::token();
  $stBadge = function(string $s): string {
    return match($s){
      'sent'=>'<span class="badge bg-info text-dark border">Gönderildi</span>',
      'approved'=>'<span class="badge bg-success">Onaylandı</span>',
      'rejected'=>'<span class="badge bg-danger">Reddedildi</span>',
      default=>'<span class="badge bg-secondary">Taslak</span>'
    };
  };
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
  <div>
    <h1 class="fw-bold mb-1">Teklif Oluştur</h1>
    <div class="text-muted">Tekliflerini yönet. Ürün, indirim ve KDV hesapları otomatik yapılır.</div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= e(base_url('firma-profili')) ?>"><i class="bi bi-building me-1"></i>Firma Profili</a>
    <a class="btn btn-primary" href="<?= e(base_url('araclar/teklif-olustur/yeni')) ?>"><i class="bi bi-plus-circle me-1"></i>Yeni teklif</a>
  </div>
</div>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= e($flashType) ?>"><?= e($flash) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <?php if (empty($offers)): ?>
      <div class="text-muted">Henüz teklif yok. “Yeni teklif” ile başlayabilirsin.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Teklif</th>
              <th>Müşteri</th>
              <th>Para Birimi</th>
              <th class="text-nowrap">Tarih</th>
              <th class="text-end">İşlem</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($offers as $o): ?>
            <tr>
              <td class="min-w-0">
                <div class="fw-semibold text-truncate" style="max-width: 380px;" title="<?= e($o['title'] ?? '') ?>">
                  <?= e($o['title'] ?? 'Teklif') ?>
                </div>
                <div class="small text-muted d-flex flex-wrap gap-2 align-items-center">
                  <span><?= e($o['offer_no'] ?? '') ?></span>
                  <?= $stBadge((string)($o['status'] ?? 'draft')) ?>
                  <span class="badge bg-light text-muted border"><?= ((int)($o['is_public'] ?? 1)===1) ? 'Paylaşılabilir' : 'Gizli' ?></span>
                </div>
              </td>
              <td>
                <div class="fw-semibold"><?= e(($o['customer_company'] ?? '') ?: ($o['customer_name'] ?? '—')) ?></div>
                <?php if (!empty($o['customer_email'])): ?><div class="small text-muted"><?= e($o['customer_email']) ?></div><?php endif; ?>
              </td>
              <td><?= e($o['currency'] ?? 'TRY') ?></td>
              <td class="text-nowrap"><?= e(date('d.m.Y H:i', strtotime((string)$o['created_at']))) ?></td>
              <td class="text-end">
                <div class="btn-group">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('teklif/' . ($o['public_code'] ?? ''))) ?>"><i class="bi bi-eye"></i></a>
                  <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('araclar/teklif-olustur/duzenle?id=' . (int)$o['id'])) ?>"><i class="bi bi-pencil"></i></a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Teklif silinsin mi?');">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
