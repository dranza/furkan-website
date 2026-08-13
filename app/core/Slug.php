<?php
declare(strict_types=1);

final class Slug {
  public static function make(string $text): string {
    $text = trim($text);
    $map = [
      'ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g',
      'ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c'
    ];
    $text = strtr($text, $map);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
    $text = preg_replace('/[\s-]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text ?: bin2hex(random_bytes(4));
  }
}
