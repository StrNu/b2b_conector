<?php
/**
 * Manejador de errores personalizado
 * 
 * Este archivo configura un manejador de errores personalizado que
 * utilizará nuestro sistema de logs para registrar los errores de PHP.
 * 
 * @package B2B Conector
 * @version 1.0
 */

// Función para manejar errores
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_level = [
        E_ERROR             => 'ERROR',
        E_WARNING           => 'WARNING',
        E_PARSE             => 'ERROR',
        E_NOTICE            => 'INFO',
        E_CORE_ERROR        => 'ERROR',
        E_CORE_WARNING      => 'WARNING',
        E_COMPILE_ERROR     => 'ERROR',
        E_COMPILE_WARNING   => 'WARNING',
        E_USER_ERROR        => 'ERROR',
        E_USER_WARNING      => 'WARNING',
        E_USER_NOTICE       => 'INFO',
        E_STRICT            => 'INFO',
        E_RECOVERABLE_ERROR => 'ERROR',
        E_DEPRECATED        => 'INFO',
        E_USER_DEPRECATED   => 'INFO',
    ];
    
    $level = isset($error_level[$errno]) ? $error_level[$errno] : 'ERROR';
    
    // Si es un error de nivel DEBUG y LOG_LEVEL no es DEBUG, no lo registramos
    if ($level == 'INFO' && defined('LOG_LEVEL') && LOG_LEVEL != 'DEBUG' && LOG_LEVEL != 'INFO') {
        return true; // Continuar con la ejecución normal
    }
    
    // Registrar el error utilizando nuestro Logger
    $message = "$errstr en $errfile línea $errline";
    Logger::log($message, $level, ['errno' => $errno]);
    
    // Si es un error fatal, mostrar la página de error 500
    if ($level == 'ERROR') {
        http_response_code(500);
        include ROOT_DIR . '/views/errors/500.php';
        exit(1);
    }
    
    // Continuar con la ejecución normal para otros tipos de errores
    return true;
}

// Función para manejar excepciones no capturadas
function customExceptionHandler($exception) {
    Logger::exception($exception);
    
    http_response_code(500);
    include ROOT_DIR . '/views/errors/500.php';
    exit(1);
}

// Función para manejar errores fatales
function shutdownHandler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $message = $error['message'] . ' en ' . $error['file'] . ' línea ' . $error['line'];
        Logger::error($message, ['type' => $error['type']]);
        
        http_response_code(500);
        include ROOT_DIR . '/views/errors/500.php';
    }
}

// Establecer los manejadores personalizados
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('shutdownHandler');