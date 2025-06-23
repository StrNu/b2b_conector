// public/assets/js/components/autosearch.js
// Componente reutilizable de búsqueda automática para tablas

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-autosearch]').forEach(function(input) {
        input.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const tableId = this.getAttribute('data-autosearch');
            const searchField = this.getAttribute('data-search-field');
            const table = document.getElementById(tableId);
            
            if (!table) return;
            
            // Usar sistema de paginación si está disponible
            if (window.repaginateTable && typeof window.repaginateTable[tableId] === 'function') {
                window.repaginateTable[tableId](row => {
                    if (searchField) {
                        const cell = row.querySelector(`[data-search-field="${searchField}"]`);
                        if (cell) {
                            return cell.textContent.toLowerCase().includes(filter);
                        }
                        return false;
                    }
                    return row.textContent.toLowerCase().includes(filter);
                });
            } else {
                // Fallback: búsqueda simple sin paginación
                const tbody = table.querySelector('tbody');
                if (tbody) {
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach(row => {
                        let match = true;
                        if (filter) {
                            if (searchField) {
                                const cell = row.querySelector(`[data-search-field="${searchField}"]`);
                                match = cell && cell.textContent.toLowerCase().includes(filter);
                            } else {
                                match = row.textContent.toLowerCase().includes(filter);
                            }
                        }
                        row.style.display = match ? '' : 'none';
                    });
                }
            }
        });
    });
});
