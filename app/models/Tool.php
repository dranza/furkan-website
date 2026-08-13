<?php
require_once __DIR__ . '/../core/DB.php';

final class Tool {
  private static bool $migrated = false;

  private static function pdo(): PDO {
    return DB::pdo();
  }

  /**
   * Backward-compatible schema migrations.
   * Some installs may have older columns (e.g. tool_usage_daily.requests) or
   * a narrower ENUM for tools.status. Migrations are best-effort.
   */
  private static function migrateSchema(PDO $pdo): void {
    // 1) Ensure tools.status enum supports expected values
    try {
      $pdo->exec("ALTER TABLE tools MODIFY status ENUM('active','maintenance','disabled') NOT NULL DEFAULT 'active'");
    } catch (Throwable $t) {}

    // Normalize potential legacy / localized values
    try {
      $pdo->exec("UPDATE tools SET status='maintenance' WHERE status IN ('Bakımda','bakimda','maintenance')");
      $pdo->exec("UPDATE tools SET status='disabled' WHERE status IN ('Pasif','pasif','inactive','disabled')");
      $pdo->exec("UPDATE tools SET status='active' WHERE status IN ('Aktif','aktif')");
    } catch (Throwable $t) {}

    // 2) Ensure tool_usage_daily has 'uses' column (older versions may use 'requests')
    try {
      $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='tool_usage_daily'");
      $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
      $hasUses = in_array('uses', $cols, true);
      $hasRequests = in_array('requests', $cols, true);
      if (!$hasUses && $hasRequests) {
        $pdo->exec("ALTER TABLE tool_usage_daily ADD COLUMN uses INT NOT NULL DEFAULT 0");
        $pdo->exec("UPDATE tool_usage_daily SET uses=requests");
      }
    } catch (Throwable $t) {}
  }

