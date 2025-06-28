// public/assets/js/components/pagination.js
// Componente JS reutilizable para paginación de tablas
// Uso: pagination(containerId, tableId, rowsPerPage)

function pagination(containerId, tableId, rowsPerPage = 10) {
    console.log('=== PAGINATION DEBUG: Starting initialization ===');
    console.log('PAGINATION DEBUG: Parameters:', {containerId, tableId, rowsPerPage});
    
    const container = document.getElementById(containerId);
    const table = document.getElementById(tableId);
    console.log('PAGINATION DEBUG: Container found:', container);
    console.log('PAGINATION DEBUG: Table found:', table);
    
    if (!container || !table) {
        console.warn('PAGINATION DEBUG: ❌ Missing container or table');
        console.log('PAGINATION DEBUG: Container exists:', !!container);
        console.log('PAGINATION DEBUG: Table exists:', !!table);
        return;
    }
    
    const tbody = table.querySelector('tbody');
    console.log('PAGINATION DEBUG: Tbody found:', tbody);
    
    if (!tbody) {
        console.warn('PAGINATION DEBUG: ❌ No tbody found in table');
        return;
    }
    let rows = Array.from(tbody.querySelectorAll('tr'));
    let currentPage = 1;
    let filteredRows = rows.slice(); // Arreglo base para paginación
    
    console.log('PAGINATION DEBUG: Total rows found:', rows.length);
    console.log('PAGINATION DEBUG: Initial filtered rows:', filteredRows.length);

    function setFilter(filterFn) {
        filteredRows = rows.filter(filterFn);
        currentPage = 1;
        renderPage(currentPage);
    }

    function renderPage(page) {
        console.log('PAGINATION DEBUG: renderPage called with page:', page);
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
        console.log('PAGINATION DEBUG: totalPages calculated:', totalPages);
        
        if (page > totalPages) page = totalPages;
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        console.log('PAGINATION DEBUG: Showing rows from', start, 'to', end);
        filteredRows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });
        rows.forEach(row => {
            if (!filteredRows.includes(row)) row.style.display = 'none';
        });
        renderControls(page, totalPages);
    }

    function renderControls(page, totalPages) {
        console.log('PAGINATION DEBUG: renderControls called with page:', page, 'totalPages:', totalPages);
        
        let html = '<nav class="pagination-nav">';
        if (page > 1) {
            html += `<button class="pagination-btn" data-page="${page - 1}">&laquo;</button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="pagination-btn${i === page ? ' active' : ''}" data-page="${i}">${i}</button>`;
        }
        if (page < totalPages) {
            html += `<button class="pagination-btn" data-page="${page + 1}">&raquo;</button>`;
        }
        html += '</nav>';
        
        console.log('PAGINATION DEBUG: Generated HTML:', html);
        container.innerHTML = html;
        console.log('PAGINATION DEBUG: ✅ Controls rendered in container');
        container.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.onclick = function() {
                renderPage(Number(this.getAttribute('data-page')));
            };
        });
    }

    // Permite reinicializar la paginación desde fuera (por ejemplo, tras un filtro)
    window.repaginateTable = window.repaginateTable || {};
    window.repaginateTable[tableId] = function(filterFn) {
        console.log('PAGINATION DEBUG: repaginateTable called for', tableId);
        console.log('PAGINATION DEBUG: Filter function provided:', typeof filterFn);
        
        rows = Array.from(tbody.querySelectorAll('tr'));
        filteredRows = filterFn ? rows.filter(filterFn) : rows.slice();
        currentPage = 1;
        
        console.log('PAGINATION DEBUG: After filter - rows:', rows.length, 'filtered:', filteredRows.length);
        renderPage(currentPage);
    };
    
    console.log('PAGINATION DEBUG: ✅ repaginateTable function registered for', tableId);

    // Inicializar
    console.log('PAGINATION DEBUG: ✅ Calling initial renderPage');
    renderPage(currentPage);
    console.log('PAGINATION DEBUG: ✅ Pagination initialization complete for', tableId);

    // Exponer setFilter si se quiere usar desde fuera
    window.setPaginationFilter = window.setPaginationFilter || {};
    window.setPaginationFilter[tableId] = setFilter;
}
