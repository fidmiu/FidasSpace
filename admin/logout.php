<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

start_secure_session();
no_cache_headers();

// Logout hanya lewat POST + CSRF (mencegah logout paksa lewat link/gambar dari situs lain)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf'] ?? null)) {
  http_response_code(405);
  header('Location: login.php');
  exit;
}
destroy_session();
header('Location: login.php?out=1');
exit;