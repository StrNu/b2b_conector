<?php
// Login con Material Design 3
require_once CONFIG_DIR . '/material-config.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- Header del login -->
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1 class="auth-title">Bienvenido</h1>
            <p class="auth-subtitle">Ingresa tus credenciales para acceder al panel de administración</p>
        </div>

        <!-- Flash Messages usando sistema Material Design 3 -->
        <?php if (isset($_SESSION['flash_messages'])): ?>
            <div class="flash-messages-container">
                <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="flash-message flash-message--<?= $type === 'danger' ? 'error' : $type ?>">
                            <div class="flash-message__icon">
                                <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                            </div>
                            <div class="flash-message__content"><?= $message ?></div>
                            <button type="button" class="flash-message__close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php unset($_SESSION['flash_messages']); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de login -->
        <form id="login-form" method="POST" action="<?= BASE_PUBLIC_URL ?>/auth/authenticate" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <!-- Campo Email/Usuario -->
            <div class="textfield-material">
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    class="textfield-material__input" 
                    placeholder=" "
                    value="<?= isset($_SESSION['form_data']['login']) ? htmlspecialchars($_SESSION['form_data']['login']) : '' ?>"
                    required
                >
                <label for="login" class="textfield-material__label">Email o Usuario</label>
                <div class="textfield-material__leading-icon">
                    <i class="fas fa-user"></i>
                </div>
                <?php if (isset($_SESSION['validation_errors']['login'])): ?>
                    <div class="textfield-material__error"><?= $_SESSION['validation_errors']['login'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Campo Contraseña -->
            <div class="textfield-material">
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="textfield-material__input" 
                    placeholder=" "
                    required
                >
                <label for="password" class="textfield-material__label">Contraseña</label>
                <div class="textfield-material__leading-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <button type="button" class="textfield-material__trailing-icon password-toggle" tabindex="-1">
                    <i class="fas fa-eye"></i>
                </button>
                <?php if (isset($_SESSION['validation_errors']['password'])): ?>
                    <div class="textfield-material__error"><?= $_SESSION['validation_errors']['password'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Recordarme y Olvidé contraseña -->
            <div class="auth-options">
                <label class="checkbox-material">
                    <input 
                        type="checkbox" 
                        name="remember_me" 
                        class="checkbox-material__input"
                        <?= isset($_SESSION['form_data']['remember_me']) ? 'checked' : '' ?>
                    >
                    <span class="checkbox-material__checkmark"></span>
                    <span class="checkbox-material__label">Recordarme</span>
                </label>
                
                <a href="<?= BASE_PUBLIC_URL ?>/auth/forgot-password" class="auth-link">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <!-- Botón de login -->
            <div class="auth-submit">
                <?= materialButton(
                    '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión',
                    'filled',
                    '',
                    'type="submit" id="login-button"'
                ) ?>
            </div>
        </form>

        <!-- Footer del login -->
        <div class="auth-footer">
            <a href="<?= BASE_PUBLIC_URL ?>" class="auth-link auth-link--secondary">
                <i class="fas fa-arrow-left"></i>
                Volver a la página principal
            </a>
        </div>
    </div>
</div>

<style>
/* Auth Material Design 3 styles */
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--md-primary-90) 0%, var(--md-secondary-90) 100%);
    padding: 2rem;
}

.auth-card {
    background: var(--md-surface-bright);
    border-radius: var(--md-shape-corner-extra-large);
    box-shadow: var(--md-elevation-3);
    padding: 3rem;
    width: 100%;
    max-width: 400px;
    position: relative;
    overflow: hidden;
}

.auth-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--md-primary-40) 0%, var(--md-secondary-40) 100%);
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-logo {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--md-primary-40) 0%, var(--md-secondary-40) 100%);
    border-radius: var(--md-shape-corner-full);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: white;
    font-size: 1.5rem;
}

.auth-title {
    font-size: 2rem;
    font-weight: 600;
    color: var(--md-on-surface);
    margin: 0 0 0.5rem 0;
    font-family: 'Montserrat', sans-serif;
}

.auth-subtitle {
    color: var(--md-on-surface-variant);
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.5;
}

.flash-messages-container {
    margin-bottom: 1.5rem;
}

.flash-message {
    background: var(--md-surface-container);
    border-radius: var(--md-shape-corner-medium);
    padding: 1rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-left: 4px solid;
    position: relative;
}

.flash-message--success {
    border-left-color: var(--md-success-40);
    background: var(--md-success-container);
    color: var(--md-on-success-container);
}

