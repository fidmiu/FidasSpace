<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

// Semua aksi pengubah data hanya lewat POST + CSRF.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf'] ?? null)) {
  http_response_code(400);
  flash_set('Your session expired. Please try again.', 'err');
  header('Location: messages.php'); exit;
}

$id     = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$f      = in_array($_POST['f'] ?? '', ['all', 'unread', 'published', 'hidden'], true) ? $_POST['f'] : 'all';
$p      = max(1, (int)($_POST['p'] ?? 1));
$list   = "messages.php?f=$f&p=$p";
$detail = "message.php?id=$id&f=$f&p=$p";

$sql = [
  'publish'   => ['UPDATE messages SET is_published = 1, is_read = 1 WHERE id = ?', 'Message published.'],
  'unpublish' => ['UPDATE messages SET is_published = 0 WHERE id = ?',              'Message hidden from the site.'],
  'read'      => ['UPDATE messages SET is_read = 1 WHERE id = ?',                   'Marked as read.'],
  'unread'    => ['UPDATE messages SET is_read = 0 WHERE id = ?',                   'Marked as unread.'],
  'delete_reply' => ['UPDATE messages SET admin_reply = NULL, replied_at = NULL WHERE id = ?', 'Reply removed.'],
  'delete'    => ['DELETE FROM messages WHERE id = ?',                              'Message deleted.'],
];

// Simpan / ubah balasan admin (dua parameter, jadi tidak lewat tabel $sql)
if ($action === 'reply' && $id > 0) {
  $text = trim((string)($_POST['admin_reply'] ?? ''));
  if ($text === '') { flash_set('Write a reply first, or use REMOVE REPLY.', 'err'); header("Location: $detail"); exit; }
  if (mb_strlen($text) > 1000) { flash_set('Reply is too long (max 1000 characters).', 'err'); header("Location: $detail"); exit; }
  try {
    db()->prepare('UPDATE messages SET admin_reply = ?, replied_at = NOW(), is_read = 1 WHERE id = ?')->execute([$text, $id]);
    flash_set('Reply saved.');
  } catch (Throwable $ex) {
    error_log('[fidas-admin] ' . $ex->getMessage());
    flash_set('Something went wrong. Please try again.', 'err');
  }
  header("Location: $detail"); exit;
}

if ($id <= 0 || !isset($sql[$action])) { flash_set('Invalid request.', 'err'); header("Location: $list"); exit; }

try {
  $st = db()->prepare($sql[$action][0]);
  $st->execute([$id]);
  flash_set($sql[$action][1]);
} catch (Throwable $ex) {
  error_log('[fidas-admin] ' . $ex->getMessage());
  flash_set('Something went wrong. Please try again.', 'err');
}
header('Location: ' . (in_array($action, ['delete', 'unread'], true) ? $list : $detail));
exit;