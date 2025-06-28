<?php
// Cambio de contraseña con Material Design 3
require_once CONFIG_DIR . '/material-config.php';
?>

<div class="auth-container">
    <div class="auth-card auth-card--change-password">
        <!-- Header -->
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-key"></i>
            </div>
            <h1 class="auth-title">Cambiar Contraseña</h1>
            <p class="auth-subtitle">Introduce tu nueva contraseña para actualizar tu cuenta</p>
        </div>

        <!-- Flash Messages -->
        <?php displayFlashMessages(); ?>

        <!-- Formulario de cambio de contraseña -->
        <form action="<?= BASE_URL ?>/auth/change_password_event" method="POST" class="auth-form" id="change-password-form">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? $_POST['email'] ?? '') ?>">
            
            <!-- Nueva contraseña -->
            <div class="textfield-material">
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="textfield-material__input" 
                    placeholder=" "
                    required
                    minlength="8"
                >
                <label for="new_password" class="textfield-material__label">Nueva Contraseña *</label>
                <div class="textfield-material__leading-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <button type="button" class="textfield-material__trailing-icon password-toggle" tabindex="-1">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="textfield-material__supporting-text">
                    Mínimo 8 caracteres, incluye mayúsculas, minúsculas y números
                </div>
            </div>

            <!-- Confirmar contraseña -->
            <div class="textfield-material">
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="textfield-material__input" 
                    placeholder=" "
                    required
                    minlength="8"
                >
                <label for="confirm_password" class="textfield-material__label">Repetir Nueva Contraseña *</label>
                <div class="textfield-material__leading-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <button type="button" class="textfield-material__trailing-icon password-toggle" tabindex="-1">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="textfield-material__error" id="password-mismatch" style="display: none;">
                    Las contraseñas no coinciden
                </div>
            </div>

            <!-- Indicador de fortaleza de contraseña -->
            <div class="password-strength">
                <div class="password-strength__label">Fortaleza de la contraseña:</div>
                <div class="password-strength__bar">
                    <div class="password-strength__progress" id="password-strength-progress"></div>
                </div>
                <div class="password-strength__text" id="password-strength-text">Muy débil</div>
            </div>

            <!-- Botones de acción -->
            <div class="auth-actions">
                <?= materialButton(
                    '<i class="fas fa-save"></i> Guardar Contraseña',
                    'filled',
                    '',
                    'type="submit" id="save-button"'
                ) ?>
                
                <?= materialButton(
                    '<i class="fas fa-arrow-left"></i> Cancelar',
                    'outlined',
                    '',
                    'type="button" onclick="history.back()"'
                ) ?>
            </div>
        </form>

        <!-- Consejos de seguridad -->
        <?= materialCard(
            '<i class="fas fa-shield-alt"></i> Consejos de Seguridad',
            '
            <ul class="security-tips">
                <li><i class="fas fa-check"></i> Usa al menos 8 caracteres</li>
                <li><i class="fas fa-check"></i> Incluye letras mayúsculas y minúsculas</li>
                <li><i class="fas fa-check"></i> Añade números y símbolos</li>
                <li><i class="fas fa-check"></i> Evita información personal</li>
                <li><i class="fas fa-check"></i> No reutilices contraseñas</li>
            </ul>',
            'outlined'
        ) ?>
    </div>
</div>

<style>
/* Change password specific styles */
.auth-card--change-password {
    max-width: 480px;
}

.auth-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.auth-actions .btn-material {
    flex: 1;
    min-width: 200px;
    justify-content: center;
}

.textfield-material__supporting-text {
    position: absolute;
    bottom: -1.25rem;
    left: 3rem;
    color: var(--md-on-surface-variant);
    font-size: 0.75rem;
    line-height: 1.2;
}

.password-strength {
    margin: 1rem 0;
    padding: 1rem;
    background: var(--md-surface-container-lowest);
    border-radius: var(--md-shape-corner-medium);
}

