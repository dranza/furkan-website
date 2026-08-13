<?php
require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Offer.php';
require_once __DIR__ . '/../app/models/FirmProfile.php';

UserAuth::start();
if (!UserAuth::check()) redirect(base_url('giris'));

$pageTitle = 'Yeni Teklif';
$pageDesc  = 'Ürün kalemleri ekle, indirim/KDV ayarla ve teklifini oluştur.';
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
      'status' => (string)($_POST['status'] ?? 'draft'),
    ];

    $itemsJson = (string)($_POST['items_json'] ?? '[]');
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) $items = [];
    if (count($items) < 1) throw new Exception('En az 1 ürün eklemelisin.');

    $id = Offer::save(UserAuth::id(), $payload, $items, null);
    $offer = Offer::getById($id, UserAuth::id());
    redirect(base_url('teklif/' . ($offer['public_code'] ?? '')));
  } catch (Throwable $t) {
    $error = $t->getMessage();
  }
}

// defaults
$fp = FirmProfile::get(UserAuth::id());
$defCurrency = (string)($fp['default_currency'] ?? 'TRY');
$defVat = (float)($fp['default_vat_rate'] ?? 20);

$default = [
  'title' => 'Teklif',
  'currency' => ($defCurrency ?: 'TRY'),
  'vat_rate' => ($defVat ?: 20),
  'discount_total' => 0,
  'is_public' => 1,
  'status' => 'draft',
];

render('tools_teklif_builder', compact('pageTitle','pageDesc','error','default','noindex'));
