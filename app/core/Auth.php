<?php
declare(strict_types=1);

final class Auth {
  public static function start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  }

  public static function check(): bool {
    self::start();
    return !empty($_SESSION['admin_id']);
  }

  public static function role(): string {
    self::start();
    return (string)($_SESSION['admin_role'] ?? 'admin');
  }

  public static function isAdmin(): bool {
    return self::role() === 'admin';
  }

  public static function requireLogin(): void {
    if (!self::check()) {
      redirect(base_url('admin/login.php'));
    }
  }

  public static function requireRole(array $roles): void {
    self::requireLogin();
    $r = self::role();
    if (!in_array($r, $roles, true)) {
      http_response_code(403);
      exit('Yetki yok.');
    }
  }

  public static function login(string $username, string $password): bool {
    self::start();
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT id, username, password_hash, role, is_active FROM users WHERE username = :u LIMIT 1");
    $st->execute([':u' => $username]);
    $u = $st->fetch();
    if (!$u) return false;
    if (!password_verify($password, $u['password_hash'])) return false;
    $role = (string)($u['role'] ?? '');
    if (!in_array($role, ['admin','editor'], true)) return false; // prevent site users from admin login
    if (isset($u['is_active']) && (int)$u['is_active'] === 0) return false;

    $_SESSION['admin_id'] = (int)$u['id'];
    $_SESSION['admin_user'] = (string)$u['username'];
    $_SESSION['admin_role'] = $role ?: 'admin';
    return true;
  }

  public static function logout(): void {
    self::start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
  }
}
