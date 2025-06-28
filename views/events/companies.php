<?php 
// Vista de companies con Material Design 3
if (file_exists(CONFIG_DIR . '/material-config.php')) {
    require_once CONFIG_DIR . '/material-config.php';
}

// Fallback functions if Material Design helpers are not available
if (!function_exists('materialButton')) {
    function materialButton($text, $variant = 'filled', $icon = '', $attributes = '', $size = '') {
        $class = 'btn btn-primary';
        if ($variant === 'outlined') $class = 'btn btn-secondary';
        if ($variant === 'tonal') $class = 'btn btn-info';
        if ($size === 'small') $class .= ' btn-sm';
        return '<button class="' . $class . '" ' . $attributes . '>' . $text . '</button>';
    }
}

if (!function_exists('materialCard')) {
    function materialCard($title, $content, $variant = 'elevated', $actions = '') {
        return '<div class="card">
                    <div class="card-header"><h5>' . $title . '</h5></div>
                    <div class="card-body">' . $content . '</div>
                    ' . ($actions ? '<div class="card-footer">' . $actions . '</div>' : '') . '
                </div>';
    }
}

if (!function_exists('displayFlashMessages')) {
    function displayFlashMessages() {
        include(VIEW_DIR . '/shared/notifications.php');
    }
}

if (!function_exists('isEventUserAuthenticated')) {
    function isEventUserAuthenticated() {
        return false;
    }
}

