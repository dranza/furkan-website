<?php
declare(strict_types=1);

final class Projects {
  public static function latest(int $limit=6): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM projects WHERE status='published' AND published_at IS NOT NULL AND published_at <= NOW() ORDER BY published_at DESC LIMIT :l");
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function featured(int $limit=6): array {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("SELECT * FROM projects WHERE status='published' AND published_at IS NOT NULL AND published_at <= NOW() AND featured=1 ORDER BY published_at DESC LIMIT :l");
      $st->bindValue(':l', $limit, PDO::PARAM_INT);
      $st->execute();
      return $st->fetchAll();
    } catch (Throwable $t) {
      return [];
    }
  }

  public static function list(int $page=1, int $per=10, array $filters=[]): array {
    $pdo = DB::pdo();
    $offset = max(0, ($page-1)*$per);

    $where = ["status='published'", "published_at IS NOT NULL", "published_at <= NOW()"];
    $params = [];
    

    if (!empty($filters['tech'])) {
      $where[] = "technologies LIKE :tech";
      $params[':tech'] = '%' . $filters['tech'] . '%';
    }

    if (!empty($filters['q'])) {
      $where[] = "(title LIKE :q OR summary LIKE :q OR details LIKE :q)";
      $params[':q'] = '%' . $filters['q'] . '%';
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM projects WHERE " . implode(' AND ', $where)
         . " ORDER BY published_at DESC LIMIT :o,:p";

    $st = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
    $st->bindValue(':o', $offset, PDO::PARAM_INT);
    $st->bindValue(':p', $per, PDO::PARAM_INT);
    $st->execute();

    $items = $st->fetchAll();
    $total = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    return ['items'=>$items,'total'=>$total,'page'=>$page,'per'=>$per];
  }

public static function similar(int $id, string $technologies, int $limit=3): array {
  $pdo = DB::pdo();
  $where = ["status='published'", "published_at IS NOT NULL", "published_at <= NOW()", "id <> :id"];
  $params = [':id'=>$id];

  $techList = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $technologies) ?: [])));
  $conds = [];
  foreach ($techList as $i=>$t) {
    $k = ':t'.$i;
    $conds[] = "technologies LIKE $k";
    $params[$k] = '%'.$t.'%';
    if ($i >= 5) break;
  }
  if ($conds) $where[] = '(' . implode(' OR ', $conds) . ')';

  $sql = "SELECT id,title,slug,technologies,published_at FROM projects WHERE ".implode(' AND ', $where)." ORDER BY published_at DESC LIMIT 40";
  $st = $pdo->prepare($sql);
  foreach ($params as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
  $st->execute();
  $rows = $st->fetchAll();

  $scores = [];
  foreach ($rows as $r) {
    $score = 0;
    $rtech = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string)($r['technologies'] ?? '')) ?: [])));
    foreach ($techList as $t) if ($t && in_array($t, $rtech, true)) $score += 2;
    $scores[] = ['row'=>$r,'score'=>$score];
  }
  usort($scores, function($a,$b){
    if ($a['score'] === $b['score']) return strcmp((string)$b['row']['published_at'], (string)$a['row']['published_at']);
    return $b['score'] <=> $a['score'];
  });
  $out = [];
  foreach ($scores as $s) {
    if (count($out) >= $limit) break;
    if ($s['score'] <= 0) continue;
    $out[] = $s['row'];
  }
  return $out;
}

public static function bySlug(string $slug): ?array {

    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM projects WHERE slug=:s AND status='published' AND published_at IS NOT NULL AND published_at <= NOW() LIMIT 1");
    $st->execute([':s'=>$slug]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function adminAll(): array {
    $pdo = DB::pdo();
    return $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
  }

  public static function adminGet(int $id): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM projects WHERE id=:id");
    $st->execute([':id'=>$id]);
    $r = $st->fetch();
    return $r ?: null;
  }

  public static function adminSave(array $d): int {
    $pdo = DB::pdo();
    $now = date('Y-m-d H:i:s');
    $isNew = empty($d['id']);
    $slug = $d['slug'] ?: Slug::make($d['title']);
    $featured = !empty($d['featured']) ? 1 : 0;

    if ($isNew) {
      $st = $pdo->prepare("INSERT INTO projects
        (title,slug,summary,details,technologies,cover_image,meta_title,meta_desc,featured,status,created_at,updated_at,published_at)
        VALUES (:t,:s,:sm,:dt,:tech,:ci,:mt,:md,:f,:st,:ca,:ua,:pa)");
      $st->execute([
        ':t'=>$d['title'], ':s'=>$slug, ':sm'=>$d['summary'], ':dt'=>$d['details'], ':tech'=>$d['technologies'],
        ':ci'=>$d['cover_image'], ':mt'=>$d['meta_title'], ':md'=>$d['meta_desc'], ':f'=>$featured,
        ':st'=>$d['status'], ':ca'=>$now, ':ua'=>$now,
        ':pa'=> $d['status']==='published' ? ($d['published_at'] ?: $now) : null
      ]);
      return (int)$pdo->lastInsertId();
    } else {
      $st = $pdo->prepare("UPDATE projects SET
        title=:t, slug=:s, summary=:sm, details=:dt, technologies=:tech, cover_image=:ci,
        meta_title=:mt, meta_desc=:md, featured=:f, status=:st, updated_at=:ua, published_at=:pa
        WHERE id=:id");
      $st->execute([
        ':id'=>$d['id'], ':t'=>$d['title'], ':s'=>$slug, ':sm'=>$d['summary'], ':dt'=>$d['details'],
        ':tech'=>$d['technologies'], ':ci'=>$d['cover_image'],
        ':mt'=>$d['meta_title'], ':md'=>$d['meta_desc'], ':f'=>$featured, ':st'=>$d['status'],
        ':ua'=>$now, ':pa'=> $d['status']==='published' ? ($d['published_at'] ?: $now) : null
      ]);
      return (int)$d['id'];
    }
  }

  public static function adminDelete(int $id): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("DELETE FROM projects WHERE id=:id");
    $st->execute([':id'=>$id]);
  }

  public static function topTechnologies(int $limit=24): array {
    $pdo = DB::pdo();
    $rows = $pdo->query("SELECT technologies FROM projects WHERE status='published' AND published_at IS NOT NULL AND published_at <= NOW() AND technologies IS NOT NULL AND technologies<>''")->fetchAll();
    $counts = [];
    foreach ($rows as $r) {
      $parts = array_filter(array_map('trim', explode(',', (string)$r['technologies'])));
      foreach ($parts as $p) {
        $k = mb_strtolower($p);
        $counts[$k] = ($counts[$k] ?? 0) + 1;
      }
    }
    arsort($counts);
    $out = [];
    foreach ($counts as $k=>$v) {
      $out[] = ['tech'=>$k, 'count'=>$v];
      if (count($out) >= $limit) break;
    }
    return $out;
  }
}
