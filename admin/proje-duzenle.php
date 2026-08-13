<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/../app/models/Media.php';
require_once __DIR__ . '/../app/models/Revisions.php';
require_once __DIR__ . '/../app/models/Sitemap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? Projects::adminGet($id) : null;

$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($item && !empty($item['id'])) { try { Revisions::saveProject($item); } catch (Throwable $t) {} }

  CSRF::checkOrExit($_POST['_csrf'] ?? null);

  $cover = trim((string)($_POST['cover_image'] ?? '')) ?: ($item['cover_image'] ?? '');
  $up = handle_upload('cover', 'projects');
  if ($up) $cover = $up;

  $savedId = Projects::adminSave([
    'id' => $_POST['id'] ?: null,
    'title' => trim((string)$_POST['title']),
    'slug' => trim((string)$_POST['slug']),
    'summary' => trim((string)$_POST['summary']),
    'details' => (string)$_POST['details'],
    'technologies' => trim((string)$_POST['technologies']),
    'cover_image' => $cover,
    'meta_title' => trim((string)$_POST['meta_title']),
    'meta_desc' => trim((string)$_POST['meta_desc']),
    'featured' => !empty($_POST['featured']) ? 1 : 0,
    'status' => (string)($_POST['status'] ?? 'draft'),
    'published_at' => trim((string)$_POST['published_at']),
  ]);
  $ok = 'Kaydedildi.';
  $id = $savedId;
  $item = Projects::adminGet($id);
  // Cache purge: only related pages
  $paths = ['/'];
  if (!empty($item['slug'])) $paths[] = '/proje/'.$item['slug'];
  $paths[] = '/projeler';
  try { purge_pages($paths); } catch (Throwable $t) {}
  try { Sitemap::generate(); } catch (Throwable $t) {}
}