$pageTitle = 'Empresas Registradas - ' . (isset($event) ? $event->getEventName() : 'Evento');
$moduleCSS = 'events';
$moduleJS = 'events';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => isEventUserAuthenticated() ? BASE_URL . '/event-dashboard' : BASE_URL . '/dashboard'],
    ['title' => 'Evento', 'url' => BASE_URL . '/events/view/' . $eventId],
    ['title' => 'Empresas']
];
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Empresas Registradas</h1>
            <p class="page-subtitle">Gestiona las empresas participantes en el evento</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Evento',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . (int)$eventId . '\'"'
            ) ?>
            <?= materialButton(
                '<i class="fas fa-plus"></i> Nueva Empresa',
                'filled',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/companies/' . (int)$eventId . '/create-company\'"'
            ) ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Search and Filter Section -->
    <div class="search-section">
        <?php
        ob_start();
        ?>
            <div class="search-controls">
                <div class="search-field">
                    <div class="textfield-material">
                        <input type="text" id="searchCompany" class="textfield-material__input"
                               placeholder=" " autocomplete="off"
                               data-autosearch="companiesTable" data-search-field="company_name">
                        <label class="textfield-material__label">Buscar por nombre de empresa...</label>
                    </div>
                </div>
                <div class="filter-field">
                    <div class="textfield-material">
                        <select id="roleFilter" class="textfield-material__input" data-role-filter>
                            <option value="">Todos los roles</option>
                            <option value="buyer" <?= (($_GET['role'] ?? '') === 'buyer') ? 'selected' : '' ?>>Comprador</option>
                            <option value="supplier" <?= (($_GET['role'] ?? '') === 'supplier') ? 'selected' : '' ?>>Proveedor</option>
                        </select>
                        <label class="textfield-material__label">Filtrar por rol</label>
                    </div>
                </div>
            </div>
        <?php
        $searchContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-search"></i> Buscar y Filtrar',
            $searchContent,
            'outlined'
        );
        ?>
    </div>

    <!-- Companies Table Section -->
    <div class="companies-section">
        <?php if (!empty($companies)): ?>
            <?php
            ob_start();
            ?>
                <div class="table-responsive">
                    <table id="companiesTable" class="table-material">
                        <thead class="table-material__header">
                            <tr>
                                <th class="table-material__cell table-material__cell--header">Logo</th>
                                <th class="table-material__cell table-material__cell--header">Empresa</th>
                                <th class="table-material__cell table-material__cell--header">Contacto</th>
                                <th class="table-material__cell table-material__cell--header">Email</th>
                                <th class="table-material__cell table-material__cell--header">Teléfono</th>
                                <th class="table-material__cell table-material__cell--header">Rol</th>
                                <th class="table-material__cell table-material__cell--header">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                                <tr class="table-material__row" data-role="<?= $company['role'] ?>">
                                    <td class="table-material__cell">
                                        <div class="company-logo">
                                            <?php if (!empty($company['company_logo'])): ?>
                                                <img src="<?= BASE_PUBLIC_URL ?>/uploads/logos/<?= htmlspecialchars($company['company_logo']) ?>" 
                                                     alt="Logo de <?= htmlspecialchars($company['company_name']) ?>" 
                                                     class="company-logo__image">
                                            <?php else: ?>
                                                <div class="company-logo__placeholder">
                                                    <i class="fas fa-building"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell" data-search-field="company_name">
                                        <div class="company-info">
                                            <div class="company-info__name"><?= htmlspecialchars($company['company_name']) ?></div>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="contact-info">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($company['contact_first_name'] . ' ' . $company['contact_last_name']) ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="contact-info">
                                            <i class="fas fa-envelope"></i>
                                            <?= htmlspecialchars($company['email']) ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="contact-info">
                                            <i class="fas fa-phone"></i>
                                            <?= htmlspecialchars($company['phone'] ?? 'N/A') ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="role-badge role-badge--<?= $company['role'] ?>">
                                            <i class="fas fa-<?= $company['role'] === 'buyer' ? 'shopping-cart' : 'truck' ?>"></i>
                                            <?= $company['role'] === 'buyer' ? 'Comprador' : 'Proveedor' ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="action-buttons">
                                            <?= materialButton(
                                                '<i class="fas fa-eye"></i>',
                                                'tonal',
                                                '',
                                                'onclick="window.location.href=\'' . BASE_URL . '/companies/view/' . (int)$company['company_id'] . '\'" title="Ver empresa"',
                                                'small'
                                            ) ?>
                                            <?= materialButton(
                                                '<i class="fas fa-edit"></i>',
                                                'tonal',
                                                '',
                                                'onclick="window.location.href=\'' . BASE_URL . '/companies/edit/' . (int)$company['company_id'] . '\'" title="Editar empresa"',
                                                'small'
                                            ) ?>
                                            <form action="<?= BASE_URL ?>/companies/delete/<?= (int)$company['company_id'] ?>" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Esta acción eliminará la empresa y todos los matches ligados a ella. ¿Está seguro de continuar?');" 
                                                  style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                                <?= materialButton(
                                                    '<i class="fas fa-trash"></i>',
                                                    'outlined',
                                                    '',
                                                    'type="submit" title="Eliminar empresa"',
                                                    'small'
                                                ) ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div id="companiesPagination" class="pagination-container"></div>
            <?php
            $tableContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-building"></i> Lista de Empresas (' . count($companies) . ')',
                $tableContent,
                'elevated'
            );
            ?>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="empty-state-container">
                <?= materialCard(
                    '',
                    '
                    <div class="empty-state">
                        <div class="empty-state__icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3 class="empty-state__title">No hay empresas registradas</h3>
                        <p class="empty-state__subtitle">
                            No se encontraron empresas registradas para este evento.
                        </p>
                        <div class="empty-state__actions">
                            ' . materialButton(
                                '<i class="fas fa-plus"></i> Agregar Primera Empresa',
                                'filled',
                                '',
                                'onclick="window.location.href=\'' . BASE_URL . '/events/companies/' . (int)$eventId . '/create-company\'"'
                            ) . '
                        </div>
                    </div>',
                    'outlined'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('companiesTable');
    const searchInput = document.getElementById('searchCompany');
    const roleFilter = document.getElementById('roleFilter');
    const paginationContainer = document.getElementById('companiesPagination');
    
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    let allRows = Array.from(tbody.querySelectorAll('tr'));
    let filteredRows = allRows.slice();
    let currentPage = 1;
    const rowsPerPage = 10;
    
    // Función para aplicar filtros
    function applyFilters() {
        const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
        const roleValue = roleFilter ? roleFilter.value : '';
        
        filteredRows = allRows.filter(row => {
            // Filtro por rol
            const roleMatch = !roleValue || row.getAttribute('data-role') === roleValue;
            
            // Filtro por búsqueda
            let searchMatch = true;
            if (searchValue) {
                const searchField = row.querySelector('[data-search-field="company_name"]');
                searchMatch = searchField && searchField.textContent.toLowerCase().includes(searchValue);
            }
            
            return roleMatch && searchMatch;
        });
        
        currentPage = 1;
        renderPage();
        renderPagination();
    }
    
    // Función para renderizar página actual
    function renderPage() {
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;
        
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        // Ocultar todas las filas
        allRows.forEach(row => row.style.display = 'none');
        
        // Mostrar solo las filas filtradas de la página actual
        filteredRows.forEach((row, i) => {
            if (i >= start && i < end) {
                row.style.display = '';
            }
        });
    }
    
    // Función para renderizar paginación
    function renderPagination() {
        if (!paginationContainer) return;
        
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
        
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let paginationHTML = '<div class="pagination">';
        
        // Botón anterior
        if (currentPage > 1) {
            paginationHTML += `<button class="pagination__item" onclick="goToPage(${currentPage - 1})">‹</button>`;
        }
        
        // Números de página
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'pagination__item--active' : '';
            paginationHTML += `<button class="pagination__item ${activeClass}" onclick="goToPage(${i})">${i}</button>`;
        }
        
        // Botón siguiente
        if (currentPage < totalPages) {
            paginationHTML += `<button class="pagination__item" onclick="goToPage(${currentPage + 1})">›</button>`;
        }
        
        paginationHTML += '</div>';
        paginationContainer.innerHTML = paginationHTML;
    }
    
    // Función global para cambiar página
    window.goToPage = function(page) {
        currentPage = page;
        renderPage();
        renderPagination();
    };
    
    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    
    if (roleFilter) {
        roleFilter.addEventListener('change', applyFilters);
    }
    
    // Inicialización
    renderPage();
    renderPagination();
});
</script>

