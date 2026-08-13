<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/_guard.php';

$ok = '';

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);

  if (($_POST['action'] ?? '') === 'save_edu') {
    Timeline::adminEduSave([
      'id' => $_POST['id'] ?? null,
      'university' => trim((string)$_POST['university']),
      'department' => trim((string)$_POST['department']),
      'start_year' => (int)$_POST['start_year'],
      'end_year' => (int)$_POST['end_year'],
      'notes' => trim((string)$_POST['notes']),
    ]);
    $ok = 'Eğitim kaydedildi.';
  }

  if (($_POST['action'] ?? '') === 'save_exp') {
    Timeline::adminExpSave([
      'id' => $_POST['id'] ?? null,
      'company' => trim((string)$_POST['company']),
      'role' => trim((string)$_POST['role']),
      'start_year' => (int)$_POST['start_year'],
      'end_year' => (int)$_POST['end_year'],
      'notes' => trim((string)$_POST['notes']),
    ]);
    $ok = 'Deneyim kaydedildi.';
  }

  if (($_POST['action'] ?? '') === 'save_skill') {
    $res = Skill::create((string)($_POST['name'] ?? ''), (int)($_POST['level'] ?? 0), (string)($_POST['tags'] ?? ''));
    if (($res['ok'] ?? false)) $ok = 'Beceri eklendi.'; else $err = (string)($res['error'] ?? 'Hata');
  }

  if (($_POST['action'] ?? '') === 'save_cert') {
    $filePath = handle_upload('cert_file', 'docs');
    // If user uploads image as logo, allow via handle_upload too
    $logoPath = handle_upload('cert_logo', 'media');
    $res = Certification::create(
      (string)($_POST['title'] ?? ''),
      (string)($_POST['issuer'] ?? ''),
      (int)($_POST['issue_year'] ?? 0),
      (string)($_POST['credential_url'] ?? ''),
      (string)($_POST['logo_path'] ?? $logoPath),
      (string)($_POST['description'] ?? ''),
      (string)($_POST['file_path'] ?? $filePath)
    );
    if (($res['ok'] ?? false)) $ok = 'Sertifika eklendi.'; else $err = (string)($res['error'] ?? 'Hata');
  }

  if (($_POST['action'] ?? '') === 'delete') {
        $table = (string)($_POST['table'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    if ($table === 'skills') Skill::delete($id);
    elseif ($table === 'certifications') Certification::delete($id);
    else Timeline::adminDelete($table, $id);

    $ok = 'Silindi.';
  }
}

$education = Timeline::educations();
$experience = Timeline::experiences();
$skills = Skill::all();
$certs = Certification::all();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Eğitim & Deneyim</h1>
    <div class="text-muted">Hakkımda sayfasında görünür</div>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-mortarboard me-1"></i>Eğitim Ekle</div>
      <form method="post" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="save_edu">

        <div class="col-12">
          <label class="form-label">Üniversite</label>
          <input class="form-control" name="university" required>
        </div>
        <div class="col-12">
          <label class="form-label">Bölüm</label>
          <input class="form-control" name="department" required>
        </div>
        <div class="col-6">
          <label class="form-label">Başlangıç</label>
          <input class="form-control" name="start_year" type="number" required>
        </div>
        <div class="col-6">
          <label class="form-label">Bitiş</label>
          <input class="form-control" name="end_year" type="number" required>
        </div>
        <div class="col-12">
          <label class="form-label">Not (opsiyonel)</label>
          <input class="form-control" name="notes">
        </div>
        <div class="col-12 d-grid">
          <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ekle</button>
        </div>
      </form>

      <hr class="my-3">
      <div class="fw-bold mb-2">Kayıtlar</div>
      <?php if (!$education): ?><div class="text-muted">Kayıt yok.</div><?php endif; ?>
      <?php foreach ($education as $ed): ?>
        <div class="d-flex justify-content-between align-items-start border rounded-4 p-3 mb-2" style="border-color:rgba(255,255,255,.10)!important;">
          <div>
            <div class="fw-semibold"><?= e($ed['university']) ?></div>
            <div class="small text-muted"><?= e($ed['department']) ?> • <?= e((string)$ed['start_year']) ?> - <?= e((string)$ed['end_year']) ?></div>
            <?php if ($ed['notes']): ?><div class="small text-muted"><?= e($ed['notes']) ?></div><?php endif; ?>
          </div>
          <form method="post" onsubmit="return confirm('Silinsin mi?')">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="table" value="education">
            <input type="hidden" name="id" value="<?= (int)$ed['id'] ?>">
            <button class="btn btn-sm btn-outline-light"><i class="bi bi-trash me-1"></i>Sil</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-briefcase me-1"></i>Deneyim Ekle</div>
      <form method="post" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="save_exp">

        <div class="col-12">
          <label class="form-label">Kurum</label>
          <input class="form-control" name="company" required>
        </div>
        <div class="col-12">
          <label class="form-label">Pozisyon</label>
          <input class="form-control" name="role" required>
        </div>
        <div class="col-6">
          <label class="form-label">Başlangıç</label>
          <input class="form-control" name="start_year" type="number" required>
        </div>
        <div class="col-6">
          <label class="form-label">Bitiş</label>
          <input class="form-control" name="end_year" type="number" required>
        </div>
        <div class="col-12">
          <label class="form-label">Not (opsiyonel)</label>
          <input class="form-control" name="notes">
        </div>
        <div class="col-12 d-grid">
          <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ekle</button>
        </div>
      </form>

      <hr class="my-3">
      <div class="fw-bold mb-2">Kayıtlar</div>
      <?php if (!$experience): ?><div class="text-muted">Kayıt yok.</div><?php endif; ?>
      <?php foreach ($experience as $ex): ?>
        <div class="d-flex justify-content-between align-items-start border rounded-4 p-3 mb-2" style="border-color:rgba(255,255,255,.10)!important;">
          <div>
            <div class="fw-semibold"><?= e($ex['company']) ?></div>
            <div class="small text-muted"><?= e($ex['role']) ?> • <?= e((string)$ex['start_year']) ?> - <?= e((string)$ex['end_year']) ?></div>
            <?php if ($ex['notes']): ?><div class="small text-muted"><?= e($ex['notes']) ?></div><?php endif; ?>
          </div>
          <form method="post" onsubmit="return confirm('Silinsin mi?')">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="table" value="experience">
            <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
            <button class="btn btn-sm btn-outline-light"><i class="bi bi-trash me-1"></i>Sil</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>


<hr class="my-4">

<div class="row g-3" id="skills">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-stars me-1"></i>Beceriler</div>
      <form method="post" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="save_skill">

        <div class="col-12">
          <label class="form-label">Beceri</label>
          <input class="form-control" name="name" placeholder="Örn: HL7, SQL, ITSM" required>
        </div>
        <div class="col-6">
          <label class="form-label">Seviye (0-100)</label>
          <input class="form-control" name="level" type="number" min="0" max="100" value="0">
        </div>
        <div class="col-6">
          <label class="form-label">Etiketler</label>
          <input class="form-control" name="tags" placeholder="Örn: ServiceNow, AD, VMware">
        </div>

        <div class="col-12 d-grid">
          <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ekle</button>
        </div>
      </form>

      <hr class="my-3">
      <div class="fw-bold mb-2">Kayıtlar</div>
      <?php if (!$skills): ?><div class="text-muted">Kayıt yok.</div><?php endif; ?>
      <?php foreach ($skills as $s): ?>
        <div class="d-flex justify-content-between align-items-start border rounded-4 p-3 mb-2" style="border-color:rgba(255,255,255,.10)!important;">
          <div>
            <div class="fw-semibold"><?= e($s['name'] ?? '') ?> <?php if ((int)($s['level'] ?? 0) > 0): ?><span class="badge bg-primary ms-1"><?= (int)$s['level'] ?>%</span><?php endif; ?></div>
            <?php if (!empty($s['tags'])): ?><div class="small text-muted"><?= e($s['tags']) ?></div><?php endif; ?>
          </div>
          <form method="post" onsubmit="return confirm('Silinsin mi?')">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="table" value="skills">
            <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>">
            <button class="btn btn-sm btn-outline-light"><i class="bi bi-trash me-1"></i>Sil</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-6" id="certs">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-award me-1"></i>Sertifikalar</div>

      <form method="post" enctype="multipart/form-data" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="save_cert">

        <div class="col-12">
          <label class="form-label">Sertifika</label>
          <input class="form-control" name="title" placeholder="Örn: ITIL Foundation" required>
        </div>
        <div class="col-12">
          <label class="form-label">Kurum</label>
          <input class="form-control" name="issuer" placeholder="Örn: Microsoft / AXELOS" required>
        </div>
        <div class="col-6">
          <label class="form-label">Yıl</label>
          <input class="form-control" name="issue_year" type="number" min="0" max="2100" value="0">
        </div>
        <div class="col-6">
          <label class="form-label">Doğrulama URL</label>
          <input class="form-control" name="credential_url" placeholder="https://...">
        </div>
        <div class="col-12">
          <label class="form-label">Açıklama (opsiyonel)</label>
          <input class="form-control" name="description" placeholder="Kısa not...">
        </div>

        <div class="col-12">
          <label class="form-label">Sertifika PDF (opsiyonel)</label>
          <input class="form-control" name="cert_file" type="file" accept="application/pdf">
          <div class="text-muted small mt-1">PDF yükleyebilirsin veya Medya’dan link yapıştırabilirsin.</div>
          <input class="form-control mt-2" name="file_path" placeholder="(opsiyonel) /uploads/docs/.. veya /uploads/media/..">
        </div>

        <div class="col-12">
          <label class="form-label">Logo (opsiyonel)</label>
          <input class="form-control" name="cert_logo" type="file" accept="image/*">
          <input class="form-control mt-2" name="logo_path" placeholder="(opsiyonel) /uploads/media/...">
        </div>

        <div class="col-12 d-grid">
          <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ekle</button>
        </div>
      </form>

      <hr class="my-3">
      <div class="fw-bold mb-2">Kayıtlar</div>
      <?php if (!$certs): ?><div class="text-muted">Kayıt yok.</div><?php endif; ?>
      <?php foreach ($certs as $c): ?>
        <div class="d-flex justify-content-between align-items-start border rounded-4 p-3 mb-2" style="border-color:rgba(255,255,255,.10)!important;">
          <div class="pe-2">
            <div class="fw-semibold"><?= e($c['title'] ?? '') ?></div>
            <div class="small text-muted"><?= e($c['issuer'] ?? '') ?><?php if ((int)($c['issue_year'] ?? 0)): ?> • <?= (int)$c['issue_year'] ?><?php endif; ?></div>
            <?php if (!empty($c['description'])): ?><div class="small text-muted"><?= e($c['description']) ?></div><?php endif; ?>
            <?php if (!empty($c['credential_url'])): ?><a class="small text-decoration-none" href="<?= e($c['credential_url']) ?>" target="_blank" rel="noopener">Doğrulama</a><?php endif; ?>
            <?php if (!empty($c['file_path'])): ?><div><a class="small text-decoration-none" href="<?= e(base_url($c['file_path'])) ?>" target="_blank" rel="noopener">PDF</a></div><?php endif; ?>
          </div>
          <form method="post" onsubmit="return confirm('Silinsin mi?')">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="table" value="certifications">
            <input type="hidden" name="id" value="<?= (int)($c['id'] ?? 0) ?>">
            <button class="btn btn-sm btn-outline-light"><i class="bi bi-trash me-1"></i>Sil</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>


<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
