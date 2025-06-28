<?php
// Vista de evento con Material Design 3
require_once CONFIG_DIR . '/material-config.php';
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title"><?= htmlspecialchars($event->getEventName()) ?></h1>
            <p class="page-subtitle">Detalles y gestión completa del evento</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events\'"'
            ) ?>
            <?= materialButton(
                '<i class="fas fa-edit"></i> Editar',
                'filled',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/edit/' . $event->getId() . '\'"'
            ) ?>
            <?= materialButton(
                '<i class="fas fa-chart-bar"></i> Reporte',
                'tonal',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/report/' . $event->getId() . '\'"'
            ) ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Main Content Grid -->
    <div class="event-view-grid">
        <!-- Event Information Section -->
        <div class="event-info-section">
            <!-- Event Details Card -->
            <?= materialCard(
                '<i class="fas fa-info-circle"></i> Información del Evento',
                '
                <div class="event-details-grid">
                    <div class="event-detail-item">
                        <div class="event-detail-item__icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="event-detail-item__content">
                            <div class="event-detail-item__label">Sede</div>
                            <div class="event-detail-item__value">' . htmlspecialchars($event->getVenue()) . '</div>
                        </div>
                    </div>
                    
                    <div class="event-detail-item">
                        <div class="event-detail-item__icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="event-detail-item__content">
                            <div class="event-detail-item__label">Fechas</div>
                            <div class="event-detail-item__value">' . dateFromDatabase($event->getStartDate()) . ' - ' . dateFromDatabase($event->getEndDate()) . '</div>
                        </div>
                    </div>
                    
                    <div class="event-detail-item">
                        <div class="event-detail-item__icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="event-detail-item__content">
                            <div class="event-detail-item__label">Horario</div>
                            <div class="event-detail-item__value">' . substr($event->getStartTime(), 0, 5) . ' - ' . substr($event->getEndTime(), 0, 5) . '</div>
                        </div>
                    </div>
                    
                    <div class="event-detail-item">
                        <div class="event-detail-item__icon">
                            <i class="fas fa-table"></i>
                        </div>
                        <div class="event-detail-item__content">
                            <div class="event-detail-item__label">Mesas Disponibles</div>
                            <div class="event-detail-item__value">' . $event->getAvailableTables() . '</div>
                        </div>
                    </div>
                    
                    <div class="event-detail-item">
                        <div class="event-detail-item__icon">
                            <i class="fas fa-stopwatch"></i>
                        </div>
                        <div class="event-detail-item__content">
                            <div class="event-detail-item__label">Duración de Reunión</div>
                            <div class="event-detail-item__value">' . $event->getMeetingDuration() . ' minutos</div>
                        </div>
                    </div>
                    
                    <div class="event-detail-item">
                        <div class="event-detail-item__icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="event-detail-item__content">
                            <div class="event-detail-item__label">Empresa Organizadora</div>
                            <div class="event-detail-item__value">' . (!empty($event->getCompanyName()) ? htmlspecialchars($event->getCompanyName()) : '<em>No especificada</em>') . '</div>
                        </div>
                    </div>
                    
                    <div class="event-detail-item">
                        <div class="event-detail-item__icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="event-detail-item__content">
                            <div class="event-detail-item__label">Contacto</div>
                            <div class="event-detail-item__value">' . (!empty($event->getContactName()) ? htmlspecialchars($event->getContactName()) : '<em>No especificado</em>') . '</div>
                        </div>
                    </div>
                    
                    <div class="event-detail-item">
                        <div class="event-detail-item__icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="event-detail-item__content">
                            <div class="event-detail-item__label">Email de Contacto</div>
                            <div class="event-detail-item__value">' . (!empty($event->getContactEmail()) ? htmlspecialchars($event->getContactEmail()) : '<em>No especificado</em>') . '</div>
                        </div>
                    </div>
                </div>
                
                <div class="event-status">
                    <span class="badge-material badge-material--' . ($event->isActive() ? 'success' : 'secondary') . '">
                        <i class="fas fa-circle"></i>
                        ' . ($event->isActive() ? 'Evento Activo' : 'Evento Inactivo') . '
                    </span>
                </div>',
                'elevated'
            ) ?>

            <!-- Categories Section -->
            <?php if ($hasCategories): ?>
            <div class="categories-section">
                <?= materialCard(
                    '<i class="fas fa-tags"></i> Categorías del Evento',
                    '
                    <div class="categories-tabs">
                        <div class="tabs-navigation">
                            ' . implode('', array_map(function($cat, $i) {
                                return '
                                <button class="tab-button ' . ($i === 0 ? 'tab-button--active' : '') . '" 
                                        id="tab-' . $i . '" 
                                        data-tab="tab-panel-' . $i . '" 
                                        type="button">
                                    ' . htmlspecialchars($cat['category']['name']) . '
                                </button>';
                            }, $categoriesWithSubcategories, array_keys($categoriesWithSubcategories))) . '
                        </div>
                        
                        <div class="tabs-content">
                            ' . implode('', array_map(function($cat, $i) {
                                return '
                                <div class="tab-panel ' . ($i === 0 ? 'tab-panel--active' : '') . '" 
                                     id="tab-panel-' . $i . '">
                                    <h3 class="tab-panel__title">Subcategorías de "' . htmlspecialchars($cat['category']['name']) . '"</h3>
                                    ' . (empty($cat['subcategories']) ? 
                                        '<p class="tab-panel__empty">No hay subcategorías para esta categoría.</p>' :
                                        '<ul class="subcategories-list">
                                            ' . implode('', array_map(function($sub) {
                                                return '<li class="subcategory-item">
                                                    <i class="fas fa-tag"></i>
                                                    ' . htmlspecialchars($sub['name']) . '
                                                </li>';
                                            }, $cat['subcategories'])) . '
                                        </ul>'
                                    ) . '
                                </div>';
                            }, $categoriesWithSubcategories, array_keys($categoriesWithSubcategories))) . '
                        </div>
                    </div>
                    
                    <div class="categories-actions">
                        ' . materialButton(
                            '<i class="fas fa-list"></i> Ver Todas',
                            'outlined',
                            '',
                            'onclick="window.location.href=\'' . BASE_URL . '/events/categories/' . $event->getId() . '\'"'
                        ) . '
                        ' . (!$hasCategories ? materialButton(
                            '<i class="fas fa-plus"></i> Agregar Categorías',
                            'filled',
                            '',
                            'onclick="window.location.href=\'' . BASE_URL . '/category_import/upload/' . (int)$event->getId() . '\'"'
                        ) : '') . '
                    </div>',
                    'outlined'
                ) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar with Stats and Actions -->
        <div class="event-sidebar">
            <!-- Statistics Card -->
            <?= materialCard(
                '<i class="fas fa-chart-bar"></i> Estadísticas del Evento',
                '
                <div class="stats-list">
                    <div class="stat-item">
                        <div class="stat-item__icon stat-item__icon--participants">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . ($participantsCount ?? 0) . '</div>
                            <div class="stat-item__label">Participantes</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-item__icon stat-item__icon--buyers">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . ($buyerCompaniesCount ?? 0) . '</div>
                            <div class="stat-item__label">Compradores</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-item__icon stat-item__icon--suppliers">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . ($supplierCompaniesCount ?? 0) . '</div>
                            <div class="stat-item__label">Proveedores</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-item__icon stat-item__icon--matches">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . ($matchCount ?? 0) . '</div>
                            <div class="stat-item__label">Matches</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-item__icon stat-item__icon--schedules">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-item__content">
                            <div class="stat-item__value">' . ($scheduleCount ?? 0) . '</div>
                            <div class="stat-item__label">Citas Programadas</div>
                        </div>
                    </div>
                </div>',
                'filled'
            ) ?>

            <!-- Quick Actions Card -->
            <?= materialCard(
                '<i class="fas fa-bolt"></i> Acciones Rápidas',
                '
                <div class="quick-actions-list">
                    ' . materialButton(
                        '<i class="fas fa-list"></i> Ver Registros Completos',
                        'filled',
                        '',
                        'onclick="window.location.href=\'' . BASE_URL . '/events/event_list?event_id=' . (int)$event->getId() . '\'"'
                    ) . '
                    ' . materialButton(
                        '<i class="fas fa-users"></i> Ver Participantes',
                        'tonal',
                        '',
                        'onclick="window.location.href=\'' . BASE_URL . '/events/participants/' . $event->getId() . '\'"'
                    ) . '
                    ' . materialButton(
                        '<i class="fas fa-handshake"></i> Ver Matches',
                        'tonal',
                        '',
                        'onclick="window.location.href=\'' . BASE_URL . '/events/matches/' . $event->getId() . '\'"'
                    ) . '
                    ' . materialButton(
                        '<i class="fas fa-calendar-alt"></i> Ver Citas',
                        'tonal',
                        '',
                        'onclick="window.location.href=\'' . BASE_URL . '/events/schedules/' . $event->getId() . '\'"'
                    ) . '
                    ' . materialButton(
                        '<i class="fas fa-clock"></i> Ver Horarios',
                        'tonal',
                        '',
                        'onclick="window.location.href=\'' . BASE_URL . '/events/time_slots/' . $event->getId() . '\'"'
                    ) . '
                    ' . materialButton(
                        '<i class="fas fa-building"></i> Ver Empresas',
                        'tonal',
                        '',
                        'onclick="window.location.href=\'' . BASE_URL . '/events/companies/' . $event->getId() . '\'"'
                    ) . '
                </div>',
                'outlined'
            ) ?>
        </div>
    </div>

    <!-- Bottom Section Grid -->
    <div class="event-bottom-grid">
        <!-- Breaks Card -->
        <?= materialCard(
            '<i class="fas fa-coffee"></i> Descansos Programados',
            '
            ' . (empty($breaks) ? 
                '<div class="empty-state empty-state--small">
                    <div class="empty-state__icon">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <p class="empty-state__text">No hay descansos configurados para este evento.</p>
                    ' . materialButton(
                        '<i class="fas fa-plus"></i> Agregar Descansos',
                        'outlined',
                        '',
                        'onclick="window.location.href=\'' . BASE_URL . '/events/breaks/' . $event->getId() . '\'"'
                    ) . '
                </div>' :
                '
                <div class="table-container">
                    <table class="table-material table-material--compact">
                        <thead class="table-material__header">
                            <tr>
                                <th class="table-material__cell table-material__cell--header">Inicio</th>
                                <th class="table-material__cell table-material__cell--header">Fin</th>
                                <th class="table-material__cell table-material__cell--header">Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . implode('', array_map(function($break) {
                                $start = new DateTime($break['start_time']);
                                $end = new DateTime($break['end_time']);
                                $interval = $start->diff($end);
                                
                                return '
                                <tr class="table-material__row">
                                    <td class="table-material__cell">' . substr($break['start_time'], 0, 5) . '</td>
                                    <td class="table-material__cell">' . substr($break['end_time'], 0, 5) . '</td>
                                    <td class="table-material__cell">' . $interval->format('%H:%I') . '</td>
                                </tr>';
                            }, $breaks)) . '
                        </tbody>
                    </table>
                </div>
                
                <div class="breaks-actions">
                    ' . materialButton(
                        '<i class="fas fa-cog"></i> Gestionar Descansos',
                        'outlined',
                        '',
                        'onclick="window.location.href=\'' . BASE_URL . '/events/breaks/' . $event->getId() . '\'"'
                    ) . '
                </div>'
            ) . '',
            'outlined'
        ) ?>

        <!-- Event Configuration Card -->
        <?= materialCard(
            '<i class="fas fa-cog"></i> Estado y Configuración',
            '
            <div class="event-config">
                <form action="' . BASE_URL . '/events/toggle-active/' . $event->getId() . '" method="POST" class="event-toggle-form">
                    <input type="hidden" name="csrf_token" value="' . ($csrfToken ?? '') . '">
                    
                    <div class="switch-material">
                        <input 
                            type="checkbox" 
                            id="is_active" 
                            name="is_active" 
                            class="switch-material__input"
                            ' . ($event->isActive() ? 'checked' : '') . '
                            onchange="this.form.submit()"
                        >
                        <label for="is_active" class="switch-material__label">
                            <span class="switch-material__track"></span>
                            <span class="switch-material__thumb"></span>
                        </label>
                        <span class="switch-material__text">
                            ' . ($event->isActive() ? 'Evento Activo' : 'Evento Inactivo') . '
                        </span>
                    </div>
                </form>
                
                <div class="event-duration">
                    <div class="event-duration__label">Duración del Evento</div>
                    <div class="event-duration__value">
                        ' . (function() use ($event) {
                            $startDate = new DateTime($event->getStartDate());
                            $endDate = new DateTime($event->getEndDate());
                            $interval = $startDate->diff($endDate);
                            return ($interval->days + 1) . ' días';
                        })() . '
                    </div>
                </div>
            </div>',
            'outlined'
        ) ?>
    </div>
