<?php
declare(strict_types=1);

final class UserAuth {
  public static function start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  }

  public static function check(): bool {
    self::start();
    return !empty($_SESSION['user_id']);
  }

  public static function id(): int {
    self::start();
    return (int)($_SESSION['user_id'] ?? 0);
  }

  public static function name(): string {
    self::start();
    return (string)($_SESSION['user_name'] ?? '');
  }

  public static function logout(): void {
    self::start();
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
  }

  public static function login(string $usernameOrEmail, string $password): bool {
    self::start();
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("SELECT id, username, email, display_name, password_hash, is_active, role
                           FROM users WHERE (username=:u OR email=:u) LIMIT 1");
      $st->execute([':u'=>$usernameOrEmail]);
      $u = $st->fetch();
    } catch (Throwable $t) {
      // fallback for older schemas
      $st = $pdo->prepare("SELECT id, username, password_hash, role
                           FROM users WHERE username=:u LIMIT 1");
      $st->execute([':u'=>$usernameOrEmail]);
      $u = $st->fetch();
      if ($u) { $u['is_active'] = 1; $u['display_name'] = $u['username']; $u['email'] = null; }
    }
    if (!$u) return false;
    if ((string)($u['role'] ?? '') !== 'user') return false;
    if ((int)($u['is_active'] ?? 0) !== 1) return false;
    if (!password_verify($password, (string)$u['password_hash'])) return false;

    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['user_name'] = (string)($u['display_name'] ?: $u['username']);
    $_SESSION['user_role'] = 'user';
    return true;
  }
}
