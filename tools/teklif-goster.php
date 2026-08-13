<?php
require_once __DIR__ . '/../app/models/Settings.php';
require_once __DIR__ . '/../app/models/Offer.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/FirmProfile.php';

$code = (string)($_GET['code'] ?? '');
if ($code === '') { http_response_code(404); echo 'Teklif bulunamadı.'; return; }

$offer = Offer::getByCode($code);
if (!$offer) { http_response_code(404); echo 'Teklif bulunamadı.'; return; }

// Offers are always public. Owner is detected only to show the "Edit" button.
UserAuth::start();
$isOwner = UserAuth::check() && (int)$offer['user_id'] === UserAuth::id();

$pageTitle = (($offer['title'] ?? 'Teklif') . ' - ' . (Settings::get('site_name','') ?? ''));
$pageDesc  = 'Teklif görüntüleme.';
$noindex = true;

// firm info from user profile
$owner = User::getById((int)$offer['user_id']);
$firm = FirmProfile::get((int)$offer['user_id']);

$totals = Offer::computeTotals($offer);

render('tools_teklif_view', compact('pageTitle','pageDesc','offer','owner','firm','totals','isOwner','noindex'));
