<?php
declare(strict_types=1);
/**
 * Kerangka tampilan halaman admin (dipakai ulang semua modul).
 * Pemakaian:  require_admin();  admin_header('Judul', 'messages');  ...isi...  admin_footer();
 */
require_once __DIR__ . '/auth.php';

function unread_count(): int {
  try { return (int)db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn(); }
  catch (Throwable $e) { error_log('[fidas-admin] ' . $e->getMessage()); return 0; }
}

/** Pesan sekali tampil (hasil aksi). */
function flash_set(string $msg, string $type = 'ok'): void { $_SESSION['flash'] = ['m' => $msg, 't' => $type]; }
function flash_render(): void {
  if (empty($_SESSION['flash'])) return;
  $f = $_SESSION['flash']; unset($_SESSION['flash']);
  $cls = $f['t'] === 'err' ? 'err err--form' : 'notice';
  echo '<p class="' . $cls . '" role="status">' . e($f['m']) . '</p>';
}

/** Penampil error per-field untuk form admin. */
function fld_err(array $errors, string $k): string { return isset($errors[$k]) ? '<p class="err" id="err-' . e($k) . '">' . e($errors[$k]) . '</p>' : ''; }
function fld_cls(array $errors, string $k): string { return isset($errors[$k]) ? ' is-invalid' : ''; }

function admin_header(string $title, string $active): void {
  $csrf   = csrf_token();
  $unread = unread_count();
  $items  = [
    'dashboard' => ['index.php', 'Dashboard'],
    'messages'  => ['messages.php', 'Messages'],
    'profile'   => ['profile.php', 'Profile'],
  ];
  $soon = ['Education', 'Experience', 'Gallery'];
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> — Fida's Space Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@800;900&family=Caveat:wght@600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('style.css') ?>">
  <link rel="stylesheet" href="<?= url('pesan.css') ?>">
  <link rel="stylesheet" href="<?= url('admin/admin.css') ?>">
</head>
<body>
<div class="frame">
  <header class="adm-head">
    <a class="adm-brand cell" href="index.php">FIDA'S SPACE <small>ADMIN</small></a>
    <nav class="adm-nav" aria-label="Admin navigation">
      <?php foreach ($items as $key => [$href, $label]): ?>
        <a class="cell<?= $active === $key ? ' is-active' : '' ?>" href="<?= e($href) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>>
          <?= e($label) ?><?php if ($key === 'messages' && $unread > 0): ?><span class="badge-n" aria-label="<?= $unread ?> unread"><?= $unread ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
      <?php foreach ($soon as $label): ?>
        <span class="cell is-soon" title="Coming soon"><?= e($label) ?></span>
      <?php endforeach; ?>
    </nav>
    <div class="adm-tools">
      <a href="<?= url('index.php') ?>" target="_blank" rel="noopener">View site ↗</a>
      <form method="post" action="logout.php">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <button type="submit">Sign out</button>
      </form>
    </div>
  </header>
  <main class="adm-main">
    <?php flash_render(); ?>
<?php
}

function admin_footer(): void { ?>
  </main>
</div>
<script src="<?= url('admin/admin.js') ?>" defer></script>
</body>
</html>
<?php
}