<?php
require_once __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../app/models/User.php';
Auth::requireRole(['admin']);

$pdo = DB::pdo();
$ok = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Site kullanıcıları
  if (!empty($_POST['user_action'])) {
    CSRF::checkOrExit($_POST['_csrf'] ?? null);
    $act = (string)$_POST['user_action'];
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id > 0) {
      if ($act === 'activate') User::adminSetActive($id, 1);
      if ($act === 'deactivate') User::adminSetActive($id, 0);
      if ($act === 'delete') User::adminDelete($id);
    }
    redirect(base_url('admin/kullanicilar.php#siteusers'));
  }

  CSRF::checkOrExit($_POST['_csrf'] ?? null);
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'create') {
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    $role = (string)($_POST['role'] ?? 'editor');
    if ($u === '' || mb_strlen($u) < 3) $err = 'Kullanıcı adı çok kısa.';
    elseif (mb_strlen($p) < 8) $err = 'Şifre en az 8 karakter olmalı.';
    else {
      try {
        $st = $pdo->prepare("INSERT INTO users (username,password_hash,role,created_at) VALUES (:u,:h,:r,NOW())");
        $st->execute([':u'=>$u, ':h'=>password_hash($p, PASSWORD_DEFAULT), ':r'=>in_array($role,['admin','editor'],true)?$role:'editor']);
        $ok = 'Kullanıcı oluşturuldu.';
      } catch (Throwable $t) {
        $err = 'Oluşturulamadı (kullanıcı adı mevcut olabilir).';
      }
    }
  }

  if ($action === 'role') {
    $id = (int)($_POST['id'] ?? 0);
    $role = (string)($_POST['role'] ?? 'editor');
    if ($id > 0) {
      $st = $pdo->prepare("UPDATE users SET role=:r WHERE id=:id");
      $st->execute([':r'=>in_array($role,['admin','editor'],true)?$role:'editor', ':id'=>$id]);
      $ok = 'Rol güncellendi.';
    }
  }

  if ($action === 'reset_pw') {
    $id = (int)($_POST['id'] ?? 0);
    $p = (string)($_POST['password'] ?? '');
    if ($id > 0 && mb_strlen($p) >= 8) {
      $st = $pdo->prepare("UPDATE users SET password_hash=:h WHERE id=:id");
      $st->execute([':h'=>password_hash($p, PASSWORD_DEFAULT), ':id'=>$id]);
      $ok = 'Şifre güncellendi.';
    } else {
      $err = 'Şifre en az 8 karakter olmalı.';
    }
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0 && $id !== (int)($_SESSION['admin_id'] ?? 0)) {
      $pdo->prepare("DELETE FROM users WHERE id=:id")->execute([':id'=>$id]);
      $ok = 'Kullanıcı silindi.';
    } else {
      $err = 'Kendi hesabını silemezsin.';
    }
  }
}

$users = $pdo->query("SELECT id, username, role, created_at FROM users WHERE role IN ('admin','editor') ORDER BY id ASC")->fetchAll();

// Site users (role=user)
$siteUsers = [];
try {
  $siteUsers = $pdo->query("SELECT id, username, email, display_name, is_active, created_at FROM users WHERE role='user' ORDER BY id DESC")->fetchAll();
} catch (Throwable $t) {
  // Older schema fallback
  try { $siteUsers = $pdo->query("SELECT id, username, '' as email, username as display_name, 1 as is_active, created_at FROM users WHERE role='user' ORDER BY id DESC")->fetchAll(); } catch (Throwable $t2) { $siteUsers = []; }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Kullanıcılar</h1>
    <div class="text-muted">Admin / Editor rolleri</div>
  </div>
</div>

<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-person-plus me-1"></i>Yeni Kullanıcı</div>
      <form method="post" class="vstack gap-2">
        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
        <input type="hidden" name="action" value="create">
        <input class="form-control" name="username" placeholder="kullaniciadi" required>
        <input class="form-control" name="password" placeholder="şifre (min 8)" type="password" required>
        <select class="form-select" name="role">
          <option value="editor">Editor</option>
          <option value="admin">Admin</option>
        </select>
        <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Oluştur</button>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card p-3">
      <div class="fw-bold mb-2"><i class="bi bi-people me-1"></i>Liste</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>#</th><th>Kullanıcı</th><th>Rol</th><th>Tarih</th><th class="text-end">İşlem</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td class="text-muted"><?= (int)$u['id'] ?></td>
                <td class="fw-semibold"><?= e($u['username']) ?></td>
                <td>
                  <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
                    <input type="hidden" name="action" value="role">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <select class="form-select form-select-sm" name="role" style="max-width:140px;">
                      <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
                      <option value="editor" <?= $u['role']==='editor'?'selected':'' ?>>editor</option>
                    </select>
                    <button class="btn btn-sm btn-outline-light">Kaydet</button>
                  </form>
                </td>
                <td class="text-muted"><?= e(date('d.m.Y', strtotime((string)$u['created_at']))) ?></td>
                <td class="text-end">
                  <details class="d-inline-block">
                    <summary class="btn btn-sm btn-outline-light"><i class="bi bi-key me-1"></i>Şifre</summary>
                    <div class="mt-2 p-2 border rounded-4">
                      <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
                        <input type="hidden" name="action" value="reset_pw">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input class="form-control form-control-sm" name="password" type="password" placeholder="yeni şifre">
                        <button class="btn btn-sm btn-primary">Güncelle</button>
                      </form>
                    </div>
                  </details>
                  <?php if ((int)$u['id'] !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <button class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')"><i class="bi bi-trash"></i></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<hr class="my-4" id="siteusers">
<div class="d-flex justify-content-between align-items-center mb-2">
  <div>
    <div class="h5 fw-bold mb-0">Site Kullanıcıları</div>
    <div class="text-muted">Kayıt olan kullanıcıları onayla / pasifleştir / sil</div>
  </div>
</div>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Kullanıcı</th>
          <th>E-posta</th>
          <th>Durum</th>
          <th>Oluşturma</th>
          <th class="text-end">İşlem</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($siteUsers as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td>
              <div class="fw-bold"><?= e($u['display_name'] ?: $u['username']) ?></div>
              <div class="text-muted small">@<?= e($u['username']) ?></div>
            </td>
            <td><?= e($u['email'] ?? '') ?></td>
            <td>
              <?php if ((int)$u['is_active'] === 1): ?>
                <span class="badge bg-success">Aktif</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Onay bekliyor</span>
              <?php endif; ?>
            </td>
            <td class="text-muted small"><?= e($u['created_at'] ?? '') ?></td>
            <td class="text-end">
              <form method="post" class="d-inline">
                <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <?php if ((int)$u['is_active'] === 1): ?>
                  <button class="btn btn-sm btn-outline-light" name="user_action" value="deactivate">Pasifleştir</button>
                <?php else: ?>
                  <button class="btn btn-sm btn-primary" name="user_action" value="activate">Onayla</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-danger" name="user_action" value="delete" onclick="return confirm('Silinsin mi?')">Sil</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($siteUsers)): ?>
          <tr><td colspan="6" class="text-muted">Kayıtlı kullanıcı yok.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
