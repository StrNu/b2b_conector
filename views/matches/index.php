<?php 
// Vista de matches con Material Design 3
require_once CONFIG_DIR . '/material-config.php';
$pageTitle = 'Matches Potenciales';
$moduleCSS = 'matches';
$moduleJS = 'matches';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => isEventUserAuthenticated() ? BASE_URL . '/event-dashboard' : BASE_URL . '/dashboard'],
    ['title' => 'Matches']
];
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Matches Potenciales</h1>
            <p class="page-subtitle">Gestiona las conexiones entre compradores y proveedores</p>
        </div>
        <div class="page-header__actions">
            <form action="<?= BASE_URL ?>/matches/saveAll" method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?= materialButton(
                    '<i class="fas fa-save"></i> Guardar Todos',
                    'filled',
                    '',
                    'type="submit"'
                ) ?>
            </form>
            <form action="<?= BASE_URL ?>/matches/autoScheduleAll" method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?= materialButton(
                    '<i class="fas fa-calendar-check"></i> Auto-programar Todo',
                    'tonal',
                    '',
                    'type="submit"'
                ) ?>
            </form>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Matches Content -->
    <div class="matches-section">
        <?php if (!empty($matches)): ?>
            <?= materialCard(
                '<i class="fas fa-handshake"></i> Lista de Matches (' . count($matches) . ')',
                '
                <!-- Search Control -->
                <div class="search-controls mb-4">
                    <div class="textfield-material">
                        <input type="text" id="search-matches" class="textfield-material__input" 
                               placeholder=" " data-autosearch="matches-table">
                        <label class="textfield-material__label">Buscar por empresa...</label>
                    </div>
                </div>
                
                <div class="matches-table-container">
                    <div class="table-responsive">
                        <table class="table-material" id="matches-table">
                            <thead class="table-material__header">
                                <tr>
                                    <th class="table-material__cell table-material__cell--header">Comprador</th>
                                    <th class="table-material__cell table-material__cell--header">Proveedor</th>
                                    <th class="table-material__cell table-material__cell--header">Categorías</th>
                                    <th class="table-material__cell table-material__cell--header">Días</th>
                                    <th class="table-material__cell table-material__cell--header">Fortaleza</th>
                                    <th class="table-material__cell table-material__cell--header">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . implode('', array_map(function($match) use ($csrfToken, $BASE_URL) {
                                    return '
                                    <tr class="table-material__row">
                                        <td class="table-material__cell">
                                            <div class="company-info">
                                                <div class="company-info__name">' . htmlspecialchars($match['buyer_name']) . '</div>
                                                <div class="company-info__type">
                                                    <span class="badge-material badge-material--primary">
                                                        <i class="fas fa-shopping-cart"></i>
                                                        Comprador
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="table-material__cell">
                                            <div class="company-info">
                                                <div class="company-info__name">' . htmlspecialchars($match['supplier_name']) . '</div>
                                                <div class="company-info__type">
                                                    <span class="badge-material badge-material--secondary">
                                                        <i class="fas fa-boxes"></i>
                                                        Proveedor
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="table-material__cell">
                                            <div class="categories-list">
                                                ' . implode('', array_map(function($cat) {
                                                    return '<span class="chip-material chip-material--category">
                                                        <i class="fas fa-tag"></i>
                                                        ' . htmlspecialchars($cat['category_name']) . ' / ' . htmlspecialchars($cat['subcategory_name']) . '
                                                    </span>';
                                                }, $match['matched_categories'])) . '
                                            </div>
                                        </td>
                                        <td class="table-material__cell">
                                            <div class="days-list">
                                                ' . (!empty($match['common_days']) ? 
                                                    implode('', array_map(function($day) {
                                                        return '<span class="chip-material chip-material--day">
                                                            <i class="fas fa-calendar-day"></i>
                                                            ' . htmlspecialchars($day) . '
                                                        </span>';
                                                    }, $match['common_days'])) :
                                                    '<span class="text-muted">Sin coincidencia</span>'
                                                ) . '
                                            </div>
                                        </td>
                                        <td class="table-material__cell">
                                            <div class="match-strength">
                                                <div class="match-strength__bar">
                                                    <div class="match-strength__progress match-strength__progress--' . 
                                                        ((float)$match['match_strength'] >= 80 ? 'excellent' : 
                                                        ((float)$match['match_strength'] >= 60 ? 'good' : 
                                                        ((float)$match['match_strength'] >= 40 ? 'fair' : 'poor'))) . '"
                                                        style="width: ' . (float)$match['match_strength'] . '%">
                                                    </div>
                                                </div>
                                                <div class="match-strength__label">
                                                    ' . $match['match_strength'] . '%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="table-material__cell">
                                            <form action="' . BASE_URL . '/matches/save/' . $match['buyer_id'] . '/' . $match['supplier_id'] . '/' . $match['event_id'] . '" method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="' . $csrfToken . '">
                                                ' . materialButton(
                                                    '<i class="fas fa-save"></i> Guardar',
                                                    'outlined',
                                                    '',
                                                    'type="submit"',
                                                    'small'
                                                ) . '
                                            </form>
                                        </td>
                                    </tr>';
                                }, $matches)) . '
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination Container -->
                <div id="matches-pagination" class="pagination-container mt-4"></div>',
                'elevated'
            ) ?>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="empty-state-container">
                <?= materialCard(
                    '',
                    '
                    <div class="empty-state">
                        <div class="empty-state__icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="empty-state__title">No hay matches potenciales</h3>
                        <p class="empty-state__subtitle">
                            No se encontraron coincidencias entre compradores y proveedores en este momento.
                        </p>
                        <div class="empty-state__actions">
                            ' . materialButton(
                                '<i class="fas fa-refresh"></i> Generar Matches',
                                'filled',
                                '',
                                'onclick="window.location.reload()"'
                            ) . '
                        </div>
                    </div>',
                    'outlined'
                ) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Estadísticas de matches -->
    <?php if (!empty($matches)): ?>
    <div class="matches-stats">
        <?php
        $totalMatches = count($matches);
        $excellentMatches = array_filter($matches, function($m) { return (float)$m['match_strength'] >= 80; });
        $goodMatches = array_filter($matches, function($m) { return (float)$m['match_strength'] >= 60 && (float)$m['match_strength'] < 80; });
        $fairMatches = array_filter($matches, function($m) { return (float)$m['match_strength'] >= 40 && (float)$m['match_strength'] < 60; });
        ?>
        
        <div class="stats-grid">
            <?= materialCard(
                '<i class="fas fa-chart-pie"></i> Resumen de Calidad',
                '
                <div class="stats-list">
                    <div class="stat-item stat-item--excellent">
                        <div class="stat-item__icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . count($excellentMatches) . '</div>
                            <div class="stat-item__label">Excelentes (80%+)</div>
                        </div>
                    </div>
                    
                    <div class="stat-item stat-item--good">
                        <div class="stat-item__icon">
                            <i class="fas fa-thumbs-up"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . count($goodMatches) . '</div>
                            <div class="stat-item__label">Buenos (60-79%)</div>
                        </div>
                    </div>
                    
                    <div class="stat-item stat-item--fair">
                        <div class="stat-item__icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . count($fairMatches) . '</div>
                            <div class="stat-item__label">Regulares (40-59%)</div>
                        </div>
                    </div>
                    
                    <div class="stat-item stat-item--total">
                        <div class="stat-item__icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . $totalMatches . '</div>
                            <div class="stat-item__label">Total Matches</div>
                        </div>
                    </div>
                </div>',
                'outlined'
            ) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Matches specific Material Design 3 styles */
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

