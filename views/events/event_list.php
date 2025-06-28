<?php 
// Vista de event_list con Material Design 3
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
        // Placeholder for flash messages
        return;
    }
}

if (!function_exists('isEventUserAuthenticated')) {
    function isEventUserAuthenticated() {
        return false;
    }
}

$pageTitle = 'Registros Completos de Empresas';
$moduleCSS = 'events';
$moduleJS = 'events';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => isEventUserAuthenticated() ? BASE_URL . '/event-dashboard' : BASE_URL . '/dashboard'],
    ['title' => 'Eventos', 'url' => BASE_URL . '/events'],
    ['title' => 'Registros Completos']
];
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Registros Completos de Empresas</h1>
            <p class="page-subtitle">Gestiona todos los registros de empresas del evento</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Evento',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . (int)$eventId . '\'"'
            ) ?>
        </div>
    </div>
    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Filters Section -->
    <div class="filters-section">
        <?= materialCard(
            '<i class="fas fa-filter"></i> Filtros de Búsqueda',
            '
            <form method="GET" class="filters-form" onsubmit="return false;">
                <input type="hidden" name="event_id" value="' . (int)$eventId . '">
                
                <div class="filter-field">
                    <div class="textfield-material">
                        <input type="text" name="search" class="textfield-material__input" 
                               id="searchCompany" data-autosearch="companiesTable" 
                               autocomplete="off" placeholder=" " 
                               value="' . htmlspecialchars($_GET['search'] ?? '') . '">
                        <label class="textfield-material__label">Buscar por nombre, contacto, email...</label>
                    </div>
                </div>
                
                <div class="filter-field">
                    <div class="textfield-material">
                        <select name="role" class="textfield-material__input" id="roleFilter">
                            <option value="">Todos los roles</option>
                            <option value="buyer" ' . ((($_GET['role'] ?? '') === 'buyer') ? 'selected' : '') . '>Comprador</option>
                            <option value="supplier" ' . ((($_GET['role'] ?? '') === 'supplier') ? 'selected' : '') . '>Proveedor</option>
                        </select>
                        <label class="textfield-material__label">Filtrar por rol</label>
                    </div>
                </div>
                
                <div class="filter-field">
                    <div class="textfield-material">
                        <select name="order" class="textfield-material__input" id="orderSelect">
                            <option value="asc" ' . ((($_GET['order'] ?? 'asc') === 'asc') ? 'selected' : '') . '>Ascendente</option>
                            <option value="desc" ' . ((($_GET['order'] ?? '') === 'desc') ? 'selected' : '') . '>Descendente</option>
                        </select>
                        <label class="textfield-material__label">Ordenar por</label>
                    </div>
                </div>
            </form>',
            'outlined'
        ) ?>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/components/autosearch.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderSelect = document.getElementById('orderSelect');
        const roleFilter = document.getElementById('roleFilter');
        const searchInput = document.getElementById('searchCompany');
        const table = document.getElementById('companiesTable');
        orderSelect.addEventListener('change', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('order', this.value);
            params.set('event_id', <?= (int)$eventId ?>);
            params.set('role', roleFilter.value);
            window.location.search = params.toString();
        });
        roleFilter.addEventListener('change', filterAndRenumber);
        searchInput.addEventListener('input', filterAndRenumber);
        function filterAndRenumber() {
            const search = searchInput.value.toLowerCase();
            const role = roleFilter.value;
            const order = orderSelect.value;
            // Filtrar filas visibles
            let visibleRows = [];
            table.querySelectorAll('tbody tr').forEach(row => {
                const rowRole = row.getAttribute('data-role');
                const name = row.querySelector('[data-search-field="company_name"]').textContent.toLowerCase();
                const contacto = row.querySelector('[data-search-field="contact"]').textContent.toLowerCase();
                const email = row.querySelector('[data-search-field="email"]').textContent.toLowerCase();
                let visible = true;
                if (role && rowRole !== role) visible = false;
                if (search && !(name.includes(search) || contacto.includes(search) || email.includes(search))) visible = false;
                row.style.display = visible ? '' : 'none';
                if (visible) visibleRows.push(row);
            });
            // Numerar según orden
            let n = (order === 'desc') ? visibleRows.length : 1;
            visibleRows.forEach(row => {
                row.querySelector('td').textContent = '#' + n;
                if (order === 'desc') {
                    n--;
                } else {
                    n++;
                }
            });
        }
        filterAndRenumber();
    });
    </script>
    <!-- Companies Table -->
    <div class="companies-section">
        <?php if (!empty($companies)): ?>
            <?php 
            ob_start();
            ?>
                <div class="table-responsive">
                    <table id="companiesTable" class="table-material">
                        <thead class="table-material__header">
                            <tr>
                                <th class="table-material__cell table-material__cell--header">#</th>
                                <th class="table-material__cell table-material__cell--header">Nombre</th>
                                <th class="table-material__cell table-material__cell--header">Contacto</th>
                                <th class="table-material__cell table-material__cell--header">Email</th>
                                <th class="table-material__cell table-material__cell--header">Teléfono</th>
                                <th class="table-material__cell table-material__cell--header">Rol</th>
                                <th class="table-material__cell table-material__cell--header">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                                <tr class="table-material__row" data-role="<?= htmlspecialchars($company['role']) ?>">
                                    <td class="table-material__cell text-center">
                                        <span class="company-id">#<?= (int)$company['company_id'] ?></span>
                                    </td>
                                    <td class="table-material__cell" data-search-field="company_name">
                                        <div class="company-info">
                                            <div class="company-info__name"><?= htmlspecialchars($company['company_name']) ?></div>
                                        </div>
                                    </td>
                                    <td class="table-material__cell" data-search-field="contact">
                                        <div class="contact-info">
                                            <?= htmlspecialchars($company['contact_first_name'] . ' ' . $company['contact_last_name']) ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell" data-search-field="email">
                                        <div class="email-info">
                                            <i class="fas fa-envelope"></i>
                                            <?= htmlspecialchars($company['email']) ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="phone-info">
                                            <i class="fas fa-phone"></i>
                                            <?= htmlspecialchars($company['phone']) ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <span class="badge-material badge-material--<?= $company['role'] === 'buyer' ? 'primary' : 'secondary' ?>">
                                            <i class="fas fa-<?= $company['role'] === 'buyer' ? 'shopping-cart' : 'boxes' ?>"></i>
                                            <?= $company['role'] === 'buyer' ? 'Comprador' : 'Proveedor' ?>
                                        </span>
                                    </td>
                                    <td class="table-material__cell">
                                        <?= materialButton(
                                            '<i class="fas fa-list"></i> Ver registro completo',
                                            'outlined',
                                            '',
                                            'onclick="window.location.href=\'' . BASE_URL . '/events/companies/' . (int)$eventId . '/full_registration/' . (int)$company['company_id'] . '\'" title="Ver registro completo"',
                                            'small'
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
                    </div>',
                    'outlined'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Event List Material Design 3 styles */
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
}

.filters-section {
    margin-bottom: 2rem;
}

.filters-form {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 1.5rem;
    align-items: end;
}

.filter-field {
    min-width: 0;
}

.companies-section {
    margin-bottom: 2rem;
}

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

.company-info__name {
    font-weight: 600;
    color: var(--md-on-surface);
    font-size: 0.875rem;
}

.contact-info,
.email-info,
.phone-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
}

.email-info i,
.phone-info i {
    font-size: 0.75rem;
    color: var(--md-primary-40);
}

.company-id {
    font-weight: 600;
    color: var(--md-on-surface-variant);
}

.badge-material {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-material--primary {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
}

.badge-material--secondary {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
}

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
    margin: 0;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-form {
        grid-template-columns: 1fr;
    }
    
    .table-material__cell {
        padding: 0.75rem;
    }
}
</style>
