<?php
// config/helpers.php

/**
 * Verificar si el usuario está autenticado
 * 
 * @return bool True si el usuario está autenticado, false en caso contrario
 */
function isAuthenticated() {
    Logger::debug('Verificando autenticación: ' . (isset($_SESSION['user_id']) ? 'usuario en sesión' : 'no hay usuario en sesión'));
    return isset($_SESSION['user_id']);
}

/**
 * Verificar si el usuario tiene alguno de los roles especificados
 * 
 * @param array $allowedRoles Roles permitidos
 * @return bool True si el usuario tiene alguno de los roles, false en caso contrario
 */
function hasRole($allowedRoles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    return in_array($_SESSION['role'] ?? '', $allowedRoles);
}

/**
 * Redirigir a una URL específica
 * 
 * @param string $url URL de destino
 * @return void
 */
function redirect($url) {
    Logger::debug('Redirigiendo a: ' . $url);
    header('Location: ' . $url);
    exit;
}

/**
 * Sanitizar una cadena
 * 
 * @param string|array $input Cadena o array a sanitizar
 * @return string|array Cadena o array sanitizado
 */
function sanitize($input) {
    return Security::sanitize($input);
}

/**
 * Establecer un mensaje flash
 * 
 * @param string $message Mensaje
 * @param string $type Tipo de mensaje (success, danger, warning, info)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    if (!isset($_SESSION['flash_messages'][$type])) {
        $_SESSION['flash_messages'][$type] = [];
    }
    
    $_SESSION['flash_messages'][$type][] = $message;
    Logger::debug('Mensaje flash establecido: ' . $message . ' (tipo: ' . $type . ')');
}

/**
 * Generar un token CSRF
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    return Security::generateCSRFToken();
}

/**
 * Verificar un token CSRF
 * 
 * @param string $token Token CSRF
 * @return bool True si el token es válido, false en caso contrario
 */
function verifyCSRFToken($token) {
    return Security::verifyCSRFToken($token);
}

/**
 * Configurar la paginación
 * 
 * @param int $totalItems Total de items
 * @param int $currentPage Página actual
 * @param int $perPage Items por página
 * @return array Configuración de paginación
 */
