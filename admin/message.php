<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$f  = in_array($_GET['f'] ?? '', ['all', 'unread', 'published', 'hidden'], true) ? $_GET['f'] : 'all';
$p  = max(1, (int)($_GET['p'] ?? 1));
$m = null;
try {
  $st = db()->prepare('SELECT * FROM messages WHERE id = ?');
  $st->execute([$id]);
  $m = $st->fetch();
  if ($m && !$m['is_read']) {                       // membuka pesan = menandai sudah dibaca
    db()->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
    $m['is_read'] = 1;
  }
} catch (Throwable $ex) { error_log('[fidas-admin] ' . $ex->getMessage()); }

if (!$m) { flash_set('Message not found.', 'err'); header('Location: messages.php'); exit; }

$csrf = csrf_token();
$act = function (string $action, string $label, string $cls = '') use ($m, $csrf, $f, $p): string {
  $confirm = ['delete' => 'Delete this message permanently?', 'delete_reply' => 'Remove your reply from this message?'][$action] ?? '';
  return '<form method="post" action="message_action.php"' . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
    . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
    . '<input type="hidden" name="id" value="' . (int)$m['id'] . '">'
    . '<input type="hidden" name="action" value="' . e($action) . '">'
    . '<input type="hidden" name="f" value="' . e($f) . '"><input type="hidden" name="p" value="' . $p . '">'
    . '<button class="adm-btn ' . $cls . '" type="submit">' . e($label) . '</button></form>';
};

$reply = (string)($m['admin_reply'] ?? '');
admin_header('Message', 'messages');
?>
<a class="link-arrow" href="messages.php?f=<?= e($f) ?>&amp;p=<?= $p ?>">← Back to messages</a>
<article class="detail">
  <div class="detail__meta">
    <span class="num">MESSAGE #<?= (int)$m['id'] ?></span>
    <span class="chip <?= $m['is_published'] ? 'chip--pub' : 'chip--hid' ?>"><?= $m['is_published'] ? 'PUBLISHED' : 'HIDDEN' ?></span>
  </div>
  <h1 class="detail__who"><?= e($m['sender_name']) ?></h1>
  <p class="detail__sub">
    <?php if ($m['sender_email']): ?><a href="mailto:<?= e($m['sender_email']) ?>"><?= e($m['sender_email']) ?></a> · <?php else: ?>No email · <?php endif; ?>
    <?= e(date('d M Y, H:i', strtotime($m['created_at']))) ?>
  </p>
  <div class="detail__body"><?= nl2br(e($m['message'])) ?></div>

  <div class="actions">
    <?= $m['is_published'] ? $act('unpublish', 'HIDE FROM SITE') : $act('publish', 'PUBLISH ↗', 'adm-btn--gold') ?>
    <?= $act('unread', 'MARK AS UNREAD') ?>
    <?= $act('delete', 'DELETE', 'adm-btn--danger') ?>
  </div>

  <section class="reply" aria-labelledby="reply-title">
    <span class="num">REPLY</span>
    <h2 id="reply-title" class="sec-title sec-title--sm">YOUR REPLY</h2>
    <p class="reply__hint">Shown under this message on the public guestbook, only while the message is <b>published</b>.</p>
    <form method="post" action="message_action.php">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <input type="hidden" name="action" value="reply">
      <input type="hidden" name="f" value="<?= e($f) ?>"><input type="hidden" name="p" value="<?= $p ?>">
      <div class="field">
        <label for="admin_reply">Reply</label>
        <textarea id="admin_reply" name="admin_reply" rows="5" maxlength="1000" placeholder="Write a reply…"><?= e($reply) ?></textarea>
      </div>
      <div class="actions">
        <button class="adm-btn adm-btn--gold" type="submit"><?= $reply !== '' ? 'UPDATE REPLY' : 'SAVE REPLY' ?></button>
      </div>
    </form>
    <?php if ($reply !== ''): ?>
      <div class="actions"><?= $act('delete_reply', 'REMOVE REPLY', 'adm-btn--danger') ?></div>
    <?php endif; ?>
  </section>
</article>
<?php admin_footer();