.flash-message--error {
    border-left-color: var(--md-error-40);
    background: var(--md-error-container);
    color: var(--md-on-error-container);
}

.flash-message--info {
    border-left-color: var(--md-info-40);
    background: var(--md-info-container);
    color: var(--md-on-info-container);
}

.flash-message__icon {
    flex-shrink: 0;
}

.flash-message__content {
    flex: 1;
    font-size: 0.875rem;
}

.flash-message__close {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: var(--md-shape-corner-small);
    opacity: 0.7;
    transition: opacity var(--md-motion-duration-short2);
}

.flash-message__close:hover {
    opacity: 1;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.textfield-material {
    position: relative;
    display: flex;
    align-items: center;
}

.textfield-material__input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--md-outline);
    border-radius: var(--md-shape-corner-small);
    background: var(--md-surface);
    color: var(--md-on-surface);
    font-size: 1rem;
    transition: all var(--md-motion-duration-short2);
}

.textfield-material__input:focus {
    outline: none;
    border-color: var(--md-primary-40);
    box-shadow: 0 0 0 1px var(--md-primary-40);
}

.textfield-material__input:focus + .textfield-material__label,
.textfield-material__input:not(:placeholder-shown) + .textfield-material__label {
    transform: translateY(-1.5rem) scale(0.75);
    color: var(--md-primary-40);
}

.textfield-material__label {
    position: absolute;
    left: 3rem;
    top: 50%;
    transform: translateY(-50%);
    background: var(--md-surface);
    padding: 0 0.5rem;
    color: var(--md-on-surface-variant);
    font-size: 1rem;
    transition: all var(--md-motion-duration-short2);
    pointer-events: none;
    z-index: 1;
}

.textfield-material__leading-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--md-on-surface-variant);
    z-index: 2;
}

.textfield-material__trailing-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--md-on-surface-variant);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: var(--md-shape-corner-small);
    transition: all var(--md-motion-duration-short2);
}

.textfield-material__trailing-icon:hover {
    background: var(--md-surface-container);
}

.textfield-material__error {
    position: absolute;
    bottom: -1.25rem;
    left: 3rem;
    color: var(--md-error-40);
    font-size: 0.75rem;
}

.auth-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.checkbox-material {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    user-select: none;
}

.checkbox-material__input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.checkbox-material__checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid var(--md-outline);
    border-radius: var(--md-shape-corner-extra-small);
    position: relative;
    transition: all var(--md-motion-duration-short2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.checkbox-material__input:checked + .checkbox-material__checkmark {
    background: var(--md-primary-40);
    border-color: var(--md-primary-40);
}

.checkbox-material__input:checked + .checkbox-material__checkmark::after {
    content: '✓';
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.checkbox-material__label {
    color: var(--md-on-surface);
    font-size: 0.875rem;
}

.auth-link {
    color: var(--md-primary-40);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: color var(--md-motion-duration-short2);
}

.auth-link:hover {
    color: var(--md-primary-30);
}

.auth-link--secondary {
    color: var(--md-on-surface-variant);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
}

.auth-submit {
    margin-top: 1rem;
}

.auth-submit .btn-material {
    width: 100%;
    justify-content: center;
    font-weight: 600;
    padding: 1rem 2rem;
}

.auth-footer {
    margin-top: 2rem;
    text-align: center;
    border-top: 1px solid var(--md-outline-variant);
    padding-top: 1.5rem;
}

/* Responsive */
@media (max-width: 480px) {
    .auth-container {
        padding: 1rem;
    }
    
    .auth-card {
        padding: 2rem;
    }
    
    .auth-title {
        font-size: 1.75rem;
    }
    
    .auth-options {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* Password toggle functionality */
.password-toggle.active i::before {
    content: '\f070'; /* fa-eye-slash */
}

/* Loading state for submit button */
.auth-form.loading .btn-material {
    opacity: 0.7;
    pointer-events: none;
}

.auth-form.loading .btn-material::after {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 0.5rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const passwordToggle = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('password');
    
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('active');
        });
    }
    
    // Form submission loading state
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            this.classList.add('loading');
        });
    }
    
    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function(message) {
        const closeBtn = message.querySelector('.flash-message__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                message.style.opacity = '0';
                message.style.transform = 'translateX(100%)';
                setTimeout(() => message.remove(), 300);
            });
        }
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            if (message.parentNode) {
                message.style.opacity = '0';
                message.style.transform = 'translateX(100%)';
                setTimeout(() => message.remove(), 300);
            }
        }, 5000);
    });
});
</script>