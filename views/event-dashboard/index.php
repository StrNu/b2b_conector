<?php
// views/event-dashboard/index.php
// Dashboard principal para usuarios de eventos

// Configurar variables para el layout de eventos
$pageTitle = 'Dashboard - ' . $event->getEventName();
$moduleCSS = 'event-dashboard';
$moduleJS = 'event-dashboard';

// Configurar breadcrumbs
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/event-dashboard']
];

// Incluir header específico para eventos
include(VIEW_DIR . '/shared/event_header.php');
?>

<style>
/* Estilos específicos para el dashboard de eventos */
.stats-dashboard {
    margin-bottom: 40px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border-left: 4px solid #007bff;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.stat-card.primary { border-left-color: #007bff; }
.stat-card.success { border-left-color: #28a745; }
.stat-card.info { border-left-color: #17a2b8; }
.stat-card.warning { border-left-color: #ffc107; }
.stat-card.danger { border-left-color: #dc3545; }

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    background: #007bff;
}

.stat-card.primary .stat-icon { background: #007bff; }
.stat-card.success .stat-icon { background: #28a745; }
.stat-card.info .stat-icon { background: #17a2b8; }
.stat-card.warning .stat-icon { background: #ffc107; }
.stat-card.danger .stat-icon { background: #dc3545; }

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 16px;
    font-weight: 600;
    color: #666;
    margin-bottom: 5px;
}

.stat-detail {
    font-size: 13px;
    color: #999;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Acciones principales */
.main-actions {
    margin-bottom: 40px;
}

.section-title {
    color: #333;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.action-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
    border-left: 4px solid #007bff;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    color: inherit;
}

.action-card.primary { border-left-color: #007bff; }
.action-card.success { border-left-color: #28a745; }
.action-card.info { border-left-color: #17a2b8; }
.action-card.warning { border-left-color: #ffc107; }

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    background: #007bff;
}

.action-card.primary .action-icon { background: #007bff; }
.action-card.success .action-icon { background: #28a745; }
.action-card.info .action-icon { background: #17a2b8; }
.action-card.warning .action-icon { background: #ffc107; }

.action-content {
    flex: 1;
}

.action-content h3 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 5px 0;
}

.action-content p {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.action-arrow {
    color: #ccc;
    font-size: 16px;
}

/* Información reciente */
.recent-info {
    margin-bottom: 40px;
}

.info-cards {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.info-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.info-header {
    background: #f8f9fa;
    padding: 20px 25px;
    border-bottom: 1px solid #e9ecef;
}

.info-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-content {
    padding: 0;
}

.data-list {
    display: flex;
    flex-direction: column;
}

.data-item {
    padding: 20px 25px;
    border-bottom: 1px solid #f1f3f4;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s ease;
}

.data-item:hover {
    background: #f8f9fa;
}

.data-item:last-child {
    border-bottom: none;
}

.item-info {
    flex: 1;
}

.item-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.item-subtitle {
    font-size: 14px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 10px;
}

.role-badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.role-buyer { background: #e3f2fd; color: #1976d2; }
.role-supplier { background: #e8f5e8; color: #388e3c; }

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-scheduled { background: #fff3cd; color: #856404; }
.status-completed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.item-status {
    display: flex;
    align-items: center;
}

.text-success { color: #28a745; }

.empty-state {
    padding: 40px 25px;
    text-align: center;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card,
    .action-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .action-content {
        text-align: center;
    }
    
    .item-subtitle {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<!-- Mensajes flash -->
<?php displayFlashMessages(); ?>

<!-- Panel de estadísticas -->
<div class="stats-dashboard">
    <div class="stats-grid">
        <?php if (isEventAdmin()): ?>
            <!-- Estadísticas para Administrador -->
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $dashboardData['stats']['total_companies'] ?></div>
                    <div class="stat-label">Empresas Registradas</div>
                    <div class="stat-detail">
                        <span class="detail-item">
                            <i class="fas fa-shopping-cart"></i> 
                            <?= $dashboardData['stats']['buyer_companies'] ?? 0 ?> Compradores
                        </span>
                        <span class="detail-item">
                            <i class="fas fa-truck"></i> 
                            <?= $dashboardData['stats']['supplier_companies'] ?? 0 ?> Proveedores
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $dashboardData['stats']['confirmed_matches'] ?></div>
                    <div class="stat-label">Matches Confirmados</div>
                    <div class="stat-detail">
                        de <?= $dashboardData['stats']['total_matches'] ?? 0 ?> matches generados
                    </div>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $dashboardData['stats']['total_appointments'] ?></div>
                    <div class="stat-label">Citas Programadas</div>
                    <div class="stat-detail">
                        <?= $dashboardData['stats']['completed_appointments'] ?? 0 ?> completadas
                    </div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $dashboardData['stats']['total_assistants'] ?></div>
                    <div class="stat-label">Asistentes Registrados</div>
                    <div class="stat-detail">
                        Representantes de empresas
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Estadísticas para Asistente -->
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $dashboardData['stats']['total_matches'] ?></div>
                    <div class="stat-label">Mis Matches</div>
                    <div class="stat-detail">
                        Oportunidades de negocio
                    </div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $dashboardData['stats']['upcoming_appointments'] ?></div>
                    <div class="stat-label">Próximas Citas</div>
                    <div class="stat-detail">
                        Reuniones programadas
                    </div>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $dashboardData['stats']['company_assistants'] ?></div>
                    <div class="stat-label">Mi Equipo</div>
                    <div class="stat-detail">
                        Asistentes de mi empresa
                    </div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $dashboardData['stats']['confirmed_matches'] ?></div>
                    <div class="stat-label">Matches Confirmados</div>
                    <div class="stat-detail">
                        Conexiones establecidas
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Acciones principales -->
<div class="main-actions">
    <h2 class="section-title">
        <i class="fas fa-bolt"></i>
        Acciones Principales
    </h2>
    
    <div class="actions-grid">
        <?php if (isEventAdmin()): ?>
            <!-- Acciones para Administrador -->
            <a href="<?= $dashboardData['quick_actions']['manage_companies'] ?>" class="action-card primary">
                <div class="action-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="action-content">
                    <h3>Gestionar Empresas</h3>
                    <p>Ver, editar y administrar empresas participantes</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <a href="<?= $dashboardData['quick_actions']['view_matches'] ?>" class="action-card success">
                <div class="action-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="action-content">
                    <h3>Ver Matches</h3>
                    <p>Revisar y gestionar coincidencias generadas</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <a href="<?= $dashboardData['quick_actions']['view_schedules'] ?>" class="action-card info">
                <div class="action-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="action-content">
                    <h3>Agenda de Citas</h3>
                    <p>Programar y supervisar reuniones</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <a href="<?= $dashboardData['quick_actions']['export_data'] ?>" class="action-card warning">
                <div class="action-icon">
                    <i class="fas fa-download"></i>
                </div>
                <div class="action-content">
                    <h3>Exportar Datos</h3>
                    <p>Descargar reportes y estadísticas</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
        <?php else: ?>
            <!-- Acciones para Asistente -->
            <a href="<?= $dashboardData['quick_actions']['view_agenda'] ?>" class="action-card primary">
                <div class="action-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="action-content">
                    <h3>Mi Agenda</h3>
                    <p>Ver mis citas y reuniones programadas</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <a href="<?= $dashboardData['quick_actions']['view_matches'] ?>" class="action-card success">
                <div class="action-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="action-content">
                    <h3>Mis Matches</h3>
                    <p>Explorar oportunidades de negocio</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <a href="<?= $dashboardData['quick_actions']['manage_assistants'] ?>" class="action-card info">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="action-content">
                    <h3>Mi Equipo</h3>
                    <p>Gestionar asistentes de mi empresa</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <a href="<?= $dashboardData['quick_actions']['edit_company'] ?>" class="action-card warning">
                <div class="action-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="action-content">
                    <h3>Mi Empresa</h3>
                    <p>Actualizar información empresarial</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Sección de información reciente -->
<div class="recent-info">
    <h2 class="section-title">
        <i class="fas fa-clock"></i>
        <?php if (isEventAdmin()): ?>
            Actividad Reciente
        <?php else: ?>
            Próximas Actividades
        <?php endif; ?>
    </h2>
    
    <div class="info-cards">
        <div class="info-card">
            <div class="info-header">
                <h3>
                    <?php if (isEventAdmin()): ?>
                        <i class="fas fa-building"></i> Empresas Recientes
                    <?php else: ?>
                        <i class="fas fa-calendar-alt"></i> Mis Próximas Citas
                    <?php endif; ?>
                </h3>
            </div>
            <div class="info-content">
                <?php if (isEventAdmin()): ?>
                    <?php if (!empty($dashboardData['recent_companies'])): ?>
                        <div class="data-list">
                            <?php foreach (array_slice($dashboardData['recent_companies'], 0, 5) as $company): ?>
                                <div class="data-item">
                                    <div class="item-info">
                                        <div class="item-title"><?= htmlspecialchars($company['company_name']) ?></div>
                                        <div class="item-subtitle">
                                            <span class="role-badge role-<?= $company['role'] ?>">
                                                <?= ucfirst($company['role']) ?>
                                            </span>
                                            <?= formatDate($company['created_at']) ?>
                                        </div>
                                    </div>
                                    <div class="item-status">
                                        <i class="fas fa-circle text-success"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-building"></i>
                            <p>No hay empresas registradas aún</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (!empty($dashboardData['upcoming_appointments'])): ?>
                        <div class="data-list">
                            <?php foreach (array_slice($dashboardData['upcoming_appointments'], 0, 5) as $appointment): ?>
                                <div class="data-item">
                                    <div class="item-info">
                                        <div class="item-title"><?= formatDateTime($appointment['start_datetime']) ?></div>
                                        <div class="item-subtitle">
                                            Mesa <?= $appointment['table_number'] ?> - 
                                            <?= htmlspecialchars($appointment['partner_company']) ?>
                                        </div>
                                    </div>
                                    <div class="item-status">
                                        <span class="status-badge status-<?= $appointment['status'] ?>">
                                            <?= ucfirst($appointment['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No hay citas programadas</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer específico para eventos
include(VIEW_DIR . '/shared/event_footer.php');
?>