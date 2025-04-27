<?php
// Asegurar que la sesión está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}

// Obtener el nombre de usuario si está disponible
$userName = $_SESSION['name'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - B2B Conector' : 'B2B Conector' ?></title>
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/normalize.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="<?= BASE_PUBLIC_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    <!-- CSS de componentes comunes -->
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/buttons.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/forms.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components/notifications.css">

    <!-- CSS específicos de módulo -->
    <?php if (isset($moduleCSS)): ?>
        <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/modules/<?= $moduleCSS ?>.css">
    <?php endif; ?>

    <!-- CSS adicionales específicos -->
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/<?= $css ?>">
        <?php endforeach; ?>
<?php endif; ?>
</head>
<body>
<?php
echo 'BASE_URL: ' . BASE_PUBLIC_URL . '<br>';
?>
    <div class="app-container">
        <!-- Header principal -->
        <header class="main-header">
            <div class="header-logo">
                <a href="<?= BASE_URL ?>/dashboard/">
                    <span class="logo-text">B2B Admin</span>
                </a>
            </div>
            <div class="header-menu">
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <button class="user-menu-btn">
                        <span class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </span>
                        <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown">
                        <ul>
                            <li><a href="<?= BASE_URL ?>/users/profile.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                            <li><a href="<?= BASE_URL ?>/auth/change-password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                            <li class="divider"></li>
                            <li><a href="<?= BASE_URL ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="main-container">
            <!-- El sidebar se incluirá en otro archivo -->
            <?php if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
                <?php include_once(ROOT_DIR . '/views/shared/sidebar.php'); ?>
            <?php endif; ?>
            
            <main class="content">
                <!-- Contenedor para mensajes de notificación -->
                <?php include_once(ROOT_DIR . '/views/shared/notifications.php'); ?>
                
                <!-- Aquí irá el contenido específico de cada página -->