</div>

<!-- Include modals -->
<?php include(VIEW_DIR . '/components/modals/import_categories.php'); ?>

<style>
/* Event view specific Material Design 3 styles */
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

.event-view-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.event-info-section {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.event-sidebar {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.event-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.event-detail-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.event-detail-item__icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
    border-radius: var(--md-shape-corner-full);
    flex-shrink: 0;
}

.event-detail-item__content {
    flex: 1;
    min-width: 0;
}

.event-detail-item__label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--md-on-surface-variant);
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.event-detail-item__value {
    font-size: 1rem;
    color: var(--md-on-surface);
    line-height: 1.4;
    word-break: break-word;
}

.event-status {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
}

.badge-material {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.875rem;
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

.categories-section {
    margin-top: 2rem;
}

.categories-tabs {
    margin-bottom: 2rem;
}

.tabs-navigation {
    display: flex;
    border-bottom: 2px solid var(--md-outline-variant);
    margin-bottom: 1.5rem;
    overflow-x: auto;
    gap: 0.5rem;
}

.tab-button {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    color: var(--md-on-surface-variant);
    font-weight: 500;
    cursor: pointer;
    transition: all var(--md-motion-duration-short2);
    border-bottom: 2px solid transparent;
    white-space: nowrap;
}

.tab-button:hover {
    background: var(--md-surface-container-low);
    color: var(--md-on-surface);
}

.tab-button--active {
    color: var(--md-primary-40);
    border-bottom-color: var(--md-primary-40);
    background: var(--md-primary-container);
}

.tabs-content {
    position: relative;
}

.tab-panel {
    display: none;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
    padding: 1.5rem;
    border: 1px solid var(--md-outline-variant);
}

.tab-panel--active {
    display: block;
}

.tab-panel__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--md-primary-40);
    margin-bottom: 1rem;
}

