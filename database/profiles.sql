-- MODUL 01 — PROFILE. Tabel ini adalah "akar" data publik: educations & experiences nanti
-- memakai profile_id (FOREIGN KEY -> profiles.id). Website hanya punya SATU pemilik,
-- jadi aplikasi selalu memakai baris dengan id terkecil (tidak ada fitur banyak profil).
CREATE TABLE IF NOT EXISTS profiles (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  full_name      VARCHAR(100) NOT NULL,
  nickname       VARCHAR(50)  NULL,
  tagline        VARCHAR(150) NULL,               -- tagline hero; " / " = pemisah baris
  about          TEXT         NULL,               -- teks section About
  birth_date     DATE         NULL,               -- hanya untuk admin, tidak ditampilkan di publik
  address        VARCHAR(255) NULL,               -- hanya untuk admin, tidak ditampilkan di publik
  profile_photo  VARCHAR(100) NULL,               -- NAMA FILE di uploads/profile/ (bukan path penuh)
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data awal = teks yang sebelumnya tertulis manual di homepage (bukan data dummy baru),
-- agar tampilan publik tidak berubah. profile_photo NULL -> publik memakai assets/img/about-photo.jpg.
INSERT INTO profiles (full_name, nickname, tagline, about)
SELECT 'Fida', 'Fida', 'LIFE IS MADE / OF LITTLE THINGS.',
'Hi, I''m Fida!
Just someone who loves exploring ideas,
creating things, and finding beauty in the little details of everyday life.
Nothing too perfect, nothing too serious. Just a space
to be, to grow, and to share a little bit of my world.'
WHERE NOT EXISTS (SELECT 1 FROM profiles);