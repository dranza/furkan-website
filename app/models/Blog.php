<?php
declare(strict_types=1);

final class Blog {
  public static function latest(int $limit=6): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM blog_posts WHERE status='published' AND published_at IS NOT NULL AND published_at <= NOW() ORDER BY published_at DESC LIMIT :l");
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function list(int $page=1, int $per=10, array $filters=[]): array {
    $pdo = DB::pdo();
    $offset = max(0, ($page-1)*$per);

    $where = ["status='published'", "published_at IS NOT NULL", "published_at <= NOW()"];
    $params = [];
    

    if (!empty($filters['category'])) {
      $where[] = "category = :cat";
      $params[':cat'] = $filters['category'];
    }

    if (!empty($filters['tag'])) {
      // simple contains; assumes tags stored as comma separated
      $where[] = "tags LIKE :tag";
      $params[':tag'] = '%' . $filters['tag'] . '%';
    }

    if (!empty($filters['q'])) {
      $where[] = "(title LIKE :q OR content LIKE :q)";
      $params[':q'] = '%' . $filters['q'] . '%';
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM blog_posts WHERE " . implode(' AND ', $where)
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

public static function similar(int $id, string $category, string $tags, int $limit=3): array {
  $pdo = DB::pdo();
  $where = ["status='published'", "published_at IS NOT NULL", "published_at <= NOW()", "id <> :id"];
  $params = [':id'=>$id];

  $tagList = array_values(array_filter(array_map('trim', explode(',', $tags))));
  $conds = [];
  if ($category !== '') { $conds[] = "category = :cat"; $params[':cat']=$category; }
  foreach ($tagList as $i=>$t) {
    $k = ':t'.$i;
    $conds[] = "tags LIKE $k";
    $params[$k] = '%'.$t.'%';
    if ($i >= 4) break; // limit
  }
  if ($conds) $where[] = '(' . implode(' OR ', $conds) . ')';

  $sql = "SELECT id,title,slug,category,tags,content,published_at FROM blog_posts WHERE ".implode(' AND ', $where)." ORDER BY published_at DESC LIMIT 40";
  $st = $pdo->prepare($sql);
  foreach ($params as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
  $st->execute();
  $rows = $st->fetchAll();

  // Score in PHP
  $scores = [];
  foreach ($rows as $r) {
    $score = 0;
    if ($category !== '' && $r['category'] === $category) $score += 3;
    $rtags = array_values(array_filter(array_map('trim', explode(',', (string)($r['tags'] ?? '')))));
    foreach ($tagList as $t) if ($t && in_array($t, $rtags, true)) $score += 2;
    $scores[] = ['row'=>$r,'score'=>$score];
  }
  usort($scores, function($a,$b){
    if ($a['score'] === $b['score']) return strcmp((string)$b['row']['published_at'], (string)$a['row']['published_at']);
    return $b['score'] <=> $a['score'];
  });
  $out = [];
  foreach ($scores as $s) {
    if (count($out) >= $limit) break;
    // if score 0, skip
    if ($s['score'] <= 0) continue;
    $out[] = $s['row'];
  }
  return $out;
}

public static function bySlug(string $slug): ?array {

    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM blog_posts WHERE slug=:s AND status='published' AND published_at IS NOT NULL AND published_at <= NOW() LIMIT 1");
    $st->execute([':s'=>$slug]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function adminAll(): array {
    $pdo = DB::pdo();
    return $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();
  }

  public static function adminGet(int $id): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM blog_posts WHERE id=:id");
    $st->execute([':id'=>$id]);
    $r = $st->fetch();
    return $r ?: null;
  }

  public static function adminSave(array $d): int {
    $pdo = DB::pdo();
    $now = date('Y-m-d H:i:s');
    $isNew = empty($d['id']);
    $slug = $d['slug'] ?: Slug::make($d['title']);

    if ($isNew) {
      $st = $pdo->prepare("INSERT INTO blog_posts
        (title,slug,category,content,cover_image,tags,meta_title,meta_desc,status,created_at,updated_at,published_at)
        VALUES (:t,:s,:cat,:c,:ci,:tags,:mt,:md,:st,:ca,:ua,:pa)");
      $st->execute([
        ':t'=>$d['title'], ':s'=>$slug, ':cat'=>$d['category'],
        ':c'=>$d['content'], ':ci'=>$d['cover_image'],
        ':tags'=>$d['tags'], ':mt'=>$d['meta_title'], ':md'=>$d['meta_desc'],
        ':st'=>$d['status'], ':ca'=>$now, ':ua'=>$now,
        ':pa'=> $d['status']==='published' ? ($d['published_at'] ?: $now) : null
      ]);
      return (int)$pdo->lastInsertId();
    } else {
      $st = $pdo->prepare("UPDATE blog_posts SET
        title=:t, slug=:s, category=:cat, content=:c, cover_image=:ci, tags=:tags, meta_title=:mt, meta_desc=:md,
        status=:st, updated_at=:ua, published_at=:pa
        WHERE id=:id");
      $st->execute([
        ':id'=>$d['id'], ':t'=>$d['title'], ':s'=>$slug, ':cat'=>$d['category'],
        ':c'=>$d['content'], ':ci'=>$d['cover_image'],
        ':tags'=>$d['tags'], ':mt'=>$d['meta_title'], ':md'=>$d['meta_desc'],
        ':st'=>$d['status'], ':ua'=>$now,
        ':pa'=> $d['status']==='published' ? ($d['published_at'] ?: $now) : null
      ]);
      return (int)$d['id'];
    }
  }

  public static function adminDelete(int $id): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("DELETE FROM blog_posts WHERE id=:id");
    $st->execute([':id'=>$id]);
  }

  public static function categories(): array {
    $pdo = DB::pdo();
    try {
      $rows = $pdo->query("SELECT DISTINCT category FROM blog_posts WHERE status='published' AND published_at IS NOT NULL AND published_at <= NOW() AND category IS NOT NULL AND category<>'' ORDER BY category ASC")->fetchAll();
      return array_values(array_filter(array_map(fn($r) => (string)$r['category'], $rows)));
    } catch (Throwable $t) {
      return [];
    }
  }

  public static function topTags(int $limit=20): array {
    $pdo = DB::pdo();
    $rows = $pdo->query("SELECT tags FROM blog_posts WHERE status='published' AND published_at IS NOT NULL AND published_at <= NOW() AND tags IS NOT NULL AND tags<>''")->fetchAll();
    $counts = [];
    foreach ($rows as $r) {
      $parts = array_filter(array_map('trim', explode(',', (string)$r['tags'])));
      foreach ($parts as $p) {
        $k = mb_strtolower($p);
        $counts[$k] = ($counts[$k] ?? 0) + 1;
      }
    }
    arsort($counts);
    $out = [];
    foreach ($counts as $k=>$v) {
      $out[] = ['tag'=>$k, 'count'=>$v];
      if (count($out) >= $limit) break;
    }
    return $out;
  }
}
