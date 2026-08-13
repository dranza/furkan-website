<?php
declare(strict_types=1);

function handle_upload(string $field, string $subdir): string {
  if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return '';
  $f = $_FILES[$field];
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','webp','gif','pdf'];
  // basic size limit ~12MB
  if (!empty($f['size']) && (int)$f['size'] > 12*1024*1024) return '';
  if (!in_array($ext, $allowed, true)) return '';
  $name = bin2hex(random_bytes(10)) . '.' . $ext;
  $dir = __DIR__ . '/../uploads/' . $subdir;
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $dest = $dir . '/' . $name;
  if (!move_uploaded_file($f['tmp_name'], $dest)) return '';
  return 'uploads/' . $subdir . '/' . $name;
}
