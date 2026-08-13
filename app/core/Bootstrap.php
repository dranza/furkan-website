<?php
declare(strict_types=1);

function app_config(): array {
  static $cfg;
  if ($cfg !== null) return $cfg;
  $path = __DIR__ . '/../../config/config.php';
  if (!file_exists($path)) {
    // Not installed yet
    return [];
  }
  $cfg = require $path;
  return $cfg;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function base_url(string $path=''): string {
  $cfg = app_config();
  $b = $cfg['app']['base_url'] ?? '';
  return rtrim($b, '/') . '/' . ltrim($path, '/');
}

function redirect(string $url): void {
  header('Location: ' . $url);
  exit;
}

/** Runtime hardening (log path) */
$storageDir = __DIR__ . '/../../storage';
if (!is_dir($storageDir)) { @mkdir($storageDir, 0755, true); }
@ini_set('error_log', $storageDir . '/php-error.log');
@ini_set('display_errors', '0');


require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Migrations.php';
require_once __DIR__ . '/CSRF.php';
require_once __DIR__ . '/Slug.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/TOTP.php';
require_once __DIR__ . '/UserAuth.php';


// Start session early for CSRF + auth (prevents headers already sent issues)
try { UserAuth::start(); } catch (Throwable $t) {}
try { if (app_config()) { Migrations::run(); } } catch (Throwable $t) {}
