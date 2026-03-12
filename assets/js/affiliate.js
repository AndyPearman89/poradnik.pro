(function () {
    var buttons = document.querySelectorAll('.paa-affiliate-btn');
    if (!buttons.length) {
        return;
    }

    for (var i = 0; i < buttons.length; i++) {
        buttons[i].setAttribute('data-paa-ready', '1');
    }
})();
