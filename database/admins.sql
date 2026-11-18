-- Tabel admins sesuai Dokumen Perancangan Sistem (Tahap 3)
CREATE TABLE IF NOT EXISTS admins (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  email       VARCHAR(100) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,            -- hasil password_hash(), bukan password asli
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;