$media = Media::latest(60);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1"><?= $id ? 'Proje Düzenle' : 'Yeni Proje' ?></h1>
    <div class="text-muted">Öne çıkan + teknoloji filtreleri + medya seçimi</div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('admin/projeler.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Geri</a>
    <?php if (!empty($item['slug']) && ($item['status'] ?? '') === 'published'): ?>
      <a class="btn btn-outline-light btn-sm" target="_blank" href="<?= e(base_url('proje/'.$item['slug'])) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Görüntüle</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card p-3">
  <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
  <input type="hidden" name="id" value="<?= e((string)($item['id'] ?? '')) ?>">

  <div class="row g-3">
    <div class="col-lg-8">
      <label class="form-label">Başlık</label>
      <input class="form-control" name="title" required value="<?= e($item['title'] ?? '') ?>">
    </div>
    <div class="col-lg-4">
      <label class="form-label">Slug (boş bırak)</label>
      <input class="form-control" name="slug" value="<?= e($item['slug'] ?? '') ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Teknolojiler (virgülle)</label>
      <input class="form-control" name="technologies" value="<?= e($item['technologies'] ?? '') ?>" placeholder="PHP, MySQL, HL7, AD, VMware...">
    </div>
    <div class="col-md-2">
      <label class="form-label">Öne Çıkan</label>
      <?php $feat = (int)($item['featured'] ?? 0); ?>
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1" <?= $feat===1?'checked':'' ?>>
        <label class="form-check-label" for="featured">Evet</label>
      </div>
    </div>
    <div class="col-md-2">
      <label class="form-label">Durum</label>
      <?php $st = $item['status'] ?? 'draft'; ?>
      <select class="form-select" name="status">
        <option value="draft" <?= $st==='draft'?'selected':'' ?>>draft</option>
        <option value="published" <?= $st==='published'?'selected':'' ?>>published</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Yayın Tarihi</label>
      <input class="form-control" name="published_at" placeholder="YYYY-MM-DD HH:MM:SS" value="<?= e($item['published_at'] ?? '') ?>">
    </div>

    <div class="col-md-12">
      <label class="form-label">Kapak Görseli</label>
      <div class="row g-2 align-items-end">
        <div class="col-lg-6">
          <input class="form-control" type="file" name="cover" accept="image/*">
          <div class="form-text">Yükle veya medya seç</div>
        </div>
        <div class="col-lg-6">
          <input id="cover_image" class="form-control" name="cover_image" value="<?= e($item['cover_image'] ?? '') ?>" placeholder="uploads/media/...">
          <div class="d-flex gap-2 mt-2">
            <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#mediaModal"><i class="bi bi-images me-1"></i>Medya seç</button>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="document.getElementById('cover_image').value='';"><i class="bi bi-x-lg me-1"></i>Temizle</button>
          </div>
        </div>
      </div>
      <?php if (!empty($item['cover_image'])): ?>
        <div class="mt-2"><img src="<?= e(base_url($item['cover_image'])) ?>" style="max-height:150px" class="rounded-3 border"></div>
      <?php endif; ?>
    </div>

    <div class="col-md-6">
      <label class="form-label">SEO Başlık</label>
      <input class="form-control" name="meta_title" value="<?= e($item['meta_title'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">SEO Açıklama</label>
      <input class="form-control" name="meta_desc" value="<?= e($item['meta_desc'] ?? '') ?>">
    </div>

    <div class="col-12">
      <label class="form-label">Kısa Özet</label>
      <textarea class="form-control" name="summary" rows="3" placeholder="Proje özeti (kısa)"><?= e($item['summary'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
      <label class="form-label d-flex justify-content-between align-items-center">
  <span>Detay</span>
  <button type="button" class="btn btn-sm btn-outline-light" onclick="insertCaseStudy()"><i class="bi bi-layout-text-window-reverse me-1"></i>Case Study Şablonu</button>
</label>
      <textarea id="editor_project" class="form-control" name="details" rows="14"><?= e($item['details'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2 flex-wrap">
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Kaydet</button>
    <a class="btn btn-outline-light" href="<?= e(base_url('admin/projeler.php')) ?>">Listeye dön</a>
    <a class="btn btn-outline-light" href="<?= e(base_url('admin/proje-revizyonlar.php?id='.$id)) ?>"><i class="bi bi-clock-history me-1"></i>Revizyonlar</a>
    <a class="btn btn-outline-light" href="<?= e(base_url('admin/medya.php')) ?>"><i class="bi bi-images me-1"></i>Medya Kütüphanesi</a>
  </div>
</form>

<!-- Media Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="background: rgba(15,23,42,.96); border:1px solid rgba(255,255,255,.12); color:#fff;">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="bi bi-images me-2"></i>Medya Kütüphanesi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (!$media): ?>
          <div class="text-muted">Henüz medya yok. <a class="text-decoration-none" href="<?= e(base_url('admin/medya.php')) ?>">Medya sayfasından</a> yükleyebilirsin.</div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($media as $m): ?>
              <div class="col-6 col-md-3 col-lg-2">
                <div class="card p-2" style="cursor:pointer;" onclick="selectMedia('<?= e($m['file_path']) ?>')">
                  <img src="<?= e(base_url($m['file_path'])) ?>" class="w-100 rounded-3" style="aspect-ratio:1/1; object-fit:cover;" alt="">
                  <div class="small text-muted mt-1 text-truncate"><?= e($m['original_name'] ?? '') ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer border-0">
        <a class="btn btn-outline-light" href="<?= e(base_url('admin/medya.php')) ?>"><i class="bi bi-upload me-1"></i>Yeni yükle</a>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tamam</button>
      </div>
    </div>
  </div>
</div>

<script>
function selectMedia(path){
  document.getElementById('cover_image').value = path;
  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('mediaModal'));
  modal.hide();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/@ckeditor/ckeditor5-build-classic@40.2.0/build/ckeditor.js"></script>
<script>
  ClassicEditor.create(document.querySelector('#editor_project')).then(ed=>{window._editorProject=ed;}).catch(console.error);
function insertCaseStudy(){
  if(!window._editorProject){return;}
  const html = `<h2>Problem</h2><p>Buraya problemi yaz.</p><h2>Hedef</h2><ul><li>Hedef 1</li><li>Hedef 2</li></ul><h2>Çözüm</h2><p>Uyguladığın yaklaşım.</p><h2>Teknolojiler</h2><ul><li>SQL</li><li>VMware</li><li>HL7</li></ul><h2>Etki</h2><ul><li>%X iyileşme</li><li>Kesinti azaldı</li></ul><h2>Dersler</h2><p>Öğrenimler.</p>`;
  window._editorProject.setData(html);
}

</script>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
