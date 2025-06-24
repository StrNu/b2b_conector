<?php
// Sidebar específico para usuarios de eventos
$currentPage = basename($_SERVER['PHP_SELF']);
$currentUrl = $_GET['url'] ?? '';
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="event-info-mini">
            <h4><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($event->getEventName()) ?></h4>
            <p class="event-dates"><?= formatDate($event->getStartDate()) ?> - <?= formatDate($event->getEndDate()) ?></p>
        </div>
        <div class="user-badge">
            <?php if (isEventAdmin()): ?>
                <i class="fas fa-user-cog"></i> Administrador
            <?php else: ?>
                <i class="fas fa-user"></i> Asistente
            <?php endif; ?>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?= (strpos($currentUrl, 'event-dashboard') !== false && !strpos($currentUrl, '/')) ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/event-dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <?php if (isEventAdmin()): ?>
                <!-- Menú para Administrador de Evento -->
                <li class="<?= (strpos($currentUrl, 'companies') !== false) ? 'active' : '' ?>">
                    <a href="<?= $dashboardData['quick_actions']['manage_companies'] ?? '#' ?>">
                        <i class="fas fa-building"></i>
                        <span>Gestionar Empresas</span>
                    </a>
                </li>
                <li class="<?= (strpos($currentUrl, 'matches') !== false) ? 'active' : '' ?>">
                    <a href="<?= $dashboardData['quick_actions']['view_matches'] ?? '#' ?>">
                        <i class="fas fa-handshake"></i>
                        <span>Ver Matches</span>
                    </a>
                </li>
                <li class="<?= (strpos($currentUrl, 'schedules') !== false) ? 'active' : '' ?>">
                    <a href="<?= $dashboardData['quick_actions']['view_schedules'] ?? '#' ?>">
                        <i class="fas fa-calendar"></i>
                        <span>Agenda de Citas</span>
                    </a>
                </li>
                <li class="<?= (strpos($currentUrl, 'export') !== false) ? 'active' : '' ?>">
                    <a href="<?= $dashboardData['quick_actions']['export_data'] ?? '#' ?>">
                        <i class="fas fa-download"></i>
                        <span>Exportar Datos</span>
                    </a>
                </li>
                
            <?php else: ?>
                <!-- Menú para Asistente de Evento -->
                <li class="<?= (strpos($currentUrl, 'agenda') !== false) ? 'active' : '' ?>">
                    <a href="<?= $dashboardData['quick_actions']['view_agenda'] ?? '#' ?>">
                        <i class="fas fa-calendar"></i>
                        <span>Mi Agenda</span>
                    </a>
                </li>
                <li class="<?= (strpos($currentUrl, 'matches') !== false) ? 'active' : '' ?>">
                    <a href="<?= $dashboardData['quick_actions']['view_matches'] ?? '#' ?>">
                        <i class="fas fa-handshake"></i>
                        <span>Mis Matches</span>
                    </a>
                </li>
                <li class="<?= (strpos($currentUrl, 'assistants') !== false) ? 'active' : '' ?>">
                    <a href="<?= $dashboardData['quick_actions']['manage_assistants'] ?? '#' ?>">
                        <i class="fas fa-users"></i>
                        <span>Mis Asistentes</span>
                    </a>
                </li>
                <li class="<?= (strpos($currentUrl, 'company') !== false) ? 'active' : '' ?>">
                    <a href="<?= $dashboardData['quick_actions']['edit_company'] ?? '#' ?>">
                        <i class="fas fa-edit"></i>
                        <span>Editar Empresa</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/event-dashboard/logout" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>