document.addEventListener('DOMContentLoaded', () => {
  const stickySearch = document.querySelector('[data-sticky-search]');
  if (!stickySearch) return;
  const onScroll = () => {
    stickySearch.classList.toggle('is-active', window.scrollY > 80);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});
