window.PoradnikAjax = {
  search(query = '') {
    const url = `${poradnikAjax.restUrl}search?query=${encodeURIComponent(query)}`;
    return fetch(url, { credentials: 'same-origin' }).then((res) => res.json());
  }
};
