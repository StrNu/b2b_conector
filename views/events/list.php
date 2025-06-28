<?php
// Lista de eventos con Material Design 3
require_once CONFIG_DIR . '/material-config.php';
?>

<div class="content-area">
    <!-- Header de la página -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Gestión de Eventos</h1>
            <p class="page-subtitle">Crea y administra tus eventos B2B con herramientas completas</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-plus"></i> Crear Evento',
                'filled',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/create\'"'
            ) ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Filtros y búsqueda -->
    <div class="filters-section">
        <?= materialCard(
            '<i class="fas fa-filter"></i> Filtros y Búsqueda',
            '
            <form method="GET" action="' . BASE_URL . '/events/list" class="filters-form">
                <div class="filters-grid">
                    <!-- Campo de búsqueda -->
                    <div class="textfield-material textfield-material--outlined">
                        <input 
                            type="text" 
                            name="search" 
                            id="search" 
                            class="textfield-material__input" 
                            placeholder=" "
                            value="' . (isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '') . '"
                        >
                        <label for="search" class="textfield-material__label">Buscar eventos</label>
                        <div class="textfield-material__leading-icon">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    
                    <!-- Filtro de estado -->
                    <div class="select-material">
                        <select name="status" class="select-material__input" onchange="this.form.submit()">
                            <option value="">Todos los estados</option>
                            <option value="1" ' . (isset($_GET['status']) && $_GET['status'] === '1' ? 'selected' : '') . '>Activos</option>
                            <option value="0" ' . (isset($_GET['status']) && $_GET['status'] === '0' ? 'selected' : '') . '>Inactivos</option>
                        </select>
                        <label class="select-material__label">Estado</label>
                        <div class="select-material__leading-icon">
                            <i class="fas fa-toggle-on"></i>
                        </div>
                    </div>
                    
                    <!-- Botones de vista -->
                    <div class="view-toggle">
                        <a href="' . BASE_URL . '/events" class="view-toggle__item ' . (strpos($_SERVER['REQUEST_URI'], '/events/list') === false ? 'view-toggle__item--active' : '') . '">
                            <i class="fas fa-list"></i>
                            <span>Tabla</span>
                        </a>
                        <a href="' . BASE_URL . '/events/list" class="view-toggle__item ' . (strpos($_SERVER['REQUEST_URI'], '/events/list') !== false ? 'view-toggle__item--active' : '') . '">
                            <i class="fas fa-th-large"></i>
                            <span>Tarjetas</span>
                        </a>
                    </div>
                </div>
                
                <div class="filters-actions">
                    ' . materialButton('Buscar', 'filled', 'fas fa-search', 'type="submit"') . '
                    <a href="' . BASE_URL . '/events/list" class="btn-material btn-material--text">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>',
            'outlined'
        ) ?>
    </div>

    <!-- Contenido principal -->
    <div class="events-content">
        <?php if (empty($events)): ?>
            <!-- Estado vacío -->
            <div class="empty-state-container">
                <?= materialCard(
                    '<div class="empty-state">
                        <div class="empty-state__icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3 class="empty-state__title">No hay eventos disponibles</h3>
                        <p class="empty-state__subtitle">No se encontraron eventos con los criterios de búsqueda actuales.</p>
                        <div class="empty-state__actions">
                            ' . materialButton('Crear Primer Evento', 'filled', 'fas fa-plus', 'onclick="window.location.href=\'' . BASE_URL . '/events/create\'"') . '
                        </div>
                    </div>',
                    '',
                    'elevated'
                ) ?>
            </div>
        <?php else: ?>
            <!-- Vista de tarjetas -->
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <?= materialCard(
                            '
                            <div class="event-card__header">
                                <h3 class="event-card__title">' . htmlspecialchars($event['event_name'] ?? '') . '</h3>
                                <div class="event-card__status">
                                    <span class="badge-material badge-material--' . (!empty($event['is_active']) ? 'success' : 'secondary') . '">
                                        ' . (!empty($event['is_active']) ? 'Activo' : 'Inactivo') . '
                                    </span>
                                </div>
                            </div>',
                            '
                            <div class="event-card__content">
                                <!-- Información del venue -->
                                <div class="event-info">
                                    <div class="event-info__item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>' . htmlspecialchars($event['venue'] ?? 'No especificado') . '</span>
                                    </div>
                                    
                                    <!-- Fechas -->
                                    <div class="event-info__item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>' . dateFromDatabase($event['start_date'] ?? '') . ' - ' . dateFromDatabase($event['end_date'] ?? '') . '</span>
                                    </div>
                                    
                                    <!-- Mesas disponibles -->
                                    <div class="event-info__item">
                                        <i class="fas fa-table"></i>
                                        <span>Mesas: ' . htmlspecialchars($event['available_tables'] ?? '0') . '</span>
                                    </div>
                                    
                                    <!-- Duración de reuniones -->
                                    <div class="event-info__item">
                                        <i class="fas fa-clock"></i>
                                        <span>Duración: ' . htmlspecialchars($event['meeting_duration'] ?? '30') . ' min</span>
                                    </div>
                                </div>
                                
                                <!-- Acciones principales -->
                                <div class="event-card__actions">
                                    ' . materialButton(
                                        '<i class="fas fa-arrow-right"></i> Ir al Evento',
                                        'filled',
                                        '',
                                        'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . ($event['event_id'] ?? '') . '\'"'
                                    ) . '
                                </div>
                                
                                <!-- Acciones secundarias -->
                                <div class="event-card__secondary-actions">
                                    ' . materialButton(
                                        '<i class="fas fa-edit"></i> Editar',
                                        'outlined',
                                        '',
                                        'onclick="window.location.href=\'' . BASE_URL . '/events/edit/' . ($event['event_id'] ?? '') . '\'"',
                                        'small'
                                    ) . '
                                    ' . materialButton(
                                        '<i class="fas fa-trash"></i> Eliminar',
                                        'text',
                                        '',
                                        'onclick="confirmDelete(\'' . ($event['event_id'] ?? '') . '\', \'' . htmlspecialchars(addslashes($event['event_name'] ?? '')) . '\')"',
                                        'small'
                                    ) . '
                                </div>
                            </div>',
                            'elevated'
                        ) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <div class="pagination-section">
                    <?= materialCard(
                        '',
                        '
                        <div class="pagination-container">
                            ' . paginationLinks($pagination, BASE_URL . '/events/list?page=') . '
                        </div>',
                        'outlined'
                    ) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmación para eliminación -->
