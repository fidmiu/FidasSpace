// Konfirmasi sebelum aksi berbahaya (form dengan atribut data-confirm)
document.addEventListener('submit', e => {
  const msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) e.preventDefault();
});

// Preview gambar sebelum disimpan (validasi klien hanya kemudahan; server tetap memvalidasi ulang)
document.querySelectorAll('input[type=file][data-preview-img]').forEach(input => {
  const img = document.getElementById(input.dataset.previewImg);
  const cap = document.getElementById('photo-caption');
  const msg = document.getElementById('photo-client-err');
  const ok = ['image/jpeg', 'image/png', 'image/webp'];
  let url = null;
  const reset = () => { if (url) { URL.revokeObjectURL(url); url = null; } img.src = img.dataset.original; };
  input.addEventListener('change', () => {
    if (msg) msg.hidden = true;
    const f = input.files[0];
    if (!f) { reset(); return; }
    const max = Number(input.dataset.max || 0);
    let bad = '';
    if (!ok.includes(f.type)) bad = 'Only JPG, PNG or WEBP images are allowed.';
    else if (max && f.size > max) bad = 'The image must be ' + (max / 1048576) + ' MB or smaller.';
    if (bad) { input.value = ''; reset(); if (msg) { msg.textContent = bad; msg.hidden = false; } return; }
    if (url) URL.revokeObjectURL(url);
    url = URL.createObjectURL(f);
    img.src = url;
    if (cap) cap.textContent = 'New photo preview — not saved yet';
  });
});

// Penghitung karakter
document.querySelectorAll('[data-for][data-max]').forEach(c => {
  const t = document.getElementById(c.dataset.for), max = Number(c.dataset.max);
  const upd = () => { c.textContent = t.value.length + ' / ' + max; c.classList.toggle('is-near', t.value.length > max * 0.9); };
  t.addEventListener('input', upd);
});