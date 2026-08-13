<?php
declare(strict_types=1);

final class Download {
  public static function latest(int $limit=500): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM downloads WHERE is_public=1 ORDER BY created_at DESC, id DESC LIMIT :l");
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll() ?: [];
  }

  public static function top(int $limit=8): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM downloads WHERE is_public=1 ORDER BY download_count DESC, created_at DESC LIMIT :l");
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll() ?: [];
  }

  public static function searchPublic(string $q='', string $cat=''): array {
    $pdo = DB::pdo();
    $q = trim($q);
    $cat = trim($cat);
    $sql = "SELECT * FROM downloads WHERE is_public=1";
    $p = [];
    if ($cat !== '') { $sql .= " AND category_slug=:c"; $p[':c']=$cat; }
    if ($q !== '') { $sql .= " AND (title LIKE :q OR description LIKE :q OR tags LIKE :q)"; $p[':q']="%$q%"; }
    $sql .= " ORDER BY created_at DESC, id DESC";
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll() ?: [];
  }

  public static function bySlug(string $slug): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM downloads WHERE slug=:s AND is_public=1 LIMIT 1");
    $st->execute([':s'=>$slug]);
    $r = $st->fetch();
    return $r ?: null;
  }

  public static function adminAll(): array {
    $pdo = DB::pdo();
    return $pdo->query("SELECT * FROM downloads ORDER BY created_at DESC, id DESC")->fetchAll() ?: [];
  }

  public static function adminGet(int $id): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM downloads WHERE id=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $r=$st->fetch();
    return $r ?: null;
  }

  public static function adminDelete(int $id): void {
    $pdo = DB::pdo();
    $item = self::adminGet($id);
    if ($item && !empty($item['file_path'])) {
      $path = __DIR__ . '/../../' . ltrim((string)$item['file_path'],'/');
      if (is_file($path)) @unlink($path);
    }
    $pdo->prepare("DELETE FROM downloads WHERE id=:id")->execute([':id'=>$id]);
    $pdo->prepare("DELETE FROM download_logs WHERE download_id=:id")->execute([':id'=>$id]);
  }

  public static function adminUpsert(array $d, ?array $file): array {
    $pdo = DB::pdo();
    $id = (int)($d['id'] ?? 0);

    $title = trim((string)($d['title'] ?? ''));
    $slug = trim((string)($d['slug'] ?? ''));
    $desc = trim((string)($d['description'] ?? ''));
    $catName = trim((string)($d['category_name'] ?? ''));
    $catSlug = trim((string)($d['category_slug'] ?? ''));
    $tags = trim((string)($d['tags'] ?? ''));
    $public = !empty($d['is_public']) ? 1 : 0;

    if ($title === '') return ['ok'=>false,'error'=>'Başlık zorunlu.'];
    if ($slug === '') $slug = self::slugify($title);
    $slug = self::slugify($slug);

    if ($catSlug === '' && $catName !== '') $catSlug = self::slugify($catName);
    $catSlug = $catSlug ? self::slugify($catSlug) : '';

    // unique slug
    $st = $pdo->prepare("SELECT id FROM downloads WHERE slug=:s AND id<>:id LIMIT 1");
    $st->execute([':s'=>$slug, ':id'=>$id]);
    if ($st->fetch()) return ['ok'=>false,'error'=>'Slug zaten kullanılıyor.'];

    $filePath = null;
    $origName = null;
    $mime = null;
    $size = null;

    if ($file && !empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
      $origName = (string)($file['name'] ?? 'dosya');
      $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

      // deny dangerous web-executables always
      $deny = ['php','phtml','php5','php7','php8','phar','cgi','pl','py','js','html','htm','shtml','svg'];
      if ($ext === '' || in_array($ext, $deny, true)) {
        return ['ok'=>false,'error'=>'Güvenlik nedeniyle bu uzantıya izin verilmiyor.'];
      }

      $allowedSetting = strtolower(trim((string)(Settings::get('downloads_allowed_ext','') ?? '')));
      if ($allowedSetting !== 'all') {
        $allowed = self::allowedExtensions();
        if (!in_array($ext, $allowed, true)) {
          return ['ok'=>false,'error'=>'Dosya türü izinli değil. İzinli: '.implode(', ',$allowed)];
        }
      }

      $dir = __DIR__ . '/../../storage/downloads';
      if (!is_dir($dir)) @mkdir($dir, 0755, true);

      $safeBase = preg_replace('~[^a-zA-Z0-9._-]+~','-', pathinfo($origName, PATHINFO_FILENAME));
      $rand = bin2hex(random_bytes(8));
      $fname = date('YmdHis')."_{$rand}_{$safeBase}.{$ext}";
      $dest = $dir . '/' . $fname;
      if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok'=>false,'error'=>'Dosya taşınamadı.'];
      }
      $filePath = 'storage/downloads/' . $fname;
      $mime = (string)($file['type'] ?? 'application/octet-stream');
      $size = (int)($file['size'] ?? 0);
    }

    if ($id > 0) {
      $cur = self::adminGet($id);
      if (!$cur) return ['ok'=>false,'error'=>'Kayıt bulunamadı.'];

      if ($filePath) {
        if (!empty($cur['file_path'])) {
          $old = __DIR__ . '/../../' . ltrim((string)$cur['file_path'],'/');
          if (is_file($old)) @unlink($old);
        }
      } else {
        $filePath = (string)$cur['file_path'];
        $origName = (string)$cur['original_name'];
        $mime = (string)$cur['mime'];
        $size = (int)$cur['size_bytes'];
      }

      $st = $pdo->prepare("UPDATE downloads SET title=:t, slug=:s, description=:d, category_name=:cn, category_slug=:cs, tags=:tags,
        file_path=:fp, original_name=:on, mime=:m, size_bytes=:sz, is_public=:pub, updated_at=NOW()
        WHERE id=:id");
      $st->execute([
        ':t'=>$title, ':s'=>$slug, ':d'=>$desc,
        ':cn'=>$catName, ':cs'=>$catSlug, ':tags'=>$tags,
        ':fp'=>$filePath, ':on'=>$origName, ':m'=>$mime, ':sz'=>$size,
        ':pub'=>$public, ':id'=>$id
      ]);
      return ['ok'=>true,'id'=>$id];
    }

    if (!$filePath) return ['ok'=>false,'error'=>'Dosya yüklemek zorunlu.'];

    $st = $pdo->prepare("INSERT INTO downloads (title,slug,description,category_name,category_slug,tags,file_path,original_name,mime,size_bytes,is_public,download_count,created_at,updated_at)
      VALUES (:t,:s,:d,:cn,:cs,:tags,:fp,:on,:m,:sz,:pub,0,NOW(),NOW())");
    $st->execute([
      ':t'=>$title, ':s'=>$slug, ':d'=>$desc,
      ':cn'=>$catName, ':cs'=>$catSlug, ':tags'=>$tags,
      ':fp'=>$filePath, ':on'=>$origName, ':m'=>$mime, ':sz'=>$size,
      ':pub'=>$public
    ]);
    return ['ok'=>true,'id'=>(int)$pdo->lastInsertId()];
  }

  public static function increment(int $id, ?int $userId, string $ip, string $ua): void {
    $pdo = DB::pdo();
    $pdo->prepare("UPDATE downloads SET download_count = download_count + 1 WHERE id=:id")->execute([':id'=>$id]);
    $salt = (string)(Settings::get('app_salt','') ?? '');
    $ipHash = hash('sha256', $ip . $salt);
    $pdo->prepare("INSERT INTO download_logs (download_id,user_id,ip_hash,user_agent,created_at) VALUES (:d,:u,:ip,:ua,NOW())")
      ->execute([':d'=>$id, ':u'=>$userId, ':ip'=>$ipHash, ':ua'=>substr($ua,0,250)]);
  }

  public static function categories(): array {
    $pdo = DB::pdo();
    $rows = $pdo->query("SELECT category_name, category_slug, COUNT(*) cnt FROM downloads WHERE is_public=1 AND category_slug<>'' GROUP BY category_slug, category_name ORDER BY cnt DESC, category_name ASC")->fetchAll() ?: [];
    return $rows;
  }

  public static function slugify(string $s): string {
    $s = trim(mb_strtolower($s,'UTF-8'));
    $tr = ['ç'=>'c','ğ'=>'g','ı'=>'i','ö'=>'o','ş'=>'s','ü'=>'u'];
    $s = strtr($s,$tr);
    $s = preg_replace('~[^a-z0-9]+~','-',$s);
    $s = trim($s,'-');
    return $s ?: 'item';
  }

  public static function allowedExtensions(): array {
    $raw = Settings::get('downloads_allowed_ext','pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,txt,csv,png,jpg,jpeg,webp,exe,msi,iso') ?? '';
    $raw = strtolower(trim((string)$raw));
    if ($raw === 'all') return [];
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $parts = array_values(array_unique($parts));
    return $parts ?: ['pdf'];
  }

  public static function formatBytes(int $bytes): string {
    if ($bytes <= 0) return '—';
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    $b = (float)$bytes;
    while ($b >= 1024 && $i < count($units)-1) { $b /= 1024; $i++; }
    return rtrim(rtrim(number_format($b, $i?2:0, '.', ''), '0'), '.') . ' ' . $units[$i];
  }
}
