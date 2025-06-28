<?php
// Mostrar mensajes flash usando flash-message classes para consistencia
if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
    foreach ($_SESSION['flash_messages'] as $type => $messages) {
        foreach ($messages as $message) {
            // Normalize type names (danger -> error)
            $normalizedType = ($type === 'danger') ? 'error' : $type;
            
            $iconClass = '';
            switch($normalizedType) {
                case 'success':
                    $iconClass = 'fa-check-circle';
                    break;
                case 'error':
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
<div class="flash-message flash-message--<?= $normalizedType ?>" role="alert">
    <div class="flash-message__icon">
        <i class="fas <?= $iconClass ?>"></i>
    </div>
    <div class="flash-message__content">
        <?= htmlspecialchars($message) ?>
    </div>
    <button type="button" class="flash-message__close" aria-label="Cerrar notificación">
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