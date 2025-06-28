<?php
// Dashboard con Material Design 3
require_once CONFIG_DIR . '/material-config.php';
?>

<div class="content-area">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Panel de Control</h1>
        <p class="dashboard-subtitle">Bienvenido al sistema de gestión B2B</p>
    </div>

    <!-- Grid de estadísticas principales -->
    <div class="dashboard-grid dashboard-grid--stats">
        <?= materialCard(
            '<i class="fas fa-building"></i> Total de Empresas',
            '<div class="stat-number">' . ($totalCompanies ?? 0) . '</div>
             <div class="stat-label">Empresas registradas</div>',
            'filled'
        ) ?>

        <?= materialCard(
            '<i class="fas fa-calendar-alt"></i> Total de Eventos',
            '<div class="stat-number">' . ($totalEvents ?? 0) . '</div>
             <div class="stat-label">Eventos creados</div>',
            'filled'
        ) ?>

        <?= materialCard(
            '<i class="fas fa-handshake"></i> Matches Generados',
            '<div class="stat-number">' . ($totalMatches ?? 0) . '</div>
             <div class="stat-label">Conexiones realizadas</div>',
            'filled'
        ) ?>
    </div>

    <!-- Estadísticas detalladas -->
    <?php if (isset($stats) && !empty($stats)): ?>
    <div class="dashboard-section">
        <h2 class="section-title">Estadísticas Detalladas</h2>
        <div class="dashboard-grid dashboard-grid--detailed">
            <!-- Card de Empresas -->
            <?= materialCard(
                '<i class="fas fa-chart-pie"></i> Empresas por Tipo',
                '
                <div class="stats-list">
                    <div class="stats-item">
                        <span class="stats-item__label">Compradores</span>
                        <span class="stats-item__value">' . ($stats['companies']['buyers'] ?? 0) . '</span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-item__label">Proveedores</span>
                        <span class="stats-item__value">' . ($stats['companies']['suppliers'] ?? 0) . '</span>
                    </div>
                </div>',
                'outlined'
            ) ?>

            <!-- Card de Eventos -->
            <?= materialCard(
                '<i class="fas fa-calendar-check"></i> Estado de Eventos',
                '
                <div class="stats-list">
                    <div class="stats-item">
                        <span class="stats-item__label">Activos</span>
                        <span class="stats-item__value stats-item__value--success">' . ($stats['events']['active'] ?? 0) . '</span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-item__label">Próximos</span>
                        <span class="stats-item__value stats-item__value--info">' . ($stats['events']['upcoming'] ?? 0) . '</span>
                    </div>
                </div>',
                'outlined'
            ) ?>

            <!-- Card de Matches -->
            <?= materialCard(
                '<i class="fas fa-exchange-alt"></i> Estado de Matches',
                '
                <div class="stats-list">
                    <div class="stats-item">
                        <span class="stats-item__label">Pendientes</span>
                        <span class="stats-item__value stats-item__value--warning">' . ($stats['matches']['pending'] ?? 0) . '</span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-item__label">Aceptados</span>
                        <span class="stats-item__value stats-item__value--success">' . ($stats['matches']['accepted'] ?? 0) . '</span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-item__label">Rechazados</span>
                        <span class="stats-item__value stats-item__value--error">' . ($stats['matches']['rejected'] ?? 0) . '</span>
                    </div>
                </div>',
                'outlined'
            ) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Acciones rápidas -->
    <div class="dashboard-section">
        <h2 class="section-title">Acciones Rápidas</h2>
        <?= materialCard(
            '<i class="fas fa-lightning-bolt"></i> Gestión Rápida',
            '
            <div class="quick-actions-grid">
                ' . materialButton('Nueva Empresa', 'filled', 'fas fa-building', 'onclick="window.location.href=\'' . BASE_URL . '/companies/create\'"') . '
                ' . materialButton('Crear Evento', 'filled', 'fas fa-calendar-plus', 'onclick="window.location.href=\'' . BASE_URL . '/events/create\'"') . '
                ' . materialButton('Ver Matches', 'tonal', 'fas fa-handshake', 'onclick="window.location.href=\'' . BASE_URL . '/matches\'"') . '
                ' . materialButton('Generar Reportes', 'outlined', 'fas fa-chart-bar', 'onclick="window.location.href=\'' . BASE_URL . '/reports\'"') . '
            </div>
            
            <div class="logout-section">
                <form action="' . BASE_URL . '/auth/logout" method="post" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="' . (isset($csrfToken) ? $csrfToken : generateCSRFToken()) . '">
                    ' . materialButton('Cerrar Sesión', 'text', 'fas fa-sign-out-alt', 'type="submit"') . '
                </form>
            </div>',
            'elevated'
        ) ?>
    </div>

    <!-- Eventos recientes -->
    <div class="dashboard-section">
        <h2 class="section-title">Eventos Recientes</h2>
        <?= materialCard(
            '<i class="fas fa-history"></i> Últimas Actividades',
            '
            <div class="table-container">
                <table class="table-material">
                    <thead class="table-material__header">
                        <tr>
                            <th class="table-material__cell table-material__cell--header">Nombre</th>
                            <th class="table-material__cell table-material__cell--header">Fecha</th>
                            <th class="table-material__cell table-material__cell--header">Estado</th>
                            <th class="table-material__cell table-material__cell--header">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . ((!empty($recentEvents)) ? 
                            implode('', array_map(function($event) {
                                $statusBadge = (isset($event['is_active']) && $event['is_active']) 
                                    ? '<span class="badge-material badge-material--success">Activo</span>'
                                    : '<span class="badge-material badge-material--secondary">Inactivo</span>';
                                
                                return '
                                <tr class="table-material__row">
                                    <td class="table-material__cell">' . htmlspecialchars($event['name']) . '</td>
                                    <td class="table-material__cell">' . htmlspecialchars($event['date']) . '</td>
                                    <td class="table-material__cell">' . $statusBadge . '</td>
                                    <td class="table-material__cell">
                                        <div class="action-buttons">
                                            ' . materialButton('Ver', 'outlined', 'fas fa-eye', 'onclick="window.location.href=\'' . BASE_URL . '/events/view/' . $event['id'] . '\'"', 'small') . '
                                            ' . materialButton('Editar', 'tonal', 'fas fa-edit', 'onclick="window.location.href=\'' . BASE_URL . '/events/edit/' . $event['id'] . '\'"', 'small') . '
                                        </div>
                                    </td>
                                </tr>';
                            }, $recentEvents))
                            : 
                            '<tr class="table-material__row">
                                <td colspan="4" class="table-material__cell table-material__cell--center">
                                    <div class="empty-state">
                                        <i class="fas fa-calendar-times empty-state__icon"></i>
                                        <p class="empty-state__text">No hay eventos recientes</p>
                                        ' . materialButton('Crear Primer Evento', 'filled', 'fas fa-plus', 'onclick="window.location.href=\'' . BASE_URL . '/events/create\'"') . '
                                    </div>
                                </td>
                            </tr>'
                        ) . '
                    </tbody>
                </table>
            </div>',
            'elevated'
        ) ?>
    </div>
