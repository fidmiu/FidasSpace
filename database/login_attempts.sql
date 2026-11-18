-- Tabel TAMBAHAN (bukan dari rancangan awal) untuk membatasi percobaan login.
-- Alasan teknis: pembatasan berbasis session mudah dilewati penyerang, jadi dicatat di database.
CREATE TABLE IF NOT EXISTS login_attempts (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  ip_address    VARCHAR(45) NOT NULL,
  identifier    CHAR(64)    NOT NULL,            -- SHA-256 dari username/email yang dicoba
  attempted_at  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip_address, attempted_at),
  INDEX idx_ident_time (identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;