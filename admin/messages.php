<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

const PER_PAGE = 10;
$filters = ['all' => 'All', 'unread' => 'Unread', 'published' => 'Published', 'hidden' => 'Hidden'];
$f = $_GET['f'] ?? 'all';
if (!isset($filters[$f])) $f = 'all';
$page = max(1, (int)($_GET['p'] ?? 1));

$where = ['all' => '1=1', 'unread' => 'is_read = 0', 'published' => 'is_published = 1', 'hidden' => 'is_published = 0'][$f];
$rows = []; $counts = ['all' => 0, 'unread' => 0, 'published' => 0, 'hidden' => 0]; $pages = 1;
try {
  $c = db()->query('SELECT COUNT(*) a, COALESCE(SUM(is_read=0),0) u, COALESCE(SUM(is_published=1),0) p, COALESCE(SUM(is_published=0),0) h FROM messages')->fetch();
  $counts = ['all' => (int)$c['a'], 'unread' => (int)$c['u'], 'published' => (int)$c['p'], 'hidden' => (int)$c['h']];
  $pages = max(1, (int)ceil($counts[$f] / PER_PAGE));
  $page  = min($page, $pages);
  $st = db()->prepare("SELECT id, sender_name, sender_email, message, is_read, is_published, (admin_reply IS NOT NULL) AS has_reply, created_at FROM messages WHERE $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?");
  $st->bindValue(1, PER_PAGE, PDO::PARAM_INT);
  $st->bindValue(2, ($page - 1) * PER_PAGE, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll();
} catch (Throwable $ex) { error_log('[fidas-admin] ' . $ex->getMessage()); flash_set('Could not load messages.', 'err'); }

admin_header('Messages', 'messages');
?>
<span class="num">GUESTBOOK</span>
<h1 class="adm-title">MESSAGES</h1>

<nav class="tabs" aria-label="Filter messages">
  <?php foreach ($filters as $k => $label): ?>
    <a href="messages.php?f=<?= $k ?>" class="<?= $f === $k ? 'is-active' : '' ?>"<?= $f === $k ? ' aria-current="page"' : '' ?>><?= e($label) ?> <small><?= $counts[$k] ?></small></a>
  <?php endforeach; ?>
</nav>

<?php if (!$rows): ?>
  <p class="empty">Nothing here yet.</p>
<?php else: ?>
<ul class="msg-list">
  <?php foreach ($rows as $m): ?>
  <li class="msg-row<?= $m['is_read'] ? '' : ' is-unread' ?>">
    <a class="msg" href="message.php?id=<?= (int)$m['id'] ?>&amp;f=<?= e($f) ?>&amp;p=<?= $page ?>">
      <span class="msg__who"><?= e($m['sender_name']) ?></span>
      <span class="msg__text"><?= e(mb_strimwidth($m['message'], 0, 110, '…')) ?></span>
      <span class="msg__meta">
        <?php if (!$m['is_read']): ?><span class="chip chip--new">NEW</span><?php endif; ?>
        <span class="chip <?= $m['is_published'] ? 'chip--pub' : 'chip--hid' ?>"><?= $m['is_published'] ? 'PUBLISHED' : 'HIDDEN' ?></span>
        <?php if ($m['has_reply']): ?><span class="chip">REPLIED</span><?php endif; ?>
        <span class="msg__date"><?= e(date('d M Y, H:i', strtotime($m['created_at']))) ?></span>
      </span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($pages > 1): ?>
<nav class="pager" aria-label="Pagination">
  <?php if ($page > 1): ?><a href="messages.php?f=<?= e($f) ?>&amp;p=<?= $page - 1 ?>">← Prev</a><?php endif; ?>
  <span>Page <?= $page ?> / <?= $pages ?></span>
  <?php if ($page < $pages): ?><a href="messages.php?f=<?= e($f) ?>&amp;p=<?= $page + 1 ?>">Next →</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php admin_footer();