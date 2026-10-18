(() => {
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.getElementById('primary-navigation');
  if (!toggle || !nav) return;
  const compact = matchMedia('(max-width: 800px)');
  const setOpen = (open) => {
    toggle.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('menu-closed', compact.matches && !open);
  };
  const sync = () => {
    toggle.hidden = !compact.matches;
    setOpen(false);
  };
  sync();
  compact.addEventListener('change', sync);
  toggle.addEventListener('click', () =>
    setOpen(toggle.getAttribute('aria-expanded') !== 'true'),
  );
  document.addEventListener('keydown', (event) => {
    if (
      event.key === 'Escape' &&
      toggle.getAttribute('aria-expanded') === 'true'
    ) {
      setOpen(false);
      toggle.focus();
    }
  });
  document
    .querySelector('[data-back-to-top]')
    ?.addEventListener('click', (event) => {
      event.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: matchMedia('(prefers-reduced-motion: reduce)').matches
          ? 'instant'
          : 'smooth',
      });
      document.querySelector('.brand')?.focus({ preventScroll: true });
    });
})();
