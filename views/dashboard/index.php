<?php
// Incluir el encabezado y la barra lateral
include VIEW_DIR . '/shared/header.php';
?>

<div class="dashboard-container">
    <h1>Panel de Control</h1>
    <!-- Sección de estadísticas principales -->
    <div class="stats-container">
        <div class="stat-card">
            <h2>Total de Empresas</h2>
            <p><?php echo $totalCompanies ?? 0; ?></p>
        </div>
        <div class="stat-card">
            <h2>Total de Eventos</h2>
            <p><?php echo $totalEvents ?? 0; ?></p>
        </div>
        <div class="stat-card">
            <h2>Matches Generados</h2>
            <p><?php echo $totalMatches ?? 0; ?></p>
        </div>
    </div>
    <!-- Sección de estadísticas detalladas (opcional) -->
    <?php if (isset($stats) && !empty($stats)): ?>
    <div class="detailed-stats">
        <h2>Estadísticas Detalladas</h2>
        <div class="stats-row">
            <div class="stat-detail">
                <h3>Empresas</h3>
                <ul>
                    <li>Compradores: <span><?php echo $stats['companies']['buyers'] ?? 0; ?></span></li>
                    <li>Proveedores: <span><?php echo $stats['companies']['suppliers'] ?? 0; ?></span></li>
                </ul>
            </div>
            <div class="stat-detail">
                <h3>Eventos</h3>
                <ul>
                    <li>Activos: <span><?php echo $stats['events']['active'] ?? 0; ?></span></li>
                    <li>Próximos: <span><?php echo $stats['events']['upcoming'] ?? 0; ?></span></li>
                </ul>
            </div>
            <div class="stat-detail">
                <h3>Matches</h3>
                <ul>
                    <li>Pendientes: <span><?php echo $stats['matches']['pending'] ?? 0; ?></span></li>
                    <li>Aceptados: <span><?php echo $stats['matches']['accepted'] ?? 0; ?></span></li>
                    <li>Rechazados: <span><?php echo $stats['matches']['rejected'] ?? 0; ?></span></li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- Sección de acciones rápidas -->
    <div class="quick-actions">
        <h2>Acciones Rápidas</h2>
        <ul>
            <?php if (isset($quickActions) && !empty($quickActions)): ?>
                <?php foreach ($quickActions as $action): ?>
                <li>
                    <a href="<?php echo $action['url']; ?>" class="quick-action-link">
                        <i class="fa fa-<?php echo $action['icon']; ?>"></i> 
                        <?php echo $action['title']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>/companies/create">Registrar Nueva Empresa</a></li>
                <li><a href="<?php echo BASE_URL; ?>/events/create">Crear Nuevo Evento</a></li>
                <li><a href="<?php echo BASE_URL; ?>/matches">Ver Matches</a></li>
            <?php endif; ?>
        </ul>
        <form action="<?= BASE_URL ?>/auth/logout" method="post" style="display:inline; margin-top: 15px;">
            <input type="hidden" name="csrf_token" value="<?= isset($csrfToken) ? $csrfToken : generateCSRFToken() ?>">
            <button type="submit" class="btn btn-danger">Cerrar sesión</button>
        </form>
    </div>
    <!-- Sección de eventos recientes -->
    <div class="recent-events">
        <h2>Eventos Recientes</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentEvents)) : ?>
                    <?php foreach ($recentEvents as $event) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['name']); ?></td>
                            <td><?php echo htmlspecialchars($event['date']); ?></td>
                            <td>
                                <?php if (isset($event['is_active']) && $event['is_active']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/events/view/<?php echo $event['id']; ?>" class="btn btn-info btn-sm">Ver</a>
                                <a href="<?php echo BASE_URL; ?>/events/edit/<?php echo $event['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="text-center">No hay eventos recientes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
// Incluir el pie de página
include VIEW_DIR . '/shared/footer.php';
?>
