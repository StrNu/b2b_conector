// public/assets/js/components/autosearch.js
// Componente reutilizable de búsqueda automática para tablas

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-autosearch]').forEach(function(input) {
        input.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const tableId = this.getAttribute('data-autosearch');
            // Usar solo el sistema de filtro de la paginación
            if (window.repaginateTable && typeof window.repaginateTable[tableId] === 'function') {
                const searchField = this.getAttribute('data-search-field');
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
            }
        });
    });
});
