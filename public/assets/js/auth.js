// assets/js/auth.js

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.querySelector('.toggle-password');
    
    // Validación de formulario
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validar email
            if (!emailInput.value.trim()) {
                showError(emailInput, 'El email es obligatorio');
                isValid = false;
            } else if (!isValidEmail(emailInput.value.trim())) {
                showError(emailInput, 'Ingrese un email válido');
                isValid = false;
            } else {
                clearError(emailInput);
            }
            
            // Validar contraseña
            if (!passwordInput.value) {
                showError(passwordInput, 'La contraseña es obligatoria');
                isValid = false;
            } else {
                clearError(passwordInput);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Mostrar/ocultar contraseña
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Cambiar ícono
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
    
    // Validar formato de email
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    // Mostrar mensaje de error
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        let errorElement = formGroup.querySelector('.error-message');
        
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            formGroup.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        input.classList.add('is-invalid');
    }
    
    // Limpiar mensaje de error
    function clearError(input) {
        const formGroup = input.closest('.form-group');
        const errorElement = formGroup.querySelector('.error-message');
        
        if (errorElement) {
            errorElement.textContent = '';
        }
        
        input.classList.remove('is-invalid');
    }
});