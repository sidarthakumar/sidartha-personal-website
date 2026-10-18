(() => {
  const form = document.getElementById('project-filters');
  if (!form) return;
  form.hidden = false;
  const cards = [...document.querySelectorAll('[data-project]')];
  const input = document.getElementById('project-q');
  const status = document.getElementById('project-status');
  const technology = document.getElementById('project-tech');
  const update = () => {
    const query = input.value.trim().toLocaleLowerCase();
    let count = 0;
    for (const card of cards) {
      const matches =
        (card.dataset.title.includes(query) ||
          card.dataset.technologies.toLocaleLowerCase().includes(query)) &&
        (!status.value || status.value === card.dataset.status) &&
        (!technology.value ||
          card.dataset.technologies.split('|').includes(technology.value));
      card.hidden = !matches;
      if (matches) count++;
    }
    document.getElementById('project-count').textContent =
      `${count} ${count === 1 ? 'project' : 'projects'}`;
    document.getElementById('project-empty').hidden = count > 0;
  };
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    update();
  });
  form.addEventListener('input', update);
  form.addEventListener('change', update);
  form.addEventListener('reset', () => setTimeout(update, 0));
})();
