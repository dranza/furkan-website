<?php
  $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$project['details'])) ?? '');
  $words = $plain ? str_word_count($plain) : 0;
  $readMin = max(1, (int)ceil($words / 200));

  // TOC (H2/H3)
  $toc = [];
  try {
    $html = (string)$project['details'];
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
    $body = $doc->getElementsByTagName('body')->item(0);
    if ($body) {
      $new = '';
      foreach ($body->childNodes as $ch) $new .= $doc->saveHTML($ch);
      $project['details'] = $new;
    }
  } catch (Throwable $t) { $toc = []; }

  $similarProjects = Projects::similar((int)$project['id'], (string)($project['technologies'] ?? ''), 3);
?>

<nav class="mb-3" aria-label="breadcrumb">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Anasayfa</a></li>
    <li class="breadcrumb-item"><a href="<?= e(base_url('projeler')) ?>"><?= e('Projeler') ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= e($project['title']) ?></li>
  </ol>
</nav>

<div class="sharebar my-3">
  <span class="label text-muted">Paylaş:</span>
  <?php
    $shareUrl = $canonical ?? base_url();
    $shareTitle = (string)$project['title'];
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
  <div class="text-muted small mb-2"><?= e(date('d.m.Y', strtotime((string)$project['published_at']))) ?> • <span class="text-muted">Okuma süresi: <?= (int)$readMin ?> dk</span></div>
  <h1 class="h4 fw-bold mb-2"><?= e($project['title']) ?></h1>
  <?php if (!empty($project['technologies'])): ?><div class="mb-3"><span class="badge bg-light text-dark"><?= e($project['technologies']) ?></span></div><?php endif; ?>
  <?php if (!empty($project['cover_image'])): ?>
    <img src="<?= e(base_url($project['cover_image'])) ?>" class="img-fluid rounded-4 mb-3" alt="<?= e($project['title']) ?>">
  <?php endif; ?>
  <?php if (!empty($project['summary'])): ?>
    <div class="alert alert-light border rounded-4"><?= e($project['summary']) ?></div>
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

  <div class="prose"><?= $project['details'] ?></div>
</article>

<style>
.prose img{max-width:100%; height:auto;}
.prose h2{margin-top:1.2rem;}
.prose p{margin-bottom:.8rem;}
</style>

<?php if (!empty($similarProjects)): ?>
<section class="mt-4">
  <div class="bg-white border rounded-4 shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 fw-bold mb-0">Benzer Projeler</h2>
      <a class="text-decoration-none" href="<?= e(base_url('projeler')) ?>">Projeler →</a>
    </div>
    <div class="row g-3">
      <?php foreach ($similarProjects as $sp): ?>
        <div class="col-md-4">
          <a class="text-decoration-none" href="<?= e(base_url('proje/'.$sp['slug'])) ?>">
            <div class="border rounded-4 p-3 h-100">
              <div class="small text-muted mb-1"><?= e($sp['published_at'] ? date('d.m.Y', strtotime((string)$sp['published_at'])) : '') ?></div>
              <div class="fw-semibold"><?= e($sp['title']) ?></div>
              <?php if (!empty($sp['technologies'])): ?><div class="text-muted small mt-1"><?= e($sp['technologies']) ?></div><?php endif; ?>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
