<?php
declare(strict_types=1);

/**
 * Minimal TOTP (RFC 6238) implementation for Google Authenticator etc.
 * Secret is Base32 (A-Z2-7) without padding.
 */
final class TOTP {
  public static function generateSecret(int $bytes = 20): string {
    $raw = random_bytes($bytes);
    return self::base32Encode($raw);
  }

  public static function verify(string $base32Secret, string $code, int $window = 1, int $step = 30): bool {
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) < 6) return false;
    $code = substr($code, -6);

    $secret = self::base32Decode($base32Secret);
    if ($secret === '') return false;

    $t = (int) floor(time() / $step);
    for ($i = -$window; $i <= $window; $i++) {
      if (hash_equals(self::hotp($secret, $t + $i), $code)) return true;
    }
    return false;
  }

  private static function hotp(string $secret, int $counter): string {
    // 8-byte counter (big-endian)
    $binCounter = pack('N*', 0) . pack('N*', $counter);
    $hash = hash_hmac('sha1', $binCounter, $secret, true);
    $offset = ord($hash[19]) & 0x0F;
    $part = substr($hash, $offset, 4);
    $value = unpack('N', $part)[1] & 0x7FFFFFFF;
    $otp = $value % 1000000;
    return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
  }

  private static function base32Encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $c) {
      $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i < strlen($bits); $i += 5) {
      $chunk = substr($bits, $i, 5);
      if (strlen($chunk) < 5) $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
      $out .= $alphabet[bindec($chunk)];
    }
    return $out;
  }

  private static function base32Decode(string $b32): string {
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32) ?? '');
    if ($b32 === '') return '';
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $map = [];
    for ($i=0; $i<strlen($alphabet); $i++) $map[$alphabet[$i]] = $i;

    $bits = '';
    foreach (str_split($b32) as $ch) {
      if (!isset($map[$ch])) return '';
      $bits .= str_pad(decbin($map[$ch]), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i+8 <= strlen($bits); $i += 8) {
      $out .= chr(bindec(substr($bits, $i, 8)));
    }
    return $out;
  }
}
