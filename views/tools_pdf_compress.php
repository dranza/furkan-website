<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="mb-4">
        <h1 class="fw-bold">PDF Küçültme</h1>
        <p class="text-muted mb-0">PDF dosyalarının boyutunu küçült. Çoklu PDF seçebilir veya klasör yükleyebilirsin.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if (($toolStatus ?? 'active') === 'maintenance'): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2">
          <i class="bi bi-tools"></i>
          <div>
            <div class="fw-semibold">Bu araç şu an bakımda.</div>
            <div class="small"><?= e($toolMessage ?? 'Kısa süre içinde tekrar aktif olacaktır.') ?></div>
          </div>
        </div>
      <?php elseif (($toolStatus ?? 'active') === 'disabled'): ?>
        <div class="alert alert-secondary d-flex align-items-center gap-2">
          <i class="bi bi-slash-circle"></i>
          <div>
            <div class="fw-semibold">Bu araç şu an devre dışı.</div>
            <div class="small"><?= e($toolMessage ?? 'Daha sonra tekrar deneyebilirsin.') ?></div>
          </div>
        </div>
      <?php endif; ?>

      <div class="card shadow-sm">
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" class="vstack gap-3" <?= (($toolStatus ?? 'active')==='active') ? '' : 'aria-disabled="true"' ?>>
            <div class="row g-3">
              <div class="col-md-7">
                <label class="form-label fw-semibold">PDF dosyaları</label>
                <input class="form-control" type="file" name="files[]" accept="application/pdf" multiple required <?= (($toolStatus ?? 'active')==='active') ? '' : 'disabled' ?>>
                <div class="form-text">Birden fazla PDF seçebilirsin. (Klasör için aşağıdaki alanı kullan)</div>
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold">Sıkıştırma profili</label>
                <select class="form-select" name="preset" <?= (($toolStatus ?? 'active')==='active') ? '' : 'disabled' ?>>
                  <option value="screen">Düşük (En küçük)</option>
                  <option value="ebook" selected>Orta (Önerilen)</option>
                  <option value="printer">Yüksek (Baskı)</option>
                  <option value="prepress">Çok yüksek</option>
                </select>
                <div class="form-text">PDFSETTINGS profili kullanılır. Metin korunur.</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-7">
                <label class="form-label fw-semibold">Klasör seç (opsiyonel)</label>
                <input class="form-control" type="file" name="folder[]" accept="application/pdf" webkitdirectory directory multiple <?= (($toolStatus ?? 'active')==='active') ? '' : 'disabled' ?>>
                <div class="form-text">Klasör seçersen, içindeki PDF’ler de işlenir.</div>
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold">Çıktı</label>
                <select class="form-select" name="output" <?= (($toolStatus ?? 'active')==='active') ? '' : 'disabled' ?>>
                  <option value="zip" selected>ZIP olarak indir (çoklu dosya)</option>
                  <option value="single">Tek PDF indir (1 dosya seçiliyse)</option>
                </select>
              </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
              <button class="btn btn-primary" type="submit" <?= (($toolStatus ?? 'active')==='active') ? '' : 'disabled' ?>><i class="bi bi-magic me-1"></i>PDF Küçült</button>
              <a class="btn btn-outline-secondary" href="<?= e(base_url('araclar')) ?>">Araçlara dön</a>
            </div>

            <div class="small text-muted">
              Not: İşlem sunucuda yapılır. Büyük dosyalarda birkaç saniye sürebilir. Dosyalar işlem sonrası otomatik silinir.
            </div>
          </form>
        </div>
      </div>

      <div class="mt-4">
        <h5 class="fw-bold">İpuçları</h5>
        <ul class="text-muted">
          <li>En iyi denge için <b>Orta (Önerilen)</b> profilini kullan.</li>
          <li>Fotoğraf ağırlıklı PDF’lerde küçülme daha belirgin olur.</li>
          <li>PDF’lerin içeriği hassassa, işlemden sonra çıktıyı kontrol et.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
