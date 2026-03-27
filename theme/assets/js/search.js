/* =====================================================
   PORADNIK.PRO — Search UI + Autocomplete Engine
   Ref: docs/specs/SEARCH-UI-v1.md
   ===================================================== */
(function () {
  'use strict';

  var DEBOUNCE_MS = 300;
  var MIN_CHARS   = 2;
  var MAX_PER_SECTION = 5;

  /**
   * Debounce helper
   * @param {Function} fn
   * @param {number}   delay
   * @returns {Function}
   */
  function debounce(fn, delay) {
    var timer;
    return function () {
      clearTimeout(timer);
      var args = arguments;
      var ctx  = this;
      timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
    };
  }

  var ALLOWED_BADGE_CLASSES = {
    'premium_plus': 'badge-premium-plus',
    'premium':      'badge-premium',
    'verified':     'badge-verified',
    'ai':           'badge-ai',
    'expert':       'badge-expert',
    'user':         'badge-user',
  };

  /**
   * Validate URL — allow only http/https/relative paths.
   * @param {string} url
   * @returns {string} Safe URL or '#' if invalid.
   */
  function safeUrl(url) {
    if (!url) return '#';
    try {
      var parsed = new URL(url, window.location.origin);
      if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
        return parsed.href;
      }
      return '#';
    } catch (e) {
      // Relative path — allow if it starts with /
      return (typeof url === 'string' && /^\/[^/]/.test(url)) ? url : '#';
    }
  }

  /**
   * Build a result item element
   * @param {Object} item   — {title, url, meta?, badge?}
   * @returns {HTMLAnchorElement}
   */
  function buildItem(item) {
    var a = document.createElement('a');
    a.className  = 'search-result-item';
    a.href       = safeUrl(item.url);

    var titleSpan = document.createElement('span');
    titleSpan.className   = 'search-result-title';
    titleSpan.textContent = item.title || '';
    a.appendChild(titleSpan);

    if (item.badge && Object.prototype.hasOwnProperty.call(ALLOWED_BADGE_CLASSES, item.badge)) {
      var badgeSpan = document.createElement('span');
      badgeSpan.className   = 'badge ' + ALLOWED_BADGE_CLASSES[item.badge];
      badgeSpan.textContent = item.badge;
      a.appendChild(badgeSpan);
    }

    if (item.meta) {
      var metaSpan = document.createElement('span');
      metaSpan.className   = 'search-result-meta';
      metaSpan.textContent = item.meta;
      a.appendChild(metaSpan);
    }

    return a;
  }

  /**
   * Render autocomplete dropdown
   * @param {HTMLElement} dropdown
   * @param {Object}      data  — {questions, listings, articles}
   * @param {string}      query
   */
  function renderDropdown(dropdown, data, query) {
    dropdown.innerHTML = '';

    var hasResults = false;

    var sections = [
      { key: 'questions', label: 'Pytania',     badge: null },
      { key: 'listings',  label: 'Specjaliści', badge: 'premium' },
      { key: 'articles',  label: 'Poradniki',   badge: null },
    ];

    sections.forEach(function (section) {
      var items = Array.isArray(data[section.key]) ? data[section.key].slice(0, MAX_PER_SECTION) : [];
      if (!items.length) return;
      hasResults = true;

      var label = document.createElement('div');
      label.className   = 'search-section-label';
      label.textContent = section.label;
      dropdown.appendChild(label);

      items.forEach(function (item) {
        dropdown.appendChild(buildItem(item));
      });
    });

    if (!hasResults) {
      var empty = document.createElement('div');
      empty.className   = 'search-empty';
      empty.textContent = 'Nie znaleziono wyników dla "' + query + '"';
      dropdown.appendChild(empty);
    }

    dropdown.classList.add('open');
  }

  /**
   * Fetch search results from PearTree REST API
   * @param {string}   query
   * @param {Function} callback — fn(data)
   */
  function fetchResults(query, callback) {
    var base = (window.poradnikSearch && window.poradnikSearch.apiBase)
      ? window.poradnikSearch.apiBase
      : '/wp-json/peartree/search';

    var url = base + '?q=' + encodeURIComponent(query);

    fetch(url, { headers: { 'X-WP-Nonce': (window.poradnikSearch && window.poradnikSearch.nonce) || '' } })
      .then(function (res) {
        if (!res.ok) throw new Error('Search request failed: ' + res.status);
        return res.json();
      })
      .then(callback)
      .catch(function (err) {
        // eslint-disable-next-line no-console
        console.warn('[poradnik-search] Error:', err.message);
        callback({ questions: [], listings: [], articles: [] });
      });
  }

  /**
   * Attach autocomplete to a search wrapper element.
   * Expects: [data-search-wrap] > input[data-search-input] + [data-search-dropdown]
   * @param {HTMLElement} wrap
   */
  function initSearchWrap(wrap) {
    var input    = wrap.querySelector('[data-search-input]');
    var dropdown = wrap.querySelector('[data-search-dropdown]');
    if (!input || !dropdown) return;

    var doFetch = debounce(function (query) {
      fetchResults(query, function (data) {
        renderDropdown(dropdown, data, query);
      });
    }, DEBOUNCE_MS);

    input.addEventListener('input', function () {
      var q = input.value.trim();
      if (q.length < MIN_CHARS) {
        dropdown.classList.remove('open');
        dropdown.innerHTML = '';
        return;
      }
      doFetch(q);
    });

    /* Navigate with keyboard */
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        dropdown.classList.remove('open');
        return;
      }
      if (e.key === 'Enter' && dropdown.classList.contains('open')) {
        var first = dropdown.querySelector('.search-result-item');
        if (first) { window.location.href = first.href; }
      }
      if (e.key === 'ArrowDown') {
        var items = dropdown.querySelectorAll('.search-result-item');
        if (items.length) { items[0].focus(); }
      }
    });

    /* Close dropdown on outside click */
    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) {
        dropdown.classList.remove('open');
      }
    });
  }

  /* ── Hero search form submit guard ── */
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-hero-search-form]');
    if (!form) return;
    var input = form.querySelector('input[name="s"]');
    if (input && input.value.trim().length < MIN_CHARS) {
      event.preventDefault();
      input.focus();
    }
  });

  /* ── Init on DOM ready ── */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-search-wrap]').forEach(initSearchWrap);
  });
}());

