<?php
declare(strict_types=1);

/** Koneksi PDO tunggal. Memakai kredensial yang sama dengan api/config.php. */
function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $c = require __DIR__ . '/../api/config.php';
    $pdo = new PDO("mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
  }
  return $pdo;
}