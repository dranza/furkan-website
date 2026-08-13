<?php
// This file is included from index.php after Bootstrap is loaded.

require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Tool.php';

$pageTitle = 'PDF Birleştirme';
$pageDesc  = 'PDF dosyalarını tarayıcıda birleştir. Sürükle-bırak ile sırala, sayfa aralığı seç, önizleme al.';

Tool::ensure('pdf_merge', 'PDF Birleştirme');
$tool = Tool::get('pdf_merge') ?? ['status'=>'active','meta'=>[]];
$toolStatus = (string)($tool['status'] ?? 'active');
$toolMeta = (array)($tool['meta'] ?? []);
$toolMsg = (string)($toolMeta['maintenance_message'] ?? 'Bu araç şu an bakımda. Lütfen daha sonra tekrar deneyin.');
$toolMessage = $toolMsg; // view compatibility

// Lightweight usage recording (client-side tool). No CSRF required because it only updates counters.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $tNow = Tool::get('pdf_merge');
    if ($tNow && ($tNow['status'] ?? 'active') !== 'active') {
      http_response_code(403);
      echo json_encode(['ok'=>false,'error'=>'Tool disabled']);
      exit;
    }
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    $files = (int)($data['files'] ?? 1);
    if ($files < 1) $files = 1;
    if ($files > 200) $files = 200;
    Tool::recordUse('pdf_merge', 1, $files);
    echo json_encode(['ok'=>true]);
  } catch (Throwable $t) {
    http_response_code(200);
    echo json_encode(['ok'=>false]);
  }
  exit;
}

if ($toolStatus !== 'active') {
  $error = ($toolStatus === 'maintenance') ? $toolMsg : 'Bu araç şu an pasif.';
  render('tools_pdf_merge', compact('pageTitle','pageDesc','error'));
  return;
}

render('tools_pdf_merge', compact('pageTitle','pageDesc','toolStatus','toolMessage'));
