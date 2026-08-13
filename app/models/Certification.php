<?php
declare(strict_types=1);

final class Certification {
  public static function all(): array {
    $pdo = DB::pdo();
    $st = $pdo->query("SELECT * FROM certifications ORDER BY issue_year DESC, id DESC");
    return $st ? $st->fetchAll() : [];
  }

  public static function create(string $title, string $issuer, int $issueYear=0, string $credentialUrl='', string $logoPath='', string $description='', string $filePath=''): array {
    $title = trim($title);
    $issuer = trim($issuer);
    $credentialUrl = trim($credentialUrl);
    $logoPath = trim($logoPath);
    $description = trim($description);
    $filePath = trim($filePath);

    if (mb_strlen($title) < 3) return ['ok'=>false,'error'=>'Sertifika adı çok kısa.'];
    if (mb_strlen($issuer) < 2) return ['ok'=>false,'error'=>'Kurum alanı çok kısa.'];
    $issueYear = (int)$issueYear;
    if ($issueYear < 0) $issueYear = 0;

    $pdo = DB::pdo();
    $st = $pdo->prepare("INSERT INTO certifications (title, issuer, issue_year, credential_url, logo_path, description, file_path, created_at)
                         VALUES (:t,:i,:y,:u,:l,:d,:f,NOW())");
    $st->execute([':t'=>$title, ':i'=>$issuer, ':y'=>$issueYear, ':u'=>$credentialUrl, ':l'=>$logoPath, ':d'=>$description, ':f'=>$filePath]);
    return ['ok'=>true];
  }

  public static function delete(int $id): void {
    $pdo = DB::pdo();
    $pdo->prepare("DELETE FROM certifications WHERE id=:id")->execute([':id'=>$id]);
  }
}
