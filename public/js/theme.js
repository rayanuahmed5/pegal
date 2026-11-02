// the dark/light switch in the top bar. The first choice is made by a tiny script in the
// layout's head (so the page never flashes), this file only handles the clicks.
(function () {
    var button = document.getElementById('theme-toggle');
    if (!button) return;

    var root = document.documentElement;

    function label() {
        var dark = root.getAttribute('data-theme') === 'dark';
        // the label says what a click will do
        button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
        button.setAttribute('aria-pressed', dark ? 'true' : 'false');
    }

    button.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        // storage can be blocked (private window), the switch still works for this page
        try { localStorage.setItem('pegel-theme', next); } catch (e) {}
        label();
    });

    label();
})();
