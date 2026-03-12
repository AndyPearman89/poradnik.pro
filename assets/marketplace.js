(function () {
  document.addEventListener('click', function (event) {
    var link = event.target.closest('.ppam-slot a');
    if (!link) {
      return;
    }
    link.classList.add('is-clicked');
  });
})();