<div class="modal-overlay" id="deleteModal" style="display: none;">
    <div class="modal-content">
        <?= materialCard(
            '<i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación',
            '
            <div class="modal-body">
                <p id="deleteMessage" class="modal-message">¿Está seguro de que desea eliminar este evento?</p>
                <div class="modal-warning">
                    <i class="fas fa-warning"></i>
                    <span>Esta acción no se puede deshacer y eliminará todos los datos asociados al evento.</span>
                </div>
            </div>
            
            <div class="modal-actions">
                ' . materialButton('Cancelar', 'outlined', '', 'type="button" onclick="closeDeleteModal()"') . '
                <form id="deleteForm" action="" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="' . ($csrfToken ?? generateCSRFToken()) . '">
                    ' . materialButton('Eliminar', 'filled', '', 'type="submit"') . '
                </form>
            </div>',
            'elevated'
        ) ?>
    </div>
</div>

<style>
/* Events list specific Material Design 3 styles */
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
    flex-shrink: 0;
}

.filters-section {
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.filters-grid {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.filters-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.textfield-material--outlined .textfield-material__input {
    border: 2px solid var(--md-outline);
    background: transparent;
}

.textfield-material--outlined .textfield-material__input:focus {
    border-color: var(--md-primary-40);
}

.select-material {
    position: relative;
}

.select-material__input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--md-outline);
    border-radius: var(--md-shape-corner-small);
    background: var(--md-surface);
    color: var(--md-on-surface);
    font-size: 1rem;
    appearance: none;
    cursor: pointer;
}

.select-material__input:focus {
    outline: none;
    border-color: var(--md-primary-40);
}

.select-material__label {
    position: absolute;
    top: -0.5rem;
    left: 2.5rem;
    background: var(--md-surface);
    padding: 0 0.5rem;
    color: var(--md-on-surface-variant);
    font-size: 0.75rem;
    font-weight: 500;
}

.select-material__leading-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--md-on-surface-variant);
}

.view-toggle {
    display: flex;
    border: 2px solid var(--md-outline);
    border-radius: var(--md-shape-corner-small);
    overflow: hidden;
}

.view-toggle__item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: var(--md-on-surface-variant);
    background: var(--md-surface);
    transition: all var(--md-motion-duration-short2);
    border-right: 1px solid var(--md-outline);
}

.view-toggle__item:last-child {
    border-right: none;
}

.view-toggle__item:hover {
    background: var(--md-surface-container-low);
}

.view-toggle__item--active {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 1.5rem;
}

.event-card {
    transition: transform var(--md-motion-duration-short2);
}

.event-card:hover {
    transform: translateY(-2px);
}

.event-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.event-card__title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0;
    line-height: 1.3;
    font-family: 'Montserrat', sans-serif;
}

.event-card__status {
    flex-shrink: 0;
}

.event-card__content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.event-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.event-info__item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--md-on-surface-variant);
    font-size: 0.875rem;
}

.event-info__item i {
    color: var(--md-primary-40);
    width: 16px;
    text-align: center;
}

.event-card__actions {
    margin: 1rem 0;
}

.event-card__actions .btn-material {
    width: 100%;
    justify-content: center;
}

.event-card__secondary-actions {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

.badge-material {
    padding: 0.25rem 0.75rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-material--success {
    background: var(--md-success-container);
    color: var(--md-on-success-container);
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
    margin: 0 0 2rem 0;
}

.empty-state__actions {
    display: flex;
    justify-content: center;
}

.pagination-section {
    margin-top: 2rem;
}

.pagination-container {
    display: flex;
    justify-content: center;
}

/* Modal styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-content {
    width: 100%;
    max-width: 400px;
}

.modal-body {
    margin-bottom: 1.5rem;
}

.modal-message {
    color: var(--md-on-surface);
    margin: 0 0 1rem 0;
    font-size: 1rem;
}

.modal-warning {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--md-error-container);
    color: var(--md-on-error-container);
    border-radius: var(--md-shape-corner-small);
    font-size: 0.875rem;
}

.modal-warning i {
    color: var(--md-error-40);
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .view-toggle {
        justify-content: center;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
    }
    
    .event-card__secondary-actions {
        flex-direction: column;
    }
    
    .modal-actions {
        flex-direction: column;
    }
}
</style>

<script>
function confirmDelete(eventId, eventName) {
    document.getElementById('deleteMessage').textContent = `¿Está seguro de que desea eliminar el evento "${eventName}"?`;
    document.getElementById('deleteForm').action = '<?= BASE_URL ?>/events/delete/' + eventId;
    document.getElementById('deleteModal').style.display = 'flex';
    
    // Prevenir scroll del body
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar modal al hacer clic fuera del contenido
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('deleteModal').style.display === 'flex') {
        closeDeleteModal();
    }
});

// Auto-submit del formulario de filtros cuando cambia la búsqueda
document.getElementById('search').addEventListener('input', function() {
    clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
        this.form.submit();
    }, 500);
});
</script>