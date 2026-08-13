<?php
// Herkese açık teklif oluştur (üyelik gerekmez) - Print/PDF

require_once __DIR__ . '/../app/models/Settings.php';

$metaTitle = 'Teklif Oluştur - ' . (Settings::get('site_name','') ?? '');
$canonical = base_url('araclar/teklif-olustur');
$ogType = 'website';
$noindex = true;

render('tools_teklif_public', compact('metaTitle','canonical','ogType','noindex'));
