<?php
declare(strict_types=1);

final class Offer {

  private static function randCode(int $len=12): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i=0; $i<$len; $i++) {
      $out .= $chars[random_int(0, strlen($chars)-1)];
    }
    return $out;
  }

  /** Generates offer_no per user with template (firm profile) */
  private static function nextOfferNo(PDO $pdo, int $userId): string {
    require_once __DIR__ . "/FirmProfile.php";
    $fp = FirmProfile::get($userId);

    $tpl = (string)($fp["offer_no_template"] ?? "TKF-{YYYY}-{SEQ4}");
    if ($tpl === "" || stripos($tpl, "{SEQ") === false) $tpl = "TKF-{YYYY}-{SEQ4}";

    $year = (int)date("Y");
    $st = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE user_id=? AND YEAR(created_at)=?");
    $st->execute([$userId, $year]);
    $seq = ((int)$st->fetchColumn()) + 1;

    $tpl = str_replace(["{YYYY}","{YY}","{MM}"], [date("Y"), date("y"), date("m")], $tpl);
    $tpl = preg_replace_callback("/\{SEQ(\d*)\}/i", function($m) use ($seq) {
      $pad = (int)($m[1] ?? 0);
      if ($pad <= 0) return (string)$seq;
      return str_pad((string)$seq, $pad, "0", STR_PAD_LEFT);
    }, $tpl);

    return $tpl;
  }

  public static function listByUser(int $userId, int $limit=200): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM offers WHERE user_id=? ORDER BY id DESC LIMIT ?");
    $st->bindValue(1, $userId, PDO::PARAM_INT);
    $st->bindValue(2, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getById(int $id, int $userId): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM offers WHERE id=? AND user_id=? LIMIT 1");
    $st->execute([$id, $userId]);
    $o = $st->fetch(PDO::FETCH_ASSOC);
    if (!$o) return null;
    $o['items'] = self::items((int)$o['id']);
    return $o;
  }

  public static function getByCode(string $code): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM offers WHERE public_code=? LIMIT 1");
    $st->execute([$code]);
    $o = $st->fetch(PDO::FETCH_ASSOC);
    if (!$o) return null;
    $o['items'] = self::items((int)$o['id']);
    return $o;
  }

  public static function items(int $offerId): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM offer_items WHERE offer_id=? ORDER BY ord ASC, id ASC");
    $st->execute([$offerId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Save or update offer.
   */
  public static function save(int $userId, array $payload, array $items, ?int $offerId=null): int {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
      $currency = (string)($payload['currency'] ?? 'TRY');
      if (!in_array($currency, ['TRY','USD','EUR'], true)) $currency = 'TRY';

      $vat = (float)($payload['vat_rate'] ?? 20);
      if ($vat < 0) $vat = 0; if ($vat > 50) $vat = 50;
      $discTotal = (float)($payload['discount_total'] ?? 0);
      if ($discTotal < 0) $discTotal = 0;

      // Offers are always public (shareable) per requirement.
      // Keep DB column for backwards compatibility.
      $isPublic = 1;

      $status = (string)($payload['status'] ?? 'draft');
      $allowed = ['draft','sent','approved','rejected'];
      if (!in_array($status, $allowed, true)) $status = 'draft';

      if ($offerId) {
        $st = $pdo->prepare("SELECT id FROM offers WHERE id=? AND user_id=? LIMIT 1");
        $st->execute([$offerId, $userId]);
        if (!$st->fetchColumn()) throw new Exception('Yetkisiz.');

        $st = $pdo->prepare("UPDATE offers SET title=?, currency=?, vat_rate=?, discount_total=?, customer_name=?, customer_company=?, customer_email=?, customer_phone=?, customer_address=?, notes=?, is_public=?, status=?, sent_at = IF(?='sent' AND (sent_at IS NULL), NOW(), sent_at), decided_at = IF((?='approved' OR ?='rejected') AND (decided_at IS NULL), NOW(), decided_at) WHERE id=?");
        $st->execute([
          trim((string)($payload['title'] ?? 'Teklif')),
          $currency,
          $vat,
          $discTotal,
          trim((string)($payload['customer_name'] ?? '')),
          trim((string)($payload['customer_company'] ?? '')),
          trim((string)($payload['customer_email'] ?? '')),
          trim((string)($payload['customer_phone'] ?? '')),
          trim((string)($payload['customer_address'] ?? '')),
          trim((string)($payload['notes'] ?? '')),
          $isPublic,
          $status, $status, $status, $status,
          $offerId
        ]);
        $pdo->prepare("DELETE FROM offer_items WHERE offer_id=?")->execute([$offerId]);
        $id = $offerId;
      } else {
        do {
          $code = self::randCode(12);
          $st = $pdo->prepare("SELECT id FROM offers WHERE public_code=? LIMIT 1");
          $st->execute([$code]);
          $exists = $st->fetchColumn();
        } while ($exists);

        $offerNo = self::nextOfferNo($pdo, $userId);

        $st = $pdo->prepare("INSERT INTO offers(user_id, public_code, offer_no, title, currency, vat_rate, discount_total, customer_name, customer_company, customer_email, customer_phone, customer_address, notes, is_public, status, created_at)
          VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $st->execute([
          $userId,
          $code,
          $offerNo,
          trim((string)($payload['title'] ?? 'Teklif')),
          $currency,
          $vat,
          $discTotal,
          trim((string)($payload['customer_name'] ?? '')),
          trim((string)($payload['customer_company'] ?? '')),
          trim((string)($payload['customer_email'] ?? '')),
          trim((string)($payload['customer_phone'] ?? '')),
          trim((string)($payload['customer_address'] ?? '')),
          trim((string)($payload['notes'] ?? '')),
          $isPublic,
          $status
        ]);
        $id = (int)$pdo->lastInsertId();
      }

      $ord = 0;
      foreach ($items as $row) {
        $name = trim((string)($row['name'] ?? ''));
        if ($name === '') continue;
        $ord++;
        $qty = (float)($row['qty'] ?? 1);
        if ($qty <= 0) $qty = 1;
        $unit = (float)($row['unit_price'] ?? 0);
        if ($unit < 0) $unit = 0;
        $dPct = (float)($row['discount_percent'] ?? 0);
        if ($dPct < 0) $dPct = 0; if ($dPct > 100) $dPct = 100;
        $dAmt = (float)($row['discount_amount'] ?? 0);
        if ($dAmt < 0) $dAmt = 0;
        $iVat = isset($row['vat_rate']) ? (float)$row['vat_rate'] : $vat;
        if ($iVat < 0) $iVat = 0; if ($iVat > 50) $iVat = 50;

        $pdo->prepare("INSERT INTO offer_items(offer_id, ord, name, qty, unit_price, discount_percent, discount_amount, vat_rate)
          VALUES(?,?,?,?,?,?,?,?)")
          ->execute([$id, $ord, $name, $qty, $unit, $dPct, $dAmt, $iVat]);
      }

      $pdo->commit();
      return $id;
    } catch (Throwable $t) {
      $pdo->rollBack();
      throw $t;
    }
  }

  public static function delete(int $id, int $userId): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("DELETE FROM offers WHERE id=? AND user_id=?");
    $st->execute([$id, $userId]);
  }

  public static function computeTotals(array $offer): array {
    $items = $offer['items'] ?? [];
    $sub = 0.0;
    $vatTotal = 0.0;
    $discountItems = 0.0;

    foreach ($items as &$it) {
      $qty = (float)($it['qty'] ?? 1);
      $unit = (float)($it['unit_price'] ?? 0);
      $line = $qty * $unit;
      $dPct = (float)($it['discount_percent'] ?? 0);
      $dAmt = (float)($it['discount_amount'] ?? 0);
      $d = ($line * ($dPct/100.0)) + $dAmt;
      if ($d > $line) $d = $line;
      $net = max(0.0, $line - $d);
      $vRate = (float)($it['vat_rate'] ?? ($offer['vat_rate'] ?? 0));
      $v = $net * ($vRate/100.0);
      $it['_line_gross'] = $line;
      $it['_line_discount'] = $d;
      $it['_line_net'] = $net;
      $it['_line_vat'] = $v;
      $it['_line_total'] = $net + $v;

      $sub += $net;
      $vatTotal += $v;
      $discountItems += $d;
    }

    $offerDisc = (float)($offer['discount_total'] ?? 0);
    if ($offerDisc < 0) $offerDisc = 0;

    $grandBefore = $sub + $vatTotal;
    $grand = max(0.0, $grandBefore - $offerDisc);

    return [
      'sub_total' => $sub,
      'vat_total' => $vatTotal,
      'items_discount_total' => $discountItems,
      'offer_discount_total' => $offerDisc,
      'grand_before_offer_discount' => $grandBefore,
      'grand_total' => $grand,
      'items' => $items,
    ];
  }
}
