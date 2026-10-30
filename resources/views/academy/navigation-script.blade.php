<script>
(function () {
    function setOpen(id, open) {
        var panel = document.getElementById(id);
        if (!panel) return;
        panel.classList.toggle('hidden', !open);
        panel.classList.toggle('block', open);
        document.querySelectorAll('[data-academy-menu-toggle]').forEach(function (button) {
            if (button.getAttribute('aria-controls') === id) button.setAttribute('aria-expanded', String(open));
        });
    }
    window.academyToggleNavigation = function (id) {
        var panel = document.getElementById(id);
        if (panel) setOpen(id, panel.classList.contains('hidden'));
    };
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setOpen('res_sidebar', false);
    });
    document.addEventListener('click', function (event) {
        if (event.target.id === 'res_sidebar') setOpen('res_sidebar', false);
    });
})();
</script>
