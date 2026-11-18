<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
session_start();

function out(int $code, array $data): never {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  out(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

// Honeypot: bot mengisi, manusia tidak. Pura-pura sukses agar bot tidak belajar.
if (!empty($_POST['website'] ?? '')) out(200, ['ok' => true]);

// Throttle sederhana: 1 pesan per 15 detik per sesi
$now = time();
if (isset($_SESSION['last_msg']) && $now - $_SESSION['last_msg'] < 15) {
  out(429, ['ok' => false, 'message' => 'Please wait a few seconds before sending another note.']);
}

$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$errors = [];
if ($name === '') $errors['name'] = 'Please tell me what to call you.';
elseif (mb_strlen($name) > 100) $errors['name'] = 'Name is too long (max 100 characters).';
if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100)) {
  $errors['email'] = 'That email doesn’t look right.';
}
if ($message === '') $errors['message'] = 'Your message is empty.';
elseif (mb_strlen($message) > 1000) $errors['message'] = 'Message is too long (max 1000 characters).';

if ($errors) out(422, ['ok' => false, 'message' => 'Please check the highlighted fields.', 'errors' => $errors]);

try {
  $c = require __DIR__ . '/config.php';
  $pdo = new PDO("mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  // is_read & is_published sengaja tidak diisi: default 0 (privat, menunggu admin)
  $st = $pdo->prepare('INSERT INTO messages (sender_name, sender_email, message) VALUES (?, ?, ?)');
  $st->execute([$name, $email === '' ? null : $email, $message]);
  $_SESSION['last_msg'] = $now;
  out(200, ['ok' => true]);
} catch (Throwable $e) {
  error_log('[fidas-space] ' . $e->getMessage());
  out(500, ['ok' => false, 'message' => 'Your message could not be saved. Please try again later.']);
}