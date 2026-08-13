<?php
// Firmalar için teklif listesi

require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Offer.php';

UserAuth::start();
if (!UserAuth::check()) redirect(base_url('giris'));

$pageTitle = 'Teklif Oluştur';
$pageDesc  = 'Tekliflerini oluştur, düzenle ve paylaş. Ürün kalemleri, indirim ve KDV otomatik hesaplanır.';
$noindex = true;

$flash = null; $flashType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      Offer::delete($id, UserAuth::id());
      $flash = 'Teklif silindi.';
      $flashType = 'success';
    }
  }
}

$offers = Offer::listByUser(UserAuth::id());
render('tools_teklif_index', compact('pageTitle','pageDesc','offers','flash','flashType','noindex'));
