<?php
// config/config.php

// --- Definiciones Fundamentales ---
// Para URLs en el navegador (sin /public)
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
if (substr($script_dir, -7) === '/public') {
    $base_url = substr($script_dir, 0, -7);
} else {
    $base_url = $script_dir;
}
define('BASE_URL', rtrim($base_url, '/'));
define('BASE_PUBLIC_URL', BASE_URL . '/public');

// Para rutas del sistema de archivos
define('ROOT_DIR', dirname(__DIR__));  // Directorio raíz del proyecto

// --- Información de la Aplicación ---
define('APP_NAME', 'B2B Conector');
define('APP_VERSION', '1.0.0');

// --- Configuración Regional y de Errores ---
date_default_timezone_set('America/Mexico_City'); // Establece la zona horaria
// Configuración de errores (recomendado: 0 en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Cambiar a 0 en producción
ini_set('log_errors', 1);
ini_set('error_log', ROOT_DIR . '/logs/php_errors.log'); // Centralizar logs de error PHP

// --- Configuración de Sesiones (antes de session_start()) ---
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', 1); // Descomentar si usas HTTPS
ini_set('session.use_trans_sid', 0); // Deshabilitar paso de ID de sesión por URL
ini_set('session.gc_maxlifetime', 7200); // Duración de la sesión (2 horas)
session_set_cookie_params([
    'lifetime' => 7200,
    'path' => '/', // Asegura que la cookie sea válida para todo el sitio
    'domain' => $_SERVER['HTTP_HOST'], // O tu dominio específico
    'secure' => isset($_SERVER['HTTPS']), // True si es HTTPS
    'httponly' => true,
    'samesite' => 'Lax' // Protección CSRF
]);

// --- Directorios de la Aplicación (usando ROOT_DIR) ---
define('CONFIG_DIR', ROOT_DIR . '/config');
define('CONTROLLER_DIR', ROOT_DIR . '/controllers');
define('MODEL_DIR', ROOT_DIR . '/models');
define('VIEW_DIR', ROOT_DIR . '/views');
define('UTILS_DIR', ROOT_DIR . '/utils');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('UPLOAD_DIR', PUBLIC_DIR . '/uploads');
define('LOGO_DIR', UPLOAD_DIR . '/logos');
define('LOG_DIR', ROOT_DIR . '/logs'); // Directorio para logs de la aplicación

// --- Logger ---
define('LOG_LEVEL', 'DEBUG'); // Niveles: ERROR, WARNING, INFO, DEBUG

// --- Roles y Estados (sin cambios) ---
define('ROLE_ADMIN', 'admin');
define('ROLE_ORGANIZER', 'organizer');
define('ROLE_BUYER', 'buyer');
define('ROLE_SUPPLIER', 'supplier');
define('MATCH_STATUS_PENDING', 'pending');
define('MATCH_STATUS_ACCEPTED', 'accepted');
define('MATCH_STATUS_REJECTED', 'rejected');
define('APPOINTMENT_STATUS_SCHEDULED', 'scheduled');
define('APPOINTMENT_STATUS_COMPLETED', 'completed');
define('APPOINTMENT_STATUS_CANCELLED', 'cancelled');

// --- Parámetros de la Aplicación (sin cambios) ---
define('DEFAULT_MEETING_DURATION', 30);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('PASSWORD_MIN_LENGTH', 8);

// --- Configuración de Email ---
define('APP_EMAIL', 'noreply@' . $_SERVER['HTTP_HOST']); // Email por defecto del sistema
define('APP_EMAIL_NAME', APP_NAME); // Nombre que aparece en el remitente

// Configuración SMTP (definir según su proveedor de email)
define('SMTP_HOST', 'mail.adndelamanufactura.com'); // Ejemplo: smtp.gmail.com
define('SMTP_PORT', 465); // Puerto SMTP (587 para TLS, 465 para SSL)
define('SMTP_USERNAME', 'b2b_conector@adndelamanufactura.com'); // Usuario SMTP
define('SMTP_PASSWORD', '%.lRn%4Sra&-9ZJN'); // Contraseña SMTP
define('SMTP_ENCRYPTION', 'ssl'); // tls o ssl
define('SMTP_DEBUG', 0); // 0 = sin debug, 1 = errores, 2 = mensajes, 3 = verbose

// --- Base de Datos (Asegúrate que database.php se cargue después o aquí) ---
// require_once CONFIG_DIR . '/database.php'; // Podrías incluirlo aquí si prefieres
