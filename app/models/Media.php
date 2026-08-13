<?php
declare(strict_types=1);

final class Media {
  public static function latest(int $limit=60): array {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("SELECT * FROM media ORDER BY created_at DESC LIMIT :l");
      $st->bindValue(':l', $limit, PDO::PARAM_INT);
      $st->execute();
      return $st->fetchAll();
    } catch (Throwable $t) {
      return [];
    }
  }

  public static function add(string $filePath, string $originalName, string $mime, int $sizeBytes): void {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("INSERT INTO media (file_path, original_name, mime, size_bytes, created_at)
        VALUES (:p,:o,:m,:s, NOW())");
      $st->execute([':p'=>$filePath, ':o'=>$originalName, ':m'=>$mime, ':s'=>$sizeBytes]);
    } catch (Throwable $t) {
      // ignore
    }
  }
}
