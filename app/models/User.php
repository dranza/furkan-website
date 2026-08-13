<?php
declare(strict_types=1);

final class User {

  public static function getById(int $id): ?array {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("SELECT id, username, email, display_name, created_at FROM users WHERE id=:id LIMIT 1");
      $st->execute([':id'=>$id]);
      $u = $st->fetch(PDO::FETCH_ASSOC);
      return $u ?: null;
    } catch (Throwable $t) {
      return null;
    }
  }
  public static function registrationEnabled(): bool {
    return ((Settings::get('registration_enabled','0') ?: '0') === '1');
  }

  public static function requireApproval(): bool {
    return ((Settings::get('registration_require_approval','1') ?: '1') === '1');
  }

  public static function create(string $username, string $email, string $password, string $displayName=''): array {
    if (!self::registrationEnabled()) return ['ok'=>false, 'error'=>'Kayıt kapalı.'];

    $username = trim($username);
    $email = trim($email);
    $displayName = trim($displayName);

    if (mb_strlen($username) < 3) return ['ok'=>false,'error'=>'Kullanıcı adı en az 3 karakter olmalı.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok'=>false,'error'=>'E-posta geçersiz.'];
    if (mb_strlen($password) < 8) return ['ok'=>false,'error'=>'Şifre en az 8 karakter olmalı.'];

    $pdo = DB::pdo();
    // Ensure uniqueness
    try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username=:u OR email=:e");
      $st->execute([':u'=>$username, ':e'=>$email]);
      if ((int)$st->fetchColumn() > 0) return ['ok'=>false,'error'=>'Kullanıcı adı veya e-posta zaten kayıtlı.'];
  } catch (Throwable $t) {
    return ['ok'=>false,'error'=>'Veritabanı güncellemesi gerekli (schema). Lütfen admin panelden Site Ayarları sayfasına girip bir kere kaydedin veya dosyaları tekrar yükleyin.'];
  }



    $active = self::requireApproval() ? 0 : 1;
    try {
    $st = $pdo->prepare("INSERT INTO users (username,email,display_name,password_hash,role,is_active,created_at)
                         VALUES (:u,:e,:d,:h,'user',:a,NOW())");
    $st->execute([
      ':u'=>$username,
      ':e'=>$email,
      ':d'=>$displayName ?: $username,
      ':h'=>password_hash($password, PASSWORD_DEFAULT),
      ':a'=>$active
    ]);
  } catch (Throwable $t) {
    return ['ok'=>false, 'error'=>'Kayıt oluşturulamadı (schema). Lütfen güncellemeleri kontrol edin.'];
  }

    return ['ok'=>true, 'active'=>$active===1];
  }

  public static function updateProfile(int $id, string $displayName, string $password=''): array {
    $displayName = trim($displayName);
    if ($displayName === '' || mb_strlen($displayName) < 2) return ['ok'=>false,'error'=>'Ad çok kısa.'];
    $pdo = DB::pdo();

    if ($password !== '') {
      if (mb_strlen($password) < 8) return ['ok'=>false,'error'=>'Şifre en az 8 karakter olmalı.'];
      $st = $pdo->prepare("UPDATE users SET display_name=:d, password_hash=:h WHERE id=:id AND role='user'");
      $st->execute([':d'=>$displayName, ':h'=>password_hash($password, PASSWORD_DEFAULT), ':id'=>$id]);
    } else {
      $st = $pdo->prepare("UPDATE users SET display_name=:d WHERE id=:id AND role='user'");
      $st->execute([':d'=>$displayName, ':id'=>$id]);
    }
    return ['ok'=>true];
  }

  public static function adminList(int $limit=500): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT id,username,email,display_name,is_active,created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT :l");
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function adminSetActive(int $id, int $active): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("UPDATE users SET is_active=:a WHERE id=:id AND role='user'");
    $st->execute([':a'=>$active, ':id'=>$id]);
  }

  public static function adminDelete(int $id): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("DELETE FROM users WHERE id=:id AND role='user'");
    $st->execute([':id'=>$id]);
  }
}
