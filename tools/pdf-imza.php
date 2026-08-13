<?php
// Included from index.php after Bootstrap is loaded.

require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Tool.php';

$pageTitle = 'PDF İmza Ekle';
$pageDesc  = 'PDF’e imza (PNG/JPG) ekle. Sayfa seç, konum belirle, boyut ayarla. Dosyalar sunucuya yüklenmez.';

Tool::ensure('pdf_sign', 'PDF İmza Ekle');
$tool = Tool::get('pdf_sign') ?? ['status'=>'active','meta'=>[]];
$toolStatus = (string)($tool['status'] ?? 'active');
$toolMeta = (array)($tool['meta'] ?? []);
$toolMsg = (string)($toolMeta['maintenance_message'] ?? 'Bu araç şu an bakımda. Lütfen daha sonra tekrar deneyin.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $tNow = Tool::get('pdf_sign');
    if ($tNow && ($tNow['status'] ?? 'active') !== 'active') {
      http_response_code(403);
      echo json_encode(['ok'=>false,'error'=>'Tool disabled']);
      exit;
    }
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    $files = (int)($data['files'] ?? 1);
    if ($files < 1) $files = 1;
    if ($files > 50) $files = 50;
    Tool::recordUse('pdf_sign', 1, $files);
    echo json_encode(['ok'=>true]);
  } catch (Throwable $t) {
    http_response_code(200);
    echo json_encode(['ok'=>false]);
  }
  exit;
}

if ($toolStatus !== 'active') {
  $error = ($toolStatus === 'maintenance') ? $toolMsg : 'Bu araç şu an pasif.';
  render('tools_pdf_sign', compact('pageTitle','pageDesc','error'));
  return;
}

render('tools_pdf_sign', compact('pageTitle','pageDesc','toolStatus'));
