(() => {
  const list = document.getElementById('wall-list');
  const state = document.getElementById('wall-state');
  const more = document.getElementById('wall-more');
  const count = document.getElementById('wall-count');
  if (!list || !state || !more) return;

  let offset = 0;
  let loading = false;

  const el = (tag, cls, text) => {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== undefined) n.textContent = text;   // textContent: isi pesan tidak pernah diperlakukan sebagai HTML
    return n;
  };

  const card = it => {
    const li = el('li', 'wall__item');
    const head = el('div', 'wall__head');
    head.append(el('b', 'wall__name', it.name), el('span', 'wall__date', it.date));
    li.append(head, el('p', 'wall__msg', it.message));
    if (it.reply) {
      const r = el('div', 'wall__reply');
      r.append(el('span', 'wall__reply-label', 'REPLY FROM FIDA'), el('p', '', it.reply));
      li.append(r);
    }
    return li;
  };

  const showState = (msg, isErr) => {
    state.textContent = msg;
    state.hidden = !msg;
    state.classList.toggle('wall__state--err', !!isErr);
  };

  const load = async () => {
    if (loading) return;
    loading = true;
    more.disabled = true;
    try {
      const res = await fetch('api/wall.php?offset=' + offset, { headers: { Accept: 'application/json' } });
      let data = null;
      try { data = await res.json(); } catch (_) {}
      if (!res.ok || !data || !data.ok) throw new Error('bad response');

      data.items.forEach(it => list.append(card(it)));
      offset += data.items.length;

      if (offset === 0) {
        showState('No notes on the wall yet. Be the first to leave one below.');
        list.hidden = true;
      } else {
        showState('');
        list.hidden = false;
      }
      count.textContent = data.total ? data.total + (data.total === 1 ? ' NOTE' : ' NOTES') : '';
      more.hidden = !data.has_more;
    } catch (_) {
      if (offset === 0) showState('The notes could not be loaded right now. Please try again later.', true);
      else { showState('Couldn’t load more notes. Try again.', true); more.hidden = false; }
    } finally {
      loading = false;
      more.disabled = false;
    }
  };

  more.addEventListener('click', load);
  load();
})();