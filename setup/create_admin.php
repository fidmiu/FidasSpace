<?php
declare(strict_types=1);
/**
 * Pembuatan akun admin PERTAMA. Hanya bisa dijalankan dari terminal:
 *   php setup/create_admin.php
 * - Ditolak jika diakses lewat browser.
 * - Ditolak jika sudah ada akun admin (bukan jalur registrasi).
 * - Password tidak disimpan di source code; hanya hash yang masuk database.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/db.php';

function ask(string $label): string { echo $label; return trim((string)fgets(STDIN)); }

try { $pdo = db(); } catch (Throwable $e) { fwrite(STDERR, "Tidak bisa terhubung ke database. Cek api/config.php.\n"); exit(1); }

if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
  fwrite(STDERR, "Akun admin sudah ada. Script ini hanya untuk akun pertama.\n"); exit(1);
}

$username = ask('Username (3-50 karakter): ');
$email    = ask('Email: ');
echo "Catatan: password akan terlihat saat diketik di terminal ini.\n";
$pass1    = ask('Password (min. 10 karakter): ');
$pass2    = ask('Ulangi password: ');

$errs = [];
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) $errs[] = 'Username 3-50 karakter (huruf, angka, _ . -).';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) $errs[] = 'Email tidak valid.';
if (strlen($pass1) < 10) $errs[] = 'Password minimal 10 karakter.';
if ($pass1 !== $pass2)   $errs[] = 'Konfirmasi password tidak sama.';
if ($errs) { fwrite(STDERR, implode("\n", $errs) . "\n"); exit(1); }

$pdo->prepare('INSERT INTO admins (username, email, password) VALUES (?, ?, ?)')
    ->execute([$username, $email, password_hash($pass1, PASSWORD_DEFAULT)]);
echo "Akun admin '{$username}' berhasil dibuat.\n";