function paginate($totalItems, $currentPage = 1, $perPage = 10) {
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

/**
 * Formatear fecha
 * 
 * @param string $date Fecha en formato Y-m-d
 * @param string $format Formato de salida
 * @return string Fecha formateada
 */
function formatDate($date, $format = 'd/m/Y') {
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * Formatear fecha y hora
 * 
 * @param string $datetime Fecha y hora en formato Y-m-d H:i:s
 * @param string $format Formato de salida
 * @return string Fecha y hora formateada
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    $dateTime = new DateTime($datetime);
    return $dateTime->format($format);
}

/**
 * Truncar texto
 * 
 * @param string $text Texto a truncar
 * @param int $length Longitud máxima
 * @param string $suffix Sufijo
 * @return string Texto truncado
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generar un slug a partir de un texto
 * 
 * @param string $text Texto
 * @return string Slug
 */
function slugify($text) {
    // Convertir a minúsculas
    $text = strtolower($text);
    
    // Reemplazar espacios y caracteres especiales
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    
    // Eliminar guiones al inicio y al final
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Obtener extensión de un archivo
 * 
 * @param string $filename Nombre del archivo
 * @return string Extensión
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Verificar si una extensión está permitida
 * 
 * @param string $extension Extensión
 * @param array $allowedExtensions Extensiones permitidas
 * @return bool True si la extensión está permitida, false en caso contrario
 */
function isAllowedExtension($extension, $allowedExtensions = ALLOWED_EXTENSIONS) {
    return in_array(strtolower($extension), $allowedExtensions);
}

/**
 * Generar un nombre de archivo único
 * 
 * @param string $originalName Nombre original
 * @return string Nombre único
 */
function generateUniqueFilename($originalName) {
    $extension = getFileExtension($originalName);
    return uniqid() . '.' . $extension;
}

/**
 * Mostrar mensajes flash
 * 
 * @return void
 */
function displayFlashMessages() {
    if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
        echo '<div class="flash-messages">';
        
        foreach ($_SESSION['flash_messages'] as $type => $messages) {
            foreach ($messages as $message) {
                echo '<div class="alert alert-' . $type . ' alert-dismissible fade show">';
                echo $message;
                echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
                echo '<span aria-hidden="true">&times;</span>';
                echo '</button>';
                echo '</div>';
            }
        }
        
        echo '</div>';
        
        // Limpiar los mensajes después de mostrarlos
        unset($_SESSION['flash_messages']);
    }
}

/**
 * Generar enlaces de paginación
 * 
 * @param array $pagination Información de paginación
 * @param string $baseUrl URL base para los enlaces de paginación
 * @return string HTML con los enlaces de paginación
 */
function paginationLinks($pagination, $baseUrl) {
    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];
    $output = '<nav aria-label="Paginación"><ul class="pagination justify-content-center">';
    
    // Botón anterior
    $prevDisabled = ($current <= 1) ? 'disabled' : '';
    $output .= '<li class="page-item ' . $prevDisabled . '">';
    $output .= '<a class="page-link" href="' . $baseUrl . ($current - 1) . '" aria-label="Anterior">';
    $output .= '<span aria-hidden="true">&laquo;</span>';
    $output .= '</a></li>';
    
    // Páginas
    $startPage = max(1, $current - 2);
    $endPage = min($total, $current + 2);
    
    // Siempre mostrar primera página
    if ($startPage > 1) {
        $output .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '1">1</a></li>';
        if ($startPage > 2) {
            $output .= '<li class="page-item disabled"><a class="page-link">...</a></li>';
        }
    }
    
    // Páginas intermedias
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = ($i == $current) ? 'active' : '';
        $output .= '<li class="page-item ' . $active . '">';
        $output .= '<a class="page-link" href="' . $baseUrl . $i . '">' . $i . '</a></li>';
    }
    
    // Siempre mostrar última página
    if ($endPage < $total) {
        if ($endPage < $total - 1) {
            $output .= '<li class="page-item disabled"><a class="page-link">...</a></li>';
        }
        $output .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $total . '">' . $total . '</a></li>';
    }
    
    // Botón siguiente
    $nextDisabled = ($current >= $total) ? 'disabled' : '';
    $output .= '<li class="page-item ' . $nextDisabled . '">';
    $output .= '<a class="page-link" href="' . $baseUrl . ($current + 1) . '" aria-label="Siguiente">';
    $output .= '<span aria-hidden="true">&raquo;</span>';
    $output .= '</a></li>';
    
    $output .= '</ul></nav>';
    return $output;
}

/**
 * Formatear una fecha de base de datos a formato legible
 * 
 * @param string $date Fecha en formato Y-m-d
 * @return string Fecha formateada
 */
function dateFromDatabase($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

/**
 * Convertir fecha del formato de visualización al formato de base de datos
 * 
 * @param string $date Fecha en formato dd/mm/yyyy
 * @return string Fecha en formato yyyy-mm-dd
 */
function dateToDatabase($date) {
    if (empty($date)) {
        return null;
    }
    
    // Intentar convertir la fecha
    $dateTime = DateTime::createFromFormat('d/m/Y', $date);
    
    // Si falló, intentar otro formato común
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        
        // Si es este formato, ya está en formato de base de datos
        if ($dateTime) {
            return $date;
        }
        
        // Intenta con más formatos si es necesario
        $formats = ['d-m-Y', 'Y/m/d', 'm/d/Y'];
        
        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $date);
            if ($dateTime) {
                break;
            }
        }
        
        // Si sigue sin poder convertir
        if (!$dateTime) {
            // Registra error pero devuelve la fecha original para evitar problemas
            Logger::warning('No se pudo convertir la fecha: ' . $date);
            return $date;
        }
    }
    
    return $dateTime->format('Y-m-d');
}
