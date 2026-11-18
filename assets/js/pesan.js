(() => {
  const form = document.getElementById('msg-form');
  const success = document.getElementById('success');
  const btn = document.getElementById('send-btn');
  const formErr = document.getElementById('form-err');
  const f = {
    name: document.getElementById('name'),
    email: document.getElementById('email'),
    message: document.getElementById('message'),
  };
  const countEl = document.getElementById('count-num');
  const counter = document.getElementById('message-count');
  const MAX = 1000;
  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  let sending = false;

  const setError = (key, msg) => {
    const wrap = f[key].closest('.field');
    document.getElementById(key + '-err').textContent = msg || '';
    wrap.classList.toggle('is-invalid', !!msg);
    f[key].setAttribute('aria-invalid', msg ? 'true' : 'false');
  };

  const validate = () => {
    const v = { name: f.name.value.trim(), email: f.email.value.trim(), message: f.message.value.trim() };
    const e = {};
    if (!v.name) e.name = 'Please tell me what to call you.';
    else if (v.name.length > 100) e.name = 'Name is too long (max 100 characters).';
    if (v.email && (!EMAIL_RE.test(v.email) || v.email.length > 100)) e.email = 'That email doesn’t look right. Check it, or leave it empty.';
    if (!v.message) e.message = 'Your message is empty. Write a few words first.';
    else if (v.message.length > MAX) e.message = `Message is too long (max ${MAX} characters).`;
    ['name', 'email', 'message'].forEach(k => setError(k, e[k]));
    return { ok: !Object.keys(e).length, v, e };
  };

  // Real-time counter
  const updateCount = () => {
    const n = f.message.value.length;
    countEl.textContent = n;
    counter.classList.toggle('is-near', n >= MAX * 0.9);
  };
  f.message.addEventListener('input', updateCount);
  updateCount();

  // Hapus error saat pengguna mulai memperbaiki
  Object.keys(f).forEach(k => f[k].addEventListener('input', () => { if (f[k].closest('.field').classList.contains('is-invalid')) setError(k, ''); }));

  const setLoading = on => {
    sending = on;
    btn.classList.toggle('is-loading', on);
    btn.disabled = on;
    btn.setAttribute('aria-busy', String(on));
  };

  form.addEventListener('submit', async ev => {
    ev.preventDefault();
    if (sending) return;                 // cegah double submit
    formErr.textContent = '';
    const { ok, v, e } = validate();
    if (!ok) { f[Object.keys(e)[0]].focus(); return; }

    setLoading(true);
    try {
      const body = new FormData(form);
      body.set('name', v.name); body.set('email', v.email); body.set('message', v.message);
      const res = await fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json' } });
      let data = null;
      try { data = await res.json(); } catch (_) {}

      if (res.ok && data && data.ok) {
        form.hidden = true;
        success.hidden = false;
        success.focus();
        return;
      }
      if (data && data.errors) {           // error validasi dari server
        Object.entries(data.errors).forEach(([k, m]) => f[k] && setError(k, m));
      }
      formErr.textContent = (data && data.message) || 'Your message could not be sent. Please try again in a moment.';
    } catch (_) {
      formErr.textContent = 'Couldn’t reach the server. Check your connection and try again. Your message is still here.';
    } finally {
      setLoading(false);                 // isi form tidak dihapus saat gagal
    }
  });

  document.getElementById('again-btn').addEventListener('click', () => {
    form.reset(); updateCount();
    ['name', 'email', 'message'].forEach(k => setError(k, ''));
    success.hidden = true; form.hidden = false; f.name.focus();
  });
})();