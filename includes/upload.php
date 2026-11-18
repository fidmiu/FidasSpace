<?php
declare(strict_types=1);
/**
 * Upload gambar yang aman — dipakai ulang oleh Profile dan Gallery.
 *  - Hanya JPG / PNG / WEBP; tipe ditentukan dari ISI file (finfo + getimagesize), bukan dari nama/ekstensi kiriman browser.
 *  - Nama file simpanan dibuat acak (32 hex) + ekstensi dari tipe terdeteksi; nama asli tidak pernah dipakai.
 *  - Disimpan di uploads/<subfolder>/ (folder itu dikunci agar tidak bisa mengeksekusi PHP: lihat uploads/.htaccess).
 */
const IMG_TYPES      = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const IMG_MAX_PIXELS = 6000;   // sisi terpanjang; mencegah gambar raksasa menghabiskan memori

function upload_root(): string { return dirname(__DIR__) . '/uploads'; }

/** Nama file simpanan yang sah (cegah path traversal bila nilai dari database terkorupsi). */
function valid_upload_name(?string $name): bool {
  return is_string($name) && (bool)preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name);
}

function upload_error_text(int $code): string {
  return match ($code) {
    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
    UPLOAD_ERR_PARTIAL                        => 'The upload was interrupted. Please try again.',
    UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'The server could not store the file.',
    default                                   => 'The file could not be uploaded.',
  };
}

/** Apakah browser benar-benar mengirim file? */
function upload_present(?array $f): bool { return is_array($f) && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE; }

/**
 * Validasi + simpan. Mengembalikan nama file baru, atau null dan $err terisi.
 * File lama TIDAK disentuh di sini — pemanggil menghapusnya setelah database berhasil diperbarui.
 */
function save_image(array $f, string $sub, int $maxBytes, ?string &$err = null): ?string {
  $err = null;
  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { $err = upload_error_text((int)($f['error'] ?? 0)); return null; }
  $mb = rtrim(rtrim(number_format($maxBytes / 1048576, 1), '0'), '.');
  if (($f['size'] ?? 0) <= 0 || $f['size'] > $maxBytes) { $err = "The image must be {$mb} MB or smaller."; return null; }
  if (!is_uploaded_file($f['tmp_name'])) { $err = 'The file could not be uploaded.'; return null; }

  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  $info = @getimagesize($f['tmp_name']);
  if (!isset(IMG_TYPES[$mime]) || $info === false || ($info['mime'] ?? '') !== $mime) {
    $err = 'Only JPG, PNG or WEBP images are allowed.'; return null;
  }
  if ($info[0] < 1 || $info[1] < 1 || max($info[0], $info[1]) > IMG_MAX_PIXELS) {
    $err = 'The image dimensions are too large (max ' . IMG_MAX_PIXELS . ' px).'; return null;
  }

  $dir = upload_root() . '/' . $sub;
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) { error_log('[fidas-upload] cannot create ' . $dir); $err = 'The server could not store the file.'; return null; }

  $name = bin2hex(random_bytes(16)) . '.' . IMG_TYPES[$mime];
  if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) { error_log('[fidas-upload] move failed'); $err = 'The server could not store the file.'; return null; }
  @chmod($dir . '/' . $name, 0644);
  return $name;
}

/** Hapus file fisik (hanya nama yang lolos pola; selalu aman dipanggil). */
function delete_upload(string $sub, ?string $name): void {
  if (!valid_upload_name($name)) return;
  $p = upload_root() . '/' . $sub . '/' . $name;
  if (is_file($p) && !@unlink($p)) error_log('[fidas-upload] cannot delete ' . $p);
}