<?php
// Mostrar mensajes flash
if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
    foreach ($_SESSION['flash_messages'] as $type => $messages) {
        foreach ($messages as $message) {
            $iconClass = '';
            
            switch($type) {
                case 'success':
                    $iconClass = 'fa-check-circle';
                    break;
                case 'danger':
                    $iconClass = 'fa-exclamation-circle';
                    break;
                case 'warning':
                    $iconClass = 'fa-exclamation-triangle';
                    break;
                case 'info':
                default:
                    $iconClass = 'fa-info-circle';
                    break;
            }
?>
<div class="notification notification-<?= $type ?>" role="alert">
    <div class="notification-icon">
        <i class="fas <?= $iconClass ?>"></i>
    </div>
    <div class="notification-content">
        <?= $message ?>
    </div>
    <button type="button" class="notification-close" aria-label="Cerrar">
        <i class="fas fa-times"></i>
    </button>
</div>
<?php
        }
    }
    // Limpiar mensajes después de mostrarlos
    unset($_SESSION['flash_messages']);
}
?>