</div>

<style>
/* Dashboard specific Material Design 3 styles */
.dashboard-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem 0;
    background: linear-gradient(135deg, var(--md-primary-40) 0%, var(--md-secondary-40) 100%);
    color: white;
    border-radius: var(--md-shape-corner-large);
    margin-bottom: 3rem;
}

.dashboard-title {
    font-size: 2.5rem;
    font-weight: 600;
    margin: 0;
    font-family: 'Montserrat', sans-serif;
}

.dashboard-subtitle {
    font-size: 1.125rem;
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.dashboard-grid {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.dashboard-grid--stats {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.dashboard-grid--detailed {
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
}

.dashboard-section {
    margin-bottom: 3rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--md-primary-40);
    margin-bottom: 1.5rem;
    font-family: 'Montserrat', sans-serif;
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--md-primary-40);
    line-height: 1;
    margin-bottom: 0.5rem;
    font-family: 'Montserrat', sans-serif;
}

.stat-label {
    color: var(--md-on-surface-variant);
    font-size: 0.875rem;
    font-weight: 500;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stats-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--md-outline-variant);
}

.stats-item:last-child {
    border-bottom: none;
}

.stats-item__label {
    font-weight: 500;
    color: var(--md-on-surface);
}

.stats-item__value {
    font-weight: 600;
    font-size: 1.25rem;
    color: var(--md-primary-40);
}

.stats-item__value--success { color: var(--md-success-40); }
.stats-item__value--warning { color: var(--md-warning-40); }
.stats-item__value--error { color: var(--md-error-40); }
.stats-item__value--info { color: var(--md-info-40); }

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.logout-section {
    border-top: 1px solid var(--md-outline-variant);
    padding-top: 1.5rem;
    text-align: center;
}

.table-material {
    width: 100%;
    border-collapse: collapse;
    background: var(--md-surface);
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

.table-material__cell--center {
    text-align: center;
    padding: 3rem 1rem;
}

.table-material__row:hover {
    background: var(--md-surface-container-lowest);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
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

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.empty-state__icon {
    font-size: 3rem;
    color: var(--md-outline);
}

.empty-state__text {
    color: var(--md-on-surface-variant);
    margin: 0;
}

@media (max-width: 768px) {
    .dashboard-title {
        font-size: 2rem;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>