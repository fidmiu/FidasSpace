<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

start_secure_session();
no_cache_headers();

// Sudah login -> langsung ke halaman tujuan
try { if (is_admin_logged_in()) { header('Location: index.php'); exit; } } catch (Throwable $ex) { error_log('[fidas-auth] ' . $ex->getMessage()); }

const GENERIC_ERROR = 'Username atau password tidak valid.';
$error = '';
$notice = isset($_GET['out']) ? 'You have been signed out.' : '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identifier = trim((string)($_POST['identifier'] ?? ''));
  $password   = (string)($_POST['password'] ?? '');

  if (!csrf_valid($_POST['csrf'] ?? null)) {
    http_response_code(400);
    $error = 'Your session expired. Please try again.';
  } elseif ($identifier === '' || $password === '') {
    $error = 'Please fill in both fields.';
  } elseif (mb_strlen($identifier) > 100 || strlen($password) > 200) {
    $error = GENERIC_ERROR;
  } else {
    try {
      $pdo = db();
      $ip  = client_ip();

      if (login_locked($pdo, $ip, $identifier)) {
        http_response_code(429);
        $error = 'Too many attempts. Please wait a few minutes and try again.';
      } else {
        $st = $pdo->prepare('SELECT id, username, password FROM admins WHERE username = ? OR email = ? LIMIT 1');
        $st->execute([$identifier, $identifier]);
        $admin = $st->fetch();

        // password_verify SELALU dijalankan (hash palsu jika akun tidak ada): waktu respons sama.
        $valid = password_verify($password, $admin['password'] ?? DUMMY_HASH) && $admin;

        if ($valid) {
          session_regenerate_id(true);                 // cegah session fixation
          $_SESSION = [];
          $_SESSION['admin_id']      = (int)$admin['id'];
          $_SESSION['admin_name']    = $admin['username'];
          $_SESSION['last_activity'] = time();
          csrf_token();                                // token baru untuk session baru
          login_clear_failures($pdo, $ip);

          if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
          }
          header('Location: index.php');
          exit;
        }

        login_record_failure($pdo, $ip, $identifier);
        $error = GENERIC_ERROR;                        // pesan sama untuk akun salah / password salah
      }
    } catch (Throwable $ex) {
      error_log('[fidas-auth] ' . $ex->getMessage());
      http_response_code(500);
      $error = 'Something went wrong. Please try again later.';
    }
  }
}
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin Login — Fida's Space</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@800;900&family=Caveat:wght@600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('style.css') ?>">
  <link rel="stylesheet" href="<?= url('pesan.css') ?>">
  <link rel="stylesheet" href="<?= url('admin/admin.css') ?>">
</head>
<body>
<div class="frame">

  <!-- HEADER: gaya sama dengan navbar homepage -->
  <header class="header login-head">
    <a class="header__brand cell" href="<?= url('index.html') ?>">FIDA'S SPACE</a>
    <p class="header__desc cell">PRIVATE ADMIN ACCESS</p>
    <nav class="header__nav" aria-label="Navigation">
      <a class="cell" href="<?= url('index.html') ?>">Back to site ↗</a>
    </nav>
    <span class="header__dot cell" aria-hidden="true"><i></i></span>
  </header>

  <main class="login">
    <!-- KOLOM KIRI: WELCOME BACK + FOTO (aset sama dengan hero homepage) -->
    <section class="login__brand cell" aria-labelledby="brand-title">
      <span class="num login__num">01 — ADMINISTRATOR ACCESS</span>
      <h1 id="brand-title" class="login__title">WELCOME<br>BACK.</h1>

      <img class="login__photo" src="<?= url('assets/img/hero-photo-login.png') ?>" alt="Portrait of Ariana Grande">

      <div class="login__copy">
        <p class="login__script">Your little space awaits.</p>
        <hr class="rule">
        <p class="login__desc">A private corner for managing everything behind Fida's Space.</p>
      </div>

      <p class="note login__sticky" aria-hidden="true">psst — just<br>you &amp; me :)</p>

      <svg class="badge badge--login" viewBox="0 0 120 120" role="img" aria-label="Create, inspire">
        <defs><path id="c4" d="M60 60 m-44 0 a44 44 0 1 1 88 0 a44 44 0 1 1 -88 0"/></defs>
        <circle cx="60" cy="60" r="58" fill="#F2C94C"/>
        <text><textPath href="#c4" startOffset="0">CREATE · INSPIRE · CREATE · INSPIRE ·</textPath></text>
        <circle cx="60" cy="60" r="16" fill="none" stroke="#171512" stroke-width="2"/>
        <path d="M44 60h32M60 44v32" stroke="#171512" stroke-width="2"/>
      </svg>
    </section>

    <!-- KOLOM KANAN: ADMIN LOGIN FORM -->
    <section class="login__panel" aria-labelledby="form-title">
      <div class="login__card">
        <span class="num">02 — PRIVATE ACCESS</span>
        <h2 id="form-title" class="login__h2">ADMIN LOGIN.</h2>
        <p class="login__sub">Sign in to access your private space.</p>

        <?php if ($notice): ?><p class="notice" role="status"><?= e($notice) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="err err--form" role="alert"><?= e($error) ?></p><?php endif; ?>

        <form id="login-form" method="post" action="login.php" novalidate autocomplete="on">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

          <div class="field">
            <label for="identifier">Username / Email <span class="req">*</span></label>
            <input id="identifier" name="identifier" type="text" maxlength="100" autocomplete="username" placeholder="Enter your username or email" value="<?= e($identifier) ?>" required autofocus>
          </div>

          <div class="field">
            <label for="password">Password <span class="req">*</span></label>
            <div class="pw">
              <input id="password" name="password" type="password" maxlength="200" autocomplete="current-password" placeholder="Enter your password" required>
              <button class="pw__toggle" id="pw-toggle" type="button" aria-label="Show password" aria-pressed="false">SHOW</button>
            </div>
          </div>

          <p class="err err--form" id="client-err" role="alert" hidden></p>

          <button class="btn btn--send" id="login-btn" type="submit">
            <span class="btn__label">SIGN IN ↗</span>
            <span class="btn__loading" aria-hidden="true">SIGNING IN…</span>
          </button>
          <p class="login__private">PRIVATE ACCESS ONLY.</p>
        </form>
      </div>
    </section>
  </main>

  <footer class="footer">
    <b class="cell">FIDA'S SPACE</b>
    <span class="cell footer__tag">MADE WITH CURIOSITY &amp; CARE.</span>
    <nav class="cell footer__social" aria-label="Site"><a href="<?= url('index.html') ?>">Home</a><a href="<?= url('pesan.html') ?>">Contact</a></nav>
    <span class="cell footer__copy">© 2026</span>
  </footer>
</div>
<script src="<?= url('admin/login.js') ?>" defer></script>
</body>
</html>