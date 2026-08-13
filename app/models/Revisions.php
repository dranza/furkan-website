<?php
declare(strict_types=1);

final class Revisions {
  public static function saveBlog(array $post): void {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("INSERT INTO blog_revisions
        (post_id,title,slug,category,content,cover_image,tags,meta_title,meta_desc,status,published_at,saved_by,created_at)
        VALUES (:id,:t,:s,:cat,:c,:ci,:tags,:mt,:md,:st,:pa,:by,NOW())");
      $st->execute([
        ':id'=>$post['id'], ':t'=>$post['title'], ':s'=>$post['slug'], ':cat'=>$post['category'] ?? null,
        ':c'=>$post['content'], ':ci'=>$post['cover_image'] ?? null, ':tags'=>$post['tags'] ?? null,
        ':mt'=>$post['meta_title'] ?? null, ':md'=>$post['meta_desc'] ?? null, ':st'=>$post['status'] ?? 'draft',
        ':pa'=>$post['published_at'] ?? null, ':by'=>($_SESSION['admin_user'] ?? null),
      ]);
      $pdo->prepare("DELETE FROM blog_revisions WHERE post_id=:id AND id NOT IN (
        SELECT id FROM (SELECT id FROM blog_revisions WHERE post_id=:id ORDER BY created_at DESC LIMIT 20) t
      )")->execute([':id'=>$post['id']]);
    } catch (Throwable $t) {}
  }

  public static function saveProject(array $p): void {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("INSERT INTO project_revisions
        (project_id,title,slug,summary,details,technologies,cover_image,meta_title,meta_desc,featured,status,published_at,saved_by,created_at)
        VALUES (:id,:t,:s,:sm,:dt,:tech,:ci,:mt,:md,:f,:st,:pa,:by,NOW())");
      $st->execute([
        ':id'=>$p['id'], ':t'=>$p['title'], ':s'=>$p['slug'], ':sm'=>$p['summary'] ?? null, ':dt'=>$p['details'],
        ':tech'=>$p['technologies'] ?? null, ':ci'=>$p['cover_image'] ?? null, ':mt'=>$p['meta_title'] ?? null,
        ':md'=>$p['meta_desc'] ?? null, ':f'=>(int)($p['featured'] ?? 0), ':st'=>$p['status'] ?? 'draft',
        ':pa'=>$p['published_at'] ?? null, ':by'=>($_SESSION['admin_user'] ?? null),
      ]);
      $pdo->prepare("DELETE FROM project_revisions WHERE project_id=:id AND id NOT IN (
        SELECT id FROM (SELECT id FROM project_revisions WHERE project_id=:id ORDER BY created_at DESC LIMIT 20) t
      )")->execute([':id'=>$p['id']]);
    } catch (Throwable $t) {}
  }

  public static function blogList(int $postId): array {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("SELECT id, created_at, saved_by, status, published_at FROM blog_revisions
        WHERE post_id=:id ORDER BY created_at DESC LIMIT 50");
      $st->execute([':id'=>$postId]);
      return $st->fetchAll();
    } catch (Throwable $t) { return []; }
  }

  public static function blogGet(int $revId): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM blog_revisions WHERE id=:id LIMIT 1");
    $st->execute([':id'=>$revId]);
    $r = $st->fetch();
    return $r ?: null;
  }

  public static function projectList(int $projectId): array {
    $pdo = DB::pdo();
    try {
      $st = $pdo->prepare("SELECT id, created_at, saved_by, status, published_at FROM project_revisions
        WHERE project_id=:id ORDER BY created_at DESC LIMIT 50");
      $st->execute([':id'=>$projectId]);
      return $st->fetchAll();
    } catch (Throwable $t) { return []; }
  }

  public static function projectGet(int $revId): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM project_revisions WHERE id=:id LIMIT 1");
    $st->execute([':id'=>$revId]);
    $r = $st->fetch();
    return $r ?: null;
  }
}
