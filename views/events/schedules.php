<?php 
// Vista de schedules con Material Design 3
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

$pageTitle = 'Horarios del Evento';
$moduleCSS = 'events';
$moduleJS = 'events';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => isEventUserAuthenticated() ? BASE_URL . '/event-dashboard' : BASE_URL . '/dashboard'],
    ['title' => 'Eventos', 'url' => BASE_URL . '/events'],
    ['title' => 'Horarios']
];
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-title">Horarios del Evento</h1>
            <p class="page-subtitle">Gestiona y visualiza los horarios programados</p>
        </div>
        <div class="page-header__actions">
            <?= materialButton(
                '<i class="fas fa-calendar-alt"></i> Ver Agendas',
                'filled',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/agendas/index/' . (int)$event->getId() . '\'"'
            ) ?>
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?= materialButton(
                    '<i class="fas fa-sync-alt"></i> Recalcular Horarios',
                    'tonal',
                    '',
                    'type="submit" name="regenerate_schedules"'
                ) ?>
            </form>
            <?= materialButton(
                '<i class="fas fa-arrow-left"></i> Volver al Evento',
                'outlined',
                '',
                'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . (int)$event->getId() . '\'"'
            ) ?>
        </div>
    </div>
    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <?php
    // $days = array de fechas (Y-m-d) del evento
    // $schedulesByDay = [ 'Y-m-d' => [schedules...] ] SOLO con registros de event_schedules
    // $matches = [match_id => [datos del match]]
    $activeDay = $_GET['day'] ?? ($days[0] ?? null);
    ?>

    <!-- Day Tabs -->
    <?php if (!empty($days)): ?>
    <div class="tabs-section">
        <?php 
        ob_start();
        ?>
            <div class="tabs-material">
                <div class="tabs-material__list">
                    <?php foreach ($days as $day): ?>
                        <?php $isActive = $activeDay === $day; ?>
                        <a href="?day=<?= $day ?>" class="tabs-material__tab <?= $isActive ? 'tabs-material__tab--active' : '' ?>">
                            <span class="tabs-material__label"><?= date('d/m/Y', strtotime($day)) ?></span>
                            <span class="tabs-material__indicator"></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php 
        $tabsContent = ob_get_clean();
        echo materialCard(
            '<i class="fas fa-calendar"></i> Seleccionar Día',
            $tabsContent,
            'outlined'
        );
        ?>
    </div>
    <?php endif; ?>

    <!-- Schedules Table -->
    <div class="schedules-section">
        <?php if (!empty($schedulesByDay[$activeDay])): ?>
            <?php 
            ob_start();
            ?>
                <div class="table-responsive">
                    <table class="table-material">
                        <thead class="table-material__header">
                            <tr>
                                <th class="table-material__cell table-material__cell--header">Hora Inicio</th>
                                <th class="table-material__cell table-material__cell--header">Hora Fin</th>
                                <th class="table-material__cell table-material__cell--header">Mesa</th>
                                <th class="table-material__cell table-material__cell--header">Comprador</th>
                                <th class="table-material__cell table-material__cell--header">Proveedor</th>
                                <th class="table-material__cell table-material__cell--header">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedulesByDay[$activeDay] as $schedule): ?>
                                <?php $match = $matches[$schedule['match_id']] ?? null; ?>
                                <tr class="table-material__row">
                                    <td class="table-material__cell">
                                        <div class="time-info">
                                            <i class="fas fa-clock"></i>
                                            <?= date('H:i', strtotime($schedule['start_datetime'])) ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="time-info">
                                            <i class="fas fa-clock"></i>
                                            <?= date('H:i', strtotime($schedule['end_datetime'])) ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell text-center">
                                        <span class="table-badge table-badge--table">
                                            <i class="fas fa-table"></i>
                                            Mesa <?= htmlspecialchars($schedule['table_number']) ?>
                                        </span>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="company-info">
                                            <?php if ($match): ?>
                                            <div class="company-info__name"><?= htmlspecialchars($match['buyer_name']) ?></div>
                                            <span class="badge-material badge-material--primary">
                                                <i class="fas fa-shopping-cart"></i>
                                                Comprador
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">Sin asignar</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <div class="company-info">
                                            <?php if ($match): ?>
                                            <div class="company-info__name"><?= htmlspecialchars($match['supplier_name']) ?></div>
                                            <span class="badge-material badge-material--secondary">
                                                <i class="fas fa-boxes"></i>
                                                Proveedor
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">Sin asignar</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="table-material__cell">
                                        <span class="status-badge status-badge--<?= $schedule['status'] === 'scheduled' ? 'scheduled' : 'default' ?>">
                                            <i class="fas fa-<?= $schedule['status'] === 'scheduled' ? 'check-circle' : 'circle' ?>"></i>
                                            <?= htmlspecialchars($schedule['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php 
            $tableContent = ob_get_clean();
            echo materialCard(
                '<i class="fas fa-clock"></i> Horarios del ' . date('d/m/Y', strtotime($activeDay)) . ' (' . count($schedulesByDay[$activeDay]) . ' citas)',
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
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3 class="empty-state__title">No hay citas programadas</h3>
                        <p class="empty-state__subtitle">
                            No se encontraron citas programadas para el día ' . date('d/m/Y', strtotime($activeDay)) . '.
                        </p>
                    </div>',
                    'outlined'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Schedules Material Design 3 styles */
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

.tabs-section,
.schedules-section {
    margin-bottom: 2rem;
}

.tabs-material {
    width: 100%;
}

.tabs-material__list {
    display: flex;
    border-bottom: 1px solid var(--md-outline-variant);
    overflow-x: auto;
    gap: 0;
}

.tabs-material__tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: var(--md-on-surface-variant);
    position: relative;
    min-width: 120px;
    transition: all var(--md-motion-duration-medium1);
    border-bottom: 2px solid transparent;
}

.tabs-material__tab:hover {
    background: var(--md-surface-container-lowest);
    color: var(--md-on-surface);
}

.tabs-material__tab--active {
    color: var(--md-primary-40);
    border-bottom-color: var(--md-primary-40);
    background: var(--md-primary-container);
}

.tabs-material__label {
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
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

.time-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--md-on-surface);
}

.time-info i {
    font-size: 0.75rem;
    color: var(--md-primary-40);
}

.table-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border-radius: var(--md-shape-corner-medium);
    font-size: 0.75rem;
    font-weight: 600;
}

.table-badge--table {
    background: var(--md-tertiary-container);
    color: var(--md-on-tertiary-container);
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

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border-radius: var(--md-shape-corner-full);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge--scheduled {
    background: var(--md-success-container);
    color: var(--md-on-success-container);
}

.status-badge--default {
    background: var(--md-surface-container);
    color: var(--md-on-surface-variant);
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
    margin: 0;
    line-height: 1.5;
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
    
    .tabs-material__tab {
        min-width: 100px;
        padding: 0.75rem 1rem;
    }
    
    .table-material__cell {
        padding: 0.75rem;
    }
    
    .company-info {
        gap: 0.25rem;
    }
}
</style>
