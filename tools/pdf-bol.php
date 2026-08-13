<?php
require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Tool.php';

$pageTitle = 'PDF Böl';
$pageDesc  = 'PDF dosyasını sayfalara veya seçtiğin aralıklara göre böl. Her şey tarayıcı içinde çalışır.';

Tool::ensure('pdf_split', 'PDF Böl');
$tool = Tool::get('pdf_split') ?? ['status'=>'active','meta'=>[]];
$toolStatus = (string)($tool['status'] ?? 'active');
$toolMeta = (array)($tool['meta'] ?? []);
$toolMsg = (string)($toolMeta['maintenance_message'] ?? 'Bu araç şu an bakımda. Lütfen daha sonra tekrar deneyin.');
$toolMessage = $toolMsg;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $tNow = Tool::get('pdf_split');
    if ($tNow && ($tNow['status'] ?? 'active') !== 'active') {
      http_response_code(403);
      echo json_encode(['ok'=>false,'error'=>'Tool disabled']);
      exit;
    }
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    $out = (int)($data['outputs'] ?? 1);
    $pages = (int)($data['pages'] ?? 1);
    if ($out < 1) $out = 1;
    if ($out > 500) $out = 500;
    if ($pages < 1) $pages = 1;
    if ($pages > 5000) $pages = 5000;
    Tool::recordUse('pdf_split', 1, $pages);
    echo json_encode(['ok'=>true]);
  } catch (Throwable $t) {
    http_response_code(200);
    echo json_encode(['ok'=>false]);
  }
  exit;
}

if ($toolStatus !== 'active') {
  $error = ($toolStatus === 'maintenance') ? $toolMsg : 'Bu araç şu an pasif.';
  render('tools_pdf_split', compact('pageTitle','pageDesc','error'));
  return;
}

render('tools_pdf_split', compact('pageTitle','pageDesc','toolStatus','toolMessage'));
