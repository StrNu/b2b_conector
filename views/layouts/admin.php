<?php
/**
 * Layout Admin con Material Design 3
 * Actualizado para usar componentes Material Design 3
 * 
 * @version 3.0.0 - Material Design 3
 */

// Set module CSS if not already set
if (!isset($moduleCSS)) {
    $moduleCSS = null;
}

// Add Material Design 3 CSS
if (!isset($additionalCSS)) {
    $additionalCSS = [];
}
$additionalCSS = array_merge($additionalCSS, getMaterialCSS());

// Include Material Design assets
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
        <!-- Material Header -->
        <header class="app-header">
            <nav class="nav nav-material">
                <a href="<?= BASE_URL ?>/dashboard" class="nav__brand">
                    <svg class="nav__logo" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="14" fill="currentColor" opacity="0.1"/>
                        <path d="M8 12h6v8H8z" fill="currentColor"/>
                        <path d="M18 12h6v8h-6z" fill="currentColor"/>
                        <path d="M14 14h4v4h-4z" fill="currentColor" opacity="0.8"/>
                        <circle cx="10" cy="8" r="2" fill="currentColor"/>
                        <circle cx="22" cy="8" r="2" fill="currentColor"/>
                        <path d="M10 10v2M22 10v2" stroke="currentColor" stroke-width="1"/>
                    </svg>
                    <span>B2B Conector</span>
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
                    
                    <a href="<?= BASE_URL ?>/matches" class="nav__link <?= ($currentPage ?? '') === 'matches' ? 'nav__link--active' : '' ?>">
                        <i class="fas fa-handshake" aria-hidden="true"></i>
                        <span>Matches</span>
                    </a>
                    
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="dropdown__trigger" aria-expanded="false">
                            <i class="fas fa-user-circle" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($_SESSION['name'] ?? 'Usuario') ?></span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        
                        <div class="dropdown__menu">
                            <a href="<?= BASE_URL ?>/profile" class="dropdown__item">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                <span>Mi Perfil</span>
                            </a>
                            <a href="<?= BASE_URL ?>/settings" class="dropdown__item">
                                <i class="fas fa-cog" aria-hidden="true"></i>
                                <span>Configuración</span>
                            </a>
                            <div class="dropdown__divider"></div>
                            <a href="<?= BASE_URL ?>/auth/logout" class="dropdown__item">
                                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </header>
        
        <!-- Main Content -->
        <main class="app-main">
            <div class="container">
                <!-- Flash Messages -->
                <div class="flash-messages">
                    <?php include VIEW_DIR . '/shared/notifications.php'; ?>
                </div>
                
                <!-- Page Content -->
                <div class="content-area">
                    <?= $content ?>
                </div>
            </div>
        </main>
        
        <!-- Material Footer -->
        <footer class="app-footer">
            <div class="container">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <p style="margin: 0; font-weight: 600;"><?= APP_NAME ?> v<?= APP_VERSION ?></p>
                        <p style="margin: 0; font-size: 0.875rem; opacity: 0.8;">Plataforma B2B con Material Design 3</p>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <a href="<?= BASE_URL ?>/help" style="color: rgba(255, 255, 255, 0.8); text-decoration: none;">Ayuda</a>
                        <a href="<?= BASE_URL ?>/privacy" style="color: rgba(255, 255, 255, 0.8); text-decoration: none;">Privacidad</a>
                        <a href="<?= BASE_URL ?>/terms" style="color: rgba(255, 255, 255, 0.8); text-decoration: none;">Términos</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Material FAB for quick actions -->
    <button class="fab-material" onclick="document.getElementById('quick-actions').style.display = document.getElementById('quick-actions').style.display === 'block' ? 'none' : 'block'">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Quick Actions Menu (hidden by default) -->
    <div id="quick-actions" style="display: none; position: fixed; bottom: 90px; right: 24px; background: var(--md-surface-container); border-radius: var(--md-shape-corner-medium); box-shadow: var(--md-elevation-3); padding: 1rem; min-width: 200px; z-index: 999;">
        <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--md-primary-10);">Acciones Rápidas</h4>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <a href="<?= BASE_URL ?>/events/create" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; color: var(--md-primary-40); text-decoration: none; border-radius: var(--md-shape-corner-small);" onmouseover="this.style.backgroundColor='var(--md-surface-container-high)'" onmouseout="this.style.backgroundColor='transparent'">
                <i class="fas fa-calendar-plus"></i>
                <span>Nuevo Evento</span>
            </a>
            <a href="<?= BASE_URL ?>/companies/create" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; color: var(--md-primary-40); text-decoration: none; border-radius: var(--md-shape-corner-small);" onmouseover="this.style.backgroundColor='var(--md-surface-container-high)'" onmouseout="this.style.backgroundColor='transparent'">
                <i class="fas fa-building"></i>
                <span>Nueva Empresa</span>
            </a>
            <a href="<?= BASE_URL ?>/matches/generate" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; color: var(--md-primary-40); text-decoration: none; border-radius: var(--md-shape-corner-small);" onmouseover="this.style.backgroundColor='var(--md-surface-container-high)'" onmouseout="this.style.backgroundColor='transparent'">
                <i class="fas fa-magic"></i>
                <span>Generar Matches</span>
            </a>
        </div>
    </div>

    <!-- Include JavaScript assets -->
    <?php
    // CSRF Token para scripts
    if (!isset($csrfToken)) {
        if (function_exists('generateCSRFToken')) {
            $csrfToken = generateCSRFToken();
        } elseif (class_exists('Security') && method_exists('Security', 'generateCsrfToken')) {
            $csrfToken = Security::generateCsrfToken();
        } else {
            $csrfToken = '';
        }
    }
    ?>
    
    <!-- Scripts principales -->
    <script>
        window.BASE_URL = '<?= BASE_PUBLIC_URL ?>';
        window.CSRF_TOKEN = '<?= $csrfToken ?>';
    </script>

    <!-- Scripts de la aplicación -->
    <?php if (file_exists(PUBLIC_DIR . '/assets/js/main.js')): ?>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/main.js"></script>
    <?php endif; ?>

    <?php if (file_exists(PUBLIC_DIR . '/assets/js/components/autosearch.js')): ?>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/components/autosearch.js"></script>
    <script>console.log('FOOTER DEBUG: ✅ autosearch.js loaded');</script>
    <?php else: ?>
    <script>console.log('FOOTER DEBUG: ❌ autosearch.js NOT FOUND');</script>
    <?php endif; ?>

    <?php if (file_exists(PUBLIC_DIR . '/assets/js/components/tabs.js')): ?>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/components/tabs.js"></script>
    <script>console.log('FOOTER DEBUG: ✅ tabs.js loaded');</script>
    <?php else: ?>
    <script>console.log('FOOTER DEBUG: ❌ tabs.js NOT FOUND');</script>
    <?php endif; ?>

    <?php if (file_exists(PUBLIC_DIR . '/assets/js/utils/form-validation.js')): ?>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/utils/form-validation.js"></script>
    <script>console.log('FOOTER DEBUG: ✅ form-validation.js loaded');</script>
    <?php else: ?>
    <script>console.log('FOOTER DEBUG: ❌ form-validation.js NOT FOUND');</script>
    <?php endif; ?>

    <?php if (file_exists(PUBLIC_DIR . '/assets/js/components/pagination.js')): ?>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/components/pagination.js"></script>
    <script>console.log('FOOTER DEBUG: ✅ pagination.js loaded');</script>
    <?php else: ?>
    <script>console.log('FOOTER DEBUG: ❌ pagination.js NOT FOUND');</script>
    <?php endif; ?>

    <?php if (file_exists(PUBLIC_DIR . '/assets/js/components/modal.js')): ?>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/components/modal.js"></script>
    <?php endif; ?>

    <!-- Scripts específicos del módulo -->
    <?php if (isset($moduleJS) && $moduleJS): ?>
        <?php $moduleJSPath = PUBLIC_DIR . '/assets/js/modules/' . $moduleJS . '.js'; ?>
        <?php if (file_exists($moduleJSPath)): ?>
            <script src="<?= BASE_PUBLIC_URL ?>/assets/js/modules/<?= $moduleJS ?>.js"></script>
            <script>console.log('FOOTER DEBUG: ✅ Module JS loaded:', '<?= $moduleJS ?>');</script>
        <?php else: ?>
            <script>console.log('FOOTER DEBUG: ❌ Module JS NOT FOUND:', '<?= $moduleJS ?>');</script>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Scripts adicionales -->
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <?php $jsPath = PUBLIC_DIR . '/assets/js/' . $js; ?>
            <?php if (file_exists($jsPath)): ?>
                <script src="<?= BASE_PUBLIC_URL ?>/assets/js/<?= $js ?>"></script>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>