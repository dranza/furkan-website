<?php
declare(strict_types=1);

final class Contact {
  public static function enabled(): bool {
    return ((Settings::get('contact_form_enabled','1') ?: '1') === '1');
  }

  private static function ipHash(): string {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $salt = Settings::get('analytics_salt','') ?? '';
    if ($salt === '') { $salt = bin2hex(random_bytes(16)); Settings::set('analytics_salt', $salt); }
    return $ip ? hash('sha256', $ip . '|' . $salt) : 'na';
  }

  public static function rateLimited(): bool {
    $pdo = DB::pdo();
    $h = self::ipHash();
    $st = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE ip_hash=:h AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $st->execute([':h'=>$h]);
    return ((int)$st->fetchColumn()) >= 3;
  }

  public static function submit(string $name, string $email, string $message, string $hp=''): array {
    if (!self::enabled()) return ['ok'=>false,'error'=>'Form kapalı.'];
    if ($hp !== '') return ['ok'=>false,'error'=>'Spam tespit edildi.'];

    $name = trim($name);
    $email = trim($email);
    $message = trim($message);

    if (mb_strlen($name) < 2) return ['ok'=>false,'error'=>'İsim çok kısa.'];
    if (mb_strlen($message) < 10) return ['ok'=>false,'error'=>'Mesaj çok kısa.'];
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok'=>false,'error'=>'E-posta formatı hatalı.'];

    if (self::rateLimited()) return ['ok'=>false,'error'=>'Çok hızlı gönderim. Lütfen biraz bekleyin.'];

    $pdo = DB::pdo();
    $h = self::ipHash();
    $ua  = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

    // Always store to DB so admin panel can see messages.
try {
  // ensure table exists
  $pdo->query("SELECT 1 FROM contact_messages LIMIT 1");
  $st = $pdo->prepare("INSERT INTO contact_messages (name,email,message,status,ip_hash,ua,created_at)
    VALUES (:n,:e,:m,'new',:h,:ua,NOW())");
  $st->execute([':n'=>$name,':e'=>$email?:null,':m'=>$message,':h'=>$h,':ua'=>$ua]);
} catch (Throwable $t) {
  error_log('CONTACT_SAVE_FAIL: ' . $t->getMessage());
  return ['ok'=>false,'error'=>'Mesaj kaydedilemedi (DB). Lütfen daha sonra tekrar deneyin.'];
}

    if ((Settings::get('contact_form_send_email','1') ?? '1') === '1') {
      $to = Settings::get('contact_email','') ?? '';
      if ($to) {
        $subj = "İletişim Formu - " . ($name ?: 'Ziyaretçi');
        $body = "Ad: {$name}\nE-posta: " . ($email ?: '-') . "\n\nMesaj:\n{$message}\n\nIP Hash: {$h}\n";
        $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
        if ($email) $headers .= "Reply-To: {$email}\r\n";
        @mail($to, '=?UTF-8?B?'.base64_encode($subj).'?=', $body, $headers);
      }
    }

    return ['ok'=>true];
  }

  public static function adminList(string $status='new', int $limit=200): array {
  $pdo = DB::pdo();
  $limit = max(1, min(500, (int)$limit));
  $status = in_array($status, ['new','read','archived'], true) ? $status : 'new';

  // Some hosts/drivers don't allow binding LIMIT; use integer in SQL safely
  $sql = "SELECT * FROM contact_messages WHERE status=:s ORDER BY created_at DESC LIMIT $limit";
  $st = $pdo->prepare($sql);
  $st->execute([':s'=>$status]);
  return $st->fetchAll();
}

  public static function adminCounts(): array {
    $pdo = DB::pdo();
    $rows = $pdo->query("SELECT status, COUNT(*) c FROM contact_messages GROUP BY status")->fetchAll();
    $out = ['new'=>0,'read'=>0,'archived'=>0];
    foreach ($rows as $r) $out[$r['status']] = (int)$r['c'];
    return $out;
  }

  public static function adminSetStatus(int $id, string $status): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("UPDATE contact_messages SET status=:s WHERE id=:id");
    $st->execute([':s'=>$status, ':id'=>$id]);
  }

  public static function adminDelete(int $id): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("DELETE FROM contact_messages WHERE id=:id");
    $st->execute([':id'=>$id]);
  }

public static function adminGet(int $id): ?array {
  $pdo = DB::pdo();
  $st = $pdo->prepare("SELECT * FROM contact_messages WHERE id=:id LIMIT 1");
  $st->execute([':id'=>$id]);
  $row = $st->fetch();
  return $row ?: null;
}

public static function adminUpdateMeta(int $id, string $tags='', string $adminNote=''): void {
  $pdo = DB::pdo();
  $tags = trim($tags);
  $adminNote = trim($adminNote);
  $st = $pdo->prepare("UPDATE contact_messages SET tags=:t, admin_note=:n, updated_at=NOW() WHERE id=:id");
  $st->execute([':t'=>$tags, ':n'=>$adminNote ?: null, ':id'=>$id]);
}

public static function adminSearchList(string $status='new', string $q='', int $limit=200): array {
  $pdo = DB::pdo();
  $limit = max(1, min(500, (int)$limit));
  $status = in_array($status, ['new','read','archived'], true) ? $status : 'new';
  $q = trim($q);

  $where = "status=:s";
  $params = [':s'=>$status];

  if ($q !== '') {
    $where .= " AND (name LIKE :q OR email LIKE :q OR message LIKE :q OR tags LIKE :q)";
    $params[':q'] = '%' . $q . '%';
  }

  $sql = "SELECT * FROM contact_messages WHERE $where ORDER BY created_at DESC LIMIT $limit";
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

public static function countByStatus(string $status='new'): int {
  $pdo = DB::pdo();
  $status = in_array($status, ['new','read','archived'], true) ? $status : 'new';
  $st = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE status=:s");
  $st->execute([':s'=>$status]);
  return (int)$st->fetchColumn();
}
}
