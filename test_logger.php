<?php
// test_logger.php
// Incluir el archivo Logger.php
require_once __DIR__ .'/utils/Logger.php';

// Definir directorio de logs
$logDir = __DIR__ .'/logs';

// Inicializar el logger
$initResult = Logger::init($logDir);
echo "Inicialización del Logger: " . ($initResult ? "Exitosa" : "Fallida") . "\n";

// Intentar escribir algunos logs
$infoResult = Logger::info("Prueba de mensaje informativo");
echo "Escribir mensaje INFO: " . ($infoResult ? "Exitoso" : "Fallido") . "\n";

$debugResult = Logger::debug("Prueba de mensaje de depuración", ['contexto' => 'prueba', 'valor' => 123]);
echo "Escribir mensaje DEBUG con contexto: " . ($debugResult ? "Exitoso" : "Fallido") . "\n";

$errorResult = Logger::error("Prueba de mensaje de error");
echo "Escribir mensaje ERROR: " . ($errorResult ? "Exitoso" : "Fallido") . "\n";

// Intentar leer los logs
$todayLogs = Logger::getLogsByDate(date('Y-m-d'));
echo "Contenido del log de hoy: \n";
echo $todayLogs ? $todayLogs : "No se pudieron leer los logs";

// Información sobre permisos y directorios
echo "\n\nInformación de diagnóstico:\n";
echo "Directorio de logs: $logDir\n";
echo "Existe el directorio: " . (is_dir($logDir) ? "Sí" : "No") . "\n";
echo "Permisos de escritura: " . (is_writable($logDir) ? "Sí" : "No") . "\n";

// Información de errores
echo "\nÚltimo error:\n";
print_r(error_get_last());
?>