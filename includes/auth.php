<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Jangan pernah menampilkan error PHP/SQL ke pengunjung; cukup dicatat di log server.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

const ADMIN_IDLE_TIMEOUT = 1800;   // 30 menit tanpa aktivitas -> otomatis keluar
const LOGIN_IP_MAX       = 5;      // gagal per IP dalam jendela waktu
const LOGIN_IDENT_MAX    = 20;     // gagal per akun yang dituju (lebih longgar agar admin tidak mudah dikunci penyerang)
const LOGIN_WINDOW_MIN   = 15;
// Hash palsu: dipakai agar waktu respons sama baik akun ada maupun tidak (anti user enumeration).
const DUMMY_HASH = '$2y$10$/gQMNJXrnZM5Z7cT2rA8kOKPtlqWfvwP6h8bzLCG5F9JAgqB7m066';

function start_secure_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  ini_set('session.use_strict_mode', '1');      // tolak session ID buatan penyerang (session fixation)
  ini_set('session.use_only_cookies', '1');
  session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'secure' => $https,
    'httponly' => true, 'samesite' => 'Lax',
  ]);
  session_name('FIDASSESS');
  session_start();
}

function no_cache_headers(): void {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
  header('X-Frame-Options: DENY');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: same-origin');
}

/** Awalan URL ke root project (mis. "/fida-space" atau ""), dihitung dari lokasi skrip.
 *  Dipakai agar CSS/JS/link tidak bergantung pada relative path "../". */
function base_path(): string {
  $dir = str_replace('\\', '/', dirname(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/admin/x.php'))));
  return ($dir === '/' || $dir === '.' || $dir === '\\') ? '' : rtrim($dir, '/');
}
function url(string $path): string { return e(base_path() . '/' . ltrim($path, '/')); }

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/* ---------- CSRF ---------- */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function csrf_valid(?string $t): bool {
  return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

/* ---------- Akses admin (dipakai ulang di semua halaman admin) ---------- */
function destroy_session(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
  }
  session_destroy();
}

function is_admin_logged_in(): bool {
  if (empty($_SESSION['admin_id'])) return false;
  if (time() - (int)($_SESSION['last_activity'] ?? 0) > ADMIN_IDLE_TIMEOUT) return false;
  // Pastikan akunnya masih ada di database
  $st = db()->prepare('SELECT 1 FROM admins WHERE id = ? LIMIT 1');
  $st->execute([(int)$_SESSION['admin_id']]);
  return (bool)$st->fetchColumn();
}

/** Panggil di BARIS PERTAMA setiap halaman admin. Validasi dilakukan di server. */
function require_admin(): void {
  start_secure_session();
  no_cache_headers();
  try { $ok = is_admin_logged_in(); } catch (Throwable $ex) { error_log('[fidas-auth] ' . $ex->getMessage()); $ok = false; }
  if (!$ok) {
    if (!empty($_SESSION['admin_id'])) destroy_session();
    header('Location: login.php');
    exit;
  }
  $_SESSION['last_activity'] = time();
}

/* ---------- Pembatasan percobaan login ---------- */
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
function ident_hash(string $ident): string { return hash('sha256', mb_strtolower(trim($ident))); }

function login_locked(PDO $pdo, string $ip, string $ident): bool {
  $q = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > (NOW() - INTERVAL ' . LOGIN_WINDOW_MIN . ' MINUTE)');
  $q->execute([$ip]);
  if ((int)$q->fetchColumn() >= LOGIN_IP_MAX) return true;
  $q = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > (NOW() - INTERVAL ' . LOGIN_WINDOW_MIN . ' MINUTE)');
  $q->execute([ident_hash($ident)]);
  return (int)$q->fetchColumn() >= LOGIN_IDENT_MAX;
}
function login_record_failure(PDO $pdo, string $ip, string $ident): void {
  $pdo->prepare('INSERT INTO login_attempts (ip_address, identifier) VALUES (?, ?)')->execute([$ip, ident_hash($ident)]);
  $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
}
function login_clear_failures(PDO $pdo, string $ip): void {
  $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
}