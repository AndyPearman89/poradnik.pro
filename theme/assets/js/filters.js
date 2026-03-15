document.addEventListener('change', (event) => {
  const filter = event.target.closest('[data-filter]');
  if (!filter) return;
  document.documentElement.setAttribute('data-filter-value', filter.value);
});
