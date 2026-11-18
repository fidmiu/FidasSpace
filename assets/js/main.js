// Mobile menu
const toggle = document.querySelector('.header__toggle');
const menu = document.getElementById('menu');
toggle.addEventListener('click', () => {
  const open = toggle.getAttribute('aria-expanded') === 'true';
  toggle.setAttribute('aria-expanded', String(!open));
  menu.classList.toggle('is-open', !open);
});
menu.addEventListener('click', e => {
  if (e.target.closest('a')) {
    toggle.setAttribute('aria-expanded', 'false');
    menu.classList.remove('is-open');
  }
});

// Reveal saat masuk viewport (dilewati jika pengguna memilih reduced motion)
const items = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const io = new IntersectionObserver(entries => {
    entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
  }, { threshold: 0.15 });
  items.forEach(el => io.observe(el));
} else {
  items.forEach(el => el.classList.add('is-in'));
}