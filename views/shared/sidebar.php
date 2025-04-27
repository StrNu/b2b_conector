<?php
// Obtener la URL actual para resaltar el elemento activo del menú
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li class="<?= ($currentPage == 'index.php' && $currentDir == 'dashboard') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/dashboard/">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="<?= ($currentDir == 'events') ? 'active' : '' ?>">
    <a href="<?= BASE_URL ?>/events/list">
        <i class="fas fa-calendar-alt"></i>
        <span>Eventos</span>
    </a>
    <?php if ($currentDir == 'events'): ?>
    <ul class="submenu">
        <li class="<?= ($currentPage == 'create') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/events/create">
                <i class="fas fa-plus"></i>
                <span>Nuevo Evento</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>
</li>
            <li class="<?= ($currentDir == 'companies') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/companies/">
                    <i class="fas fa-building"></i>
                    <span>Empresas</span>
                </a>
            </li>
            <li class="<?= ($currentDir == 'categories') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/categories/">
                    <i class="fas fa-tags"></i>
                    <span>Categorías</span>
                </a>
            </li>
            <li class="<?= ($currentDir == 'users') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/users/">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <li class="<?= ($currentDir == 'settings') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/settings/">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>