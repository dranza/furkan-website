<?php
declare(strict_types=1);

final class Ticket {

  public static function create(int $userId, string $subject, string $category, string $priority, string $message): array {
    $subject = trim($subject);
    $category = trim($category);
    $priority = trim($priority);
    $message = trim($message);

    if (mb_strlen($subject) < 4) return ['ok'=>false,'error'=>'Konu en az 4 karakter olmalı.'];
    if (mb_strlen($message) < 10) return ['ok'=>false,'error'=>'Mesaj en az 10 karakter olmalı.'];

    $allowedPriority = ['low','normal','high','urgent'];
    if (!in_array($priority, $allowedPriority, true)) $priority = 'normal';

    $allowedCat = ['entegrasyon','raporlama','itsm','guvenlik','sistem','diger'];
    if (!in_array($category, $allowedCat, true)) $category = 'diger';

    $pdo = DB::pdo();
    $st = $pdo->prepare("INSERT INTO tickets (user_id, subject, category, priority, status, created_at, updated_at)
                         VALUES (:uid, :s, :c, :p, 'open', NOW(), NOW())");
    $st->execute([':uid'=>$userId, ':s'=>$subject, ':c'=>$category, ':p'=>$priority]);
    $tid = (int)$pdo->lastInsertId();

    $st2 = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_role, sender_user_id, message, created_at)
                          VALUES (:tid, 'user', :uid, :m, NOW())");
    $st2->execute([':tid'=>$tid, ':uid'=>$userId, ':m'=>$message]);

    return ['ok'=>true, 'id'=>$tid];
  }

  public static function listForUser(int $userId, int $limit=200, string $status='all'): array {
  $pdo = DB::pdo();
  $limit = max(1, min(500, (int)$limit));
  $status = trim($status);

  $where = "WHERE t.user_id=:uid";
  $params = [':uid'=>$userId];

  if ($status !== 'all') {
    $allowed = ['open','pending','closed'];
    if (!in_array($status, $allowed, true)) $status = 'all';
    if ($status !== 'all') { $where .= " AND t.status=:st"; $params[':st']=$status; }
  }

  // Use integer LIMIT in SQL for compatibility (some hosts don't allow binding LIMIT)
  $sql = "SELECT t.id, t.subject, t.category, t.priority, t.status, t.created_at, t.updated_at,
                 (SELECT message FROM ticket_messages tm WHERE tm.ticket_id=t.id ORDER BY tm.id DESC LIMIT 1) AS last_message
          FROM tickets t
          $where
          ORDER BY t.updated_at DESC
          LIMIT $limit";
  $st = $pdo->prepare($sql);
  foreach ($params as $k=>$v) { $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
  $st->execute();
  return $st->fetchAll();
}

  public static function getForUser(int $ticketId, int $userId): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM tickets WHERE id=:id AND user_id=:uid LIMIT 1");
    $st->execute([':id'=>$ticketId, ':uid'=>$userId]);
    $t = $st->fetch();
    return $t ?: null;
  }

  public static function messages(int $ticketId): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT id, sender_role, message, created_at FROM ticket_messages WHERE ticket_id=:id ORDER BY id ASC");
    $st->execute([':id'=>$ticketId]);
    return $st->fetchAll();
  }

  public static function userReply(int $ticketId, int $userId, string $message): array {
    $message = trim($message);
    if (mb_strlen($message) < 2) return ['ok'=>false,'error'=>'Mesaj çok kısa.'];

    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT id,status FROM tickets WHERE id=:id AND user_id=:uid LIMIT 1");
    $st->execute([':id'=>$ticketId, ':uid'=>$userId]);
    $t = $st->fetch();
    if (!$t) return ['ok'=>false,'error'=>'Ticket bulunamadı.'];

    $status = (string)$t['status'];
    if ($status === 'closed') return ['ok'=>false,'error'=>'Bu ticket kapatılmış.'];

    $st2 = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_role, sender_user_id, message, created_at)
                          VALUES (:tid, 'user', :uid, :m, NOW())");
    $st2->execute([':tid'=>$ticketId, ':uid'=>$userId, ':m'=>$message]);

    $pdo->prepare("UPDATE tickets SET status='open', updated_at=NOW() WHERE id=:id")->execute([':id'=>$ticketId]);
    return ['ok'=>true];
  }

  