<style>
/* Companies Material Design 3 styles */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.page-header__content {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.page-subtitle {
    color: var(--md-on-surface-variant);
    margin: 0;
    font-size: 1rem;
}

.page-header__actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
    flex-wrap: wrap;
}

.search-section,
.companies-section {
    margin-bottom: 2rem;
}

.search-controls {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1.5rem;
    align-items: end;
}

.search-field,
.filter-field {
    min-width: 0;
}

.search-field {
    min-width: 300px;
}

.filter-field {
    min-width: 200px;
}

/* Table styles */
.table-responsive {
    overflow-x: auto;
    min-width: 100%;
}

.table-material {
    width: 100%;
    border-collapse: collapse;
    background: var(--md-surface);
    min-width: 800px;
}

.table-material__header {
    background: var(--md-surface-container);
}

.table-material__cell {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--md-outline-variant);
    vertical-align: top;
}

.table-material__cell--header {
    font-weight: 600;
    color: var(--md-on-surface-variant);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-material__row:hover {
    background: var(--md-surface-container-lowest);
}

/* Company logo */
.company-logo {
    display: flex;
    align-items: center;
    justify-content: center;
}

.company-logo__image {
    max-height: 40px;
    max-width: 60px;
    border-radius: var(--md-shape-corner-small);
    object-fit: contain;
}

.company-logo__placeholder {
    width: 40px;
    height: 40px;
    background: var(--md-surface-container);
    color: var(--md-on-surface-variant);
    border-radius: var(--md-shape-corner-small);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

/* Company info */
.company-info__name {
    font-weight: 600;
    color: var(--md-on-surface);
    font-size: 0.875rem;
}

.contact-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
}

.contact-info i {
    font-size: 0.75rem;
    color: var(--md-primary-40);
    flex-shrink: 0;
}

/* Role badge */
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-badge--buyer {
    background: var(--md-tertiary-container);
    color: var(--md-on-tertiary-container);
}

.role-badge--supplier {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
}

.role-badge i {
    font-size: 0.75rem;
}

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.action-buttons form {
    display: inline;
    margin: 0;
}

.action-buttons .btn-material--outlined {
    border-color: var(--md-error-40);
    color: var(--md-error-40);
}

.action-buttons .btn-material--outlined:hover {
    background: var(--md-error-container);
    border-color: var(--md-error-40);
    color: var(--md-on-error-container);
}

.action-buttons .btn-material--tonal {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
}

.action-buttons .btn-material--tonal:hover {
    background: var(--md-primary-container-hover, #dde5ff);
    transform: scale(1.02);
}

/* Empty state */
.empty-state-container {
    display: flex;
    justify-content: center;
    margin: 3rem 0;
}

.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-state__icon {
    font-size: 4rem;
    color: var(--md-outline);
    margin-bottom: 1.5rem;
}

.empty-state__title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.empty-state__subtitle {
    color: var(--md-on-surface-variant);
    margin: 0 0 2rem 0;
    line-height: 1.5;
}

.empty-state__actions {
    display: flex;
    justify-content: center;
}

/* Pagination */
.pagination-container {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination__item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: var(--md-surface);
    color: var(--md-on-surface);
    border: 1px solid var(--md-outline-variant);
    border-radius: var(--md-shape-corner-small);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 200ms ease;
}

.pagination__item:hover {
    background: var(--md-surface-container);
    border-color: var(--md-primary-40);
}

.pagination__item--active {
    background: var(--md-primary-40);
    color: var(--md-on-primary);
    border-color: var(--md-primary-40);
}

.pagination__item--active:hover {
    background: var(--md-primary-50);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .page-header__actions {
        flex-direction: column;
    }
    
    .search-controls {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .search-field,
    .filter-field {
        min-width: 0;
    }
    
    .table-material__cell {
        padding: 0.75rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .action-buttons .btn-material {
        width: 100%;
        min-width: auto;
    }
}
</style>