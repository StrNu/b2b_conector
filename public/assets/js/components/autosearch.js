// public/assets/js/components/autosearch.js
// Componente reutilizable de búsqueda automática para tablas

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-autosearch]').forEach(function(input) {
        input.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const tableId = this.getAttribute('data-autosearch');
            const table = document.getElementById(tableId);
            if (!table) return;
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                // Si hay un campo específico, busca solo en ese campo
                const searchField = this.getAttribute('data-search-field');
                if (searchField) {
                    const cell = row.querySelector(`[data-search-field="${searchField}"]`);
                    if (cell) {
                        const text = cell.textContent.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                        return;
                    }
                }
                // Si no, busca en todo el texto de la fila
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });
});
