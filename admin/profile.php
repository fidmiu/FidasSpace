<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/site_data.php';
require_admin();                       // wajib di baris pertama setiap halaman admin

const PHOTO_MAX_BYTES = 2 * 1048576;   // 2 MB

$errors  = [];
$profile = null;
try { $profile = get_profile(); }
catch (Throwable $ex) { error_log('[fidas-admin] ' . $ex->getMessage()); flash_set('Could not load your profile.', 'err'); }

$fields = ['full_name', 'nickname', 'tagline', 'about', 'birth_date', 'address'];
$v = [];
foreach ($fields as $f) $v[$f] = (string)($profile[$f] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_valid($_POST['csrf'] ?? null)) {
    http_response_code(400);
    $errors['form'] = 'Your session expired. Please try again.';
    foreach ($fields as $f) $v[$f] = (string)($_POST[$f] ?? '');
  } else {
    foreach ($fields as $f) $v[$f] = trim(str_replace("\r\n", "\n", (string)($_POST[$f] ?? '')));

    // --- validasi server-side ---
    if ($v['full_name'] === '')                    $errors['full_name'] = 'Please enter your full name.';
    elseif (mb_strlen($v['full_name']) > 100)      $errors['full_name'] = 'Full name must be 100 characters or fewer.';
    if (mb_strlen($v['nickname']) > 50)            $errors['nickname']  = 'Nickname must be 50 characters or fewer.';
    if (mb_strlen($v['tagline']) > 150)            $errors['tagline']   = 'Tagline must be 150 characters or fewer.';
    if (str_contains($v['tagline'], "\n"))         $errors['tagline']   = 'Tagline must be a single line.';
    if (mb_strlen($v['about']) > 2000)             $errors['about']     = 'About me must be 2,000 characters or fewer.';
    if (mb_strlen($v['address']) > 255)            $errors['address']   = 'Address must be 255 characters or fewer.';
    if ($v['birth_date'] !== '') {
      $d = DateTime::createFromFormat('!Y-m-d', $v['birth_date']);
      if (!$d || $d->format('Y-m-d') !== $v['birth_date'])               $errors['birth_date'] = 'Please enter a valid date.';
      elseif ($d > new DateTime('today') || (int)$d->format('Y') < 1900) $errors['birth_date'] = 'Please enter a realistic birth date.';
    }

    // --- foto: simpan file baru DULU; file lama baru dihapus setelah database sukses ---
    $newPhoto = null;
    $hasUpload = upload_present($_FILES['photo'] ?? null);
    if (!$errors && $hasUpload) {
      $perr = null;
      $newPhoto = save_image($_FILES['photo'], 'profile', PHOTO_MAX_BYTES, $perr);
      if ($newPhoto === null) $errors['photo'] = $perr;
    } elseif ($errors && $hasUpload) {
      $errors['photo_note'] = 'Please choose the photo again after fixing the errors above.';   // browser tidak menyimpan pilihan file
    }

    if (!$errors) {
      try {
        $oldPhoto = $profile['profile_photo'] ?? null;
        $params = [
          $v['full_name'], $v['nickname'] ?: null, $v['tagline'] ?: null, $v['about'] ?: null,
          $v['birth_date'] ?: null, $v['address'] ?: null,
        ];
        if ($profile) {
          // Tanpa foto baru: kolom profile_photo TIDAK disentuh (foto lama dipertahankan).
          $sql = 'UPDATE profiles SET full_name=?, nickname=?, tagline=?, about=?, birth_date=?, address=?' . ($newPhoto ? ', profile_photo=?' : '') . ' WHERE id=?';
          if ($newPhoto) $params[] = $newPhoto;
          $params[] = (int)$profile['id'];
        } else {
          $sql = 'INSERT INTO profiles (full_name, nickname, tagline, about, birth_date, address, profile_photo) VALUES (?,?,?,?,?,?,?)';
          $params[] = $newPhoto;
        }
        db()->prepare($sql)->execute($params);

        if ($newPhoto && $oldPhoto) delete_upload('profile', $oldPhoto);   // aman: database sudah berhasil
        flash_set('Profile saved. Your changes are now live on the site.');
        header('Location: profile.php'); exit;
      } catch (Throwable $ex) {
        error_log('[fidas-admin] ' . $ex->getMessage());
        if ($newPhoto) delete_upload('profile', $newPhoto);                // batalkan file baru; foto lama utuh
        $errors['form'] = 'Something went wrong while saving. Nothing was changed.';
      }
    } elseif ($newPhoto) {
      delete_upload('profile', $newPhoto);
    }
  }
}

