<?php
declare(strict_types=1);

final class Settings {
  public static function getAll(): array {
    $pdo = DB::pdo();
    $rows = $pdo->query("SELECT k, v FROM settings")->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[$r['k']] = $r['v'];
    return $out;
  }

  public static function get(string $k, ?string $default=null): ?string {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT v FROM settings WHERE k=:k LIMIT 1");
    $st->execute([':k'=>$k]);
    $row = $st->fetch();
    return $row ? (string)$row['v'] : $default;
  }

  public static function set(string $k, string $v): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("INSERT INTO settings (k,v) VALUES (:k,:v)
      ON DUPLICATE KEY UPDATE v=VALUES(v)");
    $st->execute([':k'=>$k, ':v'=>$v]);
  }
}
