// Componente JS para tabs reutilizable
// Uso: requiere .tab-btn y .tab-panel en el HTML
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var tabBtns = document.querySelectorAll('.tab-btn');
        var tabPanels = document.querySelectorAll('.tab-panel');
        tabBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                tabBtns.forEach(function(b) { b.classList.remove('active'); });
                tabPanels.forEach(function(p) { p.classList.add('hidden'); });
                btn.classList.add('active');
                var tabId = btn.getAttribute('data-tab');
                var panel = document.getElementById(tabId);
                if (panel) panel.classList.remove('hidden');
            });
        });
    });
})();
