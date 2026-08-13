</main>
<footer>
  <div class="container py-4 d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
    <div>© <?= date('Y') ?> <?= e(Settings::get('site_name','Furkan Cihan') ?? 'Furkan Cihan') ?></div>
    <div class="d-flex gap-3 small">
      <a class="text-decoration-none" href="<?= e(base_url('sitemap.xml')) ?>" target="_blank">Sitemap</a>
      <a class="text-decoration-none" href="<?= e(base_url('admin/login.php')) ?>">Admin</a>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-sql.min.js"></script>


<?php
  $toastMsg = (string)($flashSuccess ?? '');
  $toastType = 'success';
  if ($toastMsg === '') {
    $toastMsg = (string)($flashErr ?? '');
    $toastType = $toastMsg !== '' ? 'danger' : $toastType;
  }
?>

<?php if ($toastMsg !== ''): ?>
  <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="appToast" class="toast align-items-center text-bg-<?= e($toastType) ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= e($toastMsg) ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
      </div>
    </div>
  </div>
  <script>
    (function(){
      try {
        var el = document.getElementById('appToast');
        if (el && window.bootstrap) {
          var t = new bootstrap.Toast(el, { delay: 3500 });
          t.show();
        }
      } catch(e) {}
    })();
  </script>
<?php endif; ?>
<script src="<?= e(base_url('assets/js/main.js')) ?>"></script>
</body>
</html>
