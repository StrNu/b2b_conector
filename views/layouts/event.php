<?php
// Layout para usuarios de eventos
// Verificar autenticación de evento
if (!isEventUserAuthenticated()) {
    redirect(BASE_URL . '/auth/event-login');
    exit;
}

// Cargar información del evento
$eventId = getEventId();
if (!isset($event) && $eventId) {
    require_once MODEL_DIR . '/Event.php';
    $eventModel = new Event(Database::getInstance());
    if ($eventModel->findById($eventId)) {
        $event = $eventModel;
    }
}

// Establecer título por defecto si no está definido
if (!isset($title)) {
    $title = isset($pageTitle) ? $pageTitle : 'Dashboard de Eventos';
}

include __DIR__ . '/../shared/header.php';
?>
<div class="app-grid">
    <?php include __DIR__ . '/../shared/event_header.php'; ?>
    
    <main class="modern-main">
        <div class="main-container">
            <!-- Notificaciones -->
            <div class="notifications">
                <?php displayFlashMessages(); ?>
            </div>
            
            <!-- Contenido de la vista -->
            <div class="content-area">
                <?= $content ?>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../shared/event_footer.php'; ?>
</div>

<?php include __DIR__ . '/../shared/footer.php'; ?>