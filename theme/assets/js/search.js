document.addEventListener('submit', (event) => {
  const form = event.target.closest('[data-hero-search-form]');
  if (!form) return;
  const input = form.querySelector('input[name="s"]');
  if (input && input.value.trim().length < 2) {
    event.preventDefault();
    input.focus();
  }
});
