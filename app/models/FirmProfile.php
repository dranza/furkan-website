<?php
declare(strict_types=1);

final class FirmProfile {

  public static function get(int $userId): array {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("SELECT * FROM firm_profiles WHERE user_id=? LIMIT 1");
      $st->execute([$userId]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      if ($r) return $r;
    } catch (Throwable $t) {}

    return [
      'user_id'=>$userId,
      'company_name'=>'',
      'tax_office'=>'',
      'tax_no'=>'',
      'iban'=>'',
      'address'=>'',
      'phone'=>'',
      'email'=>'',
      'logo_path'=>'',
      'signature_path'=>'',
      'stamp_path'=>'',
      'offer_no_template'=>'TKF-{YYYY}-{SEQ4}',
      'default_currency'=>'TRY',
      'default_vat_rate'=>'20.00'
    ];
  }

  public static function upsert(int $userId, array $d): void {
    $pdo = DB::pdo();
    $pdo->prepare("INSERT IGNORE INTO firm_profiles(user_id) VALUES (?)")->execute([$userId]);

    $tpl = trim((string)($d['offer_no_template'] ?? 'TKF-{YYYY}-{SEQ4}'));
    if ($tpl === '' || stripos($tpl, '{SEQ') === false) $tpl = 'TKF-{YYYY}-{SEQ4}';

    $st = $pdo->prepare("UPDATE firm_profiles SET
      company_name=:company_name,
      tax_office=:tax_office,
      tax_no=:tax_no,
      iban=:iban,
      address=:address,
      phone=:phone,
      email=:email,
      logo_path=:logo_path,
      signature_path=:signature_path,
      stamp_path=:stamp_path,
      offer_no_template=:offer_no_template,
      default_currency=:default_currency,
      default_vat_rate=:default_vat_rate
      WHERE user_id=:user_id");

    $st->execute([
      'company_name'=>trim((string)($d['company_name'] ?? '')),
      'tax_office'=>trim((string)($d['tax_office'] ?? '')),
      'tax_no'=>trim((string)($d['tax_no'] ?? '')),
      'iban'=>trim((string)($d['iban'] ?? '')),
      'address'=>trim((string)($d['address'] ?? '')),
      'phone'=>trim((string)($d['phone'] ?? '')),
      'email'=>trim((string)($d['email'] ?? '')),
      'logo_path'=>(string)($d['logo_path'] ?? ''),
      'signature_path'=>(string)($d['signature_path'] ?? ''),
      'stamp_path'=>(string)($d['stamp_path'] ?? ''),
      'offer_no_template'=>$tpl,
      'default_currency'=>in_array(($d['default_currency'] ?? 'TRY'), ['TRY','USD','EUR'], true) ? (string)$d['default_currency'] : 'TRY',
      'default_vat_rate'=>(string)(float)($d['default_vat_rate'] ?? 20),
      'user_id'=>$userId
    ]);
  }
}
