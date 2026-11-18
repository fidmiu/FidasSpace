<?php
declare(strict_types=1);
/**
 * Pengambilan data konten untuk halaman publik DAN admin (satu sumber: database).
 * Hanya bergantung pada db.php — aman di-require dari halaman publik tanpa memuat session admin.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/upload.php';

const DEFAULT_ABOUT_PHOTO = 'assets/img/about-photo.jpg';

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Website hanya punya satu pemilik: selalu baris profile dengan id terkecil. */
function get_profile(): ?array {
  $r = db()->query('SELECT id, full_name, nickname, tagline, about, birth_date, address, profile_photo FROM profiles ORDER BY id ASC LIMIT 1')->fetch();
  return $r ?: null;
}

/** Path foto profil relatif terhadap root project (foto unggahan, atau foto bawaan bila belum ada). */
function profile_photo_path(?array $p): string {
  return ($p && valid_upload_name($p['profile_photo'] ?? null)) ? 'uploads/profile/' . $p['profile_photo'] : DEFAULT_ABOUT_PHOTO;
}