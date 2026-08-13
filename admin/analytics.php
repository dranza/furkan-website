<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/models/Analytics.php';

$days = (int)($_GET['days'] ?? 30);
$days = max(7, min(180, $days));

$total = Analytics::total($days);
$uniq  = Analytics::uniques($days);
$daily = Analytics::dailyCounts($days);
$hourly = Analytics::hourlyCounts($days);
$returning = Analytics::returningRate($days);
$topPages = Analytics::topPages(12, $days);
$topRefs  = Analytics::topReferrers(10, $days);
$recent   = Analytics::recent(60, $days);

$labels = array_map(fn($r) => $r['d'], $daily);
$values = array_map(fn($r) => (int)$r['c'], $daily);

$hLabels = [];
$hValues = [];
for ($i=0; $i<24; $i++) { $hLabels[] = sprintf('%02d:00', $i); $hValues[] = 0; }
foreach ($hourly as $r) {
  $h = (int)$r['h'];
  if ($h>=0 && $h<24) $hValues[$h] = (int)$r['c'];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Analytics</h1>
    <div class="text-muted">Son <?= $days ?> gün • sayfa görüntüleme</div>
  </div>
  <form method="get" class="d-flex gap-2 align-items-center">
    <select class="form-select form-select-sm" name="days" onchange="this.form.submit()">
      <?php foreach ([7,14,30,60,90,180] as $d): ?>
        <option value="<?= $d ?>" <?= $d===$days?'selected':'' ?>><?= $d ?> gün</option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3">
      <div class="text-muted">Toplam Görüntüleme</div>
      <div class="display-6 fw-bold mb-0"><?= $total ?></div>
      <div class="text-muted small">Seçilen aralık</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <div class="text-muted">Tahmini Tekil</div>
      <div class="display-6 fw-bold mb-0"><?= $uniq ?></div>
      <div class="text-muted small">Cookie + IP hash</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <div class="text-muted">Geri Dönen Oranı</div>
      <div class="display-6 fw-bold mb-0"><?= number_format($returning, 1) ?>%</div>
      <div class="text-muted small">Aynı ziyaretçi (2+ gün)</div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-8">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-graph-up me-1"></i>Günlük Trafik</div>
      <canvas id="chart" height="110"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-link-45deg me-1"></i>Top Sayfalar</div>
      <?php if (!$topPages): ?><div class="text-muted">Veri yok.</div><?php endif; ?>
      <?php foreach ($topPages as $p): ?>
        <div class="d-flex justify-content-between gap-2 border rounded-4 p-2 mb-2" style="border-color:rgba(255,255,255,.10)!important;">
          <div class="text-truncate" style="max-width:75%;"><?= e($p['path']) ?></div>
          <div class="fw-bold"><?= (int)$p['c'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-clock-history me-1"></i>Saatlik Dağılım</div>
      <canvas id="hourly" height="130"></canvas>
      <div class="text-muted small mt-2">Yoğun saatleri görmek için idealdir.</div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-shield-check me-1"></i>Gizlilik</div>
      <div class="text-muted">Bu panel KVKK uyumlu çalışacak şekilde tasarlanmıştır.</div>
      <ul class="small text-muted mt-2 mb-0">
        <li>IP adresleri <b>maskeli</b> gösterilir (örn: 1.2.3.xxx).</li>
        <li>Tekil ziyaretçi hesaplaması cookie + hash ile yapılır.</li>
        <li>Harici analytics servisi kullanılmaz.</li>
      </ul>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-box-arrow-in-right me-1"></i>Referrer</div>
      <?php if (!$topRefs): ?><div class="text-muted">Harici yönlendirme yok.</div><?php endif; ?>
      <div class="row g-2">
        <?php foreach ($topRefs as $r): ?>
          <div class="col-md-6 col-lg-4">
            <div class="border rounded-4 p-2" style="border-color:rgba(255,255,255,.10)!important;">
              <div class="small text-muted text-truncate"><?= e($r['referrer']) ?></div>
              <div class="fw-bold"><?= (int)$r['c'] ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold"><i class="bi bi-list-ul me-1"></i>Son Ziyaretler</div>
        <div class="text-muted small">Son <?= min(60, count($recent)) ?> kayıt • IP maskeli</div>
      </div>
      <?php if (!$recent): ?>
        <div class="text-muted">Veri yok.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr class="text-muted">
                <th style="width:160px">Tarih</th>
                <th>Sayfa</th>
                <th style="width:180px">Referrer</th>
                <th style="width:160px">Cihaz</th>
                <th style="width:140px">IP</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $v):
                $ref = (string)($v['referrer'] ?? '');
                $refHost = '';
                if ($ref) {
                  $p = parse_url($ref);
                  $refHost = $p['host'] ?? $ref;
                }
                $ua = (string)($v['ua'] ?? '');
                $uaShort = $ua ? substr($ua, 0, 28) . (strlen($ua) > 28 ? '…' : '') : '';
              ?>
              <tr>
                <td class="text-muted small"><?= e(date('Y-m-d H:i', strtotime((string)$v['created_at']))) ?></td>
                <td class="text-truncate" style="max-width:420px" title="<?= e((string)$v['path']) ?>"><?= e((string)$v['path']) ?></td>
                <td class="text-truncate" title="<?= e($ref) ?>"><?= e($refHost ?: '-') ?></td>
                <td class="text-truncate" title="<?= e($ua) ?>"><?= e($uaShort ?: '-') ?></td>
                <td><span class="badge rounded-pill text-bg-dark border" style="border-color:rgba(255,255,255,.15)!important;"><?= e((string)($v['ip_masked'] ?? '-')) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const labels = <?= json_encode($labels) ?>;
const values = <?= json_encode($values) ?>;
const hLabels = <?= json_encode($hLabels) ?>;
const hValues = <?= json_encode($hValues) ?>;

function loadScript(url){
  return new Promise((resolve,reject)=>{
    const s=document.createElement('script');
    s.src=url; s.async=true;
    s.onload=()=>resolve(true);
    s.onerror=()=>reject(new Error('load fail'));
    document.head.appendChild(s);
  });
}

async function ensureChart(){
  if (window.Chart) return;
  const urls = [
    'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
    'https://unpkg.com/chart.js@4.4.1/dist/chart.umd.min.js'
  ];
  for (const u of urls){
    try { await loadScript(u); if (window.Chart) return; } catch(e){}
  }
}

(async()=>{
  await ensureChart();
  if (!window.Chart) {
    console.warn('Chart.js yüklenemedi');
    return;
  }
  new Chart(document.getElementById('chart'), {
    type: 'line',
    data: { labels, datasets: [{ label: 'Görüntüleme', data: values, tension: 0.25 }] },
    options: { responsive:true, plugins:{ legend:{display:false}}, scales:{ y:{ beginAtZero:true } } }
  });
  new Chart(document.getElementById('hourly'), {
    type: 'bar',
    data: { labels: hLabels, datasets: [{ label: 'Görüntüleme', data: hValues }] },
    options: { responsive:true, plugins:{ legend:{display:false}}, scales:{ y:{ beginAtZero:true } } }
  });
})();
</script>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
