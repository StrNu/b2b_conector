// public/assets/js/components/autosearch.js
// Componente reutilizable de búsqueda automática para tablas

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== AUTOSEARCH DEBUG: Starting initialization ===');
    
    // Marcar como inicializado para evitar conflictos con matches.js
    window.autoSearchInitialized = true;
    console.log('AUTOSEARCH DEBUG: Set window.autoSearchInitialized = true');
    
    const autosearchInputs = document.querySelectorAll('[data-autosearch]');
    console.log('AUTOSEARCH DEBUG: Found inputs with data-autosearch:', autosearchInputs.length);
    
    autosearchInputs.forEach(function(input, index) {
        console.log(`AUTOSEARCH DEBUG: Processing input ${index}:`, input);
        console.log(`AUTOSEARCH DEBUG: Input ID:`, input.id);
        console.log(`AUTOSEARCH DEBUG: Input data-autosearch:`, input.getAttribute('data-autosearch'));
        input.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const tableId = this.getAttribute('data-autosearch');
            const searchField = this.getAttribute('data-search-field');
            console.log(`AUTOSEARCH DEBUG: Input event - filter: "${filter}", tableId: "${tableId}"`);
            
            const table = document.getElementById(tableId);
            console.log('AUTOSEARCH DEBUG: Target table found:', table);
            
            if (!table) {
                console.warn('AUTOSEARCH DEBUG: ❌ Table not found for ID:', tableId);
                return;
            }
            
            // Usar sistema de paginación si está disponible
            console.log('AUTOSEARCH DEBUG: Checking repaginateTable...', window.repaginateTable);
            console.log('AUTOSEARCH DEBUG: repaginateTable for tableId:', typeof window.repaginateTable?.[tableId]);
            
            if (window.repaginateTable && typeof window.repaginateTable[tableId] === 'function') {
                console.log('AUTOSEARCH DEBUG: ✅ Using repaginateTable function');
                window.repaginateTable[tableId](row => {
                    if (searchField) {
                        const cell = row.querySelector(`[data-search-field="${searchField}"]`);
                        if (cell) {
                            const match = cell.textContent.toLowerCase().includes(filter);
                            console.log('AUTOSEARCH DEBUG: Field search match:', match);
                            return match;
                        }
                        return false;
                    }
                    const match = row.textContent.toLowerCase().includes(filter);
                    console.log('AUTOSEARCH DEBUG: Full text match:', match);
                    return match;
                });
            } else {
                console.log('AUTOSEARCH DEBUG: ⚠️ Using fallback search method');
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