.password-strength__label {
    font-size: 0.875rem;
    color: var(--md-on-surface-variant);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.password-strength__bar {
    width: 100%;
    height: 6px;
    background: var(--md-outline-variant);
    border-radius: var(--md-shape-corner-full);
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.password-strength__progress {
    height: 100%;
    width: 0%;
    border-radius: var(--md-shape-corner-full);
    transition: all var(--md-motion-duration-medium1);
}

.password-strength__progress.weak {
    background: var(--md-error-40);
    width: 25%;
}

.password-strength__progress.fair {
    background: var(--md-warning-40);
    width: 50%;
}

.password-strength__progress.good {
    background: var(--md-info-40);
    width: 75%;
}

.password-strength__progress.strong {
    background: var(--md-success-40);
    width: 100%;
}

.password-strength__text {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.password-strength__text.weak {
    color: var(--md-error-40);
}

.password-strength__text.fair {
    color: var(--md-warning-40);
}

.password-strength__text.good {
    color: var(--md-info-40);
}

.password-strength__text.strong {
    color: var(--md-success-40);
}

.security-tips {
    list-style: none;
    padding: 0;
    margin: 0;
}

.security-tips li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: var(--md-on-surface);
}

.security-tips i {
    color: var(--md-success-40);
    font-size: 0.75rem;
}

/* Animation for password strength */
@keyframes strengthPulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.password-strength__progress.animating {
    animation: strengthPulse 1s ease-in-out;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .auth-actions {
        flex-direction: column;
    }
    
    .auth-actions .btn-material {
        min-width: auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.textfield-material__input');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('active');
        });
    });

    // Password strength checker
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthProgress = document.getElementById('password-strength-progress');
    const strengthText = document.getElementById('password-strength-text');
    const passwordMismatch = document.getElementById('password-mismatch');
    const saveButton = document.getElementById('save-button');

    function checkPasswordStrength(password) {
        let strength = 0;
        let label = 'Muy débil';
        let className = 'weak';

        // Length check
        if (password.length >= 8) strength += 1;
        if (password.length >= 12) strength += 1;

        // Character variety checks
        if (/[a-z]/.test(password)) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;

        // Determine strength level
        if (strength >= 6) {
            label = 'Muy fuerte';
            className = 'strong';
        } else if (strength >= 4) {
            label = 'Fuerte';
            className = 'good';
        } else if (strength >= 3) {
            label = 'Aceptable';
            className = 'fair';
        } else if (strength >= 1) {
            label = 'Débil';
            className = 'weak';
        }

        return { strength, label, className };
    }

    function updatePasswordStrength() {
        const password = newPasswordInput.value;
        const result = checkPasswordStrength(password);
        
        // Remove all strength classes
        strengthProgress.className = 'password-strength__progress';
        strengthText.className = 'password-strength__text';
        
        if (password) {
            strengthProgress.classList.add(result.className, 'animating');
            strengthText.classList.add(result.className);
            strengthText.textContent = result.label;
            
            // Remove animation class after animation completes
            setTimeout(() => {
                strengthProgress.classList.remove('animating');
            }, 1000);
        } else {
            strengthText.textContent = 'Muy débil';
        }
    }

    function checkPasswordMatch() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword && password !== confirmPassword) {
            passwordMismatch.style.display = 'block';
            confirmPasswordInput.style.borderColor = 'var(--md-error-40)';
            return false;
        } else {
            passwordMismatch.style.display = 'none';
            confirmPasswordInput.style.borderColor = 'var(--md-outline)';
            return true;
        }
    }

    // Event listeners
    newPasswordInput.addEventListener('input', function() {
        updatePasswordStrength();
        if (confirmPasswordInput.value) {
            checkPasswordMatch();
        }
    });

    confirmPasswordInput.addEventListener('input', checkPasswordMatch);

    // Form submission validation
    document.getElementById('change-password-form').addEventListener('submit', function(e) {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            passwordMismatch.style.display = 'block';
            B2B.showNotification('Las contraseñas no coinciden', 'error');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            B2B.showNotification('La contraseña debe tener al menos 8 caracteres', 'error');
            return false;
        }
        
        // Add loading state
        this.classList.add('loading');
        saveButton.disabled = true;
    });

    // Initialize
    updatePasswordStrength();
});
</script>