<?php
declare(strict_types=1);

final class Analytics {

  private static function maskIp(?string $ip): ?string {
    if (!$ip) return null;

    // IPv4: mask last octet
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $parts = explode('.', $ip);
      if (count($parts) === 4) {
        $parts[3] = 'xxx';
        return implode('.', $parts);
      }
      return $ip;
    }

    // IPv6: keep first 4 hextets, mask rest
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      $hextets = explode(':', $ip);
      $hextets = array_values(array_filter($hextets, fn($h) => $h !== ''));
      $keep = array_slice($hextets, 0, 4);
      return implode(':', $keep) . ':xxxx:xxxx:xxxx:xxxx';
    }

    return $ip;
  }

  private static function ensureVisitorId(): string {
    $cookie = $_COOKIE['aid'] ?? '';
    if (is_string($cookie) && preg_match('/^[A-Za-z0-9_-]{16,80}$/', $cookie)) return $cookie;

    $id = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // 180 days, KVKK-friendly pseudo id (not raw IP)
    @setcookie('aid', $id, [
      'expires' => time() + 60*60*24*180,
      'path' => '/',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
    $_COOKIE['aid'] = $id;
    return $id;
  }

  public static function logView(string $path, ?string $title=null): void {
    if (str_starts_with($path, '/admin') || str_starts_with($path, '/assets') || str_starts_with($path, '/install')) return;
    if (preg_match('#\.(css|js|png|jpg|jpeg|webp|gif|svg|ico|map)$#i', $path)) return;

    $pdo = DB::pdo();
    $ref = isset($_SERVER['HTTP_REFERER']) ? substr((string)$_SERVER['HTTP_REFERER'], 0, 255) : null;
    $ua  = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
    $ip  = $_SERVER['REMOTE_ADDR'] ?? null;

    // KVKK: store masked IP for display; hashes for uniqueness.
    $ipMasked = self::maskIp($ip);
    $aid = self::ensureVisitorId();

    $salt = Settings::get('analytics_salt','') ?? '';
    if ($salt === '') {
      $salt = bin2hex(random_bytes(16));
      Settings::set('analytics_salt', $salt);
    }

    $ipHash = $ip ? hash('sha256', $ip . '|' . $salt) : null;
    $visitorHash = $aid ? hash('sha256', $aid . '|' . $salt) : null;

    try {
      $st = $pdo->prepare("INSERT INTO page_views (path,title,referrer,ua,ip_hash,ip_masked,visitor_hash,created_at)
        VALUES (:p,:t,:r,:u,:h,:m,:vh,NOW())");
      $st->execute([
        ':p'  => substr($path,0,255),
        ':t'  => $title ? substr($title,0,255) : null,
        ':r'  => $ref,
        ':u'  => $ua,
        ':h'  => $ipHash,
        ':m'  => $ipMasked,
        ':vh' => $visitorHash,
      ]);
    } catch (Throwable $t) {}
  }

  public static function dailyCounts(int $days=30): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT DATE(created_at) d, COUNT(*) c
      FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL :n DAY)
      GROUP BY DATE(created_at) ORDER BY d ASC");
    $st->bindValue(':n', $days, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function hourlyCounts(int $days=30): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT HOUR(created_at) h, COUNT(*) c
      FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL :n DAY)
      GROUP BY HOUR(created_at) ORDER BY h ASC");
    $st->bindValue(':n', $days, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function topPages(int $limit=10, int $days=30): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT path, COUNT(*) c
      FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL :n DAY)
      GROUP BY path ORDER BY c DESC LIMIT :l");
    $st->bindValue(':n', $days, PDO::PARAM_INT);
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function topReferrers(int $limit=10, int $days=30): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT referrer, COUNT(*) c
      FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL :n DAY)
      AND referrer IS NOT NULL AND referrer <> ''
      GROUP BY referrer ORDER BY c DESC LIMIT :l");
    $st->bindValue(':n', $days, PDO::PARAM_INT);
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function uniques(int $days=30): int {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT COUNT(DISTINCT COALESCE(visitor_hash, ip_hash)) FROM page_views
      WHERE created_at >= DATE_SUB(NOW(), INTERVAL :n DAY)
      AND (visitor_hash IS NOT NULL OR ip_hash IS NOT NULL)");
    $st->bindValue(':n', $days, PDO::PARAM_INT);
    $st->execute();
    return (int)$st->fetchColumn();
  }

  public static function total(int $days=30): int {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT COUNT(*) FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL :n DAY)");
    $st->bindValue(':n', $days, PDO::PARAM_INT);
    $st->execute();
    return (int)$st->fetchColumn();
  }

  public static function recent(int $limit=50, int $days=30): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT path, referrer, ua, ip_masked, created_at
      FROM page_views
      WHERE created_at >= DATE_SUB(NOW(), INTERVAL :n DAY)
      ORDER BY created_at DESC
      LIMIT :l");
    $st->bindValue(':n', $days, PDO::PARAM_INT);
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function returningRate(int $days=30): float {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT
        SUM(CASE WHEN dd >= 2 THEN 1 ELSE 0 END) returning,
        COUNT(*) total
      FROM (
        SELECT COALESCE(visitor_hash, ip_hash) k, COUNT(DISTINCT DATE(created_at)) dd
        FROM page_views
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :n DAY)
          AND (visitor_hash IS NOT NULL OR ip_hash IS NOT NULL)
        GROUP BY k
      ) x");
    $st->bindValue(':n', $days, PDO::PARAM_INT);
    $st->execute();
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $total = (int)($row['total'] ?? 0);
    if ($total <= 0) return 0.0;
    $ret = (int)($row['returning'] ?? 0);
    return ($ret / $total) * 100.0;
  }
}
