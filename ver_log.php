<?php
// ver_log.php - Script temporal para visualizar el log de hoy
// ¡Elimina este archivo después de usarlo por seguridad!

$logFile = __DIR__ . '/logs/2025-04-18.log';

if (!file_exists($logFile)) {
    echo "<b>No existe el archivo de log para hoy.</b>";
    exit;
}

// Opcional: protección básica por IP (descomenta para activar)
// $allowed_ip = 'TU_IP_AQUI';
// if ($_SERVER['REMOTE_ADDR'] !== $allowed_ip) {
//     http_response_code(403);
//     exit('Acceso denegado.');
// }

header('Content-Type: text/plain; charset=utf-8');
readfile($logFile);
