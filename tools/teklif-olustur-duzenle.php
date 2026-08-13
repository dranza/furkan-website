<?php
require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Offer.php';

UserAuth::start();
if (!UserAuth::check()) redirect(base_url('giris'));

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); echo 'Teklif bulunamadı.'; return; }

$offer = Offer::getById($id, UserAuth::id());
if (!$offer) { http_response_code(404); echo 'Teklif bulunamadı.'; return; }

$pageTitle = 'Teklifi Düzenle';
$pageDesc  = 'Teklif bilgilerini güncelle.';
$noindex = true;

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  try {
    $payload = [
      'title' => (string)($_POST['title'] ?? 'Teklif'),
      'currency' => (string)($_POST['currency'] ?? 'TRY'),
      'vat_rate' => (float)($_POST['vat_rate'] ?? 20),
      'discount_total' => (float)($_POST['discount_total'] ?? 0),
      'customer_name' => (string)($_POST['customer_name'] ?? ''),
      'customer_company' => (string)($_POST['customer_company'] ?? ''),
      'customer_email' => (string)($_POST['customer_email'] ?? ''),
      'customer_phone' => (string)($_POST['customer_phone'] ?? ''),
      'customer_address' => (string)($_POST['customer_address'] ?? ''),
      'notes' => (string)($_POST['notes'] ?? ''),
      'is_public' => (int)($_POST['is_public'] ?? 1) === 1,
      'status' => (string)($_POST['status'] ?? (string)($offer['status'] ?? 'draft')),
    ];

    $itemsJson = (string)($_POST['items_json'] ?? '[]');
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) $items = [];
    if (count($items) < 1) throw new Exception('En az 1 ürün eklemelisin.');

    Offer::save(UserAuth::id(), $payload, $items, $id);
    $offer = Offer::getById($id, UserAuth::id());
    redirect(base_url('teklif/' . ($offer['public_code'] ?? '')));
  } catch (Throwable $t) {
    $error = $t->getMessage();
  }
}

$default = $offer;
$default['items'] = $offer['items'] ?? [];

render('tools_teklif_builder', compact('pageTitle','pageDesc','error','default','offer','noindex'));
