<!-- views/auth/event_login.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Evento - B2B Conector</title>
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/modules/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .event-login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .event-login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        .event-login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #6610f2, #e83e8c);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-header .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .event-badge {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }
        
        .access-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .access-type {
            text-align: center;
            padding: 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .access-type.active {
            border-color: #007bff;
            background: #e7f3ff;
        }
        
        .access-type i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
            color: #007bff;
        }
        
        .access-type span {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        
        .back-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .back-links a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        
        .back-links a:hover {
            color: #007bff;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .notification {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .notification-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .notification-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="event-login-container">
        <div class="event-login-card">
            <div class="login-header">
                <h1><i class="fas fa-calendar-alt"></i> Acceso de Evento</h1>
                <p class="subtitle">Acceso para administradores y asistentes de eventos</p>
                <?php if (isset($eventName)): ?>
                    <div class="event-badge">
                        <i class="fas fa-tag"></i>
                        <?= htmlspecialchars($eventName) ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php displayFlashMessages(); ?>

            <form id="event-login-form" method="POST" action="<?= BASE_URL ?>/auth/event-authenticate" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?php if (isset($eventId)): ?>
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                <?php endif; ?>
                
                <div class="access-types">
                    <div class="access-type active" data-type="event_admin">
                        <i class="fas fa-user-cog"></i>
                        <span>Administrador</span>
                    </div>
                    <div class="access-type" data-type="assistant">
                        <i class="fas fa-users"></i>
                        <span>Asistente</span>
                    </div>
                </div>
                
                <input type="hidden" name="user_type" id="user_type" value="event_admin">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                        value="<?= isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : '' ?>"
                        placeholder="Ingrese su email de acceso"
                        required>
                    <?php if (isset($_SESSION['validation_errors']['email'])): ?>
                        <div class="error-message"><?= $_SESSION['validation_errors']['email'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" 
                            placeholder="Ingrese su contraseña"
                            required>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($_SESSION['validation_errors']['password'])): ?>
                        <div class="error-message"><?= $_SESSION['validation_errors']['password'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Acceder al Evento
                    </button>
                </div>
            </form>

            <div class="back-links">
                <a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                <a href="<?= BASE_URL ?>/auth/login"><i class="fas fa-user-shield"></i> Admin Sistema</a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
            
            // Handle access type selection
            const accessTypes = document.querySelectorAll('.access-type');
            const userTypeInput = document.getElementById('user_type');
            
            accessTypes.forEach(type => {
                type.addEventListener('click', function() {
                    // Remove active class from all
                    accessTypes.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked
                    this.classList.add('active');
                    
                    // Update hidden input
                    const userType = this.getAttribute('data-type');
                    userTypeInput.value = userType;
                    
                    // Update placeholder text based on type
                    const emailInput = document.getElementById('email');
                    if (userType === 'event_admin') {
                        emailInput.placeholder = 'Email del administrador del evento';
                    } else {
                        emailInput.placeholder = 'Email del asistente registrado';
                    }
                });
            });
            
            // Form validation
            const form = document.getElementById('event-login-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const email = document.getElementById('email').value.trim();
                    const password = document.getElementById('password').value.trim();
                    
                    if (!email || !password) {
                        e.preventDefault();
                        alert('Por favor, complete todos los campos requeridos.');
                        return false;
                    }
                    
                    // Show loading state
                    const submitBtn = form.querySelector('.btn-login');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
                    submitBtn.disabled = true;
                });
            }
        });
    </script>
</body>
</html>