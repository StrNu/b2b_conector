<!-- Fin del contenido principal -->

<?php
// CSRF Token para modals
if (!isset($csrfToken)) {
    if (function_exists('generateCSRFToken')) {
        $csrfToken = generateCSRFToken();
    } elseif (class_exists('Security') && method_exists('Security', 'generateCsrfToken')) {
        $csrfToken = Security::generateCsrfToken();
    } else {
        $csrfToken = '';
    }
}

// Incluir modals si el archivo existe
if (file_exists(VIEW_DIR . '/shared/modals.php')) {
    include(VIEW_DIR . '/shared/modals.php');
}
?>

<!-- Scripts principales -->
<script>
    window.BASE_URL = '<?= BASE_PUBLIC_URL ?>';
    
    // Configuración global de CSRF
    window.CSRF_TOKEN = '<?= $csrfToken ?>';
    
    // Funciones globales útiles
    window.showNotification = function(message, type = 'info') {
        // Crear notificación temporal
        const notification = document.createElement('div');
        notification.className = `fixed top-20 right-4 z-50 max-w-sm w-full transform transition-all duration-300 translate-x-full`;
        
        let bgColor = '';
        let iconClass = '';
        
        switch (type) {
            case 'success':
                bgColor = 'bg-green-50 border-green-200 text-green-800';
                iconClass = 'fas fa-check-circle text-green-400';
                break;
            case 'error':
            case 'danger':
                bgColor = 'bg-red-50 border-red-200 text-red-800';
                iconClass = 'fas fa-exclamation-circle text-red-400';
                break;
            case 'warning':
                bgColor = 'bg-yellow-50 border-yellow-200 text-yellow-800';
                iconClass = 'fas fa-exclamation-triangle text-yellow-400';
                break;
            default:
                bgColor = 'bg-blue-50 border-blue-200 text-blue-800';
                iconClass = 'fas fa-info-circle text-blue-400';
        }
        
        notification.innerHTML = `
            <div class="rounded-md border p-4 shadow-lg ${bgColor}">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.closest('.fixed').remove()" class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    };
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