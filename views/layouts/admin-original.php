<?php
// Layout para administradores normales
include __DIR__ . '/../shared/header.php';
?>
<div class="app-grid">
    <?php include __DIR__ . '/../shared/admin_header.php'; ?>
    
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
    
    <?php include __DIR__ . '/../shared/admin_footer.php'; ?>
    <?php include __DIR__ . '/../shared/footer.php'; ?>