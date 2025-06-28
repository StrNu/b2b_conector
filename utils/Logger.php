<?php
/**
 * Clase Logger
 * 
 * Esta clase maneja todas las operaciones de registro (logging) en el sistema
 * para facilitar el seguimiento de errores y actividades importantes.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class Logger {
    // Niveles de log
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    
    // Directorio donde se guardarán los logs
    private static $logDir;
    
    // Formato del nombre del archivo de log
    private static $logFileFormat = 'Y-m-d';
    
    // Mensaje para cuando el directorio de logs no existe o no se puede escribir
    private static $dirErrorMessage = "El directorio de logs no existe o no tiene permisos de escritura";
    
    /**
     * Inicializar el logger
     *
     * @param string $logDir Directorio donde se guardarán los logs
     * @return bool True si se inicializó correctamente, false en caso contrario
     */
    public static function init($logDir) {
        self::$logDir = $logDir;
        
        // Crear el directorio si no existe
        if (!is_dir(self::$logDir)) {
            if (!@mkdir(self::$logDir, 0755, true)) {
                error_log(self::$dirErrorMessage . ": No se pudo crear el directorio");
                // Continuar en modo degradado (solo error_log)
                return true;
            }
        }
        
        // Verificar si se puede escribir en el directorio
        if (!is_writable(self::$logDir)) {
            error_log(self::$dirErrorMessage . ": Sin permisos de escritura");
            // Continuar en modo degradado (solo error_log)
            return true;
        }
        
        return true;
    }
    
    /**
     * Registrar un mensaje en el log
     *
     * @param string $message Mensaje a registrar
     * @param string $level Nivel del mensaje (ERROR, WARNING, INFO, DEBUG)
     * @param array $context Datos de contexto adicionales
     * @return bool True si se registró correctamente, false en caso contrario
     */
    public static function log($message, $level = self::INFO, $context = []) {
        if (!isset(self::$logDir)) {
            error_log('Logger no inicializado. Llame a Logger::init() primero.');
            return false;
        }
        
        // Obtener la fecha actual
        $date = date('Y-m-d H:i:s');
        
        // Formatear el contexto a JSON si es un array
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        
        // Formatear el mensaje de log
        $logMessage = "[$date] [$level] $message$contextStr" . PHP_EOL;
        
        // Construir el nombre del archivo de log (un archivo por día)
        $logFile = self::$logDir . '/' . date(self::$logFileFormat) . '.log';
        
        // Escribir en el archivo de log (intentar, pero no fallar si no se puede)
        $result = false;
        try {
            $result = @file_put_contents($logFile, $logMessage, FILE_APPEND) !== false;
        } catch (Exception $e) {
            // Silenciosamente continuar si no se puede escribir al archivo
        }
        
        // También enviar a error_log de PHP como respaldo
        error_log("[LOGGER] $logMessage");
        
        // Siempre retornar true para no interrumpir la aplicación
        return true;
    }
    
    /**
     * Registrar un mensaje de error
     *
     * @param string $message Mensaje de error
     * @param array $context Datos de contexto adicionales
     * @return bool True si se registró correctamente, false en caso contrario
     */
    public static function error($message, $context = []) {
        return self::log($message, self::ERROR, $context);
    }
    
    /**
     * Registrar un mensaje de advertencia
     *
     * @param string $message Mensaje de advertencia
     * @param array $context Datos de contexto adicionales
     * @return bool True si se registró correctamente, false en caso contrario
     */
    public static function warning($message, $context = []) {
        return self::log($message, self::WARNING, $context);
    }
    
    /**
     * Registrar un mensaje informativo
     *
     * @param string $message Mensaje informativo
     * @param array $context Datos de contexto adicionales
     * @return bool True si se registró correctamente, false en caso contrario
     */
    public static function info($message, $context = []) {
        return self::log($message, self::INFO, $context);
    }
    
    /**
     * Registrar un mensaje de depuración
     *
     * @param string $message Mensaje de depuración
     * @param array $context Datos de contexto adicionales
     * @return bool True si se registró correctamente, false en caso contrario
     */
    public static function debug($message, $context = []) {
        return self::log($message, self::DEBUG, $context);
    }
    
    /**
     * Registrar una excepción
     *
     * @param Exception $exception Excepción a registrar
     * @param array $context Datos de contexto adicionales
     * @return bool True si se registró correctamente, false en caso contrario
     */
    /*public static function exception(Exception $exception, $context = []) {
        $message = get_class($exception) . ': ' . $exception->getMessage() . ' en ' . $exception->getFile() . ':' . $exception->getLine();
        $contextWithTrace = array_merge($context, ['trace' => $exception->getTraceAsString()]);
        return self::error($message, $contextWithTrace);
    }*/

     /**
     * Registrar una excepción o error Throwable
     *
     * @param Throwable $exception Excepción o Error a registrar  <-- CAMBIO AQUÍ
     * @param array $context Datos de contexto adicionales
     * @return bool True si se registró correctamente, false en caso contrario
     */
    public static function exception(\Throwable $exception, $context = []) { // <-- CAMBIO AQUÍ (añade la barra invertida si no tienes un 'use Throwable;' al inicio del archivo)
        // El resto del código del método no necesita cambiar,
        // ya que get_class(), getMessage(), getFile(), getLine() y getTraceAsString()
        // están definidos en la interfaz Throwable y funcionan tanto para Exception como para Error.
        $message = get_class($exception) . ': ' . $exception->getMessage() . ' en ' . $exception->getFile() . ':' . $exception->getLine();
        $contextWithTrace = array_merge($context, ['trace' => $exception->getTraceAsString()]);
        return self::error($message, $contextWithTrace); // Sigue usando error() para el nivel de log
    }
    
    /**
     * Obtener todos los logs de una fecha específica
     *
     * @param string $date Fecha en formato Y-m-d
     * @return string|false Contenido del archivo de log o false si no existe
     */
    public static function getLogsByDate($date = null) {
        if (!isset(self::$logDir)) {
            error_log('Logger no inicializado. Llame a Logger::init() primero.');
            return false;
        }
        
        $date = $date ?: date(self::$logFileFormat);
        $logFile = self::$logDir . '/' . $date . '.log';
        
        if (!file_exists($logFile)) {
            return false;
        }
        
        return file_get_contents($logFile);
    }
    
    /**
     * Limpiar los logs antiguos (retener solo los últimos X días)
     *
     * @param int $daysToKeep Número de días de logs a conservar
     * @return int Número de archivos eliminados
     */
    public static function cleanOldLogs($daysToKeep = 30) {
        if (!isset(self::$logDir)) {
            error_log('Logger no inicializado. Llame a Logger::init() primero.');
            return 0;
        }
        
        $files = glob(self::$logDir . '/*.log');
        $now = time();
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileDate = basename($file, '.log');
                if (strtotime($fileDate) < strtotime("-$daysToKeep days")) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
}