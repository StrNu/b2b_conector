<?php
// /var/www/html/b2b_conector/test_email.php

// Habilitar la visualización de errores para depuración directa en pantalla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "Iniciando prueba de envío de email...\n";

// Cargar configuración principal, autoloader y dependencias
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Logger.php';
require_once __DIR__ . '/utils/EmailService.php';

// Inicializar el Logger
// Este paso es crucial para que se puedan registrar los errores.
Logger::init(LOG_DIR);
Logger::info('--- INICIO DE PRUEBA DE EMAIL ---');

// --- ¡IMPORTANTE! CONFIGURACIÓN DE PRUEBA PARA SMTP ---
// Reemplaza estos valores con tus credenciales de SMTP reales.
// Si no están definidas en tu config.php, puedes definirlas aquí para la prueba.
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'mail.adndelamanufactura.com'); // Ej: smtp.gmail.com
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'contacto_b2b@adndelamanufactura.com');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', '%.lRn%4Sra&-9ZJN');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465); // Puerto común para SSL
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'ssl'); // 'tls' o 'ssl'

// Habilitar el modo debug de PHPMailer.
// Esto es clave para que PHPMailer escriba un log detallado de la conexión SMTP.
use PHPMailer\PHPMailer\SMTP;
if (!defined('SMTP_DEBUG')) define('SMTP_DEBUG', SMTP::DEBUG_SERVER);

// --- Fin de la configuración de prueba ---

// --- ¡IMPORTANTE! DIRECCIÓN DE DESTINO ---
// Cambia esto por una dirección de correo real a la que tengas acceso.
$testRecipient = 'mixhumx@gmail.com';

echo "Configuración de prueba cargada.\n";
echo "Intentando enviar email a: " . htmlspecialchars($testRecipient) . "\n";
echo "Revisa el archivo de log (ej: logs/2025-06-24.log) para ver la salida de depuración de SMTP.\n";

// Llamar al servicio de email
$subject = 'Prueba de envío de correo desde B2B Conector';
$message = '<h1>Email de prueba</h1><p>Si recibes este correo, la configuración de PHPMailer funciona correctamente.</p>';

$sent = EmailService::sendMail($testRecipient, $subject, $message);

if ($sent) {
    echo "\nEl método sendMail() devolvió: <strong>true</strong>.\n";
    echo "El correo fue aceptado para envío por PHPMailer. Revisa la bandeja de entrada de " . htmlspecialchars($testRecipient) . ".\n";
} else {
    echo "\nEl método sendMail() devolvió: <strong>false</strong>.\n";
    echo "Hubo un error al intentar enviar el correo. Revisa los logs para ver el error específico de SMTP.\n";
}

echo "\nPrueba finalizada.</pre>";
