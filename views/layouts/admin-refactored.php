<?php
/**
 * Refactored Admin Layout
 * Clean, semantic HTML with modern CSS architecture
 * 
 * @version 2.0.0
 */

// Set module CSS if not already set
if (!isset($moduleCSS)) {
    $moduleCSS = null;
}

// Include Material Design 3 assets
include __DIR__ . '/../shared/assets.php';
?>
<!DOCTYPE html>
<html lang="es" class="admin-layout">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Panel de Administración') ?> - B2B Conector</title>
    
    <!-- Asset loading handled by assets.php above -->
</head>
<body class="material-theme">
    <div class="app-layout admin-layout">
        <!-- Header -->
        <header class="app-header">
            <nav class="nav nav-material">
                <a href="<?= BASE_URL ?>/dashboard" class="nav__brand">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <span>Panel de Administración</span>
                </a>
                
                <div class="nav__menu">
                    <a href="<?= BASE_URL ?>/dashboard" class="nav__link <?= ($currentPage ?? '') === 'dashboard' ? 'nav__link--active' : '' ?>">
                        <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="<?= BASE_URL ?>/events" class="nav__link <?= ($currentPage ?? '') === 'events' ? 'nav__link--active' : '' ?>">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <span>Eventos</span>
                    </a>
                    
                    <a href="<?= BASE_URL ?>/companies" class="nav__link <?= ($currentPage ?? '') === 'companies' ? 'nav__link--active' : '' ?>">
                        <i class="fas fa-building" aria-hidden="true"></i>
                        <span>Empresas</span>
                    </a>
                    
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="dropdown__trigger" aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-user-circle" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($_SESSION['name'] ?? 'Usuario') ?></span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        
                        <div class="dropdown__menu" role="menu">
                            <a href="<?= BASE_URL ?>/auth/change-password" class="dropdown__item" role="menuitem">
                                <i class="fas fa-key" aria-hidden="true"></i>
                                <span>Cambiar Contraseña</span>
                            </a>
                            <div class="dropdown__divider"></div>
                            <a href="<?= BASE_URL ?>/auth/logout" class="dropdown__item" role="menuitem" style="color: var(--color-error-700);">
                                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" aria-expanded="false" aria-controls="mobile-menu">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                    <span class="sr-only">Menú</span>
                </button>
            </nav>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="mobile-menu" role="menu">
                <a href="<?= BASE_URL ?>/dashboard" class="mobile-menu__item" role="menuitem">
                    <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?= BASE_URL ?>/events" class="mobile-menu__item" role="menuitem">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <span>Eventos</span>
                </a>
                <a href="<?= BASE_URL ?>/companies" class="mobile-menu__item" role="menuitem">
                    <i class="fas fa-building" aria-hidden="true"></i>
                    <span>Empresas</span>
                </a>
                <div class="mobile-menu__divider"></div>
                <div class="mobile-menu__user">
                    <span><?= htmlspecialchars($_SESSION['name'] ?? 'Usuario') ?></span>
                </div>
                <a href="<?= BASE_URL ?>/auth/change-password" class="mobile-menu__item" role="menuitem">
                    <i class="fas fa-key" aria-hidden="true"></i>
                    <span>Cambiar Contraseña</span>
                </a>
                <a href="<?= BASE_URL ?>/auth/logout" class="mobile-menu__item mobile-menu__item--danger" role="menuitem">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="app-main" role="main">
            <div class="container-fluid">
                <!-- Flash Messages -->
                <div class="notifications-container">
                    <?php displayFlashMessages(); ?>
                </div>
                
                <!-- Page Content -->
                <div class="content-area">
                    <?= $content ?? '' ?>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="app-footer" role="contentinfo">
            <div class="container">
                <div class="flex justify-between items-center flex-wrap gap-4">
                    <div>
                        <p>&copy; <?= date('Y') ?> B2B Conector. Todos los derechos reservados.</p>
                    </div>
                    <div class="flex gap-6 flex-wrap">
                        <a href="<?= BASE_URL ?>/pages/privacy-policy" class="text-sm">
                            <i class="fas fa-shield-alt" aria-hidden="true"></i>
                            Privacidad
                        </a>
                        <a href="<?= BASE_URL ?>/pages/terms" class="text-sm">
                            <i class="fas fa-file-contract" aria-hidden="true"></i>
                            Términos
                        </a>
                        <a href="<?= BASE_URL ?>/pages/help" class="text-sm">
                            <i class="fas fa-question-circle" aria-hidden="true"></i>
                            Ayuda
                        </a>
                    </div>
                </div>
                
                <div class="border-top mt-4 pt-4">
                    <div class="flex justify-between items-center text-xs flex-wrap gap-2">
                        <div>
                            <span>Panel de Administración</span>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <span> • Usuario: <?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['name'] ?? 'N/A') ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span>Versión 2.0 • PHP <?= PHP_VERSION ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Additional Scripts -->
    <?php if (isset($moduleJS) && $moduleJS): ?>
        <script src="<?= BASE_PUBLIC_URL ?>/assets/js/modules/<?= $moduleJS ?>.js" defer></script>
    <?php endif; ?>
    
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= BASE_PUBLIC_URL ?>/assets/js/<?= $js ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>