.matches-section {
    margin-bottom: 2rem;
}

.matches-table-container {
    overflow-x: auto;
}

.table-responsive {
    min-width: 100%;
    overflow-x: auto;
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
    padding: 1rem;
}

.table-material__row:hover {
    background: var(--md-surface-container-lowest);
}

.company-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.company-info__name {
    font-weight: 600;
    color: var(--md-on-surface);
    font-size: 0.875rem;
}

.company-info__type {
    display: flex;
}

.categories-list,
.days-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.chip-material {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.chip-material--category {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
}

.chip-material--day {
    background: var(--md-tertiary-container);
    color: var(--md-on-tertiary-container);
}

.chip-material i {
    font-size: 0.625rem;
}

.match-strength {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 100px;
}

.match-strength__bar {
    width: 100%;
    height: 8px;
    background: var(--md-surface-container);
    border-radius: var(--md-shape-corner-full);
    overflow: hidden;
}

.match-strength__progress {
    height: 100%;
    border-radius: var(--md-shape-corner-full);
    transition: width var(--md-motion-duration-medium1);
}

.match-strength__progress--excellent {
    background: var(--md-success-40);
}

.match-strength__progress--good {
    background: var(--md-info-40);
}

.match-strength__progress--fair {
    background: var(--md-warning-40);
}

.match-strength__progress--poor {
    background: var(--md-error-40);
}

.match-strength__label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--md-on-surface-variant);
    text-align: center;
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