$photoSrc = url(profile_photo_path($profile));
$hasOwnPhoto = $profile && valid_upload_name($profile['profile_photo'] ?? null);
$csrf = csrf_token();
admin_header('Profile', 'profile');
?>
<span class="num">PROFILE</span>
<h1 class="adm-title">PROFILE.</h1>
<p class="adm-script">The person behind the space.</p>
<p class="adm-note">This is the main identity of your website. The tagline, about text and photo appear on the public homepage.</p>

<?php if (!empty($errors['form'])): ?><p class="err err--form" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>

<form class="pform" method="post" action="profile.php" enctype="multipart/form-data" novalidate>
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

  <div class="pform__photo">
    <span class="num">PHOTO</span>
    <figure class="pform__frame">
      <img id="photo-preview" src="<?= $photoSrc ?>" alt="Profile photo preview" data-original="<?= $photoSrc ?>">
    </figure>
    <p class="pform__caption" id="photo-caption"><?= $hasOwnPhoto ? 'Current photo' : 'Default photo — upload your own to replace it' ?></p>
    <div class="field<?= fld_cls($errors, 'photo') ?>">
      <label for="photo">Upload or replace photo</label>
      <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" data-preview-img="photo-preview" data-max="<?= PHOTO_MAX_BYTES ?>" aria-describedby="photo-hint">
      <p class="count" id="photo-hint">JPG, PNG or WEBP · max 2 MB. Leave empty to keep the current photo.</p>
      <p class="err" id="photo-client-err" hidden></p>
      <?= fld_err($errors, 'photo') ?>
      <?= isset($errors['photo_note']) ? '<p class="err">' . e($errors['photo_note']) . '</p>' : '' ?>
    </div>
  </div>

  <div class="pform__fields">
    <div class="field<?= fld_cls($errors, 'full_name') ?>">
      <label for="full_name">Full name <span class="req">*</span></label>
      <input id="full_name" name="full_name" type="text" maxlength="100" value="<?= e($v['full_name']) ?>" required>
      <?= fld_err($errors, 'full_name') ?>
    </div>

    <div class="field<?= fld_cls($errors, 'nickname') ?>">
      <label for="nickname">Nickname <span class="opt">(optional)</span></label>
      <input id="nickname" name="nickname" type="text" maxlength="50" value="<?= e($v['nickname']) ?>">
      <?= fld_err($errors, 'nickname') ?>
    </div>

    <div class="field<?= fld_cls($errors, 'tagline') ?>">
      <label for="tagline">Tagline <span class="opt">(shown big on the homepage)</span></label>
      <input id="tagline" name="tagline" type="text" maxlength="150" value="<?= e($v['tagline']) ?>" placeholder="Life is made / of little things.">
      <p class="count">Type " / " (space, slash, space) where you want a line break.</p>
      <?= fld_err($errors, 'tagline') ?>
    </div>

    <div class="field<?= fld_cls($errors, 'about') ?>">
      <label for="about">About me</label>
      <textarea id="about" name="about" rows="8" maxlength="2000"><?= e($v['about']) ?></textarea>
      <p class="count" id="about-count" data-for="about" data-max="2000"><?= mb_strlen($v['about']) ?> / 2000</p>
      <?= fld_err($errors, 'about') ?>
    </div>

    <div class="pform__row">
      <div class="field<?= fld_cls($errors, 'birth_date') ?>">
        <label for="birth_date">Birth date <span class="opt">(private)</span></label>
        <input id="birth_date" name="birth_date" type="date" min="1900-01-01" max="<?= e(date('Y-m-d')) ?>" value="<?= e($v['birth_date']) ?>">
        <?= fld_err($errors, 'birth_date') ?>
      </div>
      <div class="field<?= fld_cls($errors, 'address') ?>">
        <label for="address">Address <span class="opt">(private)</span></label>
        <input id="address" name="address" type="text" maxlength="255" value="<?= e($v['address']) ?>">
        <?= fld_err($errors, 'address') ?>
      </div>
    </div>
    <p class="pform__hint">Birth date and address are stored for your records only and are not shown on the public site.</p>

    <div class="actions">
      <button class="adm-btn adm-btn--gold" type="submit">Save profile</button>
      <a class="adm-btn adm-btn--ghost" href="<?= url('index.php') ?>" target="_blank" rel="noopener">View site ↗</a>
    </div>
  </div>
</form>
<?php admin_footer();