<div class="hero contact-hero rounded-4 p-4 p-md-5 mb-4">
  <div class="row g-4 align-items-center position-relative" style="z-index:1;">
    <div class="col-lg-7">
      <span class="badge bg-light text-dark border mb-3">İletişim • İşbirliği • Danışmanlık</span>
      <h1 class="display-6 fw-bold mb-2">İletişime Geç</h1>
      <p class="lead mb-0">Proje, entegrasyon, BT operasyonu ve süreç iyileştirme konularında birlikte çalışabiliriz.</p>
      <div class="d-flex gap-2 align-items-center flex-wrap mt-3">
        <?php
          $badge = ['available'=>'Müsait', 'busy'=>'Yoğun', 'offline'=>'Offline'][$availability ?? 'available'] ?? 'Müsait';
          $badgeCls = ['available'=>'success', 'busy'=>'warning', 'offline'=>'secondary'][$availability ?? 'available'] ?? 'success';
        ?>
        <span class="badge bg-<?= e($badgeCls) ?>"><?= e($badge) ?></span>
        <span class="text-muted small"><i class="bi bi-clock me-1"></i><?= e($hours ?? '') ?></span>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="glass p-4">
        <div class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i>Hızlı Bilgiler</div>
        <ul class="list-unstyled mb-0">
          <?php if ($email): ?><li class="mb-2"><i class="bi bi-envelope me-2"></i><a class="text-decoration-none" href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li><?php endif; ?>
          <?php if ($phone): ?><li class="mb-2"><i class="bi bi-telephone me-2"></i><?= e($phone) ?></li><?php endif; ?>
          <?php if ($location): ?><li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= e($location) ?></li><?php endif; ?>
          <?php if (!$email && !$phone && !$location): ?><li class="text-muted">Bilgi eklenmemiş.</li><?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card p-3 p-md-4 contact-card">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <div class="h5 fw-bold mb-1"><i class="bi bi-envelope-paper me-2"></i>İletişim Formu</div>
          <div class="text-muted">Mesaj bırakın, en kısa sürede dönüş yapayım.</div>
        </div>
        <span class="badge bg-light text-dark border">Kurumsal</span>
      </div>

      <?php if (empty($contactEnabled)): ?>
        <div class="alert alert-warning border-0 mt-3">Form şu an kapalı.</div>
      <?php else: ?>

        <?php if (!empty($formMsg)): ?>
          <div class="alert alert-<?= e($formType ?? 'info') ?> mt-3"><?= e($formMsg) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-3">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <!-- honeypot -->
          <input type="text" name="website" value="" style="display:none" tabindex="-1" autocomplete="off">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Ad Soyad</label>
              <input class="form-control form-control-lg" name="name" placeholder="Örn: Furkan Cihan" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">E-posta (opsiyonel)</label>
              <input class="form-control form-control-lg" name="email" placeholder="ornek@domain.com" type="email">
            </div>

            <div class="col-md-6">
              <label class="form-label">Kurum (opsiyonel)</label>
              <input class="form-control form-control-lg" name="company_name" placeholder="Örn: Hastane / Firma">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon (opsiyonel)</label>
              <input class="form-control form-control-lg" name="sender_phone" placeholder="05xx xxx xx xx">
            </div>

            <div class="col-12">
              <label class="form-label">Hızlı Teklif Konuları</label>
              <div class="row g-2">
                <?php
                  $opts = ['Entegrasyon','Raporlama','ITSM','Güvenlik','Otomasyon','Sistem Yönetimi','Süreç İyileştirme'];
                ?>
                <?php foreach ($opts as $o): ?>
                  <div class="col-sm-6">
                    <label class="offer-check">
                      <input class="form-check-input me-2" type="checkbox" name="topics[]" value="<?= e($o) ?>">
                      <span><?= e($o) ?></span>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="small text-muted mt-1">Seçenekleri işaretleyerek mesajın daha hızlı değerlendirilmesini sağlayabilirsin.</div>
            </div>

            <div class="col-12">
              <label class="form-label">Mesaj</label>
              <textarea class="form-control form-control-lg" name="message" rows="7" placeholder="Projenizin hedefi, mevcut durum, zaman planı, beklentiler..." required></textarea>
            </div>

            <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="small text-muted">
                <i class="bi bi-shield-check me-1"></i>Spam koruması + hız limiti aktiftir.
              </div>
              <button class="btn btn-primary btn-lg">
                <i class="bi bi-send me-1"></i>Gönder
              </button>
            </div>
          </div>
        </form>

      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card p-3 p-md-4 mb-3">
      <div class="h6 fw-bold mb-2"><i class="bi bi-lightning-charge me-1"></i>Beklenen yanıt</div>
      <div class="text-muted mb-3">Genelde 24–48 saat içinde dönüş yaparım.</div>

      <div class="border rounded-4 p-3 mb-3">
        <div class="fw-bold mb-1">Hangi konularda?</div>
        <ul class="text-muted mb-0">
          <li>Hastane bilgi sistemleri & süreçler</li>
          <li>Entegrasyon, raporlama, otomasyon</li>
          <li>Güvenlik, erişim, loglama</li>
          <li>ITSM & süreç standardizasyonu</li>
        </ul>
      </div>

      <div class="border rounded-4 p-3">
        <div class="fw-bold mb-1">İpucu</div>
        <div class="text-muted small">Blog yazılarımı takip ederek yeni içeriklerden haberdar olabilirsin.</div>
        <a class="btn btn-outline-primary mt-3" href="<?= e(base_url('blog')) ?>"><i class="bi bi-journal-text me-1"></i>Blog'a git</a>
      </div>
    </div>

    <div class="card p-3 p-md-4">
      <div class="h6 fw-bold mb-2"><i class="bi bi-map me-1"></i>Konum</div>
      <?php if (!empty($mapEmbed)): ?>
        <div class="map-embed rounded-4 overflow-hidden border">
          <?= $mapEmbed ?>
        </div>
      <?php else: ?>
        <div class="text-muted small mb-2">Harita eklemek için Admin > Site Ayarları bölümünden Google Maps embed kodu girebilirsin.</div>
        <div class="map-embed rounded-4 overflow-hidden border">
          <iframe
            src="https://www.google.com/maps?q=Ankara%20Mamak&output=embed"
            width="100%" height="260" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>
