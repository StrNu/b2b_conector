<!-- views/auth/login.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - B2B Conector</title>
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/auth.css">
    <?php
    error_log('[login.php] Vista de login cargada');
    echo '<link rel="stylesheet" href="' . BASE_PUBLIC_URL . '/assets/css/auth.css">';
    ?>
</head>
<body>
    <div class="app-container">
        <header class="main-header">
            <div class="header-logo">
                <a href="<?= BASE_PUBLIC_URL ?>">
                    <img src="<?= BASE_PUBLIC_URL ?>/assets/images/logo.png" alt="B2B Conector">
                    <span class="logo-text">B2B Conector</span>
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?= BASE_PUBLIC_URL ?>">Home</a></li>
                    <li><a href="<?= BASE_PUBLIC_URL ?>/events">Events</a></li>
                    <li><a href="<?= BASE_PUBLIC_URL ?>/about">About</a></li>
                    <li><a href="<?= BASE_PUBLIC_URL ?>/contact">Contact</a></li>
                </ul>
            </nav>
        </header>

        <main class="login-container">
            <div class="login-card">
                <h1>Admin Login</h1>
                <p class="login-subtitle">Ingresa tus credenciales para acceder al panel de administración</p>

                <?php if (isset($_SESSION['flash_messages'])): ?>
                    <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="notification notification-<?= $type ?>">
                                <div class="notification-icon">
                                    <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <?= $message ?>
                                </div>
                                <button type="button" class="notification-close">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['flash_messages']); ?>
                <?php endif; ?>

                <!-- CAMBIO: Actualizado action a authenticate en lugar de login -->
                <form id="login-form" method="POST" action="<?= BASE_PUBLIC_URL ?>/auth/authenticate" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <!-- CAMBIO: Actualizado name a login en lugar de email -->
                    <div class="form-group">
                        <label for="login">Email o Usuario</label>
                        <input type="text" id="login" name="login" class="form-control" 
                            value="<?= isset($_SESSION['form_data']['login']) ? htmlspecialchars($_SESSION['form_data']['login']) : '' ?>"
                            required>
                        <?php if (isset($_SESSION['validation_errors']['login'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['login'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <button type="button" class="toggle-password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($_SESSION['validation_errors']['password'])): ?>
                            <div class="error-message"><?= $_SESSION['validation_errors']['password'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group remember-me">
                        <label>
                            <input type="checkbox" name="remember_me" <?= isset($_SESSION['form_data']['remember_me']) ? 'checked' : '' ?>>
                            Recordarme
                        </label>
                        <a href="<?= BASE_PUBLIC_URL ?>/auth/forgot-password" class="forgot-password">¿Olvidaste tu contraseña?</a>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>
                    </div>
                </form>

                <div class="back-to-home">
                    <a href="<?= BASE_PUBLIC_URL ?>">Volver a la página principal</a>
                </div>
            </div>
        </main>

        <footer class="main-footer">
            <div class="footer-content">
                <div class="footer-copyright">
                    &copy; <?= date('Y') ?> B2B Connector. All rights reserved.
                </div>
                <div class="footer-links">
                    <a href="<?= BASE_PUBLIC_URL ?>/privacy">Privacy</a>
                    <a href="<?= BASE_PUBLIC_URL ?>/terms">Terms</a>
                    <a href="<?= BASE_PUBLIC_URL ?>/cookies">Cookies</a>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_PUBLIC_URL ?>/assets/js/auth.js"></script>
</body>
</html>