public static function countsForUser(int $userId): array {
  $pdo = DB::pdo();
  $st = $pdo->prepare("SELECT status, COUNT(*) c FROM tickets WHERE user_id=:uid GROUP BY status");
  $st->execute([':uid'=>$userId]);
  $rows = $st->fetchAll();
  $out = ['open'=>0,'pending'=>0,'closed'=>0,'all'=>0];
  foreach ($rows as $r) {
    $s = (string)($r['status'] ?? '');
    $c = (int)($r['c'] ?? 0);
    if (isset($out[$s])) $out[$s] = $c;
    $out['all'] += $c;
  }
  return $out;
}


public static function countByStatus(string $status='open'): int {
  $pdo = DB::pdo();
  $allowed = ['open','pending','closed'];
  if (!in_array($status, $allowed, true)) $status = 'open';
  $st = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status=:s");
  $st->execute([':s'=>$status]);
  return (int)$st->fetchColumn();
}

public static function adminCounts(): array {
  $pdo = DB::pdo();
  $st = $pdo->query("SELECT status, COUNT(*) c FROM tickets GROUP BY status");
  $rows = $st ? $st->fetchAll() : [];
  $out = ['open'=>0,'pending'=>0,'closed'=>0,'all'=>0];
  foreach ($rows as $r) {
    $s = (string)($r['status'] ?? '');
    $c = (int)($r['c'] ?? 0);
    if (isset($out[$s])) $out[$s] = $c;
    $out['all'] += $c;
  }
  return $out;
}