  /**
   * Create required tables if they don't exist.
   * Idempotent + shared-hosting friendly.
   */
  private static function migrate(): void {
    if (self::$migrated) return;
    self::$migrated = true;
    $pdo = DB::pdo();

    // tools: settings + totals
    $pdo->exec("CREATE TABLE IF NOT EXISTS tools (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      slug VARCHAR(120) NOT NULL,
      name VARCHAR(190) NOT NULL,
      status ENUM('active','maintenance','disabled') NOT NULL DEFAULT 'active',
      maintenance_message TEXT NULL,
      total_uses INT UNSIGNED NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uniq_tools_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // daily usage (for charts/counters)
    $pdo->exec("CREATE TABLE IF NOT EXISTS tool_usage_daily (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      tool_slug VARCHAR(120) NOT NULL,
      day DATE NOT NULL,
      uses INT UNSIGNED NOT NULL DEFAULT 0,
      files INT UNSIGNED NOT NULL DEFAULT 0,
      PRIMARY KEY (id),
      UNIQUE KEY uniq_tool_day (tool_slug, day),
      KEY idx_tool_slug (tool_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

	    // Backward compatibility: add missing columns on older installs
	    try {
	      $cols = $pdo->query("SHOW COLUMNS FROM tool_usage_daily")?->fetchAll(PDO::FETCH_ASSOC) ?: [];
	      $hasFiles = false;
	      foreach ($cols as $c) {
	        if (isset($c['Field']) && strtolower((string)$c['Field']) === 'files') { $hasFiles = true; break; }
	      }
	      if (!$hasFiles) {
	        $pdo->exec("ALTER TABLE tool_usage_daily ADD COLUMN files INT UNSIGNED NOT NULL DEFAULT 0 AFTER uses");
	      }
	    } catch (Throwable $t) {
	      // ignore
	    }

    // Best-effort migrations for older installs
    self::migrateSchema($pdo);
  }

  /**
   * Public alias for migrate(). Some admin pages call this.
   */
  public static function ensureTables(): void {
    self::migrate();
  }

  private static function safeMigrateOnThrowable(Throwable $t): void {
    // Most common: table missing (SQLSTATE 42S02)
    // We attempt migration once and let caller retry.
    self::$migrated = false;
    try { self::migrate(); } catch (Throwable $t2) {}
  }
  public static function ensure(string $slug, string $name): void {
    self::migrate();
    try {
      $pdo = DB::pdo();
      $stmt = $pdo->prepare("INSERT IGNORE INTO tools (slug,name,status,total_uses,updated_at) VALUES (:s,:n,'active',0,NOW())");
      $stmt->execute([':s'=>$slug, ':n'=>$name]);
    } catch (Throwable $t) {
      self::safeMigrateOnThrowable($t);
      $pdo = DB::pdo();
      $stmt = $pdo->prepare("INSERT IGNORE INTO tools (slug,name,status,total_uses,updated_at) VALUES (:s,:n,'active',0,NOW())");
      $stmt->execute([':s'=>$slug, ':n'=>$name]);
    }
  }

  public static function get(string $slug): ?array {
    self::migrate();
    try {
      $pdo = DB::pdo();
      $stmt = $pdo->prepare('SELECT * FROM tools WHERE slug = :s LIMIT 1');
      $stmt->execute([':s'=>$slug]);
      $r = $stmt->fetch();
      if (!$r) return null;
      // Backward compatibility: some pages expect a "meta" array.
      $r['meta'] = [
        'maintenance_message' => (string)($r['maintenance_message'] ?? ''),
      ];
      // If DB schema doesn't support "maintenance" enum, treat disabled+msg as maintenance.
      if (($r['status'] ?? '') === 'disabled' && trim((string)($r['maintenance_message'] ?? '')) !== '') {
        $r['status'] = 'maintenance';
      }
      return $r;
    } catch (Throwable $t) {
      self::safeMigrateOnThrowable($t);
      return null;
    }
  }

  public static function setStatus(string $slug, string $status, string $maintenanceMessage = ''): void {
    $allowed = ['active','maintenance','disabled'];
    if (!in_array($status, $allowed, true)) $status = 'active';
    self::migrate();
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('UPDATE tools SET status=:st, maintenance_message=:mm, updated_at=NOW() WHERE slug=:s');
    try {
      $stmt->execute([':st'=>$status, ':mm'=>$maintenanceMessage, ':s'=>$slug]);
    } catch (PDOException $e) {
      // Some hosts run older schema (ENUM without "maintenance"). In that case MySQL throws 1265
      // (data truncated) when writing an unsupported enum value. We fallback to "disabled" and
      // infer maintenance from maintenance_message.
      $msg = (string)$e->getMessage();
      if ($status === 'maintenance' && (str_contains($msg, '1265') || str_contains($msg, 'Data truncated'))) {
        $stmt->execute([':st'=>'disabled', ':mm'=>$maintenanceMessage, ':s'=>$slug]);
        return;
      }
      throw $e;
    }
  }

  public static function bumpUse(string $slug, int $inc = 1): void {
    if ($inc < 1) $inc = 1;
    self::migrate();
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('UPDATE tools SET total_uses = total_uses + :i, updated_at=NOW() WHERE slug=:s');
    $stmt->execute([':i'=>$inc, ':s'=>$slug]);
  }

	public static function dailyBump(string $slug, int $usesInc = 1, int $filesInc = 0): void {
		if ($usesInc < 1) $usesInc = 1;
		if ($filesInc < 0) $filesInc = 0;
    self::migrate();
    $pdo = DB::pdo();
		$stmt = $pdo->prepare('INSERT INTO tool_usage_daily (tool_slug, day, uses, files) VALUES (:s, CURDATE(), :u, :f)
		  ON DUPLICATE KEY UPDATE uses = uses + VALUES(uses), files = files + VALUES(files)');
		$stmt->execute([':s'=>$slug, ':u'=>$usesInc, ':f'=>$filesInc]);
  }

	public static function getDaily(string $slug, int $days = 14): array {
    $days = max(1, min(90, $days));
    self::migrate();
    $pdo = DB::pdo();
		$stmt = $pdo->prepare('SELECT day, uses, files FROM tool_usage_daily WHERE tool_slug=:s ORDER BY day DESC LIMIT '.$days);
    $stmt->execute([':s'=>$slug]);
    return $stmt->fetchAll();
  }

  /**
   * Update tool metadata (name + status + maintenance message).
   * Used by admin/araclar.php.
   */
  public static function setMeta(string $slug, string $name, string $status = 'active', string $maintenanceMessage = ''): void {
    self::ensure($slug, $name);
    $pdo = self::pdo();
    $stmt = $pdo->prepare('UPDATE tools SET name=:n, status=:st, maintenance_message=:mm WHERE slug=:s');
    $stmt->execute([
      ':n' => $name,
      ':st' => $status,
      ':mm' => $maintenanceMessage,
      ':s' => $slug,
    ]);
  }

  /**
   * Get tool metadata (name, status, maintenance_message, totals).
   * Used by admin/araclar.php.
   */
  public static function meta(string $slug): array {
    self::ensureTables();
    $pdo = self::pdo();
    $stmt = $pdo->prepare('SELECT slug, name, status, maintenance_message, total_uses, created_at FROM tools WHERE slug=:s LIMIT 1');
    $stmt->execute([':s' => $slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      self::ensure($slug, $slug);
      $stmt->execute([':s' => $slug]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    return $row;
  }

  /**
   * Totals + today's uses for a tool.
   */
  public static function usageTotals(string $slug): array {
    self::ensureTables();
    $pdo = self::pdo();
    $stmt = $pdo->prepare('SELECT total_uses FROM tools WHERE slug=:s LIMIT 1');
    $stmt->execute([':s' => $slug]);
    $total = (int)($stmt->fetchColumn() ?: 0);

    $stmt = $pdo->prepare('SELECT uses FROM tool_usage_daily WHERE tool_slug=:s AND day=CURDATE() LIMIT 1');
    $stmt->execute([':s' => $slug]);
    $today = (int)($stmt->fetchColumn() ?: 0);
    return ['total' => $total, 'today' => $today];
  }

  /**
   * Record tool usage. Increments total uses and daily uses.
   */
  public static function recordUse(string $slug, int $usesInc = 1, ?int $filesInc = null): void {
    // Backward-compat: old calls passed a single count; treat it as both uses and files.
    if ($usesInc < 1) $usesInc = 1;
    if ($filesInc === null) $filesInc = $usesInc;
    if ($filesInc < 0) $filesInc = 0;
    self::ensure($slug, $slug);
    $pdo = self::pdo();
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare('UPDATE tools SET total_uses = total_uses + :c WHERE slug=:s');
		$stmt->execute([':c' => $usesInc, ':s' => $slug]);
		self::dailyBump($slug, $usesInc, $filesInc);
      $pdo->commit();
    } catch (Throwable $t) {
      $pdo->rollBack();
      throw $t;
    }
  }

  /**
   * Daily usage between dates (inclusive).
   * Returns array of rows: ['day' => 'YYYY-MM-DD', 'uses' => int, 'files' => int]
   * Missing days are filled with zeros.
   */
  public static function usageDaily(string $slug, string $from, string $to): array {
    self::ensureTables();
		$pdo = self::pdo();

    $stmt = $pdo->prepare(
      "SELECT day, uses, files FROM tool_usage_daily WHERE tool_slug = :s AND day BETWEEN :f AND :t ORDER BY day ASC"
    );
    $stmt->execute([':s' => $slug, ':f' => $from, ':t' => $to]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $map = [];
    foreach ($rows as $r) {
      $d = (string)($r['day'] ?? '');
      if ($d === '') continue;
      $map[$d] = [
        'day' => $d,
        'uses' => (int)($r['uses'] ?? 0),
        'files' => (int)($r['files'] ?? 0),
      ];
    }

    // Fill missing days
    $out = [];
    $start = new DateTime($from);
    $end = new DateTime($to);
    for ($dt = clone $start; $dt <= $end; $dt->modify('+1 day')) {
      $key = $dt->format('Y-m-d');
      $out[] = $map[$key] ?? ['day' => $key, 'uses' => 0, 'files' => 0];
    }
    return $out;
  }

  /**
   * Admin: reset usage counters for a tool (tool_usage + tool_usage_daily).
   */
  public static function resetUsage(string $slug): void {
    self::ensureTables();
		$pdo = self::pdo();

    // Reset total counter
    $st0 = $pdo->prepare('UPDATE tools SET total_uses = 0, updated_at = NOW() WHERE slug = :s');
    $st0->execute([':s' => $slug]);

    $st2 = $pdo->prepare('DELETE FROM tool_usage_daily WHERE tool_slug = :s');
    $st2->execute([':s' => $slug]);
  }

}
