-- Jalankan SEKALI pada database yang sudah ada (tabel messages sudah terisi).
-- Instalasi baru cukup memakai database/messages.sql.
ALTER TABLE messages
  ADD COLUMN admin_reply TEXT NULL AFTER is_published,
  ADD COLUMN replied_at  TIMESTAMP NULL DEFAULT NULL AFTER admin_reply;