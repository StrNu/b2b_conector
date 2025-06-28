<?php
// config/helpers.php

// Include Material Design helpers
if (file_exists(ROOT_DIR . '/config/material-config.php')) {
    require_once ROOT_DIR . '/config/material-config.php';
}

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
                // Normalize type names
                $normalizedType = ($type === 'danger') ? 'error' : $type;
                $cssClass = 'flash-message flash-message--' . $normalizedType;
                
                switch ($normalizedType) {
                    case 'success':
                        $iconClass = 'fas fa-check-circle';
                        break;
                    case 'error':
                        $iconClass = 'fas fa-exclamation-circle';
                        break;
                    case 'warning':
                        $iconClass = 'fas fa-exclamation-triangle';
                        break;
                    case 'info':
                        $iconClass = 'fas fa-info-circle';
                        break;
                    default:
                        $iconClass = 'fas fa-bell';
                        break;
                }
                
                echo '<div class="' . $cssClass . '">';
                echo '<div class="flash-message__icon">';
                echo '<i class="' . $iconClass . '"></i>';
                echo '</div>';
                echo '<div class="flash-message__content">' . htmlspecialchars($message) . '</div>';
                echo '<button class="flash-message__close" aria-label="Close notification">';
                echo '<i class="fas fa-times"></i>';
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

// === FUNCIONES DE AUTENTICACIÓN DE USUARIOS DE EVENTOS ===

/**
 * Verificar si un usuario de evento está autenticado
 * 
 * @return bool True si está autenticado, false en caso contrario
 */
function isEventUserAuthenticated() {
    $isAuth = isset($_SESSION['event_user_id']) && 
              isset($_SESSION['event_user_email']) && 
              isset($_SESSION['event_user_type']) &&
              isset($_SESSION['event_id']);
    
    Logger::debug('Verificando autenticación de evento: ' . ($isAuth ? 'usuario evento en sesión' : 'no hay usuario evento'), [
        'event_user_id' => $_SESSION['event_user_id'] ?? 'no_set',
        'event_id' => $_SESSION['event_id'] ?? 'no_set',
        'event_user_type' => $_SESSION['event_user_type'] ?? 'no_set'
    ]);
    
    return $isAuth;
}

/**
 * Obtener el ID del usuario de evento autenticado
 * 
 * @return int|null ID del usuario o null si no está autenticado
 */
function getEventUserId() {
    return isEventUserAuthenticated() ? $_SESSION['event_user_id'] : null;
}

/**
 * Obtener el email del usuario de evento autenticado
 * 
 * @return string|null Email del usuario o null si no está autenticado
 */
function getEventUserEmail() {
    return isEventUserAuthenticated() ? $_SESSION['event_user_email'] : null;
}

/**
 * Obtener el tipo de usuario de evento autenticado
 * 
 * @return string|null Tipo de usuario ('event_admin' o 'assistant') o null si no está autenticado
 */
function getEventUserType() {
    return isEventUserAuthenticated() ? $_SESSION['event_user_type'] : null;
}

/**
 * Obtener el ID del evento del usuario autenticado
 * 
 * @return int|null ID del evento o null si no está autenticado
 */
function getEventId() {
    $eventId = isEventUserAuthenticated() ? $_SESSION['event_id'] : null;
    Logger::debug("getEventId() retornando: " . ($eventId ?? 'null'));
    return $eventId;
}

/**
 * Cerrar sesión de usuario admin únicamente
 */
function logoutAdminUser() {
    // Solo limpiar variables de admin, mantener las de evento
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    unset($_SESSION['user_email']);
    Logger::info('Logout de usuario admin realizado');
}

/**
 * Cerrar todas las sesiones
 */
function logoutAllUsers() {
    session_destroy();
    session_start();
    Logger::info('Logout completo realizado');
}

/**
 * Verificar si hay conflicto de sesiones (usuario admin y evento a la vez)
 * 
 * @return array|null Array con información del conflicto o null si no hay conflicto
 */
