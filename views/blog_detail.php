<?php
  // Okuma süresi
  $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$post['content'])) ?? '');
  $words = $plain ? str_word_count($plain) : 0;
  $readMin = max(1, (int)ceil($words / 200));

  // TOC (H2/H3)
  $toc = [];
  try {
    $html = (string)$post['content'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);
    $nodes = $xpath->query('//h2|//h3');
    foreach ($nodes as $n) {
      $tag = strtolower($n->nodeName);
      $text = trim($n->textContent ?? '');
      if ($text === '') continue;
      $id = $n->getAttribute('id');
      if (!$id) {
        $id = 'sec-' . substr(md5($text), 0, 10);
        $n->setAttribute('id', $id);
      }
      $toc[] = ['id'=>$id,'text'=>$text,'lvl'=>$tag];
    }
    // write back content with injected ids
    $body = $doc->getElementsByTagName('body')->item(0);
    if ($body) {
      $new = '';
      foreach ($body->childNodes as $ch) $new .= $doc->saveHTML($ch);
      $post['content'] = $new;
    }
  } catch (Throwable $t) { $toc = []; }

  // Benzer yazılar
  $similarPosts = Blog::similar((int)$post['id'], (string)($post['category'] ?? ''), (string)($post['tags'] ?? ''), 3);
?>

<nav class="mb-3" aria-label="breadcrumb">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Anasayfa</a></li>
    <li class="breadcrumb-item"><a href="<?= e(base_url('blog')) ?>"><?= e('Blog') ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= e($post['title']) ?></li>
  </ol>
</nav>

<div class="sharebar my-3">
  <span class="label text-muted">Paylaş:</span>
  <?php
    $shareUrl = $canonical ?? base_url();
    $shareTitle = (string)$post['title'];
    $encUrl = rawurlencode($shareUrl);
    $encText = rawurlencode($shareTitle);
    $wa = "https://wa.me/?text=".$encText."%20".$encUrl;
    $tw = "https://twitter.com/intent/tweet?text=".$encText."&url=".$encUrl;
  ?>
  <a href="<?= e($wa) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i>WhatsApp</a>
  <a href="<?= e($tw) ?>" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i>X</a>
  <button type="button" data-copy-link="<?= e($shareUrl) ?>"><i class="bi bi-link-45deg"></i>Linki Kopyala</button>
  <a href="https://www.instagram.com/" target="_blank" rel="noopener" title="Instagram doğrudan link paylaşımı kısıtlıdır. Linki kopyalayıp yapıştırabilirsin.">
    <i class="bi bi-instagram"></i>Instagram
  </a>
</div>

<article class="bg-white border rounded-4 shadow-sm p-4">
  <div class="text-muted small mb-2"><?= e(date('d.m.Y', strtotime((string)$post['published_at']))) ?> • <span class="text-muted">Okuma süresi: <?= (int)$readMin ?> dk</span></div>
  <h1 class="h4 fw-bold mb-2"><?= e($post['title']) ?></h1>
  <?php if (!empty($post['category'])): ?><div class="mb-2"><span class="badge bg-light text-dark border"><?= e($post['category']) ?></span></div><?php endif; ?>
  <?php if (!empty($post['tags'])): ?><div class="mb-3"><span class="badge bg-light text-dark"><?= e($post['tags']) ?></span></div><?php endif; ?>
  <?php if (!empty($post['cover_image'])): ?>
    <img src="<?= e(base_url($post['cover_image'])) ?>" class="img-fluid rounded-4 mb-3" alt="<?= e($post['title']) ?>">
  <?php endif; ?>
