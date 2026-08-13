<?php
declare(strict_types=1);

final class CSRF {
  public static function token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['_csrf'])) {
      $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
  }

  /** Returns true if token is valid. Does NOT exit. */
  public static function check(?string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['_csrf']) && is_string($token) && hash_equals((string)$_SESSION['_csrf'], $token);
  }

  /** Backward-compatible strict check: exits with 403 on failure. */
  public static function checkOrExit(?string $token): void {
    if (!self::check($token)) {
      http_response_code(403);
      exit('CSRF doğrulaması başarısız.');
    }
  }
}
