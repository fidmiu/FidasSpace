-- Tabel sesuai Dokumen Perancangan Sistem (Tahap 3)
CREATE TABLE IF NOT EXISTS messages (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  sender_name   VARCHAR(100) NOT NULL,
  sender_email  VARCHAR(100) NULL,
  message       TEXT NOT NULL,
  is_read       TINYINT(1) NOT NULL DEFAULT 0,
  is_published  TINYINT(1) NOT NULL DEFAULT 0,   -- privat sampai admin memublikasikan
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;