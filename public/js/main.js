document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert').forEach(function (alert) {
        function close() {
            alert.classList.add('hide');
            setTimeout(function () { alert.remove(); }, 400);
        }
        var button = alert.querySelector('.alert-close');
        if (button) button.addEventListener('click', close);
        setTimeout(close, 4000);
    });

    var themeToggle = document.getElementById('themeToggle');
    function updateThemeButton() {
        if (!themeToggle) return;
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        var icon = themeToggle.querySelector('i');
        if (icon) icon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        themeToggle.title = dark ? 'Chuy\u1EC3n sang giao di\u1EC7n s\u00E1ng' : 'Chuy\u1EC3n sang giao di\u1EC7n t\u1ED1i';
    }
    if (themeToggle) {
        updateThemeButton();
        themeToggle.addEventListener('click', function () {
            var dark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (dark) document.documentElement.removeAttribute('data-theme');
            else document.documentElement.setAttribute('data-theme', 'dark');
            try { localStorage.setItem('theme', dark ? 'light' : 'dark'); } catch (e) {}
            updateThemeButton();
        });
    }

    var sidebar = document.getElementById('sidebar');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    function setSidebar(open) {
        if (!sidebar) return;
        sidebar.classList.toggle('open', open);
        if (backdrop) backdrop.classList.toggle('show', open);
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', String(open));
    }
    if (sidebarToggle) sidebarToggle.addEventListener('click', function () { setSidebar(!sidebar.classList.contains('open')); });
    if (backdrop) backdrop.addEventListener('click', function () { setSidebar(false); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') setSidebar(false); });

    var tabs = document.querySelectorAll('.profile-tab');
    var panels = document.querySelectorAll('.tab-panel');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-tab');
            tabs.forEach(function (item) { item.classList.toggle('active', item === tab); });
            panels.forEach(function (panel) { panel.classList.toggle('active', panel.getAttribute('data-panel') === target); });
        });
    });

    var quickAdd = document.querySelector('.quick-add-form');
    if (quickAdd) quickAdd.addEventListener('submit', function (event) {
        var input = quickAdd.querySelector('.quick-add-input');
        if (input && input.value.trim() === '') { event.preventDefault(); input.focus(); }
    });
});
