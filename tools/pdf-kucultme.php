<?php
// This file is included from index.php after Bootstrap is loaded.

require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Tool.php';

$pageTitle = 'PDF Küçültme';
$pageDesc  = 'PDF dosyalarının boyutunu küçültme aracı. Çoklu PDF ve klasör desteği.';

// Tool status + meta
Tool::ensure('pdf_compress', 'PDF Küçültme');
$tool = Tool::get('pdf_compress') ?? ['status'=>'active','meta'=>[]];
$toolStatus = (string)($tool['status'] ?? 'active');
$toolMeta = (array)($tool['meta'] ?? []);
$toolMsg = (string)($toolMeta['maintenance_message'] ?? 'Bu araç şu an bakımda. Lütfen daha sonra tekrar deneyin.');
$toolMessage = $toolMsg; // view compatibility

if ($toolStatus !== 'active') {
  $error = ($toolStatus === 'maintenance') ? $toolMsg : 'Bu araç şu an pasif.';
  render('tools_pdf_compress', compact('pageTitle','pageDesc','error'));
  return;
}

function _gs_exists(): bool {
  if (function_exists('shell_exec')) {
    $out = @shell_exec('gs -version 2>/dev/null');
    return is_string($out) && trim($out) !== '';
  }
  return false;
}

function _collect_files(array $filesSpec): array {
  $out = [];
  if (!isset($filesSpec['name'])) return $out;
  $isMulti = is_array($filesSpec['name']);
  if (!$isMulti) {
    $filesSpec = [
      'name' => [$filesSpec['name']],
      'type' => [$filesSpec['type'] ?? ''],
      'tmp_name' => [$filesSpec['tmp_name'] ?? ''],
      'error' => [$filesSpec['error'] ?? UPLOAD_ERR_NO_FILE],
      'size' => [$filesSpec['size'] ?? 0],
    ];
  }
  $n = count($filesSpec['name']);
  for ($i=0;$i<$n;$i++){
    if (($filesSpec['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
    $tmp = (string)($filesSpec['tmp_name'][$i] ?? '');
    if (!is_file($tmp)) continue;
    $name = (string)($filesSpec['name'][$i] ?? 'file.pdf');
    $out[] = ['tmp'=>$tmp,'name'=>$name];
  }
  return $out;
}

function _sanitize_name(string $name): string {
  $name = preg_replace('~[^a-zA-Z0-9\.\-\_\s]+~','', $name);
  $name = trim($name);
  if ($name === '') $name = 'dosya.pdf';
  return $name;
}

function _run_gs(string $in, string $out, string $preset): bool {
  $presetMap = [
    'screen'   => '/screen',
    'ebook'    => '/ebook',
    'printer'  => '/printer',
    'prepress' => '/prepress',
    'default'  => '/default',
  ];
  $ps = $presetMap[$preset] ?? '/ebook';

  $cmd = 'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dBATCH -dQUIET'
       . ' -dDetectDuplicateImages=true -dCompressFonts=true'
       . ' -dPDFSETTINGS=' . escapeshellarg($ps)
       . ' -sOutputFile=' . escapeshellarg($out)
       . ' ' . escapeshellarg($in) . ' 2>&1';

  $outText = @shell_exec($cmd);
  return is_file($out) && filesize($out) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // safety: refuse if tool was disabled after page load
  try {
    $tNow = Tool::get('pdf_compress');
    if ($tNow && ($tNow['status'] ?? 'active') !== 'active') {
      $error = (($tNow['status'] ?? '') === 'maintenance')
        ? (string)(($tNow['meta']['maintenance_message'] ?? '') ?: $toolMsg)
        : 'Bu araç şu an pasif.';
      render('tools_pdf_compress', compact('pageTitle','pageDesc','error'));
      exit;
    }
  } catch (Throwable $t) {}

  if (!_gs_exists()) {
    $error = 'Sunucuda Ghostscript (gs) bulunamadı veya çalıştırma izni yok. Bu araç için hosting tarafında Ghostscript gerekir.';
    render('tools_pdf_compress', compact('pageTitle','pageDesc','error'));
    exit;
  }

  $preset = (string)($_POST['preset'] ?? 'ebook');
  $outputMode = (string)($_POST['output'] ?? 'zip');

  $files = [];
  $files = array_merge($files, _collect_files($_FILES['files'] ?? []));
  $files = array_merge($files, _collect_files($_FILES['folder'] ?? []));

  // keep only PDFs
  $pdfs = [];
  foreach ($files as $f) {
    $n = strtolower((string)$f['name']);
    if (substr($n, -4) !== '.pdf') continue;
    $pdfs[] = $f;
  }
  if (count($pdfs) === 0) {
    $error = 'Lütfen en az 1 adet PDF seç.';
    render('tools_pdf_compress', compact('pageTitle','pageDesc','error'));
    exit;
  }

  $tmpBase = __DIR__ . '/../storage/tmp/pdfcompress_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
  @mkdir($tmpBase, 0755, true);

  $outputs = [];
  foreach ($pdfs as $f) {
    $origName = _sanitize_name($f['name']);
    $baseName = preg_replace('~\.pdf$~i','', $origName);
    $outFile = $tmpBase . '/' . $baseName . '_kucultulmus.pdf';
    if (_run_gs($f['tmp'], $outFile, $preset)) {
      $outputs[] = ['path'=>$outFile, 'name'=>basename($outFile)];
    }
  }

  if (count($outputs) === 0) {
    $error = 'PDF işlenemedi. Dosya bozuk olabilir veya Ghostscript çalıştırılamıyor.';
    render('tools_pdf_compress', compact('pageTitle','pageDesc','error'));
    // cleanup
    if (is_dir($tmpBase)) { foreach (glob($tmpBase.'/*') as $ff) @unlink($ff); @rmdir($tmpBase); }
    exit;
  }

  // Single file fast path
  if (count($outputs) === 1 && $outputMode === 'single') {
    // 1 işlem + işlenen PDF adedi
    try { Tool::recordUse('pdf_compress', 1, count($pdfs)); } catch (Throwable $t) {}
    $path = $outputs[0]['path'];
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . str_replace('"','', $outputs[0]['name']) . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    // cleanup
    foreach (glob($tmpBase.'/*') as $ff) @unlink($ff);
    @rmdir($tmpBase);
    exit;
  }

  // ZIP output
  // 1 işlem + çıktı adedi (PDF sayısı)
  try { Tool::recordUse('pdf_compress', 1, count($outputs)); } catch (Throwable $t) {}
  $zipPath = $tmpBase . '/pdf_kucultme_sonuclar.zip';
  $zip = new ZipArchive();
  $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
  foreach ($outputs as $o) {
    $zip->addFile($o['path'], $o['name']);
  }
  $zip->close();

  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="pdf_kucultme_sonuclar.zip"');
  header('Content-Length: ' . filesize($zipPath));
  header('X-Content-Type-Options: nosniff');
  readfile($zipPath);

  // cleanup
  foreach (glob($tmpBase.'/*') as $ff) @unlink($ff);
  @rmdir($tmpBase);
  exit;
}

// GET
render('tools_pdf_compress', compact('pageTitle','pageDesc','toolStatus','toolMessage'));
