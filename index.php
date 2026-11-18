<?php
declare(strict_types=1);
/**
 * Homepage. Bagian About (teks + foto) dan tagline hero dibaca dari tabel profiles.
 * Bila database tidak bisa dibaca, tampil teks bawaan yang sama persis seperti sebelumnya
 * (pengunjung tidak pernah melihat error).
 */
require_once __DIR__ . '/includes/site_data.php';
try { $profile = get_profile(); } catch (Throwable $ex) { error_log('[fidas-public] ' . $ex->getMessage()); $profile = null; }
$tagline = $profile ? (string)$profile['tagline'] : 'LIFE IS MADE / OF LITTLE THINGS.';
$about   = $profile ? (string)$profile['about']   : "Hi, I'm Fida!\nJust someone who loves exploring ideas, creating things, and finding beauty in the little details of everyday life.\nNothing too perfect, nothing too serious. Just a space to be, to grow, and to share a little bit of my world.";
$aboutPhoto = profile_photo_path($profile);
$aboutAlt   = 'Foto profil ' . ($profile ? ($profile['nickname'] ?: $profile['full_name']) : 'Fida');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fida's Space</title>
  <meta name="description" content="Fida's Space — personal portfolio dan ruang digital.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@800;900&family=Caveat:wght@600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=7">
  <script>document.documentElement.classList.add('js');</script>
