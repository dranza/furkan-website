<?php
declare(strict_types=1);

final class Skill {
  public static function all(): array {
    $pdo = DB::pdo();
    $st = $pdo->query("SELECT * FROM skills ORDER BY sort_order ASC, id DESC");
    return $st ? $st->fetchAll() : [];
  }

  public static function create(string $name, int $level=0, string $tags=''): array {
    $name = trim($name);
    if (mb_strlen($name) < 2) return ['ok'=>false,'error'=>'Beceri adı çok kısa.'];
    $level = max(0, min(100, (int)$level));
    $tags = trim($tags);

    $pdo = DB::pdo();
    $st = $pdo->prepare("INSERT INTO skills (name, level, tags, sort_order, created_at) VALUES (:n,:l,:t,0,NOW())");
    $st->execute([':n'=>$name, ':l'=>$level, ':t'=>$tags]);
    return ['ok'=>true];
  }

  public static function delete(int $id): void {
    $pdo = DB::pdo();
    $pdo->prepare("DELETE FROM skills WHERE id=:id")->execute([':id'=>$id]);
  }

  public static function updateOrder(array $ids): void {
    $pdo = DB::pdo();
    $ord = 0;
    foreach ($ids as $id) {
      $ord++;
      $pdo->prepare("UPDATE skills SET sort_order=:o WHERE id=:id")->execute([':o'=>$ord, ':id'=>(int)$id]);
    }
  }
}
