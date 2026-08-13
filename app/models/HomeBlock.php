<?php
declare(strict_types=1);

final class HomeBlock {
  public static function bySection(string $section, bool $activeOnly=true): array {
    $pdo = DB::pdo();
    $sql = "SELECT * FROM home_blocks WHERE section=:s";
    if ($activeOnly) $sql .= " AND is_active=1";
    $sql .= " ORDER BY sort_order ASC, id ASC";
    $st = $pdo->prepare($sql);
    $st->execute([':s'=>$section]);
    return $st->fetchAll() ?: [];
  }

  public static function adminAll(string $section): array {
    return self::bySection($section, false);
  }

  public static function create(array $d): array {
    $pdo = DB::pdo();
    $section = trim((string)($d['section'] ?? ''));
    $title = trim((string)($d['title'] ?? ''));
    $body = trim((string)($d['body'] ?? ''));
    $icon = trim((string)($d['icon'] ?? 'bi-stars')) ?: 'bi-stars';
    $link = trim((string)($d['link_url'] ?? ''));
    $sort = (int)($d['sort_order'] ?? 0);
    $active = !empty($d['is_active']) ? 1 : 0;

    if ($section === '' || $title === '') return ['ok'=>false,'error'=>'Bölüm ve başlık zorunlu.'];

    $st = $pdo->prepare("INSERT INTO home_blocks (section,title,body,icon,link_url,sort_order,is_active,created_at,updated_at)
      VALUES (:s,:t,:b,:i,:l,:o,:a,NOW(),NOW())");
    $st->execute([':s'=>$section,':t'=>$title,':b'=>$body,':i'=>$icon,':l'=>$link,':o'=>$sort,':a'=>$active]);
    return ['ok'=>true,'id'=>(int)$pdo->lastInsertId()];
  }

  public static function update(int $id, array $d): array {
    $pdo = DB::pdo();
    $title = trim((string)($d['title'] ?? ''));
    $body = trim((string)($d['body'] ?? ''));
    $icon = trim((string)($d['icon'] ?? 'bi-stars')) ?: 'bi-stars';
    $link = trim((string)($d['link_url'] ?? ''));
    $sort = (int)($d['sort_order'] ?? 0);
    $active = !empty($d['is_active']) ? 1 : 0;

    if ($title === '') return ['ok'=>false,'error'=>'Başlık zorunlu.'];

    $st = $pdo->prepare("UPDATE home_blocks SET title=:t, body=:b, icon=:i, link_url=:l, sort_order=:o, is_active=:a, updated_at=NOW() WHERE id=:id");
    $st->execute([':t'=>$title,':b'=>$body,':i'=>$icon,':l'=>$link,':o'=>$sort,':a'=>$active,':id'=>$id]);
    return ['ok'=>true];
  }

  public static function delete(int $id): void {
    $pdo = DB::pdo();
    $pdo->prepare("DELETE FROM home_blocks WHERE id=:id")->execute([':id'=>$id]);
  }
}