</head>
<body>
<div class="frame">

  <!-- ===== HEADER ===== -->
  <header class="header">
    <a class="header__brand cell" href="index.php">FIDA'S SPACE</a>
    <p class="header__desc cell">Personal portfolio<br>profile · experience · gallery</p>

    <button class="header__toggle" type="button" aria-expanded="false" aria-controls="menu">Menu</button>
    <nav class="header__nav" id="menu" aria-label="Navigasi utama">
      <a class="cell" href="#about">About</a>
      <a class="cell" href="#experience">Experience</a>
      <a class="cell" href="#gallery">Gallery</a>
      <a class="cell" href="pesan.html">Contact</a>
    </nav>
    <span class="header__dot cell" aria-hidden="true"><i></i></span>
  </header>

  <main>
    <!-- ===== HERO ===== -->
    <section class="hero" aria-labelledby="hero-title">
      <h1 id="hero-title" class="sr-only">Fida's Space</h1>

      <div class="hero__canvas">
        <svg class="hero__wordmark" viewBox="0 0 1000 215" role="img" aria-label="Fida's Space">
          <text x="0" y="205" textLength="1000" lengthAdjust="spacingAndGlyphs">FIDA'S SPACE</text>
        </svg>

        <img class="hero__photo" src="assets/img/hero-photo.png" alt="Foto Fida">

        <div class="hero__left">
          <?php if ($tagline !== ''): ?><p class="hero__tagline"><?= str_replace(' / ', '<br>', h($tagline)) ?></p><?php endif; ?>
          <hr class="rule">
          <p class="hero__tags">PROFILE<br>EXPERIENCE<br>GALLERY</p>
        </div>

        <svg class="badge badge--hero" viewBox="0 0 120 120" role="img" aria-label="Create, inspire">
          <defs><path id="c1" d="M60 60 m-44 0 a44 44 0 1 1 88 0 a44 44 0 1 1 -88 0"/></defs>
          <circle cx="60" cy="60" r="58" fill="#F2C94C"/>
          <text><textPath href="#c1" startOffset="0">CREATE · INSPIRE · CREATE · INSPIRE ·</textPath></text>
          <circle cx="60" cy="60" r="16" fill="none" stroke="#171512" stroke-width="2"/>
          <path d="M44 60h32M60 44v32" stroke="#171512" stroke-width="2"/>
        </svg>

        <div class="hero__right">
          <p class="note">FRAGMENTS<br>OF MY<br>WORLD</p>
        </div>
        <div class="hero__details">
          <p class="hero__caption">5TH-SEMESTER <br>INFORMATION <br> TECHNOLOGY <br> STUDENT</p>
          <hr class="rule">
          <p class="hero__caption">EST. 2026</p>
        </div>
      </div>
    </section>

    <!-- ===== STATEMENT ===== (teks placeholder: ganti dengan kalimat Fida) -->
    <section class="statement" aria-label="Pernyataan">
      <div class="statement__row">
        <p class="cell statement__bold">MY STORY,</p>
        <p class="cell statement__script">told in my</p>
      </div>
      <div class="statement__row statement__row--last">
        <p class="cell statement__bold">MY SPACE.</p>
        <p class="cell statement__script">own words.</p>
        <p class="cell statement__words">PROFILE EDUCATION<br>EXPERIENCE GALLERY</p>
      </div>
    </section>

    <!-- ===== 01 ABOUT + 02 EDUCATION ===== -->
    <section class="about" id="about" aria-labelledby="about-title">
      <div class="about__text cell reveal">
        <span class="num">01</span>
        <h2 id="about-title" class="sec-title">ABOUT</h2>
        <?php if ($about !== ''): ?><p class="about__bio"><?= nl2br(h($about), false) ?></p><?php endif; ?>
        <hr class="rule">
        <p class="small">A WORK IN PROGRESS, <br> JUST LIKE LIFE.</p>
      </div>

      <figure class="about__photo cell reveal">
        <span class="tape" aria-hidden="true"></span>
        <img src="<?= h($aboutPhoto) ?>" alt="<?= h($aboutAlt) ?>">
      </figure>

      <aside class="about__edu reveal" aria-labelledby="edu-title">
        <span class="num">02</span>
        <h2 id="edu-title" class="sec-title sec-title--sm">EDUCATION</h2>
        <!-- Nanti di-loop dari tabel educations -->
        <ol class="edu-list">
          <li>SMKS SUNAN BONANG<small>SMA/K · TKJ · 2022</small></li>
          <li>THE FAMILY COURSE<small>SMA/K · ENGLISH · 2023</small></li>
          <li>UNIVERSITAS NURUL JADID<small>S-1 · TEKNOLOGI INFORMASI · 2024</small></li>
        </ol>
      </aside>
    </section>

    <!-- ===== 03 EXPERIENCE ===== -->
    <section class="block" id="experience" aria-labelledby="exp-title">
      <header class="block__head reveal">
        <span class="num">03</span>
        <h2 id="exp-title" class="sec-title sec-title--sm">EXPERIENCE</h2>
      </header>
      <!-- Nanti di-loop dari tabel experiences -->
      <ol class="exp-list">
        <li class="exp reveal"><span class="exp__no">001</span><h3 class="exp__title">CREATING</h3><span class="exp__cat">THINGS I ENJOY MAKING AND EXPLORING</span><span class="exp__year">2026</span></li>
        <li class="exp reveal"><span class="exp__no">002</span><h3 class="exp__title">LEARNING</h3><span class="exp__cat">NEW KNOWLEDGE, SKILLS, AND EXPERIENCES</span><span class="exp__year">2026</span></li>
        <li class="exp reveal"><span class="exp__no">003</span><h3 class="exp__title">EXPLORING</h3><span class="exp__cat">IDEAS, INTERESTS, AND EVERYTHING IN BETWEEN</span><span class="exp__year">2026</span></li>
      </ol>
    </section>

    <!-- ===== 04 GALLERY ===== -->
    <section class="block" id="gallery" aria-labelledby="gal-title">
      <header class="block__head reveal">
        <span class="num">04</span>
        <h2 id="gal-title" class="sec-title sec-title--sm">GALLERY</h2>
      </header>
      <!-- Nanti di-loop dari tabel galleries -->
      <div class="gallery">
        <a class="shot reveal" href="assets/img/gallery-1.jpg"><span class="shot__img"><img src="assets/img/gallery-1.jpg" alt="[Judul foto 1]"></span><span class="shot__meta"><b>001</b> LIHAT KANAN</span></a>
        <a class="shot reveal" href="assets/img/gallery-2.jpg"><span class="shot__img"><img src="assets/img/gallery-2.jpg" alt="[Judul foto 2]"></span><span class="shot__meta"><b>002</b> SEMBUR</span></a>
        <a class="shot reveal" href="assets/img/gallery-3.jpg"><span class="shot__img"><img src="assets/img/gallery-3.jpg" alt="[Judul foto 3]"></span><span class="shot__meta"><b>003</b> LIHAT KIRI</span></a>
      </div>
    </section>

    <!-- ===== 05 CTA ===== -->
    <section class="cta" aria-labelledby="cta-title">
      <span class="num">05</span>
      <h2 id="cta-title" class="cta__title">LEAVE ME<br>A MESSAGE<span class="cta__script">say hi.</span></h2>
      <div class="cta__side">
        <p>Passing by? I'd love to hear from you. Leave a little note, thought, or just a simple hello!</p>
        <a class="btn" href="pesan.html">Send a message <span aria-hidden="true">→</span></a>
      </div>
      <svg class="badge badge--cta" viewBox="0 0 120 120" aria-hidden="true">
        <defs><path id="c2" d="M60 60 m-44 0 a44 44 0 1 1 88 0 a44 44 0 1 1 -88 0"/></defs>
        <circle cx="60" cy="60" r="58" fill="#E87932"/>
        <text><textPath href="#c2">GOOD IDEAS · GOOD IDEAS · GOOD IDEAS ·</textPath></text>
        <path d="M60 46v28M46 60h28" stroke="#171512" stroke-width="2"/>
      </svg>
    </section>
  </main>

  <!-- ===== FOOTER ===== -->
  <footer class="footer">
    <b class="cell">FIDA'S SPACE</b>
    <span class="cell footer__tag">MADE WITH CURIOSITY & CARE.</span>
    <nav class="cell footer__social" aria-label="Sosial media">
      <a href="#https://www.instagram.com/fidnoi/">Instagram</a><a href="#https://github.com/mufida-2421500032/">GitHub</a><a href="#mu4fida@gmail.com">Mufida</a>
    </nav>
    <span class="cell footer__copy">© 2026</span>
  </footer>

</div>
<script src="assets/js/main.js" defer></script>
</body>
</html>