<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();                       // wajib di baris pertama setiap halaman admin

$s = ['total' => 0, 'unread' => 0, 'published' => 0];
$recent = [];
try {
  $s = db()->query('SELECT COUNT(*) total, COALESCE(SUM(is_read=0),0) unread, COALESCE(SUM(is_published=1),0) published FROM messages')->fetch();
  $recent = db()->query('SELECT id, sender_name, message, is_read, is_published, created_at FROM messages ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();
} catch (Throwable $ex) { error_log('[fidas-admin] ' . $ex->getMessage()); flash_set('Could not load statistics.', 'err'); }

admin_header('Dashboard', 'dashboard');
?>
<span class="num">OVERVIEW</span>
<h1 class="adm-title">HELLO, <?= e(mb_strtoupper($_SESSION['admin_name'] ?? 'ADMIN')) ?>.</h1>
<p class="adm-script">Here's what's happening in your space.</p>

<section class="stats" aria-label="Statistics">
  <a class="stat" href="messages.php"><span class="stat__n"><?= (int)$s['total'] ?></span><span class="stat__l">Total messages</span></a>
  <a class="stat stat--hot" href="messages.php?f=unread"><span class="stat__n"><?= (int)$s['unread'] ?></span><span class="stat__l">Unread</span></a>
  <a class="stat" href="messages.php?f=published"><span class="stat__n"><?= (int)$s['published'] ?></span><span class="stat__l">Published</span></a>
</section>

<section class="adm-block" aria-labelledby="recent-t">
  <div class="adm-block__head"><h2 id="recent-t" class="sec-title sec-title--sm">LATEST MESSAGES</h2><a class="link-arrow" href="messages.php">View all →</a></div>
  <?php if (!$recent): ?>
    <p class="empty">No messages yet. They will appear here when visitors write to you.</p>
  <?php else: ?>
  <ul class="msg-list">
    <?php foreach ($recent as $m): ?>
    <li><a class="msg" href="message.php?id=<?= (int)$m['id'] ?>">
      <span class="msg__who"><?= e($m['sender_name']) ?></span>
      <span class="msg__text"><?= e(mb_strimwidth($m['message'], 0, 90, '…')) ?></span>
      <span class="msg__meta"><?php if (!$m['is_read']): ?><span class="chip chip--new">NEW</span><?php endif; ?><span class="msg__date"><?= e(date('d M Y', strtotime($m['created_at']))) ?></span></span>
    </a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>

<section class="adm-block" aria-labelledby="soon-t">
  <h2 id="soon-t" class="sec-title sec-title--sm">COMING NEXT</h2>
  <p class="adm-note">Education, Experience and Gallery management will be added in the next stages.</p>
</section>
<?php admin_footer();