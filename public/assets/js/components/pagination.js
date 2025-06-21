// public/assets/js/components/pagination.js
// Componente JS reutilizable para paginación de tablas
// Uso: pagination(containerId, tableId, rowsPerPage)

function pagination(containerId, tableId, rowsPerPage = 10) {
    const container = document.getElementById(containerId);
    const table = document.getElementById(tableId);
    if (!container || !table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    let rows = Array.from(tbody.querySelectorAll('tr'));
    let currentPage = 1;
    let filteredRows = rows.slice(); // Arreglo base para paginación

    function setFilter(filterFn) {
        filteredRows = rows.filter(filterFn);
        currentPage = 1;
        renderPage(currentPage);
    }

    function renderPage(page) {
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
        if (page > totalPages) page = totalPages;
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        filteredRows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });
        rows.forEach(row => {
            if (!filteredRows.includes(row)) row.style.display = 'none';
        });
        renderControls(page, totalPages);
    }

    function renderControls(page, totalPages) {
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
        container.innerHTML = html;
        container.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.onclick = function() {
                renderPage(Number(this.getAttribute('data-page')));
            };
        });
    }

    // Permite reinicializar la paginación desde fuera (por ejemplo, tras un filtro)
    window.repaginateTable = window.repaginateTable || {};
    window.repaginateTable[tableId] = function(filterFn) {
        rows = Array.from(tbody.querySelectorAll('tr'));
        filteredRows = filterFn ? rows.filter(filterFn) : rows.slice();
        currentPage = 1;
        renderPage(currentPage);
    };

    // Inicializar
    renderPage(currentPage);

    // Exponer setFilter si se quiere usar desde fuera
    window.setPaginationFilter = window.setPaginationFilter || {};
    window.setPaginationFilter[tableId] = setFilter;
}
