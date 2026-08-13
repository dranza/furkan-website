<?php
declare(strict_types=1);

final class Migrations {
  private static bool $ran = false;

  public static function run(): void {
    if (self::$ran) return;
    self::$ran = true;

    $pdo = DB::pdo();
    try { $pdo->query("SELECT 1 FROM settings LIMIT 1"); } catch (Throwable $t) { return; }

    $ver = 1;
    try {
      $st = $pdo->prepare("SELECT v FROM settings WHERE k='schema_version' LIMIT 1");
      $st->execute();
      $v = $st->fetchColumn();
      if ($v !== false) $ver = (int)$v;
      else { $pdo->prepare("INSERT INTO settings (k,v) VALUES ('schema_version','1')")->execute(); }
    } catch (Throwable $t) {}

    if ($ver < 2) { self::migrateTo2($pdo); self::setVersion($pdo, 2); $ver = 2; }
    if ($ver < 3) { self::migrateTo3($pdo); self::setVersion($pdo, 3); $ver = 3; }
    if ($ver < 4) { self::migrateTo4($pdo); self::setVersion($pdo, 4); $ver = 4; }
    if ($ver < 5) { self::migrateTo5($pdo); self::setVersion($pdo, 5); $ver = 5; }
    if ($ver < 6) { self::migrateTo6($pdo); self::setVersion($pdo, 6); $ver = 6; }
    if ($ver < 7) { self::migrateTo7($pdo); self::setVersion($pdo, 7); $ver = 7; }
    if ($ver < 8) { self::migrateTo8($pdo); self::setVersion($pdo, 8); $ver = 8; }
    if ($ver < 9) { self::migrateTo9($pdo); self::setVersion($pdo, 9); $ver = 9; }
    if ($ver < 10) { self::migrateTo10($pdo); self::setVersion($pdo, 10); $ver = 10; }
    if ($ver < 11) { self::migrateTo11($pdo); self::setVersion($pdo, 11); $ver = 11; }
    if ($ver < 12) { self::migrateTo12($pdo); self::setVersion($pdo, 12); $ver = 12; }
    if ($ver < 13) { self::migrateTo13($pdo); self::setVersion($pdo, 13); $ver = 13; }
    if ($ver < 14) { self::migrateTo14($pdo); self::setVersion($pdo, 14); $ver = 14; }
    if ($ver < 15) { self::migrateTo15($pdo); self::setVersion($pdo, 15); $ver = 15; }
    if ($ver < 16) { self::migrateTo16($pdo); self::setVersion($pdo, 16); $ver = 16; }
    if ($ver < 17) { self::migrateTo17($pdo); self::setVersion($pdo, 17); $ver = 17; }
    if ($ver < 18) { self::migrateTo18($pdo); self::setVersion($pdo, 18); $ver = 18; }
  }

  private static function setVersion(PDO $pdo, int $v): void {
    $st = $pdo->prepare("INSERT INTO settings (k,v) VALUES ('schema_version', :v)
      ON DUPLICATE KEY UPDATE v=VALUES(v)");
    $st->execute([':v' => (string)$v]);
  }

  private static function columnExists(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
    $st->execute([':t'=>$table, ':c'=>$column]);
    return ((int)$st->fetchColumn()) > 0;
  }

  private static function indexExists(PDO $pdo, string $table, string $indexName): bool {
  $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND INDEX_NAME = :i");
  $st->execute([':t'=>$table, ':i'=>$indexName]);
  return ((int)$st->fetchColumn()) > 0;
}

private static function tableExists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
    $st->execute([':t'=>$table]);
    return ((int)$st->fetchColumn()) > 0;
  }