.tab-panel__empty {
    color: var(--md-on-surface-variant);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

.subcategories-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.subcategory-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--md-surface);
    border-radius: var(--md-shape-corner-small);
    color: var(--md-on-surface);
    font-weight: 500;
}

.subcategory-item i {
    color: var(--md-secondary-40);
    font-size: 0.75rem;
}

.categories-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1.5rem;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
}

.stat-item__icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--md-shape-corner-full);
    font-size: 1.25rem;
    flex-shrink: 0;
}

.stat-item__icon--participants {
    background: var(--md-primary-container);
    color: var(--md-on-primary-container);
}

.stat-item__icon--buyers {
    background: var(--md-tertiary-container);
    color: var(--md-on-tertiary-container);
}

.stat-item__icon--suppliers {
    background: var(--md-secondary-container);
    color: var(--md-on-secondary-container);
}

.stat-item__icon--matches {
    background: var(--md-success-container);
    color: var(--md-on-success-container);
}

.stat-item__icon--schedules {
    background: var(--md-info-container);
    color: var(--md-on-info-container);
}

.stat-item__content {
    flex: 1;
}

.stat-item__value {
    font-size: 1.75rem;
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

.quick-actions-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-actions-list .btn-material {
    justify-content: flex-start;
    width: 100%;
}

.event-bottom-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.table-material {
    width: 100%;
    border-collapse: collapse;
    background: var(--md-surface);
}

.table-material--compact .table-material__cell {
    padding: 0.75rem;
}

.table-material__header {
    background: var(--md-surface-container);
}

.table-material__cell {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--md-outline-variant);
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

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-state--small {
    padding: 2rem 1rem;
}

.empty-state__icon {
    font-size: 3rem;
    color: var(--md-outline);
    margin-bottom: 1rem;
}

.empty-state__text {
    color: var(--md-on-surface-variant);
    margin: 0 0 1.5rem 0;
}

.breaks-actions {
    display: flex;
    justify-content: center;
    margin-top: 1.5rem;
}

.event-config {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.event-toggle-form {
    display: flex;
    justify-content: center;
}

.switch-material {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.switch-material__input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.switch-material__label {
    position: relative;
    cursor: pointer;
    display: flex;
    align-items: center;
}

.switch-material__track {
    width: 52px;
    height: 32px;
    background: var(--md-outline);
    border-radius: 16px;
    transition: all var(--md-motion-duration-short2);
    position: relative;
}

.switch-material__thumb {
    width: 20px;
    height: 20px;
    background: var(--md-outline);
    border-radius: 50%;
    position: absolute;
    top: 6px;
    left: 6px;
    transition: all var(--md-motion-duration-short2);
}

.switch-material__input:checked + .switch-material__label .switch-material__track {
    background: var(--md-primary-40);
}

.switch-material__input:checked + .switch-material__label .switch-material__thumb {
    background: var(--md-on-primary);
    left: 26px;
}

.switch-material__text {
    font-weight: 500;
    color: var(--md-on-surface);
}

.event-duration {
    text-align: center;
    padding: 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
}

.event-duration__label {
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.event-duration__value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--md-on-surface);
    font-family: 'Montserrat', sans-serif;
}

/* Responsive */
@media (max-width: 1200px) {
    .event-view-grid {
        grid-template-columns: 1fr;
    }
    
    .event-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .page-header__actions {
        flex-direction: column;
    }
    
    .event-details-grid {
        grid-template-columns: 1fr;
    }
    
    .event-bottom-grid {
        grid-template-columns: 1fr;
    }
    
    .tabs-navigation {
        gap: 0;
    }
    
    .tab-button {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
    
    .subcategories-list {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active classes from all tabs and panels
            tabButtons.forEach(btn => btn.classList.remove('tab-button--active'));
            tabPanels.forEach(panel => panel.classList.remove('tab-panel--active'));
            
            // Add active class to clicked tab and corresponding panel
            this.classList.add('tab-button--active');
            document.getElementById(targetTab).classList.add('tab-panel--active');
        });
    });
});
</script>