<?php 
// Vista de agendas con Material Design 3
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

// Las variables $pageTitle, $moduleCSS, $moduleJS, $eventId, $event y $agendas 
// ya vienen del controlador, no necesitamos redefinirlas aquí
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => isEventUserAuthenticated() ? BASE_URL . '/event-dashboard' : BASE_URL . '/dashboard'],
    ['title' => 'Evento', 'url' => BASE_URL . '/events/view/' . $eventId],
    ['title' => 'Agenda']
];
?>
<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Citas del evento</h1>
            <p class="page-subtitle">Gestión de citas programadas por empresa</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Evento',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . (int)$event->getId() . '\'"'
            ) ?>
            <?= materialButton(
                '<i class="fas fa-paper-plane"></i> Enviar Agendas',
                'filled',
                '',
                'id="sendAllAgendasBtn"'
            ) ?>
            <?= materialButton(
                '<i class="fas fa-download"></i> Exportar',
                'tonal',
                '',
                'id="exportAgendasBtn"'
            ) ?>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <?php
    $tab = $_GET['tab'] ?? 'all';
    $all = $agendas['all'] ?? [];
    $buyers = $agendas['buyers'] ?? [];
    $suppliers = $agendas['suppliers'] ?? [];
    ?>
    <div class="tabs-container">
        <div class="tabs-list" role="tablist">
            <button class="tab-button <?= $tab === 'all' ? 'tab-button--active' : '' ?>" 
                    role="tab" aria-selected="<?= $tab === 'all' ? 'true' : 'false' ?>"
                    onclick="switchTab('all')">
                <i class="fas fa-list"></i>
                <span>Todas</span>
                <span class="tab-count"><?= count($all) ?></span>
            </button>
            <button class="tab-button <?= $tab === 'buyers' ? 'tab-button--active' : '' ?>" 
                    role="tab" aria-selected="<?= $tab === 'buyers' ? 'true' : 'false' ?>"
                    onclick="switchTab('buyers')">
                <i class="fas fa-shopping-cart"></i>
                <span>Compradores</span>
                <span class="tab-count"><?= count($buyers) ?></span>
            </button>
            <button class="tab-button <?= $tab === 'suppliers' ? 'tab-button--active' : '' ?>" 
                    role="tab" aria-selected="<?= $tab === 'suppliers' ? 'true' : 'false' ?>"
                    onclick="switchTab('suppliers')">
                <i class="fas fa-industry"></i>
                <span>Proveedores</span>
                <span class="tab-count"><?= count($suppliers) ?></span>
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        <div id="tab-all" class="tab-panel <?= $tab === 'all' ? 'tab-panel--active' : '' ?>">
            <?php
            $agendas = $all;
            include(VIEW_DIR . '/events/_all_schedules.php');
            ?>
        </div>
        
        <div id="tab-buyers" class="tab-panel <?= $tab === 'buyers' ? 'tab-panel--active' : '' ?>">
            <?php
            $agendas = $buyers;
            include(VIEW_DIR . '/events/_buyers_schedules.php');
            ?>
        </div>
        
        <div id="tab-suppliers" class="tab-panel <?= $tab === 'suppliers' ? 'tab-panel--active' : '' ?>">
            <?php
            $agendas = $suppliers;
            include(VIEW_DIR . '/events/_suppliers_schedules.php');
            ?>
        </div>
    </div>
</div>

<style>
/* Agendas Material Design 3 Styles */
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

.tabs-container {
    margin-bottom: 2rem;
}

.tabs-list {
    display: flex;
    border-bottom: 1px solid var(--md-outline-variant);
    gap: 0.5rem;
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--md-on-surface-variant);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--md-motion-duration-short2);
    position: relative;
}

.tab-button:hover {
    background: var(--md-surface-container-highest);
    color: var(--md-on-surface);
}

.tab-button--active {
    color: var(--md-primary-40);
    border-bottom-color: var(--md-primary-40);
    background: var(--md-primary-container);
}

.tab-count {
    background: var(--md-surface-container-high);
    color: var(--md-on-surface-variant);
    padding: 0.25rem 0.5rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 1.5rem;
    text-align: center;
}

.tab-button--active .tab-count {
    background: var(--md-primary-40);
    color: var(--md-on-primary);
}

.tab-panel {
    display: none;
}

.tab-panel--active {
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .tabs-list {
        flex-direction: column;
        gap: 0;
    }
    
    .tab-button {
        justify-content: space-between;
        border-bottom: 1px solid var(--md-outline-variant);
        border-radius: 0;
    }
    
    .tab-button:last-child {
        border-bottom: none;
    }
}
</style>

<script>
// JavaScript para funcionalidad de tabs
function switchTab(tabName) {
    // Ocultar todos los paneles
    document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.remove('tab-panel--active');
    });
    
    // Desactivar todos los botones
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('tab-button--active');
        button.setAttribute('aria-selected', 'false');
    });
    
    // Activar el panel seleccionado
    const targetPanel = document.getElementById('tab-' + tabName);
    if (targetPanel) {
        targetPanel.classList.add('tab-panel--active');
    }
    
    // Activar el botón seleccionado
    const targetButton = document.querySelector('[onclick="switchTab(\'' + tabName + '\')"]');
    if (targetButton) {
        targetButton.classList.add('tab-button--active');
        targetButton.setAttribute('aria-selected', 'true');
    }
    
    // Actualizar URL sin recargar página
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

// Funcionalidad para botones de acción
document.addEventListener('DOMContentLoaded', function() {
    const sendBtn = document.getElementById('sendAllAgendasBtn');
    const exportBtn = document.getElementById('exportAgendasBtn');
    
    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            // Implementar funcionalidad de envío
            alert('Funcionalidad de envío de agendas');
        });
    }
    
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // Implementar funcionalidad de exportación
            alert('Funcionalidad de exportación');
        });
    }
});
</script>