  private static function migrateTo17(PDO $pdo): void {
    // Offers / Quotes (Teklif oluşturucu)
    try {
      if (!self::tableExists($pdo, 'offers')) {
        $pdo->exec("CREATE TABLE offers (
          id BIGINT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NOT NULL,
          public_code VARCHAR(20) NOT NULL,
          offer_no VARCHAR(40) NOT NULL,
          title VARCHAR(190) NOT NULL,
          currency VARCHAR(10) NOT NULL DEFAULT 'TRY',
          vat_rate DECIMAL(6,2) NOT NULL DEFAULT 20.00,
          discount_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          customer_name VARCHAR(190) NULL,
          customer_company VARCHAR(190) NULL,
          customer_email VARCHAR(190) NULL,
          customer_phone VARCHAR(60) NULL,
          customer_address TEXT NULL,
          notes TEXT NULL,
          is_public TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY uq_offers_code (public_code),
          INDEX idx_offers_user (user_id),
          INDEX idx_offers_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      }
    } catch (Throwable $t) {}

    try {
      if (!self::tableExists($pdo, 'offer_items')) {
        $pdo->exec("CREATE TABLE offer_items (
          id BIGINT AUTO_INCREMENT PRIMARY KEY,
          offer_id BIGINT NOT NULL,
          ord INT NOT NULL DEFAULT 0,
          name VARCHAR(255) NOT NULL,
          qty DECIMAL(12,3) NOT NULL DEFAULT 1.000,
          unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          discount_percent DECIMAL(6,2) NOT NULL DEFAULT 0.00,
          discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          vat_rate DECIMAL(6,2) NOT NULL DEFAULT 20.00,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_offer_items_offer (offer_id),
          INDEX idx_offer_items_ord (offer_id, ord)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Try to add FK if supported
        try {
          $pdo->exec("ALTER TABLE offer_items ADD CONSTRAINT fk_offer_items_offer FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE");
        } catch (Throwable $t2) {}
      }
    } catch (Throwable $t) {}
  }

  private static function migrateTo2(PDO $pdo): void {
    try {
      if (!self::columnExists($pdo, 'projects', 'featured')) {
        $pdo->exec("ALTER TABLE projects ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0, ADD INDEX(featured)");
      }
    } catch (Throwable $t) {}

    try {
      if (!self::columnExists($pdo, 'blog_posts', 'category')) {
        $pdo->exec("ALTER TABLE blog_posts ADD COLUMN category VARCHAR(120) NULL, ADD INDEX(category)");
      }
    } catch (Throwable $t) {}

    try {
      if (!self::tableExists($pdo, 'media')) {
        $pdo->exec("CREATE TABLE media (
          id INT AUTO_INCREMENT PRIMARY KEY,
          file_path VARCHAR(255) NOT NULL,
          original_name VARCHAR(255) NULL,
          mime VARCHAR(120) NULL,
          size_bytes INT NULL,
          created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE INDEX idx_media_created ON media(created_at)");
      }
    } catch (Throwable $t) {}
  }

  private static function migrateTo3(PDO $pdo): void {
    try {
      if (!self::tableExists($pdo, 'blog_revisions')) {
        $pdo->exec("CREATE TABLE blog_revisions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          post_id INT NOT NULL,
          title VARCHAR(200) NOT NULL,
          slug VARCHAR(220) NOT NULL,
          category VARCHAR(120) NULL,
          content MEDIUMTEXT NOT NULL,
          cover_image VARCHAR(255) NULL,
          tags VARCHAR(255) NULL,
          meta_title VARCHAR(255) NULL,
          meta_desc VARCHAR(255) NULL,
          status VARCHAR(30) NOT NULL,
          published_at DATETIME NULL,
          saved_by VARCHAR(80) NULL,
          created_at DATETIME NOT NULL,
          INDEX(post_id),
          INDEX(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      }
    } catch (Throwable $t) {}

    try {
      if (!self::tableExists($pdo, 'project_revisions')) {
        $pdo->exec("CREATE TABLE project_revisions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          project_id INT NOT NULL,
          title VARCHAR(200) NOT NULL,
          slug VARCHAR(220) NOT NULL,
          summary TEXT NULL,
          details MEDIUMTEXT NOT NULL,
          technologies VARCHAR(255) NULL,
          cover_image VARCHAR(255) NULL,
          meta_title VARCHAR(255) NULL,
          meta_desc VARCHAR(255) NULL,
          featured TINYINT(1) NOT NULL DEFAULT 0,
          status VARCHAR(30) NOT NULL,
          published_at DATETIME NULL,
          saved_by VARCHAR(80) NULL,
          created_at DATETIME NOT NULL,
          INDEX(project_id),
          INDEX(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      }
    } catch (Throwable $t) {}

    try {
      if (!self::tableExists($pdo, 'page_views')) {
        $pdo->exec("CREATE TABLE page_views (
          id BIGINT AUTO_INCREMENT PRIMARY KEY,
          path VARCHAR(255) NOT NULL,
          title VARCHAR(255) NULL,
          referrer VARCHAR(255) NULL,
          ua VARCHAR(255) NULL,
          ip_hash VARCHAR(64) NULL,
          created_at DATETIME NOT NULL,
          INDEX(created_at),
          INDEX(path)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      }
    } catch (Throwable $t) {}
  }

  private static function migrateTo4(PDO $pdo): void {
  // comments table
  try {
    if (!self::tableExists($pdo, 'comments')) {
      $pdo->exec("CREATE TABLE comments (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(160) NULL,
        content TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        ip_hash VARCHAR(64) NULL,
        ua VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        INDEX(post_id),
        INDEX(status),
        INDEX(created_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
  } catch (Throwable $t) {}

  // defaults (do not overwrite existing)
  try {
    $pairs = [
      'sitemap_auto' => '1',
      'sitemap_ping' => '1',
      'comments_enabled' => '1',
      'comments_require_approval' => '1',
    ];
    foreach ($pairs as $k=>$v) {
      $st = $pdo->prepare("SELECT v FROM settings WHERE k=:k LIMIT 1");
      $st->execute([':k'=>$k]);
      $ex = $st->fetchColumn();
      if ($ex === false) {
        $ins = $pdo->prepare("INSERT INTO settings (k,v) VALUES (:k,:v)");
        $ins->execute([':k'=>$k, ':v'=>$v]);
      }
    }
  } catch (Throwable $t) {}
}

private static function migrateTo5(PDO $pdo): void {
  try {
    $pairs = [
      'profile_photo' => '',
      'totp_enabled' => '0',
      'totp_secret' => '',
    ];
    foreach ($pairs as $k=>$v) {
      $st = $pdo->prepare("SELECT v FROM settings WHERE k=:k LIMIT 1");
      $st->execute([':k'=>$k]);
      $ex = $st->fetchColumn();
      if ($ex === false) {
        $ins = $pdo->prepare("INSERT INTO settings (k,v) VALUES (:k,:v)");
        $ins->execute([':k'=>$k, ':v'=>$v]);
      }
    }
  } catch (Throwable $t) {}
}


private static function migrateTo6(PDO $pdo): void {
  // users.role
  try {
    if (!self::columnExists($pdo, 'users', 'role')) {
      $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin', ADD INDEX(role)");
      $pdo->exec("UPDATE users SET role='admin' WHERE role='' OR role IS NULL");
    }
  } catch (Throwable $t) {}

  // contact messages
  try {
    if (!self::tableExists($pdo, 'contact_messages')) {
      $pdo->exec("CREATE TABLE contact_messages (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(160) NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        ip_hash VARCHAR(64) NULL,
        ua VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        INDEX(status),
        INDEX(created_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
  } catch (Throwable $t) {}

  // settings defaults (do not overwrite)
  try {
    $pairs = [
      'contact_form_enabled' => '1',
      'contact_form_store' => '1',
      'contact_form_send_email' => '1',
        'contact_hours' => 'Hafta içi 09:00 - 18:00',
        'contact_availability' => 'available',
        'contact_map_embed' => '',
        'brand_tagline' => 'Bilgi Sistemleri Uzmanı',
        'page_cache_enabled' => '1',
        'page_cache_ttl' => '300',
        'registration_enabled' => '0',
        'registration_require_approval' => '1',
        'comments_require_login' => '0',
        'debug_mode' => '0',
    ];
    foreach ($pairs as $k=>$v) {
      $st = $pdo->prepare("SELECT v FROM settings WHERE k=:k LIMIT 1");
      $st->execute([':k'=>$k]);
      $ex = $st->fetchColumn();
      if ($ex === false) {
        $ins = $pdo->prepare("INSERT INTO settings (k,v) VALUES (:k,:v)");
        $ins->execute([':k'=>$k, ':v'=>$v]);
      }
    }
  } catch (Throwable $t) {}
}


private static function migrateTo7(PDO $pdo): void {
  // users: email, display_name, is_active
  try {
    if (!self::columnExists($pdo, 'users', 'email')) {
      $pdo->exec("ALTER TABLE users
        ADD COLUMN email VARCHAR(190) NULL,
        ADD COLUMN display_name VARCHAR(120) NULL,
        ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1,
        ADD UNIQUE KEY uniq_email (email)");
      $pdo->exec("UPDATE users SET display_name = username WHERE display_name IS NULL OR display_name=''");
    }
  } catch (Throwable $t) {}

  // login attempts (admin rate limit)
  try {
    if (!self::tableExists($pdo, 'login_attempts')) {
      $pdo->exec("CREATE TABLE login_attempts (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ip_hash VARCHAR(64) NOT NULL,
        scope VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX(ip_hash),
        INDEX(scope),
        INDEX(created_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
  } catch (Throwable $t) {}

  // comments: optional user_id
  try {
    if (self::tableExists($pdo, 'comments') && !self::columnExists($pdo,'comments','user_id')) {
      $pdo->exec("ALTER TABLE comments ADD COLUMN user_id BIGINT NULL, ADD INDEX(user_id)");
    }
  } catch (Throwable $t) {}

  // settings defaults (do not overwrite)
  try {
    $pairs = [
      'page_cache_enabled' => '1',
      'page_cache_ttl' => '300',
      'registration_enabled' => '0',
      'registration_require_approval' => '1',
      'comments_require_login' => '0',
      'debug_mode' => '0'
    ];
    foreach ($pairs as $k=>$v) {
      $st = $pdo->prepare("SELECT v FROM settings WHERE k=:k LIMIT 1");
      $st->execute([':k'=>$k]);
      $ex = $st->fetchColumn();
      if ($ex === false) {
        $ins = $pdo->prepare("INSERT INTO settings (k,v) VALUES (:k,:v)");
        $ins->execute([':k'=>$k, ':v'=>$v]);
      }
    }
  } catch (Throwable $t) {}
}


private static function migrateTo8(PDO $pdo): void {
  // Tickets
  try {
    if (!self::tableExists($pdo, 'tickets')) {
      $pdo->exec("CREATE TABLE tickets (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        subject VARCHAR(200) NOT NULL,
        category VARCHAR(40) NOT NULL DEFAULT 'diger',
        priority VARCHAR(20) NOT NULL DEFAULT 'normal',
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX(user_id),
        INDEX(status),
        INDEX(updated_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
  } catch (Throwable $t) {}

  try {
    if (!self::tableExists($pdo, 'ticket_messages')) {
      $pdo->exec("CREATE TABLE ticket_messages (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ticket_id BIGINT NOT NULL,
        sender_role VARCHAR(10) NOT NULL,
        sender_user_id BIGINT NULL,
        message TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX(ticket_id),
        INDEX(created_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
  } catch (Throwable $t) {}

  // Settings defaults
  try {
    $pairs = [
      'tickets_enabled' => '1'
    ];
    foreach ($pairs as $k=>$v) {
      $st = $pdo->prepare("SELECT v FROM settings WHERE k=:k LIMIT 1");
      $st->execute([':k'=>$k]);
      $ex = $st->fetchColumn();
      if ($ex === false) {
        $ins = $pdo->prepare("INSERT INTO settings (k,v) VALUES (:k,:v)");
        $ins->execute([':k'=>$k, ':v'=>$v]);
      }
    }
  } catch (Throwable $t) {}
}


private static function migrateTo9(PDO $pdo): void {
  // Skills
  try {
    if (!self::tableExists($pdo, 'skills')) {
      $pdo->exec("CREATE TABLE skills (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        level INT NOT NULL DEFAULT 0,
        tags VARCHAR(255) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        INDEX(sort_order)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
  } catch (Throwable $t) {}

  // Certifications
  try {
    if (!self::tableExists($pdo, 'certifications')) {
      $pdo->exec("CREATE TABLE certifications (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        issuer VARCHAR(160) NOT NULL,
        issue_year INT NOT NULL DEFAULT 0,
        credential_url VARCHAR(255) NOT NULL DEFAULT '',
        logo_path VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        INDEX(issue_year)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
  } catch (Throwable $t) {}
}


private static function migrateTo10(PDO $pdo): void {
  // extend certifications: description + file path
  try {
    if (self::tableExists($pdo, 'certifications')) {
      // Add columns if not exist
      $cols = $pdo->query("SHOW COLUMNS FROM certifications")->fetchAll();
      $names = array_map(fn($r)=>$r['Field'] ?? '', $cols ?: []);
      if (!in_array('description', $names, true)) {
        $pdo->exec("ALTER TABLE certifications ADD COLUMN description TEXT NULL");
      }
      if (!in_array('file_path', $names, true)) {
        $pdo->exec("ALTER TABLE certifications ADD COLUMN file_path VARCHAR(255) NOT NULL DEFAULT ''");
      }
    }
  } catch (Throwable $t) {}
}


private static function migrateTo11(PDO $pdo): void {
  // contact_messages enhancements: tags + admin_note + updated_at
  try {
    if (self::tableExists($pdo, 'contact_messages')) {
      $cols = $pdo->query("SHOW COLUMNS FROM contact_messages")->fetchAll();
      $names = array_map(fn($r)=>$r['Field'] ?? '', $cols ?: []);
      if (!in_array('tags', $names, true)) {
        $pdo->exec("ALTER TABLE contact_messages ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT ''");
      }
      if (!in_array('admin_note', $names, true)) {
        $pdo->exec("ALTER TABLE contact_messages ADD COLUMN admin_note TEXT NULL");
      }
      if (!in_array('updated_at', $names, true)) {
        $pdo->exec("ALTER TABLE contact_messages ADD COLUMN updated_at DATETIME NULL");
      }
      // indexes
      try { $pdo->exec("CREATE INDEX idx_contact_status_created ON contact_messages(status, created_at)"); } catch (Throwable $t) {}
    }
  } catch (Throwable $t) {}
}


private static function migrateTo12(PDO $pdo): void {
  // tickets enhancements: tags + admin_note
  try {
    if (self::tableExists($pdo, 'tickets')) {
      $cols = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll();
      $names = array_map(fn($r)=>$r['Field'] ?? '', $cols ?: []);
      if (!in_array('tags', $names, true)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT ''");
      }
      if (!in_array('admin_note', $names, true)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN admin_note TEXT NULL");
      }
      try { $pdo->exec("CREATE INDEX idx_tickets_status_updated ON tickets(status, updated_at)"); } catch (Throwable $t) {}
    }
  } catch (Throwable $t) {}
}

private static function migrateTo13(PDO $pdo): void {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS home_blocks (
      id INT AUTO_INCREMENT PRIMARY KEY,
      section VARCHAR(50) NOT NULL,
      title VARCHAR(200) NOT NULL,
      body TEXT NULL,
      icon VARCHAR(60) NOT NULL DEFAULT 'bi-stars',
      link_url VARCHAR(255) NOT NULL DEFAULT '',
      sort_order INT NOT NULL DEFAULT 0,
      is_active TINYINT NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      INDEX idx_home_blocks_section (section),
      INDEX idx_home_blocks_active (section, is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $c = (int)$pdo->query("SELECT COUNT(*) FROM home_blocks")->fetchColumn();
    if ($c === 0) {
      $seed = [
        ['services','Hastane Bilgi Sistemleri','HIS süreçleri, rol/yetki yönetimi, kritik iş akışları, kesintisiz hizmet ve sorun giderme.','bi-hospital','',10,1],
        ['services','Güvenlik & Erişim','AD/LDAP, MFA, hardening, loglama, yedek/DR senaryoları ve risk azaltma.','bi-shield-lock','',20,1],
        ['services','Entegrasyon & Otomasyon','HL7/FHIR, API entegrasyonları, raporlama otomasyonu ve iş yükünü azaltan senaryolar.','bi-diagram-3','',30,1],
        ['services','ITSM & Süreç İyileştirme','Talep/olay/problem yönetimi, standartlaştırma, dokümantasyon ve sürdürülebilir operasyon.','bi-kanban','',40,1],
        ['stats','7/24 Kritik Sistem','Kesintisiz hizmet odağı','bi-activity','',10,1],
        ['stats','Güvenlik Odaklı','Yetkilendirme, loglama, hardening','bi-shield-check','',20,1],
        ['stats','Veri & Raporlama','Dashboard ve otomasyonla görünürlük','bi-bar-chart','',30,1],
        ['process','Analiz & Planlama','İhtiyaç analizi, riskler, kapsam ve iş planı netleştirilir.','bi-clipboard2-check','',10,1],
        ['process','Uygulama & Entegrasyon','Kurulum, entegrasyon, test ve geri dönüş planı ile ilerlenir.','bi-diagram-2','',20,1],
        ['process','Dokümantasyon','Runbook, SOP, devralma dokümanları ve eğitim tamamlanır.','bi-journal-text','',30,1],
        ['process','İzleme & İyileştirme','Log/izleme, performans takibi ve sürekli iyileştirme yapılır.','bi-graph-up','',40,1],
        ['tech','Active Directory','Kimlik & erişim','bi-person-badge','',10,1],
        ['tech','VMware/Hyper-V','Sanallaştırma','bi-hdd-network','',20,1],
        ['tech','SQL / Raporlama','Veri & rapor','bi-database','',30,1],
        ['tech','HL7 / FHIR','Sağlık entegrasyon','bi-bezier2','',40,1],
        ['tech','Monitoring','Zabbix/Prometheus vb.','bi-eye','',50,1],
        ['tech','Backup & DR','Yedekleme ve felaket kurtarma','bi-cloud-arrow-up','',60,1],
      ];
      $st = $pdo->prepare("INSERT INTO home_blocks (section,title,body,icon,link_url,sort_order,is_active,created_at,updated_at)
        VALUES (:s,:t,:b,:i,:l,:o,:a,NOW(),NOW())");
      foreach ($seed as $r) {
        $st->execute([':s'=>$r[0],':t'=>$r[1],':b'=>$r[2],':i'=>$r[3],':l'=>$r[4],':o'=>$r[5],':a'=>$r[6]]);
      }
    }
  } catch (Throwable $t) {}
}

private static function migrateTo14(PDO $pdo): void {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS downloads (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(200) NOT NULL,
      slug VARCHAR(200) NOT NULL UNIQUE,
      description TEXT NULL,
      category_name VARCHAR(120) NOT NULL DEFAULT '',
      category_slug VARCHAR(120) NOT NULL DEFAULT '',
      tags VARCHAR(255) NOT NULL DEFAULT '',
      file_path VARCHAR(255) NOT NULL,
      original_name VARCHAR(255) NOT NULL,
      mime VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
      size_bytes INT NOT NULL DEFAULT 0,
      is_public TINYINT NOT NULL DEFAULT 1,
      download_count INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      INDEX idx_dl_public (is_public, created_at),
      INDEX idx_dl_cat (category_slug, is_public, created_at),
      INDEX idx_dl_count (download_count)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS download_logs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      download_id INT NOT NULL,
      user_id INT NULL,
      ip_hash CHAR(64) NOT NULL,
      user_agent VARCHAR(250) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL,
      INDEX idx_dlog_dl (download_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // defaults
    $exists = (int)$pdo->query("SELECT COUNT(*) FROM settings WHERE `key`='downloads_allowed_ext'")->fetchColumn();
    if ($exists === 0) {
      $st = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES ('downloads_allowed_ext', :v)");
      $st->execute([':v'=>'all']);
    }
  } catch (Throwable $t) {}
}

// Demo / template content seeding (runs once, only if tables are empty)
private static function migrateTo15(PDO $pdo): void {
  // settings table has historically been used with different column names in some installs.
  $settingsKeyCol = self::columnExists($pdo, 'settings', 'k') ? 'k' : (self::columnExists($pdo, 'settings', 'key') ? '`key`' : 'k');
  $settingsValCol = self::columnExists($pdo, 'settings', 'v') ? 'v' : (self::columnExists($pdo, 'settings', 'value') ? '`value`' : 'v');

  $setIfMissing = function(string $k, string $v) use ($pdo, $settingsKeyCol, $settingsValCol): void {
    try {
      $sql = "SELECT {$settingsValCol} FROM settings WHERE {$settingsKeyCol}=:k LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute([':k'=>$k]);
      $ex = $st->fetchColumn();
      if ($ex === false || $ex === null || (string)$ex === '') {
        // insert if missing, otherwise update only if empty
        $ins = $pdo->prepare("INSERT INTO settings ({$settingsKeyCol},{$settingsValCol}) VALUES (:k,:v)");
        try { $ins->execute([':k'=>$k, ':v'=>$v]); }
        catch (Throwable $t) {
          $upd = $pdo->prepare("UPDATE settings SET {$settingsValCol}=:v WHERE {$settingsKeyCol}=:k");
          $upd->execute([':k'=>$k, ':v'=>$v]);
        }
      }
    } catch (Throwable $t) {}
  };

  // Core identity (template values – user can customize later)
  $setIfMissing('site_description', 'Bilgi işlem, sistem yönetimi, güvenlik ve otomasyon odağında; sürdürülebilir, ölçülebilir ve güvenli IT çözümleri.');
  $setIfMissing('hero_title', 'Bilgi İşlem / Sistem ve Ağ Uzmanı');
  $setIfMissing('home_title', 'Bilgi İşlem ve IT Operasyonları');
  $setIfMissing('home_desc', 'Kurumsal altyapı yönetimi, güvenlik, izleme ve otomasyon çözümleri.');
  $setIfMissing('about_desc', 'Rol, yetki ve kritik sistem operasyonları odağında IT deneyimi.');
  $setIfMissing('projects_desc', 'Uyguladığım / örnek proje çalışmaları (şablon içerik).');
  $setIfMissing('blog_desc', 'Bilgi işlem notları, pratik rehberler ve vaka örnekleri.');
  $setIfMissing('contact_desc', 'İletişim formu üzerinden ulaşabilirsiniz.');

  // About text
  $setIfMissing('about_text',
"Merhaba!\n\nBen bilgi işlem, sistem yönetimi ve IT operasyonları tarafında; güvenlik, izleme ve otomasyon konularına odaklanan bir uzmanım.\n\n• Kurumsal altyapı yönetimi (Windows/Linux, sanallaştırma, yedekleme)\n• Kimlik ve erişim yönetimi (AD/LDAP, MFA, rol/yetki)\n• İzleme ve log yönetimi (alarm, kapasite, performans)\n• ITSM süreçleri (incident/problem/change), dokümantasyon ve standartlaştırma\n\nBu sitedeki içerikler \"şablon/demo\" amaçlı örnek metinlerle doldurulmuştur; panelden dilediğiniz gibi güncelleyebilirsiniz.");

  // Seed skills
  try {
    $c = (int)$pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();
    if ($c === 0) {
      $seed = [
        ['Windows Server', 85, 'AD, GPO, DNS, DHCP', 10],
        ['Active Directory', 90, 'RBAC, OU tasarımı, hardening', 20],
        ['Linux', 70, 'Ubuntu/CentOS, servis yönetimi', 30],
        ['Network', 75, 'VLAN, routing temelleri, troubleshooting', 40],
        ['Firewall & Security', 75, 'MFA, hardening, least privilege', 50],
        ['Virtualization', 80, 'VMware/Hyper-V, kaynak planlama', 60],
        ['Backup & DR', 85, '3-2-1, test restore, RPO/RTO', 70],
        ['Monitoring', 80, 'Zabbix/Prometheus, alert rules', 80],
        ['SQL & Reporting', 65, 'Temel sorgular, rapor otomasyonu', 90],
        ['Scripting', 70, 'PowerShell/Bash, otomasyon', 100],
        ['ITSM', 75, 'Incident/Problem/Change, SLA', 110],
        ['Documentation', 80, 'Runbook, SOP, envanter', 120],
      ];
      $st = $pdo->prepare("INSERT INTO skills (name,level,tags,sort_order,created_at) VALUES (:n,:l,:t,:o,NOW())");
      foreach ($seed as $r) {
        $st->execute([':n'=>$r[0],':l'=>$r[1],':t'=>$r[2],':o'=>$r[3]]);
      }
    }
  } catch (Throwable $t) {}

  // Seed certifications (template)
  try {
    $c = (int)$pdo->query("SELECT COUNT(*) FROM certifications")->fetchColumn();
    if ($c === 0) {
      $seed = [
        ['ITIL® 4 Foundation (Örnek)', 'AXELOS / PeopleCert', 2023, '', 'ITSM temelleri: incident/problem/change, SLA ve servis yaşam döngüsü.'],
        ['Microsoft Azure Fundamentals (Örnek)', 'Microsoft', 2024, '', 'Bulut temelleri, güvenlik ve maliyet farkındalığı.'],
        ['CompTIA Security+ (Örnek)', 'CompTIA', 2024, '', 'Güvenlik temelleri, risk yönetimi, ağ güvenliği ve olay yanıtı.'],
        ['Cisco CCNA (Örnek)', 'Cisco', 2022, '', 'Ağ temelleri, switching/routing, troubleshooting yaklaşımı.'],
        ['Linux Administration (Örnek)', 'Linux Foundation / Vendor', 2023, '', 'Linux servis yönetimi, temel güvenlik ve otomasyon.'],
      ];
      $st = $pdo->prepare("INSERT INTO certifications (title,issuer,issue_year,credential_url,logo_path,description,file_path,created_at)
        VALUES (:t,:i,:y,:u,'',:d,'',NOW())");
      foreach ($seed as $r) {
        $st->execute([':t'=>$r[0],':i'=>$r[1],':y'=>$r[2],':u'=>$r[3],':d'=>$r[4]]);
      }
    }
  } catch (Throwable $t) {}

  // Seed timeline (education / experience) – template
  try {
    $ce = (int)$pdo->query("SELECT COUNT(*) FROM education")->fetchColumn();
    if ($ce === 0) {
      $st = $pdo->prepare("INSERT INTO education (university,department,start_year,end_year,notes) VALUES (:u,:d,:sy,:ey,:n)");
      $st->execute([':u'=>'(Örnek) Üniversite / MYO',':d'=>'Bilgisayar Programcılığı / Bilgi Sistemleri',':sy'=>2016,':ey'=>2018,':n'=>'Ağ, veritabanı ve yazılım temelleri']);
      $st->execute([':u'=>'(Örnek) Eğitim Programı',':d'=>'Sistem ve Ağ Yönetimi',':sy'=>2019,':ey'=>2019,':n'=>'Uygulamalı lab çalışmaları']);
    }
  } catch (Throwable $t) {}

  try {
    $cx = (int)$pdo->query("SELECT COUNT(*) FROM experience")->fetchColumn();
    if ($cx === 0) {
      $st = $pdo->prepare("INSERT INTO experience (company,role,start_year,end_year,notes) VALUES (:c,:r,:sy,:ey,:n)");
      $st->execute([':c'=>'(Örnek) Kurum / Hastane',':r'=>'Bilgi İşlem Uzmanı',':sy'=>2020,':ey'=>9999,':n'=>'Kritik sistem operasyonları, izleme, yedek/DR ve kullanıcı destek süreçleri']);
      $st->execute([':c'=>'(Örnek) Kurum',':r'=>'Sistem ve Ağ Destek',':sy'=>2018,':ey'=>2020,':n'=>'AD, istemci yönetimi, ağ arıza giderme ve dokümantasyon']);
    }
  } catch (Throwable $t) {}

  // Seed projects – template
  try {
    $cp = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    if ($cp === 0) {
      $seed = [
        ['IT Operasyon Dashboard & İzleme','it-operasyon-dashboard-izleme','Kritik sistemlerin durumunu tek ekranda izlemek için alarm ve metrik odaklı dashboard.',
          "Bu proje; sunucu, servis, disk, CPU/RAM, ağ gecikmesi ve uygulama sağlığını tek panelde göstermek için tasarlanmış bir izleme yaklaşımını içerir.\n\nKapsam:\n• Host/VM envanteri ve etiketleme\n• Alarm kuralları (kritik/uyarı/bilgi)\n• Haftalık kapasite raporu ve trend analizi\n• Olay sonrası RCA için log referansları\n\nNot: Bu içerik şablon/demodur; kendi projenize göre düzenleyebilirsiniz.",'Zabbix, Prometheus, Grafana, Syslog, Linux',1],
        ['Active Directory Hardening & MFA Geçişi','active-directory-hardening-mfa','Rol/yetki, MFA ve en iyi uygulamalarla kimlik güvenliğinin güçlendirilmesi.',
          "Hedef; domain yapısının sadeleştirilmesi, ayrıcalıklı hesapların güvenliğinin artırılması ve kritik erişimlerde MFA uygulanmasıdır.\n\nAdımlar:\n• OU/GPO standardizasyonu\n• Local admin azaltma (least privilege)\n• MFA/Conditional Access senaryoları\n• Denetim politikaları ve loglama\n• Break-glass hesap stratejisi\n\nNot: Şablon içeriktir.",'Windows Server, AD, GPO, MFA, Security Baselines',1],
        ['Yedekleme & DR Otomasyonu','yedekleme-dr-otomasyonu','3-2-1 kuralı, otomatik test restore ve RPO/RTO hedefleriyle sürdürülebilir yedekleme.',
          "Proje; yedekleme işlerinin görünür hale gelmesi, düzenli test restore yapılması ve felaket senaryolarının dokümante edilmesini içerir.\n\nÇıktılar:\n• Yedekleme politikası ve takvim\n• Otomatik rapor (başarılı/başarısız işler)\n• Aylık test restore planı\n• DR runbook ve iletişim planı\n\nNot: Şablon içeriktir.",'Veeam/Benzeri, Storage, Linux/Windows, PowerShell',1],
        ['HL7 / FHIR Entegrasyon Akışları (Örnek)','hl7-fhir-entegrasyon-ornek','Sağlık sistemleri arasında veri alışverişi için entegrasyon yaklaşımı ve hata yönetimi.',
          "Hedef; HBYS/LIS/RIS gibi sistemler arasında veri akışlarını izlenebilir ve yönetilebilir hale getirmektir.\n\nKapsam:\n• Mesaj doğrulama ve kuyruk yönetimi\n• Hata kayıtları ve yeniden gönderim\n• API güvenliği ve rate limit\n• Uçtan uca izleme\n\nNot: Şablon içeriktir.",'HL7, FHIR, REST API, SQL, Monitoring',0],
        ['ITSM / Ticket Portal İyileştirme','itsm-ticket-portal-iyilestirme','Talep/olay süreçlerini hızlandıran formlar, kategoriler, SLA ve raporlama.',
          "Bu çalışma; destek taleplerinin standartlaştırılması ve ölçümlenebilir hale gelmesi için tasarlanmıştır.\n\nÖzellikler:\n• Kategori/öncelik ve etiketleme\n• SLA hedefleri\n• Atama ve durum akışları\n• Haftalık KPI raporları\n\nNot: Şablon içeriktir.",'PHP, MySQL, Bootstrap, ITIL, Reporting',0],
      ];
      $st = $pdo->prepare("INSERT INTO projects (title,slug,summary,details,technologies,cover_image,meta_title,meta_desc,featured,status,created_at,user_id,updated_at,published_at)
        VALUES (:t,:s,:sum,:d,:tech,'','','',:f,'published',NOW(),NULL,NOW(),NOW())");
      foreach ($seed as $p) {
        $st->execute([':t'=>$p[0],':s'=>$p[1],':sum'=>$p[2],':d'=>$p[3],':tech'=>$p[4],':f'=>$p[5]]);
      }
    }
  } catch (Throwable $t) {}

  // Seed a few blog posts – template
  try {
    $cb = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
    if ($cb === 0) {
      $seed = [
        ['Bilgi İşlemde 3-2-1 Yedekleme Kuralı','bilgi-islem-3-2-1-yedekleme','Yedekleme',
          "3-2-1 kuralı; 3 kopya, 2 farklı medya, 1 kopya off-site prensibiyle veri kaybı riskini düşürür.\n\nBu yazıda: politika oluşturma, test restore ve raporlama pratiklerini özetliyorum.\n\nNot: Şablon içeriktir.",'yedekleme, dr, 3-2-1','Yedekleme için 3-2-1 kuralı ve pratik öneriler.'],
        ['Active Directory İçin Hızlı Hardening Kontrol Listesi','active-directory-hardening-kontrol-listesi','Güvenlik',
          "AD güvenliği; ayrıcalıklı hesap yönetimi, GPO standardizasyonu ve denetim/loglama ile başlar.\n\nBu yazı: en çok kullanılan kontrol maddelerini kısa bir liste halinde sunar.\n\nNot: Şablon içeriktir.",'ad, security, hardening','Active Directory güvenliği için temel kontrol listesi.'],
      ];
      $st = $pdo->prepare("INSERT INTO blog_posts (title,slug,category,content,cover_image,tags,meta_title,meta_desc,status,created_at,user_id,updated_at,published_at)
        VALUES (:t,:s,:c,:ct,'',:tags,'',:md,'published',NOW(),NULL,NOW(),NOW())");
      foreach ($seed as $b) {
        $st->execute([':t'=>$b[0],':s'=>$b[1],':c'=>$b[2],':ct'=>$b[3],':tags'=>$b[4],':md'=>$b[5]]);
      }
    }
  } catch (Throwable $t) {}
}

// Analytics upgrades: masked IP + cookie-based visitor id
private static function migrateTo16(PDO $pdo): void {
  try {
    if (self::tableExists($pdo, 'page_views')) {
      if (!self::columnExists($pdo, 'page_views', 'ip_masked')) {
        $pdo->exec("ALTER TABLE page_views ADD COLUMN ip_masked VARCHAR(64) NULL AFTER ip_hash");
      }
      if (!self::columnExists($pdo, 'page_views', 'visitor_hash')) {
        $pdo->exec("ALTER TABLE page_views ADD COLUMN visitor_hash VARCHAR(64) NULL AFTER ip_masked");
      }
      if (!self::indexExists($pdo, 'page_views', 'created_at') && !self::indexExists($pdo, 'page_views', 'idx_page_views_created')) {
        $pdo->exec("CREATE INDEX idx_page_views_created ON page_views(created_at)");
      }
      if (!self::indexExists($pdo, 'page_views', 'idx_page_views_visitor')) {
        $pdo->exec("CREATE INDEX idx_page_views_visitor ON page_views(visitor_hash)");
      }
    }
  } catch (Throwable $t) {}
}



  private static function migrateTo18(PDO $pdo): void {
    // Firm profiles
    try {
      if (!self::tableExists($pdo, 'firm_profiles')) {
        $pdo->exec("CREATE TABLE firm_profiles (
          user_id INT PRIMARY KEY,
          company_name VARCHAR(190) NOT NULL DEFAULT '',
          tax_office VARCHAR(190) NOT NULL DEFAULT '',
          tax_no VARCHAR(60) NOT NULL DEFAULT '',
          iban VARCHAR(64) NOT NULL DEFAULT '',
          address TEXT NULL,
          phone VARCHAR(60) NOT NULL DEFAULT '',
          email VARCHAR(190) NOT NULL DEFAULT '',
          logo_path VARCHAR(255) NOT NULL DEFAULT '',
          signature_path VARCHAR(255) NOT NULL DEFAULT '',
          stamp_path VARCHAR(255) NOT NULL DEFAULT '',
          offer_no_template VARCHAR(80) NOT NULL DEFAULT 'TKF-{YYYY}-{SEQ4}',
          default_currency VARCHAR(10) NOT NULL DEFAULT 'TRY',
          default_vat_rate DECIMAL(6,2) NOT NULL DEFAULT 20.00
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      }
    } catch (Throwable $t) {}

    // Offers: status tracking
    try { if (!self::columnExists($pdo, 'offers', 'status')) { $pdo->exec("ALTER TABLE offers ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft'"); } } catch (Throwable $t) {}
    try { if (!self::columnExists($pdo, 'offers', 'sent_at')) { $pdo->exec("ALTER TABLE offers ADD COLUMN sent_at DATETIME NULL"); } } catch (Throwable $t) {}
    try { if (!self::columnExists($pdo, 'offers', 'decided_at')) { $pdo->exec("ALTER TABLE offers ADD COLUMN decided_at DATETIME NULL"); } } catch (Throwable $t) {}
    try { if (!self::indexExists($pdo, 'offers', 'idx_offers_status')) { $pdo->exec("CREATE INDEX idx_offers_status ON offers(status)"); } } catch (Throwable $t) {}
  }

}
