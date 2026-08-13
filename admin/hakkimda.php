<?php
require_once __DIR__ . '/_layout_top.php';

$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  Settings::set('about_short', trim((string)$_POST['about_short']));
  Settings::set('about_text', (string)$_POST['about_text']);
  $ok = 'Kaydedildi.';
}

$aboutShort = Settings::get('about_short','') ?? '';
$aboutText = Settings::get('about_text','') ?? '';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Hakkımda</h1>
    <div class="text-muted">Kişisel özet ve detay içerik</div>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success border-0" style="background: rgba(34,197,94,.18); color:#fff;"><?= e($ok) ?></div><?php endif; ?>

<form method="post" class="card p-3">
  <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
  <div class="mb-3">
    <label class="form-label">Kısa Özet</label>
    <input class="form-control" name="about_short" value="<?= e($aboutShort) ?>" placeholder="1-2 cümlelik özet">
  </div>

  <div class="mb-3">
    <label class="form-label">Detay (Editör)</label>
    <textarea id="editor_about" class="form-control" name="about_text" rows="12"><?= e($aboutText) ?></textarea>
    <div class="form-text text-muted">Başlık, liste, link, görsel ekleyebilirsin.</div>
  </div>

  <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Kaydet</button>
</form>

<script src="https://cdn.jsdelivr.net/npm/@ckeditor/ckeditor5-build-classic@40.2.0/build/ckeditor.js"></script>
<script>
  ClassicEditor.create(document.querySelector('#editor_about')).catch(console.error);
</script>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