function checkSessionConflict() {
    $isAdmin = isAuthenticated();
    $isEventUser = isEventUserAuthenticated();
    
    if ($isAdmin && $isEventUser) {
        return [
            'conflict' => true,
            'admin_user' => $_SESSION['username'] ?? 'Admin desconocido',
            'event_user' => $_SESSION['event_user_email'] ?? 'Usuario evento desconocido',
            'event_id' => $_SESSION['event_id'] ?? 'ID desconocido'
        ];
    }
    
    return null;
}

/**
 * Mostrar página de conflicto de sesiones
 * 
 * @param array $conflict Información del conflicto
 */
function showSessionConflictPage($conflict) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Conflicto de Sesiones - B2B Conector</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 40px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
            .warning h2 { margin: 0 0 15px 0; color: #e17055; }
            .session-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .btn-material { display: inline-flex; align-items: center; justify-content: center; padding: 12px 25px; margin: 10px 5px; text-decoration: none; border-radius: var(--md-shape-corner-medium, 8px); font-weight: 600; cursor: pointer; border: none; font-family: 'Poppins', sans-serif; transition: all 0.2s ease; gap: 0.5rem; }
            .btn-material--filled { background: var(--md-primary-40, #6750a4); color: var(--md-on-primary, white); }
            .btn-material--tonal { background: var(--md-secondary-container, #e8def8); color: var(--md-on-secondary-container, #1d192b); }
            .btn-material--outlined { background: transparent; color: var(--md-primary-40, #6750a4); border: 2px solid var(--md-outline, #79747e); }
            .btn-material--danger { background: var(--md-error-40, #ba1a1a); color: var(--md-on-error, white); }
            .btn-material:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
            .icon { font-size: 50px; text-align: center; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">⚠️</div>
            <div class="warning">
                <h2>Conflicto de Sesiones Detectado</h2>
                <p>Tienes múltiples sesiones activas simultáneamente. Por seguridad y para evitar confusiones, solo puedes tener una sesión activa a la vez.</p>
            </div>
            
            <div class="session-info">
                <h3>📊 Sesiones Activas:</h3>
                <ul>
                    <li><strong>👤 Usuario Admin:</strong> <?= htmlspecialchars($conflict['admin_user']) ?></li>
                    <li><strong>🎯 Usuario Evento:</strong> <?= htmlspecialchars($conflict['event_user']) ?> (Evento ID: <?= htmlspecialchars($conflict['event_id']) ?>)</li>
                </ul>
            </div>
            
            <h3>🔧 Selecciona qué sesión mantener:</h3>
            
            <form method="POST" style="text-align: center;">
                <input type="hidden" name="action" value="logout_event">
                <button type="submit" class="btn-material btn-material--filled">
                    👤 Mantener sesión ADMIN<br>
                    <small>(Cerrar sesión de evento)</small>
                </button>
            </form>
            
            <form method="POST" style="text-align: center;">
                <input type="hidden" name="action" value="logout_admin">
                <button type="submit" class="btn-material btn-material--tonal">
                    🎯 Mantener sesión EVENTO<br>
                    <small>(Cerrar sesión admin)</small>
                </button>
            </form>
            
            <form method="POST" style="text-align: center;">
                <input type="hidden" name="action" value="logout_all">
                <button type="submit" class="btn-material btn-material--danger">
                    🚪 Cerrar TODAS las sesiones<br>
                    <small>(Empezar desde cero)</small>
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 30px; color: #6c757d;">
                <small>Para evitar este problema en el futuro, cierra sesión antes de cambiar de tipo de usuario.</small>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Obtener el ID de la empresa del usuario autenticado (para asistentes)
 * 
 * @return int|null ID de la empresa o null si no está disponible
 */
function getEventUserCompanyId() {
    return isEventUserAuthenticated() && isset($_SESSION['company_id']) ? $_SESSION['company_id'] : null;
}

/**
 * Obtener el nombre del evento del usuario autenticado
 * 
 * @return string|null Nombre del evento o null si no está disponible
 */
function getEventName() {
    return isEventUserAuthenticated() && isset($_SESSION['event_name']) ? $_SESSION['event_name'] : null;
}

/**
 * Verificar si el usuario de evento tiene un rol específico
 * 
 * @param string|array $roles Rol o array de roles permitidos
 * @return bool True si tiene el rol, false en caso contrario
 */
function hasEventRole($roles) {
    if (!isEventUserAuthenticated()) {
        return false;
    }
    
    $userType = getEventUserType();
    
    if (is_array($roles)) {
        return in_array($userType, $roles);
    }
    
    return $userType === $roles;
}

/**
 * Verificar si el usuario es administrador de evento
 * 
 * @return bool True si es administrador de evento, false en caso contrario
 */
function isEventAdmin() {
    return hasEventRole('event_admin');
}

/**
 * Verificar si el usuario es asistente de evento
 * 
 * @return bool True si es asistente de evento, false en caso contrario
 */
function isEventAssistant() {
    return hasEventRole('assistant');
}

/**
 * Cerrar sesión de usuario de evento
 * 
 * @return void
 */
function logoutEventUser() {
    // Limpiar variables de sesión relacionadas con eventos
    $eventSessionKeys = [
        'event_user_id',
        'event_user_email', 
        'event_user_type',
        'event_id',
        'company_id',
        'event_name'
    ];
    
    foreach ($eventSessionKeys as $key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    Logger::info('Usuario de evento cerró sesión');
}

/**
 * Redirigir si el usuario de evento no está autenticado
 * 
 * @param string $redirectUrl URL de redirección (por defecto al login de eventos)
 * @return void
 */
function requireEventAuth($redirectUrl = null) {
    if (!isEventUserAuthenticated()) {
        $redirectUrl = $redirectUrl ?: BASE_URL . '/auth/event-login';
        setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'warning');
        redirect($redirectUrl);
        exit;
    }
}

/**
 * Redirigir si el usuario de evento no tiene los roles requeridos
 * 
 * @param string|array $requiredRoles Roles requeridos
 * @param string $redirectUrl URL de redirección
 * @return void
 */
function requireEventRole($requiredRoles, $redirectUrl = null) {
    requireEventAuth(); // Primero verificar autenticación
    
    if (!hasEventRole($requiredRoles)) {
        $redirectUrl = $redirectUrl ?: BASE_URL . '/event-dashboard';
        setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
        redirect($redirectUrl);
        exit;
    }
}

/**
 * Incluir header apropiado según el tipo de usuario
 * 
 * @param array $options Opciones para el header (pageTitle, moduleCSS, moduleJS, breadcrumbs)
 * @return void
 */
function includeAppropriateHeader($options = []) {
    // Configurar variables por defecto
    $pageTitle = $options['pageTitle'] ?? 'B2B Conector';
    $moduleCSS = $options['moduleCSS'] ?? 'main';
    $moduleJS = $options['moduleJS'] ?? 'main';
    $breadcrumbs = $options['breadcrumbs'] ?? null;
    
    if (isEventUserAuthenticated()) {
        // Usuario de evento - usar header específico de eventos
        $eventId = getEventId();
        if ($eventId) {
            // Cargar información del evento
            require_once MODEL_DIR . '/Event.php';
            $eventModel = new Event(Database::getInstance());
            if ($eventModel->findById($eventId)) {
                $event = $eventModel;
                include(VIEW_DIR . '/shared/event_header.php');
                return;
            }
        }
    }
    
    // Usuario normal - usar header principal
    include(VIEW_DIR . '/shared/header.php');
}

/**
 * Incluir footer apropiado según el tipo de usuario
 * 
 * @return void
 */
function includeAppropriateFooter() {
    if (isEventUserAuthenticated()) {
        // Usuario de evento - usar footer específico de eventos
        $eventId = getEventId();
        if ($eventId) {
            // Cargar información del evento si no está disponible
            if (!isset($event)) {
                require_once MODEL_DIR . '/Event.php';
                $eventModel = new Event(Database::getInstance());
                if ($eventModel->findById($eventId)) {
                    $event = $eventModel;
                }
            }
            if (isset($event)) {
                include(VIEW_DIR . '/shared/event_footer.php');
                return;
            }
        }
    }
    
    // Usuario normal - usar footer principal
    include(VIEW_DIR . '/shared/footer.php');
}
