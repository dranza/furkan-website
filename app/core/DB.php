<?php
declare(strict_types=1);

final class DB {
  private static ?PDO $pdo = null;

  public static function pdo(): PDO {
    if (self::$pdo) return self::$pdo;
    $cfg = app_config();
    if (!$cfg) {
      throw new RuntimeException("Uygulama kurulu değil. /install/ ile kurulum yapın.");
    }
    $db = $cfg['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    $opt = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];
    self::$pdo = new PDO($dsn, $db['user'], $db['pass'], $opt);
    return self::$pdo;
  }
}
