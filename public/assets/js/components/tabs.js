// Componente JS para tabs reutilizable y accesible
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var tabBtns = document.querySelectorAll('.tab-btn');
        var tabPanels = document.querySelectorAll('.tab-panel');
        tabBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                tabBtns.forEach(function(b) {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });
                tabPanels.forEach(function(p) {
                    p.classList.remove('active');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-selected', 'true');
                var tabId = btn.getAttribute('data-tab');
                var panel = document.getElementById(tabId);
                if (panel) panel.classList.add('active');
            });
        });
    });
})();
