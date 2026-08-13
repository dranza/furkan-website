<?php
declare(strict_types=1);

final class Sitemap {
  public static function sitemapUrl(): string {
    $cfg = app_config();
    $base = rtrim($cfg['app']['base_url'] ?? '', '/');
    if ($base === '') {
      $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $base = $scheme . '://' . $host;
    }
    return $base . '/sitemap.xml';
  }

  public static function generate(bool $saveFile=true): string {
    $cfg = app_config();
    $base = rtrim($cfg['app']['base_url'] ?? '', '/');
    if ($base === '') {
      $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $base = $scheme . '://' . $host;
    }

    $urls = [];
    $urls[] = ['loc'=>$base.'/', 'lastmod'=>date('c')];
    foreach (['/hakkimda','/blog','/projeler','/iletisim'] as $p) {
      $urls[] = ['loc'=>$base.$p, 'lastmod'=>date('c')];
    }

    $pdo = DB::pdo();

    $posts = $pdo->query("SELECT slug, updated_at, published_at FROM blog_posts
      WHERE status='published' AND published_at IS NOT NULL AND published_at <= NOW()")->fetchAll();
    foreach ($posts as $p) {
      $lmSrc = $p['updated_at'] ?: ($p['published_at'] ?: null);
      $lm = $lmSrc ? date('c', strtotime((string)$lmSrc)) : date('c');
      $urls[] = ['loc'=>$base.'/blog/'.$p['slug'], 'lastmod'=>$lm];
    }

    $projs = $pdo->query("SELECT slug, updated_at, published_at FROM projects
      WHERE status='published' AND published_at IS NOT NULL AND published_at <= NOW()")->fetchAll();
    foreach ($projs as $p) {
      $lmSrc = $p['updated_at'] ?: ($p['published_at'] ?: null);
      $lm = $lmSrc ? date('c', strtotime((string)$lmSrc)) : date('c');
      $urls[] = ['loc'=>$base.'/proje/'.$p['slug'], 'lastmod'=>$lm];
    }

    // Downloads + Tools
    $urls[] = ['loc'=>$base.'/dokumanlar', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-kucultme', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-birlestirme', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/word-pdf', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/jpg-pdf', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-bol', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-sayfa-sil', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-dondur', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-filigran', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/resim-sikistir', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-sayfa-cikar', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-sayfa-numarasi', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-metadata', 'lastmod'=>date('c')];
    $urls[] = ['loc'=>$base.'/araclar/pdf-imza', 'lastmod'=>date('c')];
    try {
      $docs = $pdo->query("SELECT slug, updated_at, created_at FROM downloads WHERE is_public=1")->fetchAll();
      foreach ($docs as $d) {
        if (empty($d['slug'])) continue;
        $lmSrc = $d['updated_at'] ?: ($d['created_at'] ?: null);
        $lm = $lmSrc ? date('c', strtotime((string)$lmSrc)) : date('c');
        $urls[] = ['loc'=>$base.'/dokuman/'.$d['slug'], 'lastmod'=>$lm];
      }
    } catch (Throwable $t) {}

$xml =
 "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($urls as $u) {
      $xml .= "  <url><loc>".htmlspecialchars($u['loc'])."</loc><lastmod>{$u['lastmod']}</lastmod></url>\n";
    }
    $xml .= "</urlset>";

    if ($saveFile) {
      $path = __DIR__ . '/../../sitemap.xml';
      @file_put_contents($path, $xml);
      Settings::set('sitemap_last_generated', date('Y-m-d H:i:s'));
      // also write robots.txt
      $robots = "User-agent: *\nAllow: /\n\nSitemap: ".$base."/sitemap.xml\n";
      @file_put_contents(__DIR__ . '/../../robots.txt', $robots);
    }
    return $xml;
  }

  public static function maybeAutoGenerate(): void {
    if ((Settings::get('sitemap_auto','0') ?? '0') !== '1') return;
    $last = Settings::get('sitemap_last_generated','') ?? '';
    $lastTs = $last ? strtotime($last) : 0;
    // regenerate at most every 30 minutes
    if (time() - $lastTs < 1800) return;
    self::generate(true);
    if ((Settings::get('sitemap_ping','0') ?? '0') === '1') self::ping();
  }

  public static function ping(): array {
    $sitemap = self::sitemapUrl();
    $targets = [
      'google' => 'https://www.google.com/ping?sitemap=' . rawurlencode($sitemap),
      'bing'   => 'https://www.bing.com/ping?sitemap=' . rawurlencode($sitemap),
    ];

    $results = [];
    foreach ($targets as $k=>$u) {
      $ok = false;
      try {
        if (function_exists('curl_init')) {
          $ch = curl_init($u);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_TIMEOUT, 5);
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
          curl_setopt($ch, CURLOPT_USERAGENT, 'furkancihan-site-sitemap-ping/1.0');
          curl_exec($ch);
          $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
          curl_close($ch);
          $ok = ($code >= 200 && $code < 400);
          $results[$k] = ['code'=>$code,'ok'=>$ok];
        } else {
          $ctx = stream_context_create(['http'=>['timeout'=>5, 'header'=>"User-Agent: furkancihan-site-sitemap-ping/1.0\r\n"]]);
          @file_get_contents($u, false, $ctx);
          $results[$k] = ['code'=>0,'ok'=>true];
        }
      } catch (Throwable $t) {
        $results[$k] = ['code'=>0,'ok'=>false];
      }
    }
    Settings::set('sitemap_last_ping', date('Y-m-d H:i:s'));
    return $results;
  }
}
