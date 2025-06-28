// public/assets/js/modules/matches.js
// Módulo específico para la gestión de matches - VERSION SIMPLIFICADA

(function() {
    'use strict';
    
    // Configuración de paginación
    const PAGINATION_CONFIG = {
        'direct-matches-table': { 
            containerId: 'direct-matches-pagination', 
            rowsPerPage: 10 
        },
        'potential-matches-table': { 
            containerId: 'potential-matches-pagination', 
            rowsPerPage: 10 
        },
        'no-match-companies-table': { 
            containerId: 'no-match-companies-pagination', 
            rowsPerPage: 10 
        }
    };
    
    // Búsqueda automática solo para tablas de matches (evita conflicto con autosearch.js)
    function initMatchesAutosearch() {
        // Solo manejar los inputs específicos de matches si autosearch.js no está presente
        if (typeof window.autoSearchInitialized === 'undefined') {
            document.querySelectorAll('[data-autosearch]').forEach(input => {
                const tableId = input.getAttribute('data-autosearch');
                
                // Solo manejar tablas de matches
                if (!tableId.includes('matches-table') && !tableId.includes('companies-table')) return;
                
                // Aplicar debounce para mejor rendimiento
                let searchTimeout = null;
                
                input.addEventListener('input', function() {
                    const filter = this.value.toLowerCase().trim();
                    
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        performSearch(tableId, filter);
                    }, 300); // 300ms de debounce
                });
                
                // Limpiar búsqueda con Escape
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                        performSearch(tableId, '');
                    }
                });
            });
        }
    }
    
    // Realizar búsqueda en tabla específica
    function performSearch(tableId, filter) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        // Usar sistema de paginación si está disponible
        if (window.repaginateTable && typeof window.repaginateTable[tableId] === 'function') {
            window.repaginateTable[tableId](row => {
                if (!filter) return true;
                return row.textContent.toLowerCase().includes(filter);
            });
        } else {
            // Fallback: búsqueda simple
            const tbody = table.querySelector('tbody');
            if (tbody) {
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const match = !filter || row.textContent.toLowerCase().includes(filter);
                    row.style.display = match ? '' : 'none';
                });
            }
        }
        
        // Actualizar contador de resultados
        updateSearchCounter(tableId, filter);
    }
    
    // Actualizar contador de resultados de búsqueda
    function updateSearchCounter(tableId, filter) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const visibleRows = Array.from(tbody.querySelectorAll('tr')).filter(row => 
            row.style.display !== 'none' && !row.classList.contains('no-data-row')
        );
        
        const totalRows = Array.from(tbody.querySelectorAll('tr')).filter(row => 
            !row.classList.contains('no-data-row')
        );
        
        // Buscar o crear elemento de contador
        let counter = document.getElementById(`search-counter-${tableId}`);
        if (!counter) {
            counter = document.createElement('div');
            counter.id = `search-counter-${tableId}`;
            counter.className = 'search-counter text-sm text-gray-500 mt-2';
            
            const container = table.closest('.table-container');
            if (container) {
                container.appendChild(counter);
            }
        }
        
        if (filter) {
            counter.textContent = `Mostrando ${visibleRows.length} de ${totalRows.length} resultados`;
            counter.style.display = 'block';
        } else {
            counter.style.display = 'none';
        }
    }
    
    // Inicializar paginación para tabla específica
    function initPaginationForTable(tableId) {
        const config = PAGINATION_CONFIG[tableId];
        if (!config) return;
        
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody || tbody.children.length === 0) return;
        
        // Verificar que la función pagination esté disponible
        if (typeof window.pagination === 'function') {
            window.pagination(config.containerId, tableId, config.rowsPerPage);
            console.log(`Pagination initialized for ${tableId}`);
        } else {
            console.warn('Pagination function not available');
        }
    }
    
    // Aplicar filtro por razón de match
    function applyReasonFilter(selectedReason) {
        const table = document.getElementById('potential-matches-table');
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            if (!selectedReason) {
                row.style.display = '';
                return;
            }
            
            // Buscar la celda con la razón (generalmente la columna 5)
            const reasonCell = row.children[4]; // 0-indexed, 5ta columna
            if (reasonCell) {
                const match = reasonCell.textContent.trim() === selectedReason;
                row.style.display = match ? '' : 'none';
            }
        });
        
        // Re-aplicar paginación si está activa
        if (window.repaginateTable && typeof window.repaginateTable['potential-matches-table'] === 'function') {
            window.repaginateTable['potential-matches-table'](row => {
                if (!selectedReason) return true;
                const reasonCell = row.children[4];
                return reasonCell && reasonCell.textContent.trim() === selectedReason;
            });
        }
    }
    
    // Función para reinicializar paginación después de cargar datos
    function reinitializePaginationForTable(tableId) {
        setTimeout(() => {
            initPaginationForTable(tableId);
        }, 200);
    }
    
    // Función de inicialización principal
    function initMatches() {
        console.log('Matches module: Initializing search and pagination only...');
        
        // Solo inicializar búsqueda automática si es necesario
        initMatchesAutosearch();
        
        // Configurar filtro de razones si existe
        const filterMatchReason = document.getElementById('filter-match-reason');
        if (filterMatchReason) {
            filterMatchReason.addEventListener('change', function() {
                applyReasonFilter(this.value);
            });
        }
        
        console.log('Matches module: Search and pagination initialized');
    }
    
    // Exponer funciones públicas
    window.MatchesModule = {
        init: initMatches,
        reinitializePagination: reinitializePaginationForTable,
        performSearch: performSearch,
        applyReasonFilter: applyReasonFilter
    };
    
    // Auto-inicializar solo las funciones de búsqueda y paginación
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMatches);
    } else {
        initMatches();
    }
    
})();