<?php
declare(strict_types=1);

require_once __DIR__ . '/app/core/Bootstrap.php';
require_once __DIR__ . '/app/models/Settings.php';
require_once __DIR__ . '/app/models/Sitemap.php';

// Auto-generate if missing or older than 6 hours
$path = __DIR__ . '/sitemap.xml';
$need = true;
if (file_exists($path)) {
  $age = time() - (int)filemtime($path);
  if ($age < 21600) $need = false;
}
if ($need) {
  Sitemap::generate(true);
}

header('Content-Type: application/xml; charset=utf-8');
readfile($path);