public static function adminSearchList(string $status='all', string $q='', int $limit=300): array {
  $pdo = DB::pdo();
  $limit = max(1, min(800, (int)$limit));
  $q = trim($q);

  $where = [];
  $params = [];

  $allowed = ['open','pending','closed'];
  if ($status !== 'all') {
    if (!in_array($status, $allowed, true)) $status = 'open';
    $where[] = "t.status=:s";
    $params[':s'] = $status;
  }

  if ($q !== '') {
    $where[] = "(t.subject LIKE :q OR t.category LIKE :q OR t.priority LIKE :q OR t.tags LIKE :q OR u.username LIKE :q OR u.display_name LIKE :q OR u.email LIKE :q)";
    $params[':q'] = '%' . $q . '%';
  }

  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
  $sql = "SELECT t.id,t.subject,t.category,t.priority,t.status,t.created_at,t.updated_at,t.tags,
                 u.username,u.display_name,u.email,
                 (SELECT message FROM ticket_messages tm WHERE tm.ticket_id=t.id ORDER BY tm.id DESC LIMIT 1) AS last_message
          FROM tickets t
          LEFT JOIN users u ON u.id=t.user_id
          $whereSql
          ORDER BY t.updated_at DESC
          LIMIT $limit";
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

public static function adminUpdateMeta(int $ticketId, string $tags='', string $adminNote=''): void {
  $pdo = DB::pdo();
  $tags = trim($tags);
  $adminNote = trim($adminNote);
  $st = $pdo->prepare("UPDATE tickets SET tags=:t, admin_note=:n, updated_at=NOW() WHERE id=:id");
  $st->execute([':t'=>$tags, ':n'=>$adminNote ?: null, ':id'=>$ticketId]);
}

public static function adminDelete(int $ticketId): void {
  $pdo = DB::pdo();
  $pdo->prepare("DELETE FROM ticket_messages WHERE ticket_id=:id")->execute([':id'=>$ticketId]);
  $pdo->prepare("DELETE FROM tickets WHERE id=:id")->execute([':id'=>$ticketId]);
}


public static function listForUserPaged(int $userId, int $page=1, int $perPage=20, string $status='all', string $q=''): array {
  $pdo = DB::pdo();
  $page = max(1, (int)$page);
  $perPage = max(5, min(100, (int)$perPage));
  $status = trim($status);
  $q = trim($q);

  $where = "WHERE t.user_id=:uid";
  $params = [':uid'=>$userId];

  if ($status !== 'all') {
    $allowed = ['open','pending','closed'];
    if (!in_array($status, $allowed, true)) $status = 'all';
    if ($status !== 'all') { $where .= " AND t.status=:st"; $params[':st']=$status; }
  }

  if ($q !== '') {
    // Search only ticket fields (fast + index friendly). Message search intentionally omitted.
    $where .= " AND (t.subject LIKE :q OR t.category LIKE :q OR t.priority LIKE :q OR t.status LIKE :q OR CAST(t.id AS CHAR) LIKE :q)";
    $params[':q'] = '%' . $q . '%';
  }

  $countSql = "SELECT COUNT(*) FROM tickets t $where";
  $stc = $pdo->prepare($countSql);
  foreach ($params as $k=>$v) { $stc->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  $offset = ($page - 1) * $perPage;
  $offset = max(0, $offset);

  $sql = "SELECT t.id, t.subject, t.category, t.priority, t.status, t.created_at, t.updated_at,
                 (SELECT message FROM ticket_messages tm WHERE tm.ticket_id=t.id ORDER BY tm.id DESC LIMIT 1) AS last_message
          FROM tickets t
          $where
          ORDER BY t.updated_at DESC
          LIMIT $perPage OFFSET $offset";
  $st = $pdo->prepare($sql);
  foreach ($params as $k=>$v) { $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
  $st->execute();
  $items = $st->fetchAll();

  return ['items'=>$items, 'total'=>$total, 'page'=>$page, 'perPage'=>$perPage];
}

public static function userSetStatus(int $ticketId, int $userId, string $status): array {
  $allowed = ['open','pending','closed'];
  if (!in_array($status, $allowed, true)) return ['ok'=>false,'error'=>'Geçersiz durum.'];

  $pdo = DB::pdo();
  $st = $pdo->prepare("SELECT id,status FROM tickets WHERE id=:id AND user_id=:uid LIMIT 1");
  $st->execute([':id'=>$ticketId, ':uid'=>$userId]);
  $t = $st->fetch();
  if (!$t) return ['ok'=>false,'error'=>'Ticket bulunamadı.'];

  $pdo->prepare("UPDATE tickets SET status=:s, updated_at=NOW() WHERE id=:id")->execute([':s'=>$status, ':id'=>$ticketId]);
  return ['ok'=>true];
}

// ===== Admin side =====
  public static function adminList(string $status='all', int $limit=500): array {
    $pdo = DB::pdo();
    $where = "";
    $params = [];
    if ($status !== 'all') { $where = "WHERE t.status=:s"; $params[':s']=$status; }
    $sql = "SELECT t.id,t.subject,t.category,t.priority,t.status,t.created_at,t.updated_at,
                   u.username,u.display_name,u.email
            FROM tickets t
            LEFT JOIN users u ON u.id=t.user_id
            $where
            ORDER BY t.updated_at DESC
            LIMIT $limit";
    $st = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $st->bindValue($k,$v);
    $st->execute();
    return $st->fetchAll();
  }

  public static function adminGet(int $ticketId): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT t.*, u.username,u.display_name,u.email
                         FROM tickets t
                         LEFT JOIN users u ON u.id=t.user_id
                         WHERE t.id=:id LIMIT 1");
    $st->execute([':id'=>$ticketId]);
    $t = $st->fetch();
    return $t ?: null;
  }

  public static function adminReply(int $ticketId, string $message, string $setStatus=''): array {
    $message = trim($message);
    if ($message === '') return ['ok'=>false,'error'=>'Mesaj boş.'];

    $pdo = DB::pdo();
    $t = self::adminGet($ticketId);
    if (!$t) return ['ok'=>false,'error'=>'Ticket bulunamadı.'];

    $st = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_role, sender_user_id, message, created_at)
                         VALUES (:tid, 'admin', NULL, :m, NOW())");
    $st->execute([':tid'=>$ticketId, ':m'=>$message]);

    $allowed = ['open','pending','closed'];
    $status = $setStatus && in_array($setStatus,$allowed,true) ? $setStatus : (string)$t['status'];
    if ($status === '') $status = 'open';

    $pdo->prepare("UPDATE tickets SET status=:s, updated_at=NOW() WHERE id=:id")->execute([':s'=>$status, ':id'=>$ticketId]);
    return ['ok'=>true];
  }

  public static function adminSetStatus(int $ticketId, string $status): void {
    $allowed = ['open','pending','closed'];
    if (!in_array($status,$allowed,true)) $status = 'open';
    $pdo = DB::pdo();
    $pdo->prepare("UPDATE tickets SET status=:s, updated_at=NOW() WHERE id=:id")->execute([':s'=>$status, ':id'=>$ticketId]);
  }
}