.text-muted {
    color: var(--md-on-surface-variant);
    font-style: italic;
    font-size: 0.875rem;
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
    margin: 0 0 2rem 0;
    line-height: 1.5;
}

.empty-state__actions {
    display: flex;
    justify-content: center;
}

.matches-stats {
    margin-top: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.stats-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
    border-left: 4px solid;
}

.stat-item--excellent {
    border-left-color: var(--md-success-40);
}

.stat-item--good {
    border-left-color: var(--md-info-40);
}

.stat-item--fair {
    border-left-color: var(--md-warning-40);
}

.stat-item--total {
    border-left-color: var(--md-primary-40);
}

.stat-item__icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--md-shape-corner-full);
    font-size: 1.25rem;
}

.stat-item--excellent .stat-item__icon {
    background: var(--md-success-container);
    color: var(--md-on-success-container);
}

.stat-item--good .stat-item__icon {
    background: var(--md-info-container);
    color: var(--md-on-info-container);
}

.stat-item--fair .stat-item__icon {
    background: var(--md-warning-container);
    color: var(--md-on-warning-container);
}

.stat-item--total .stat-item__icon {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
}

.stat-item__content {
    flex: 1;
}

.stat-item__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--md-on-surface);
    line-height: 1;
    margin-bottom: 0.25rem;
    font-family: 'Montserrat', sans-serif;
}

.stat-item__label {
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
    font-weight: 500;
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
    
    .stats-list {
        grid-template-columns: 1fr;
    }
    
    .matches-table-container {
        margin: -1rem;
        padding: 1rem;
    }
}

@media (max-width: 600px) {
    .company-info,
    .categories-list,
    .days-list {
        gap: 0.25rem;
    }
    
    .chip-material {
        font-size: 0.625rem;
        padding: 0.25rem 0.5rem;
    }
    
    .table-material__cell {
        padding: 0.75rem;
    }
}

/* Search Controls */
.search-controls {
    margin-bottom: 1rem;
    max-width: 400px;
}

.textfield-material {
    position: relative;
    display: flex;
    align-items: center;
}

.textfield-material__input {
    width: 100%;
    padding: 1rem;
    border: 1px solid var(--md-outline-variant);
    border-radius: var(--md-shape-corner-medium);
    background: var(--md-surface);
    color: var(--md-on-surface);
    font-size: 1rem;
    transition: border-color var(--md-transition-duration);
}

.textfield-material__input:focus {
    outline: none;
    border-color: var(--md-primary-40);
    box-shadow: 0 0 0 2px rgba(103, 80, 164, 0.1);
}

.textfield-material__label {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: var(--md-surface);
    color: var(--md-on-surface-variant);
    font-size: 1rem;
    transition: all var(--md-transition-duration);
    pointer-events: none;
    padding: 0 0.25rem;
}

.textfield-material__input:focus + .textfield-material__label,
.textfield-material__input:not(:placeholder-shown) + .textfield-material__label {
    top: 0;
    font-size: 0.75rem;
    color: var(--md-primary-40);
}

/* Pagination Styles */
.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 1.5rem;
}

