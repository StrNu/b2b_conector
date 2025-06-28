<?php
// Layout para páginas de registro público
// NO requiere autenticación

// Cargar información del evento si está disponible
if (!isset($event) && isset($eventId)) {
    require_once MODEL_DIR . '/Event.php';
    $eventModel = new Event(Database::getInstance());
    if ($eventModel->findById($eventId)) {
        $event = $eventModel;
    }
}

include __DIR__ . '/../shared/header.php';
?>
<div class="app-grid public-layout">
    <?php include __DIR__ . '/../shared/event_header.php'; ?>
    
    <main class="public-main">
        <div class="main-container">
            <!-- Notificaciones -->
            <div class="notifications">
                <?php displayFlashMessages(); ?>
            </div>
            
            <!-- Contenido principal -->
            <div class="content-wrapper">
                <?= $content ?>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../shared/event_footer.php'; ?>
</div>

<style>
/* Estilos específicos para layout público */
.public-layout {
    min-height: 100vh;
    display: grid;
    grid-template-rows: auto 1fr auto;
}

.public-main {
    padding: 2rem 0;
    background: #ffffff;
}

.main-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.content-wrapper {
    background: #ffffff;
    border-radius: 0.75rem;
    overflow: hidden;
}

/* Responsive */
@media (max-width: 768px) {
    .public-main {
        padding: 1rem 0;
    }
    
    .main-container {
        padding: 0 0.5rem;
    }
}
</style>

<?php include __DIR__ . '/../shared/footer.php'; ?>