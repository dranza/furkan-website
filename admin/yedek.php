<?php
// IMPORTANT: Backup download must happen BEFORE any HTML output (headers).
require_once __DIR__ . '/../app/core/Bootstrap.php';
Auth::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'download_sql') {
    $pdo = DB::pdo();

    // Ensure no buffered output breaks headers
    if (function_exists('ob_get_level')) {
      while (ob_get_level() > 0) { @ob_end_clean(); }
    }

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_' . date('Ymd_His') . '.sql"');

    echo "-- Backup generated at " . date('c') . "\n";
    echo "SET NAMES utf8mb4;\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $trow) {
      $table = (string)$trow[0];

      echo "-- ----------------------------\n";
      echo "-- Table: `{$table}`\n";
      echo "-- ----------------------------\n";

      $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
      $createSql = $create['Create Table'] ?? '';
      echo "DROP TABLE IF EXISTS `{$table}`;\n";
      echo $createSql . ";\n\n";

      $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
      if (!$rows) { echo "\n"; continue; }

      foreach ($rows as $r) {
        $cols = array_map(fn($c)=>"`{$c}`", array_keys($r));
        $vals = [];
        foreach ($r as $v) {
          if ($v === null) $vals[] = "NULL";
          else $vals[] = $pdo->quote((string)$v);
        }
        echo "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
      }
      echo "\n";
    }

    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
  }
}


if ($action === 'download_full') {
  $pdo = DB::pdo();

  $storageDir = __DIR__ . '/../storage/backups';
  if (!is_dir($storageDir)) { @mkdir($storageDir, 0755, true); }
  // Protect backups directory
  $ht = __DIR__ . '/../storage/.htaccess';
  if (!is_file($ht)) { @file_put_contents($ht, "Deny from all\n"); }

  $zipPath = $storageDir . '/full_backup_' . date('Ymd_His') . '.zip';
  $zip = new ZipArchive();
  if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('ZIP oluşturulamadı.');
  }

  // 1) Add SQL dump
  $sql = '';
  $sql .= "-- Full backup generated at " . date('c') . "\n";
  $sql .= "SET NAMES utf8mb4;\n";
  $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
  $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
  foreach ($tables as $trow) {
    $table = (string)$trow[0];
    $sql .= "-- Table: `{$table}`\n";
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
    $createSql = $create['Create Table'] ?? '';
    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $sql .= $createSql . ";\n\n";
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
      $cols = array_map(fn($c)=>"`{$c}`", array_keys($r));
      $vals = [];
      foreach ($r as $v) $vals[] = ($v === null) ? "NULL" : $pdo->quote((string)$v);
      $sql .= "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
    }
    $sql .= "\n";
  }
  $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
  $zip->addFromString("database.sql", $sql);

  // 2) Add all files from public_html root (this project), excluding backups/cache/logs
  $root = realpath(__DIR__ . '/..');
  $exclude = [
    realpath(__DIR__ . '/../storage/backups'),
    realpath(__DIR__ . '/../storage/cache'),
  ];

  $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
  foreach ($rii as $file) {
    /** @var SplFileInfo $file */
    $path = $file->getPathname();
    $real = realpath($path);
    if (!$real) continue;

    // exclude backups/cache
    $skip = false;
    foreach ($exclude as $ex) {
      if ($ex && strpos($real, $ex) === 0) { $skip = true; break; }
    }
    if ($skip) continue;

    // exclude server log files in root
    if (basename($real) === 'error_log') continue;
    if (basename($real) === 'php-error.log') continue;

    $rel = ltrim(str_replace($root, '', $real), DIRECTORY_SEPARATOR);
    if ($file->isDir()) continue;
    $zip->addFile($real, "public_html/" . str_replace(DIRECTORY_SEPARATOR, '/', $rel));
  }

  $zip->close();

  // stream download BEFORE any HTML output
  if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
  header('Content-Length: ' . filesize($zipPath));
  readfile($zipPath);
  exit;
}


require_once __DIR__ . '/_layout_top.php';

$ok = '';
$err = '';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Yedek</h1>
    <div class="text-muted">Veritabanı yedeğini indir</div>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-download me-1"></i>SQL Yedeği</div>
      <div class="text-muted mb-3">
        Tüm tabloların <b>SQL dump</b> çıktısını üretir. Büyük veride biraz sürebilir.
      </div>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <button class="btn btn-primary" name="action" value="download_sql" type="submit"><i class="bi bi-cloud-download me-1"></i>SQL indir</button>
      </form>
      <hr class="my-3">
      <div class="fw-bold mb-2"><i class="bi bi-hdd-stack me-1"></i>Tam Yedek (Site + SQL)</div>
      <div class="text-muted mb-3">Tüm site dosyalarını (public_html) ve veritabanını tek ZIP içinde indirir.</div>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <button class="btn btn-outline-light" name="action" value="download_full" type="submit"><i class="bi bi-file-earmark-zip me-1"></i>Tam yedek indir (ZIP)</button>
      </form>
      
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-shield-check me-1"></i>Not</div>
      <div class="text-muted">
        Bu yedek sadece veritabanını içerir. Görseller için <code>uploads/</code> klasörünü ayrıca indirmen önerilir.
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
