(() => {
  const form = document.getElementById('login-form');
  const btn = document.getElementById('login-btn');
  const err = document.getElementById('client-err');
  const pw = document.getElementById('password');
  const toggle = document.getElementById('pw-toggle');

  // Show / hide password
  toggle.addEventListener('click', () => {
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    toggle.textContent = show ? 'HIDE' : 'SHOW';
    toggle.setAttribute('aria-pressed', String(show));
    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });

  form.addEventListener('submit', ev => {
    err.hidden = true;
    const id = form.identifier.value.trim();
    if (!id || !pw.value) {
      ev.preventDefault();
      err.textContent = 'Please fill in both fields.';
      err.hidden = false;
      (id ? pw : form.identifier).focus();
      return;
    }
    btn.classList.add('is-loading');
    btn.setAttribute('aria-busy', 'true');
  });

  // Tombol Back dari bfcache: pastikan tombol tidak tertinggal dalam kondisi loading
  window.addEventListener('pageshow', () => { btn.classList.remove('is-loading'); btn.removeAttribute('aria-busy'); });
})();