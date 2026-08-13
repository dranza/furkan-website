<?php
declare(strict_types=1);

final class Timeline {

// Unified list for homepage previews
public static function all(): array {
  $pdo = DB::pdo();

  $edu = $pdo->query("SELECT university, department, start_year, end_year, notes FROM education ORDER BY start_year DESC")->fetchAll();
  $exp = $pdo->query("SELECT company, role, start_year, end_year, notes FROM experience ORDER BY start_year DESC")->fetchAll();

  $items = [];

  foreach ($exp as $e) {
    $sy = (int)$e['start_year']; $ey = (int)$e['end_year'];
    $range = $sy . ' - ' . (($ey <= 0 || $ey >= 9999) ? 'Devam' : $ey);
    if (!empty($e['notes'])) $range .= ' • ' . $e['notes'];
    $items[] = [
      'type' => 'experience',
      'title' => (string)$e['company'],
      'subtitle' => (string)$e['role'],
      'date_range' => $range,
      'sort_key' => ($ey <= 0 ? 9999 : $ey) * 100 + $sy
    ];
  }

  foreach ($edu as $e) {
    $sy = (int)$e['start_year']; $ey = (int)$e['end_year'];
    $range = $sy . ' - ' . (($ey <= 0 || $ey >= 9999) ? 'Devam' : $ey);
    if (!empty($e['notes'])) $range .= ' • ' . $e['notes'];
    $items[] = [
      'type' => 'education',
      'title' => (string)$e['university'],
      'subtitle' => (string)$e['department'],
      'date_range' => $range,
      'sort_key' => ($ey <= 0 ? 9999 : $ey) * 100 + $sy
    ];
  }

  usort($items, function($a,$b){
    return ($b['sort_key'] <=> $a['sort_key']);
  });

  // remove helper
  foreach ($items as &$it) unset($it['sort_key']);
  return $items;
}

  public static function educations(): array {
    $pdo = DB::pdo();
    return $pdo->query("SELECT * FROM education ORDER BY start_year DESC")->fetchAll();
  }
  public static function experiences(): array {
    $pdo = DB::pdo();
    return $pdo->query("SELECT * FROM experience ORDER BY start_year DESC")->fetchAll();
  }

  public static function adminEduSave(array $d): int {
    $pdo = DB::pdo();
    $isNew = empty($d['id']);
    if ($isNew) {
      $st = $pdo->prepare("INSERT INTO education (university,department,start_year,end_year,notes)
        VALUES (:u,:d,:sy,:ey,:n)");
      $st->execute([':u'=>$d['university'],':d'=>$d['department'],':sy'=>$d['start_year'],':ey'=>$d['end_year'],':n'=>$d['notes']]);
      return (int)$pdo->lastInsertId();
    } else {
      $st = $pdo->prepare("UPDATE education SET university=:u, department=:d, start_year=:sy, end_year=:ey, notes=:n WHERE id=:id");
      $st->execute([':id'=>$d['id'],':u'=>$d['university'],':d'=>$d['department'],':sy'=>$d['start_year'],':ey'=>$d['end_year'],':n'=>$d['notes']]);
      return (int)$d['id'];
    }
  }

  public static function adminExpSave(array $d): int {
    $pdo = DB::pdo();
    $isNew = empty($d['id']);
    if ($isNew) {
      $st = $pdo->prepare("INSERT INTO experience (company,role,start_year,end_year,notes)
        VALUES (:c,:r,:sy,:ey,:n)");
      $st->execute([':c'=>$d['company'],':r'=>$d['role'],':sy'=>$d['start_year'],':ey'=>$d['end_year'],':n'=>$d['notes']]);
      return (int)$pdo->lastInsertId();
    } else {
      $st = $pdo->prepare("UPDATE experience SET company=:c, role=:r, start_year=:sy, end_year=:ey, notes=:n WHERE id=:id");
      $st->execute([':id'=>$d['id'],':c'=>$d['company'],':r'=>$d['role'],':sy'=>$d['start_year'],':ey'=>$d['end_year'],':n'=>$d['notes']]);
      return (int)$d['id'];
    }
  }

  public static function adminDelete(string $table, int $id): void {
    $pdo = DB::pdo();
    if (!in_array($table, ['education','experience'], true)) return;
    $st = $pdo->prepare("DELETE FROM {$table} WHERE id=:id");
    $st->execute([':id'=>$id]);
  }
}