<?php if (count($toc) >= 2): ?>
  <div class="bg-light border rounded-4 p-3 mb-3">
    <div class="fw-bold mb-2"><i class="bi bi-list-ul me-1"></i>İçindekiler</div>
    <div class="vstack gap-1">
      <?php foreach ($toc as $t): ?>
        <a class="text-decoration-none small <?= $t['lvl']==='h3'?'ms-3':'' ?>" href="#<?= e($t['id']) ?>">• <?= e($t['text']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

  <div class="prose"><?= $post['content'] ?></div>
</article>

<style>
.prose img{max-width:100%; height:auto;}
.prose h2{margin-top:1.2rem;}
.prose p{margin-bottom:.8rem;}
</style>


<?php if (!empty($similarPosts)): ?>
<section class="mt-4">
  <div class="bg-white border rounded-4 shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 fw-bold mb-0">Benzer Yazılar</h2>
      <a class="text-decoration-none" href="<?= e(base_url('blog')) ?>">Blog →</a>
    </div>
    <div class="row g-3">
      <?php foreach ($similarPosts as $sp): ?>
        <div class="col-md-4">
          <a class="text-decoration-none" href="<?= e(base_url('blog/'.$sp['slug'])) ?>">
            <div class="border rounded-4 p-3 h-100">
              <div class="small text-muted mb-1"><?= e($sp['published_at'] ? date('d.m.Y', strtotime((string)$sp['published_at'])) : '') ?></div>
              <div class="fw-semibold"><?= e($sp['title']) ?></div>
              <div class="text-muted small mt-1"><?= e(mb_substr(strip_tags((string)$sp['content']),0,90)) ?>…</div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="yorumlar" class="mt-4">
  <div class="bg-white border rounded-4 shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 fw-bold mb-0">Yorumlar</h2>
      <?php if (Comments::enabled()): ?>
        <span class="text-muted small"><?= (int)Comments::countApproved((int)$post['id']) ?> onaylı</span>
      <?php endif; ?>
    </div>

    <?php
      $msg = '';
      $type = 'info';
      if (!empty($_GET['comment'])) {
        if ($_GET['comment'] === 'ok') { $msg = 'Yorumunuz yayınlandı. Teşekkürler!'; $type = 'success'; }
        if ($_GET['comment'] === 'pending') { $msg = 'Yorumunuz alındı. Onaylandıktan sonra yayınlanacaktır.'; $type = 'success'; }
        if ($_GET['comment'] === 'err') { $msg = $_SESSION['comment_error'] ?? 'Yorum gönderilemedi.'; $type = 'danger'; unset($_SESSION['comment_error']); }
      }
      $approved = Comments::enabled() ? Comments::approvedForPost((int)$post['id']) : [];
    ?>

    <?php if ($msg): ?>
      <div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div>
    <?php endif; ?>

    <?php if (!Comments::enabled()): ?>
      <div class="text-muted">Yorumlar kapalı.</div>
    <?php else: ?>
      <?php if (!$approved): ?>
        <div class="text-muted mb-3">Henüz yorum yok.</div>
      <?php else: ?>
        <div class="vstack gap-3 mb-4">
          <?php foreach ($approved as $c): ?>
            <div class="border rounded-4 p-3">
              <div class="d-flex justify-content-between align-items-center">
                <div class="fw-semibold"><?= e($c['name']) ?></div>
                <div class="text-muted small"><?= e(date('d.m.Y H:i', strtotime((string)$c['created_at']))) ?></div>
              </div>
              <div class="mt-2"><?= nl2br(e($c['content'])) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="border-top pt-3">
        <div class="fw-bold mb-2">Yorum Yaz</div>
        <form method="post" class="row g-3">
          <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
          <!-- honeypot -->
          <input type="text" name="company" value="" style="display:none" tabindex="-1" autocomplete="off">

          <div class="col-md-6">
            <label class="form-label">Ad Soyad</label>
            <input class="form-control" name="name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">E-posta (opsiyonel)</label>
            <input class="form-control" name="email" type="email" placeholder="ornek@mail.com">
          </div>
          <div class="col-12">
            <label class="form-label">Yorum</label>
            <textarea class="form-control" name="content" rows="4" required></textarea>
            <div class="form-text text-muted">Spam koruması var. Çok kısa yorumlar kabul edilmez.</div>
          </div>
          <div class="col-12">
            <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Gönder</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