.pagination-nav {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.pagination-btn {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--md-outline-variant);
    background: var(--md-surface);
    color: var(--md-on-surface);
    border-radius: var(--md-shape-corner-small);
    cursor: pointer;
    transition: all var(--md-transition-duration);
    font-size: 0.875rem;
    min-width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pagination-btn:hover {
    background: var(--md-surface-container-high);
    border-color: var(--md-primary-40);
}

.pagination-btn.active {
    background: var(--md-primary-40);
    color: var(--md-on-primary);
    border-color: var(--md-primary-40);
}

.mb-4 {
    margin-bottom: 1rem;
}

.mt-4 {
    margin-top: 1rem;
}
</style>

<script>
// Inicializar paginación para la tabla de matches con logs detallados
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== MATCHES DEBUG: DOMContentLoaded fired ===');
    
    // Verificar si la tabla existe
    const table = document.getElementById('matches-table');
    console.log('MATCHES DEBUG: Table found:', table);
    
    if (table) {
        console.log('MATCHES DEBUG: Table rows:', table.querySelectorAll('tbody tr').length);
        console.log('MATCHES DEBUG: Table HTML:', table.outerHTML.substring(0, 200) + '...');
    }
    
    // Verificar si la función pagination está disponible
    console.log('MATCHES DEBUG: pagination function available:', typeof pagination);
    console.log('MATCHES DEBUG: window.pagination:', window.pagination);
    
    // Verificar autosearch
    const searchInput = document.getElementById('search-matches');
    console.log('MATCHES DEBUG: Search input found:', searchInput);
    
    if (searchInput) {
        console.log('MATCHES DEBUG: Search input data-autosearch:', searchInput.getAttribute('data-autosearch'));
    }
    
    // Verificar si autosearch.js está inicializado
    console.log('MATCHES DEBUG: window.autoSearchInitialized:', window.autoSearchInitialized);
    
    // Verificar contenedor de paginación
    const paginationContainer = document.getElementById('matches-pagination');
    console.log('MATCHES DEBUG: Pagination container found:', paginationContainer);
    
    // Intentar inicializar paginación
    if (table && typeof pagination === 'function') {
        console.log('MATCHES DEBUG: Attempting to initialize pagination...');
        
        // Esperar un momento para que el DOM esté completamente listo
        setTimeout(() => {
            try {
                pagination('matches-pagination', 'matches-table', 10);
                console.log('MATCHES DEBUG: ✅ Pagination initialized successfully for matches-table');
                
                // Verificar si se crearon los controles de paginación
                const paginationControls = document.querySelector('#matches-pagination .pagination-nav');
                console.log('MATCHES DEBUG: Pagination controls created:', paginationControls);
                
            } catch (error) {
                console.error('MATCHES DEBUG: ❌ Error initializing pagination:', error);
            }
        }, 100);
    } else {
        console.warn('MATCHES DEBUG: ❌ Cannot initialize pagination - missing requirements');
        console.log('MATCHES DEBUG: - Table exists:', !!table);
        console.log('MATCHES DEBUG: - Pagination function exists:', typeof pagination === 'function');
    }
    
    // Test manual de autosearch
    if (searchInput) {
        console.log('MATCHES DEBUG: Adding manual search test...');
        searchInput.addEventListener('input', function() {
            console.log('MATCHES DEBUG: Manual search triggered, value:', this.value);
            
            // Verificar si window.repaginateTable está disponible
            if (window.repaginateTable && window.repaginateTable['matches-table']) {
                console.log('MATCHES DEBUG: Using repaginateTable function');
                window.repaginateTable['matches-table'](row => {
                    const match = !this.value || row.textContent.toLowerCase().includes(this.value.toLowerCase());
                    console.log('MATCHES DEBUG: Row match:', match, 'Row text:', row.textContent.substring(0, 50));
                    return match;
                });
            } else {
                console.log('MATCHES DEBUG: Using fallback search');
                const tbody = table.querySelector('tbody');
                if (tbody) {
                    const rows = tbody.querySelectorAll('tr');
                    console.log('MATCHES DEBUG: Found rows for search:', rows.length);
                    rows.forEach((row, index) => {
                        const match = !this.value || row.textContent.toLowerCase().includes(this.value.toLowerCase());
                        row.style.display = match ? '' : 'none';
                        if (index < 3) console.log(`MATCHES DEBUG: Row ${index} match:`, match);
                    });
                }
            }
        });
    }
});

// Log adicional cuando se carguen los scripts
window.addEventListener('load', function() {
    console.log('=== MATCHES DEBUG: Window load complete ===');
    console.log('MATCHES DEBUG: All scripts loaded, checking again...');
    console.log('MATCHES DEBUG: pagination function:', typeof pagination);
    console.log('MATCHES DEBUG: autosearch initialized:', window.autoSearchInitialized);
    console.log('MATCHES DEBUG: repaginateTable:', window.repaginateTable);
});
</script>
</style>