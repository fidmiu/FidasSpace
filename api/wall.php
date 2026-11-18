<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function out(int $code, array $data): never {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  out(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

const WALL_PER_PAGE = 6;
$offset = min(max(0, (int)($_GET['offset'] ?? 0)), 100000);

try {
  $c = require __DIR__ . '/config.php';
  $pdo = new PDO("mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  // Hanya is_published = 1. Email pengirim dan status baca TIDAK pernah ikut dikirim.
  $total = (int)$pdo->query('SELECT COUNT(*) FROM messages WHERE is_published = 1')->fetchColumn();

  $st = $pdo->prepare('SELECT sender_name, message, admin_reply, created_at
                       FROM messages WHERE is_published = 1
                       ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
  $st->bindValue(1, WALL_PER_PAGE + 1, PDO::PARAM_INT);   // +1 untuk mendeteksi masih ada halaman berikutnya
  $st->bindValue(2, $offset, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll();

  $hasMore = count($rows) > WALL_PER_PAGE;
  $rows = array_slice($rows, 0, WALL_PER_PAGE);

  $items = array_map(static fn(array $r): array => [
    'name'    => $r['sender_name'],
    'message' => $r['message'],
    'date'    => date('d M Y', strtotime($r['created_at'])),
    'reply'   => ($r['admin_reply'] !== null && trim($r['admin_reply']) !== '') ? $r['admin_reply'] : null,
  ], $rows);

  out(200, ['ok' => true, 'total' => $total, 'has_more' => $hasMore, 'items' => $items]);
} catch (Throwable $e) {
  error_log('[fidas-space] ' . $e->getMessage());
  out(500, ['ok' => false, 'message' => 'The notes could not be loaded right now.']);
}