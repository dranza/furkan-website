<?php
declare(strict_types=1);

final class Comments {
  public static function enabled(): bool {
    return ((Settings::get('comments_enabled','0') ?: '0') === '1');
  }
  public static function requireApproval(): bool {
    return ((Settings::get('comments_require_approval','1') ?: '1') === '1');
  }

  public static function approvedForPost(int $postId): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM comments WHERE post_id=:id AND status='approved' ORDER BY created_at ASC");
    $st->execute([':id'=>$postId]);
    return $st->fetchAll();
  }

  public static function countApproved(int $postId): int {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id=:id AND status='approved'");
    $st->execute([':id'=>$postId]);
    return (int)$st->fetchColumn();
  }

  public static function rateLimited(string $ipHash, int $postId): bool {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id=:pid AND ip_hash=:h AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $st->execute([':pid'=>$postId, ':h'=>$ipHash]);
    return ((int)$st->fetchColumn()) >= 2;
  }

  public static function submit(int $postId, string $name, string $email, string $content, string $hp='', ?int $userId=null): array {
    if ($hp !== '') return ['ok'=>false,'error'=>'Spam tespit edildi.'];
    $name = trim($name);
    $email = trim($email);
    $content = trim($content);

    if (mb_strlen($name) < 2) return ['ok'=>false,'error'=>'İsim çok kısa.'];
    if (mb_strlen($content) < 10) return ['ok'=>false,'error'=>'Yorum çok kısa.'];

    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua  = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $salt = Settings::get('analytics_salt','') ?? '';
    if ($salt === '') { $salt = bin2hex(random_bytes(16)); Settings::set('analytics_salt', $salt); }
    $ipHash = $ip ? hash('sha256', $ip . '|' . $salt) : 'na';

    if (self::rateLimited($ipHash, $postId)) return ['ok'=>false,'error'=>'Çok hızlı yorum gönderildi. Lütfen biraz bekleyin.'];

    $status = self::requireApproval() ? 'pending' : 'approved';
    $pdo = DB::pdo();
    $st = $pdo->prepare("INSERT INTO comments (post_id,name,email,content,status,ip_hash,ua,created_at,user_id)
      VALUES (:pid,:n,:e,:c,:s,:h,:u,NOW(),:uid)");
    $st->execute([
      ':pid'=>$postId, ':n'=>$name, ':e'=>$email ?: null, ':c'=>$content,
      ':s'=>$status, ':h'=>$ipHash, ':u'=>$ua, ':uid'=>$userId
    ]);

    return ['ok'=>true,'status'=>$status];
  }

  
public static function listForUser(int $userId, int $limit=50): array {
  $pdo = DB::pdo();
  $st = $pdo->prepare("SELECT c.id, c.post_id, c.content, c.status, c.created_at,
                              p.title AS post_title, p.slug AS post_slug
                       FROM comments c
                       LEFT JOIN blog_posts p ON p.id=c.post_id
                       WHERE c.user_id=:uid
                       ORDER BY c.created_at DESC
                       LIMIT :l");
  $st->bindValue(':uid', $userId, PDO::PARAM_INT);
  $st->bindValue(':l', $limit, PDO::PARAM_INT);
  $st->execute();
  return $st->fetchAll();
}

// Admin
  public static function adminList(string $status='pending', int $limit=200): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT c.*, b.title post_title, b.slug post_slug
      FROM comments c JOIN blog_posts b ON b.id=c.post_id
      WHERE c.status=:s ORDER BY c.created_at DESC LIMIT :l");
    $st->bindValue(':s', $status, PDO::PARAM_STR);
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function adminCounts(): array {
    $pdo = DB::pdo();
    $rows = $pdo->query("SELECT status, COUNT(*) c FROM comments GROUP BY status")->fetchAll();
    $out = ['pending'=>0,'approved'=>0,'spam'=>0];
    foreach ($rows as $r) $out[$r['status']] = (int)$r['c'];
    return $out;
  }

  public static function adminSetStatus(int $id, string $status): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("UPDATE comments SET status=:s WHERE id=:id");
    $st->execute([':s'=>$status, ':id'=>$id]);
  }

  public static function adminDelete(int $id): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("DELETE FROM comments WHERE id=:id");
    $st->execute([':id'=>$id]);
  }
}
