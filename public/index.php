<?php
// public/index.php

// --- 1. Bootstrap Esencial ---
// Cargar configuración principal (define constantes como ROOT_DIR, LOG_DIR)
require_once __DIR__ . '/../config/config.php'; // Usa __DIR__ para ruta absoluta

// Cargar la clase Logger (usa la constante UTILS_DIR definida en config)
require_once UTILS_DIR . '/Logger.php';

// --- 2. Inicializar Logger TEMPRANO ---
// Usa la constante LOG_DIR (ruta absoluta) y verifica el resultado
if (!Logger::init(LOG_DIR)) {
    // Falla crítica: el logger no se pudo inicializar.
    // Registra en el error_log de PHP ya que nuestro logger falló.
    error_log("CRITICAL ERROR: Logger initialization failed for directory: " . LOG_DIR);
    // En lugar de morir, continuar sin logging (modo de emergencia)
    // Esto permite que la aplicación funcione aunque el logging no esté disponible
}

// Ahora que el Logger funciona, podemos usarlo
Logger::info('Application bootstrap started.');

// --- 3. Cargar Componentes Restantes ---
// Cargar manejador de errores personalizado (si existe y gestiona display_errors)
require_once UTILS_DIR . '/error_handler.php'; // Asegúrate que este archivo exista y funcione
require_once CONFIG_DIR . '/database.php';
require_once CONFIG_DIR . '/helpers.php'; // Asumiendo que helpers.php está en config
require_once UTILS_DIR . '/Security.php';
require_once UTILS_DIR . '/Validator.php';

// --- 4. Iniciar Sesión ---
// Las configuraciones de sesión ya se hicieron en config.php
session_start();
Logger::debug('Session started. Session ID: ' . session_id());

// --- DEBUG: Log de sesión y CSRF ---
if (isset($_SESSION)) {
    Logger::debug('[DEBUG] session_id: ' . session_id() . ' | csrf_token: ' . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '[NO CSRF TOKEN]'));
} else {
    Logger::debug('[DEBUG] session_id: ' . session_id() . ' | $_SESSION no está definido');
}

// --- MIDDLEWARE: Verificar conflictos de sesión ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Manejar acciones de resolución de conflictos
    switch ($_POST['action']) {
        case 'logout_admin':
            logoutAdminUser();
            break;
        case 'logout_event':
            logoutEventUser();
            break;
        case 'logout_all':
            logoutAllUsers();
            break;
    }
    // Redirigir a la misma página sin POST
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Verificar conflictos de sesión (excepto en páginas de auth y logout)
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$excludePaths = ['/auth/', '/logout', '/api/', '/ajax/'];
$shouldCheckConflict = true;

foreach ($excludePaths as $exclude) {
    if (strpos($currentPath, $exclude) !== false) {
        $shouldCheckConflict = false;
        break;
    }
}

if ($shouldCheckConflict) {
    $conflict = checkSessionConflict();
    if ($conflict) {
        showSessionConflictPage($conflict);
    }
}

// --- 5. Routing ---
// Procesar la URL
$rawUrl = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$url = filter_var($rawUrl, FILTER_SANITIZE_URL);
$urlParts = explode('/', $url);

// --- Routing especial para /events/companies/{event_id}/(view|edit|delete|full_registration)/{company_id} ---
if (
    isset($urlParts[0], $urlParts[1], $urlParts[2], $urlParts[3], $urlParts[4]) &&
    $urlParts[0] === 'events' &&
    $urlParts[1] === 'companies' &&
    is_numeric($urlParts[2]) &&
    in_array($urlParts[3], ['view', 'edit', 'delete', 'full_registration']) &&
    is_numeric($urlParts[4])
) {
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Break.php';
    require_once MODEL_DIR . '/Assistant.php';
    require_once MODEL_DIR . '/Appointment.php';
    require_once MODEL_DIR . '/Requirement.php';
    require_once MODEL_DIR . '/AttendanceDay.php';
    require_once MODEL_DIR . '/User.php';
    require_once CONTROLLER_DIR . '/EventController.php';
    $controllerInstance = new EventController();
    $eventId = (int)$urlParts[2];
    $actionType = $urlParts[3];
    $companyId = (int)$urlParts[4];
    if ($actionType === 'view') {
        $controllerInstance->viewCompany($eventId, $companyId);
        exit;
    } elseif ($actionType === 'edit') {
        $controllerInstance->editCompany($eventId, $companyId);
        exit;
    } elseif ($actionType === 'delete') {
        $controllerInstance->deleteCompany($eventId, $companyId);
        exit;
    } elseif ($actionType === 'full_registration') {
        $controllerInstance->viewFullRegistration($eventId, $companyId);
        exit;
    }
}

// --- Routing para alta de empresa desde evento ---
if (
    isset($urlParts[0], $urlParts[1], $urlParts[2], $urlParts[3]) &&
    $urlParts[0] === 'events' &&
    $urlParts[1] === 'companies' &&
    is_numeric($urlParts[2]) &&
    $urlParts[3] === 'create-company'
) {
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Category.php'; // Asegura que la clase Category esté disponible
    require_once CONTROLLER_DIR . '/EventController.php';
    $controllerInstance = new EventController();
    $eventId = (int)$urlParts[2];
    $controllerInstance->createCompany($eventId);
    exit;
}

// --- Ajustar ruta: eliminar 'public' del inicio si existe ---
if (!empty($urlParts[0]) && $urlParts[0] === 'public') {
    Logger::debug("Adjusting route: Removing leading 'public' segment from URL parts.", ['original_parts' => $urlParts]);
    array_shift($urlParts); // Elimina 'public' del inicio del array
}

// --- Routing público para registro de compradores: /buyers_registration/{event_id} y /buyers_registration/{event_id}/store ---
if (
    isset($urlParts[0]) && $urlParts[0] === 'buyers_registration'
) {
    Logger::debug('Entrando a bloque buyers_registration', ['urlParts' => $urlParts]);
    // Si es buyers_registration/{event_id} o buyers_registration/{event_id}/store
    if (
        isset($urlParts[1]) && is_numeric($urlParts[1]) &&
        (!isset($urlParts[2]) || $urlParts[2] === 'store')
    ) {
        Logger::debug('Dentro del if de buyers_registration', ['urlParts' => $urlParts]);
        require_once __DIR__ . '/../models/Category.php';
        require_once CONTROLLER_DIR . '/RegistrationController.php';
        require_once MODEL_DIR . '/Event.php';
        require_once MODEL_DIR . '/Company.php';
        require_once MODEL_DIR . '/Requirement.php';
        require_once MODEL_DIR . '/AttendanceDay.php';
        require_once MODEL_DIR . '/Assistant.php';
        require_once MODEL_DIR . '/User.php';
        $controller = new RegistrationController();
        $eventId = (int)$urlParts[1];
        if (isset($urlParts[2]) && $urlParts[2] === 'store') {
            Logger::debug('buyers_registration: ejecutando storeBuyersRegistration', ['eventId' => $eventId]);
            $controller->storeBuyersRegistration($eventId);
        } else {
            Logger::debug('buyers_registration: ejecutando buyersRegistration', ['eventId' => $eventId]);
            $controller->buyersRegistration($eventId);
        }
        Logger::debug('Saliendo del bloque buyers_registration');
        exit;
    } else {
        Logger::warning('Ruta buyers_registration inválida', ['urlParts' => $urlParts]);
        header('HTTP/1.0 404 Not Found');
        echo 'Error 404: Ruta de registro de compradores no válida.';
        exit;
    }
}

// --- NUEVO: Bloque para rutas públicas adicionales ---
$publicViews = [
    'buyers_registration.php',
    'evento_page.php',
    'suppliers_registration.php',
    'events/assistant_login.php'
];
if (isset($urlParts[0]) && in_array($urlParts[0], $publicViews)) {
    // Cargar la vista pública directamente
    $viewFile = VIEW_DIR . '/' . $urlParts[0];
    if (file_exists($viewFile)) {
        include $viewFile;
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Error 404: Vista pública no encontrada.';
        exit;
    }
}

// --- Ruta para cambio de contraseña de compradores (evento) ---
if (isset($urlParts[0]) && $urlParts[0] === 'auth' && isset($urlParts[1]) && $urlParts[1] === 'change_password_event') {
    require_once CONTROLLER_DIR . '/AuthController.php';
    require_once MODEL_DIR . '/User.php';
    $controller = new AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->changePasswordEvent();
    } else {
        $controller->changePasswordEventForm();
    }
    exit;
}

// Determinar controlador, acción y parámetros
$controllerSlug = !empty($urlParts[0]) ? $urlParts[0] : 'auth'; // 'auth' como default
// Establecer la acción predeterminada según el controlador
if (!empty($urlParts[1])) {
    $action = $urlParts[1];
    // Convert kebab-case to snake_case for method names
    $action = str_replace('-', '_', $action);
} else {
    // Si es el controlador de autenticación, la acción predeterminada es 'login'
    // Para todos los demás controladores, la acción predeterminada es 'index'
    $action = ($controllerSlug === 'auth') ? 'login' : 'index';
}
$params = array_slice($urlParts, 2);

// Asegurar que $params sea siempre un array
if ($params === null) {
    $params = [];
}

Logger::info("=== ROUTING DEBUG START ===", [
    'raw_url' => $rawUrl,
    'sanitized_url' => $url,
    'url_parts' => $urlParts,
    'controller_slug' => $controllerSlug, 
    'action' => $action, 
    'params' => $params,
    'method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI']
]);

// --- Routing manual para generación de matches desde la vista de matches ---
if (
    isset($urlParts[0], $urlParts[1]) &&
    $urlParts[0] === 'matches' &&
    in_array($urlParts[1], ['generateForBuyer', 'generateForSupplier'])
) {
    require_once CONTROLLER_DIR . '/MatchController.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Event.php';
    $controller = new MatchController();
    if ($urlParts[1] === 'generateForBuyer') {
        $controller->generateForBuyer();
    } else {
        $controller->generateForSupplier();
    }
    return; // Cambia exit por return para evitar que el flujo continúe y cause el warning
}

// --- Routing manual para matches por otros criterios (VIEW unmatched_due_to_dates) ---
if (
    isset($urlParts[0], $urlParts[1]) &&
    $urlParts[0] === 'events' &&
    $urlParts[1] === 'buscarMatchesOtrosCriterios'
) {
    require_once CONTROLLER_DIR . '/EventController.php';
    $controller = new EventController();
    $controller->buscarMatchesOtrosCriterios();
    exit;
}

// --- Routing manual específico para events/matches/{event_id} ---
if (
    isset($urlParts[0], $urlParts[1], $urlParts[2]) &&
    $urlParts[0] === 'events' &&
    $urlParts[1] === 'matches' &&
    is_numeric($urlParts[2])
) {
    require_once CONTROLLER_DIR . '/MatchController.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Event.php';
    
    $controller = new MatchController();
    $eventId = (int)$urlParts[2];
    
    // Simular que viene como parámetro GET para el método index()
    $_GET['event_id'] = $eventId;
    
    Logger::debug('Routing events/matches/{event_id}', [
        'event_id' => $eventId,
        'url_parts' => $urlParts
    ]);
    
    $controller->index();
    exit;
}

// --- Routing manual para edición de categorías/subcategorías de evento ---
if (
    isset($urlParts[0], $urlParts[1], $urlParts[2], $urlParts[3]) &&
    $urlParts[0] === 'events' &&
    in_array($urlParts[1], ['editEventCategory', 'editEventSubcategory']) &&
    is_numeric($urlParts[2]) && is_numeric($urlParts[3])
) {
    require_once CONTROLLER_DIR . '/CategoryController.php';
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Subcategory.php';
    require_once MODEL_DIR . '/Event.php';
    $controller = new CategoryController();
    if ($urlParts[1] === 'editEventCategory') {
        $controller->editEventCategory((int)$urlParts[2], (int)$urlParts[3]);
    } else {
        $controller->editEventSubcategory((int)$urlParts[2], (int)$urlParts[3]);
    }
    exit;
}

// --- Routing manual para actualización de categorías/subcategorías de evento ---
if (
    isset($urlParts[0], $urlParts[1], $urlParts[2], $urlParts[3]) &&
    $urlParts[0] === 'events' &&
    in_array($urlParts[1], ['updateEventCategory', 'updateEventSubcategory']) &&
    is_numeric($urlParts[2]) && is_numeric($urlParts[3])
) {
    require_once CONTROLLER_DIR . '/CategoryController.php';
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Subcategory.php';
    require_once MODEL_DIR . '/Event.php';
    $controller = new CategoryController();
    if ($urlParts[1] === 'updateEventCategory') {
        $controller->editEventCategory((int)$urlParts[2], (int)$urlParts[3]);
    } else {
        $controller->editEventSubcategory((int)$urlParts[2], (int)$urlParts[3]);
    }
    exit;
}

// --- Routing manual para agregar/eliminar categorías y subcategorías de evento ---
if (
    isset($urlParts[0], $urlParts[1], $urlParts[2]) &&
    $urlParts[0] === 'events' &&
    in_array($urlParts[1], ['addEventCategory', 'addEventSubcategory', 'deleteEventCategory', 'deleteEventSubcategory'])
) {
    require_once CONTROLLER_DIR . '/CategoryController.php';
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Subcategory.php';
    require_once MODEL_DIR . '/Event.php';
    $controller = new CategoryController();
    if ($urlParts[1] === 'addEventCategory') {
        $controller->addEventCategory((int)$urlParts[2]);
    } elseif ($urlParts[1] === 'addEventSubcategory' && isset($urlParts[3])) {
        $controller->addEventSubcategory((int)$urlParts[2], (int)$urlParts[3]);
    } elseif ($urlParts[1] === 'deleteEventCategory' && isset($urlParts[3])) {
        $controller->deleteEventCategory((int)$urlParts[2], (int)$urlParts[3]);
    } elseif ($urlParts[1] === 'deleteEventSubcategory' && isset($urlParts[3])) {
        $controller->deleteEventSubcategory((int)$urlParts[2], (int)$urlParts[3]);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Error 404: Acción no encontrada.';
    }
    exit;
}

// Mapa de controladores (slug => NombreClase)
$controllerMap = [
    'auth' => 'AuthController',
    'dashboard' => 'DashboardController',
    'events' => 'EventController',
    'event' => 'EventController', // <-- Añadido para compatibilidad con rutas /event/*
    'companies' => 'CompanyController',
    'categories' => 'CategoryController',
    'matches' => 'MatchController',
    'appointments' => 'AppointmentController',
    'users' => 'UserController',
    'agendas' => 'AgendaController', // <-- Añadido para rutas /agendas
    'category_import' => 'CategoryImportController', // <-- Añadido para importación de categorías
    'event-dashboard' => 'EventDashboardController', // <-- Añadido para dashboard de eventos
    'event-admin' => 'EventAdminController', // <-- Nuevo sistema de layouts para event-admin
    // Añade más según sea necesario
];

// --- 6. Cargar Modelos Base y Verificar Autenticación ---
require_once MODEL_DIR . '/User.php'; // Modelo de usuario siempre necesario

$publicRoutes = [ // Rutas que NO requieren autenticación
    'auth/login',
    'auth/register',
    'auth/forgot', 
    'auth/recover',
    'auth/authenticate',
    'auth/event_login', // Permitir acceso al login de eventos
    'auth/event_authenticate', // Permitir acceso al proceso de autenticación de eventos
    // Permitir acceso público a cualquier buyers_registration
    // Esto permite /buyers_registration/{event_id} y /buyers_registration/{event_id}/store
    'buyers_registration',
    // Permitir acceso público a cualquier suppliers_registration
    // Esto permite /suppliers_registration/{event_id} y /suppliers_registration/{event_id}/store
    'suppliers_registration',
];

$publicPaths = [
    'assets/', // Para todos los archivos en la carpeta assets
    'public/',  // Para todos los archivos en la carpeta public
    'buyers_registration', // Asegura que cualquier ruta que empiece así sea pública
    'suppliers_registration', // Asegura que cualquier ruta que empiece así sea pública
];

// Antes de la comprobación de autenticación, permitir rutas que empiecen con buyers_registration o suppliers_registration
if (isset($urlParts[0]) && (strpos($urlParts[0], 'buyers_registration') === 0 || strpos($urlParts[0], 'suppliers_registration') === 0)) {
    $isPublicPath = true;
}

// --- Routing público para registro de proveedores: /suppliers_registration/{event_id} y /suppliers_registration/{event_id}/store ---
if (
    isset($urlParts[0]) && $urlParts[0] === 'suppliers_registration'
) {
    Logger::debug('Entrando a bloque suppliers_registration', ['urlParts' => $urlParts]);
    // Si es suppliers_registration/{event_id} o suppliers_registration/{event_id}/store
    if (
        isset($urlParts[1]) && is_numeric($urlParts[1]) &&
        (!isset($urlParts[2]) || $urlParts[2] === 'store')
    ) {
        Logger::debug('Dentro del if de suppliers_registration', ['urlParts' => $urlParts]);
        require_once __DIR__ . '/../models/Category.php';
        require_once CONTROLLER_DIR . '/RegistrationController.php';
        require_once MODEL_DIR . '/Event.php';
        require_once MODEL_DIR . '/Company.php';
        require_once MODEL_DIR . '/AttendanceDay.php';
        require_once MODEL_DIR . '/Assistant.php';
        require_once MODEL_DIR . '/User.php';
        $controller = new RegistrationController();
        $eventId = (int)$urlParts[1];
        if (isset($urlParts[2]) && $urlParts[2] === 'store') {
            Logger::debug('suppliers_registration: ejecutando storeSuppliersRegistration', ['eventId' => $eventId]);
            $controller->storeSuppliersRegistration($eventId);
        } else {
            Logger::debug('suppliers_registration: ejecutando suppliersRegistration', ['eventId' => $eventId]);
            $controller->suppliersRegistration($eventId);
        }
        Logger::debug('Saliendo del bloque suppliers_registration');
        exit;
    } else {
        Logger::warning('Ruta suppliers_registration inválida', ['urlParts' => $urlParts]);
        header('HTTP/1.0 404 Not Found');
        echo 'Error 404: Ruta de registro de proveedores no válida.';
        exit;
    }
}

// Verificar primero si la solicitud es para un recurso estático (css, js, images)
if (preg_match('/\.(css|js|jpe?g|png|gif|svg|ico|woff2?|ttf|eot)$/i', $url)) {
    // Definir posibles rutas para el archivo estático
    $possible_paths = [
        PUBLIC_DIR . '/' . $url,                // /public/archivo.ext
        PUBLIC_DIR . '/assets/' . $url,         // /public/assets/archivo.ext
        ROOT_DIR . '/assets/' . $url,           // /assets/archivo.ext
        PUBLIC_DIR . '/' . ltrim($url, '/')     // En caso de URLs con slash inicial
    ];
    
    // Registrar intento de acceso a recurso estático
    Logger::debug("Intentando servir recurso estático: " . $url, [
        'possible_paths' => $possible_paths
    ]);
    
    foreach ($possible_paths as $file_path) {
        if (file_exists($file_path)) {
            // Determinar el tipo MIME correcto
            $mime_types = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject'
            ];
            $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
            $content_type = $mime_types[$extension] ?? 'application/octet-stream';
            
            Logger::debug("Sirviendo archivo estático: " . $file_path);
            header('Content-Type: ' . $content_type);
            readfile($file_path);
            exit;
        }
    }
    
    // Si llegamos aquí, el archivo no existe
    Logger::warning("Archivo estático no encontrado: " . $url);
    header("HTTP/1.0 404 Not Found");
    echo "Error 404: Archivo estático no encontrado.";
    exit;
}

// Continuar con la lógica de enrutamiento normal para peticiones no estáticas
$currentRoute = $controllerSlug . '/' . $action;

$isPublicPath = false;
foreach ($publicPaths as $publicPath) {
    if (strpos($currentRoute, $publicPath) === 0) {
        $isPublicPath = true;
        break;
    }
}

Logger::debug('Comprobando autenticación para ruta: ' . $currentRoute, [
    'es_ruta_publica' => in_array($currentRoute, $publicRoutes),
    'estado_sesion' => isset($_SESSION['user_id']) ? 'autenticado' : 'no autenticado'
]);

// Verificar autenticación según el tipo de ruta
$requiresAuth = !in_array($currentRoute, $publicRoutes) && !$isPublicPath;
$isEventOnlyRoute = strpos($controllerSlug, 'event-dashboard') === 0 || strpos($currentRoute, 'event-dashboard') !== false;
$isEventsRoute = $controllerSlug === 'events';

if ($requiresAuth) {
    if ($isEventOnlyRoute) {
        // Para rutas de event-dashboard, solo verificar autenticación de eventos
        if (!isEventUserAuthenticated()) {
            Logger::warning('Event authentication required, redirecting to event login.', ['route' => $currentRoute]);
            setFlashMessage('Debe iniciar sesión como usuario de evento para acceder a esta sección.', 'danger');
            header('Location: ' . BASE_URL . '/auth/event-login');
            exit;
        }
    } else if ($isEventsRoute) {
        // Para rutas de events, verificar cualquier tipo de autenticación (admin o evento)
        if (!isAuthenticated() && !isEventUserAuthenticated()) {
            Logger::warning('Authentication required for events route, redirecting to login.', ['route' => $currentRoute]);
            setFlashMessage('Debe iniciar sesión para acceder a esta sección.', 'danger');
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    } else {
        // Para otras rutas, verificar autenticación principal
        if (!isAuthenticated()) {
            Logger::warning('Authentication required, redirecting to login.', ['route' => $currentRoute]);
            setFlashMessage('Debe iniciar sesión para acceder a esta sección.', 'danger');
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }
}

// --- 7. Despachar al Controlador ---
$controllerName = $controllerMap[$controllerSlug] ?? ucfirst($controllerSlug) . 'Controller';
$controllerFile = CONTROLLER_DIR . '/' . $controllerName . '.php';

Logger::info("=== CONTROLLER LOADING ===", [
    'controller_slug' => $controllerSlug,
    'controller_name' => $controllerName,
    'controller_file' => $controllerFile,
    'file_exists' => file_exists($controllerFile),
    'controller_map' => $controllerMap
]);

// Permitir /agendas/{event_id} como alias de /agendas/index/{event_id}
if ($controllerName === 'AgendaController' && isset($action) && is_numeric($action)) {
    array_unshift($params, $action); // Pone el event_id como primer parámetro
    $action = 'index';
}

// Manejo especial para rutas /event/companies sin event_id
if ($controllerName === 'EventController' && $action === 'companies' && empty($params)) {
    Logger::error("Ruta /event/companies accedida sin event_id", [
        'controller' => $controllerName,
        'action' => $action,
        'params' => $params,
        'url' => $url
    ]);
    header('HTTP/1.0 400 Bad Request');
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error 400 - Parámetros Faltantes</title>
    <style>body{font-family:Arial,sans-serif;margin:40px;background:#f8f9fa;}.error{background:#fff;padding:40px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}.error h1{color:#dc3545;margin:0 0 20px 0;}.error p{color:#6c757d;line-height:1.6;}.btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;margin-top:20px;}</style>
</head>
<body>
    <div class="error">
        <h1>Error 400 - Parámetros Faltantes</h1>
        <p>La ruta <code>/event/companies</code> requiere un ID de evento.</p>
        <p>Formato correcto: <code>/events/companies/{event_id}</code></p>
        <a href="' . BASE_URL . '/events" class="btn">← Volver a Eventos</a>
    </div>
</body>
</html>';
    exit;
}

// Cargar BaseController SIEMPRE (necesario para todos los controladores)
require_once CONTROLLER_DIR . '/BaseController.php';

// Cargar modelos adicionales según el controlador
if ($controllerName === 'EventController') {
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Assistant.php';
    require_once MODEL_DIR . '/Appointment.php';
    require_once MODEL_DIR . '/Requirement.php';
    require_once MODEL_DIR . '/AttendanceDay.php';
    require_once MODEL_DIR . '/User.php';
    require_once MODEL_DIR . '/Break.php';
} elseif ($controllerName === 'DashboardController') {
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Appointment.php';
    require_once MODEL_DIR . '/User.php';
    require_once MODEL_DIR . '/Category.php';
} elseif ($controllerName === 'CompanyController') {
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Appointment.php';
    require_once MODEL_DIR . '/Assistant.php';
} elseif ($controllerName === 'MatchController') {
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Appointment.php';
} elseif ($controllerName === 'AppointmentController') {
    require_once MODEL_DIR . '/Appointment.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Event.php';
} elseif ($controllerName === 'CategoryController') {
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Subcategory.php';
    require_once MODEL_DIR . '/Event.php';
} elseif ($controllerName === 'RegistrationController') {
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Requirement.php';
    require_once MODEL_DIR . '/AttendanceDay.php';
    require_once MODEL_DIR . '/Assistant.php';
    require_once MODEL_DIR . '/User.php';
} elseif ($controllerName === 'AgendaController') {
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Appointment.php';
} elseif ($controllerName === 'CategoryImportController') {
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Event.php';
} elseif ($controllerName === 'EventDashboardController') {
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Assistant.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Appointment.php';
} elseif ($controllerName === 'EventAdminController') {
    require_once MODEL_DIR . '/Event.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Appointment.php';
    require_once MODEL_DIR . '/Assistant.php';
    require_once MODEL_DIR . '/Category.php';
    require_once MODEL_DIR . '/Subcategory.php';
}


// Asegurarse que el archivo del controlador existe antes de incluirlo
if (file_exists($controllerFile)) {
    Logger::info("=== CONTROLLER FILE FOUND ===", [
        'controller_file' => $controllerFile,
        'including_file' => true
    ]);
    require_once $controllerFile;
    Logger::info("=== CONTROLLER FILE INCLUDED ===", [
        'controller_name' => $controllerName
    ]);
} else {
    Logger::error("=== CONTROLLER FILE NOT FOUND ===", [
        'controller_file' => $controllerFile,
        'controller_dir' => CONTROLLER_DIR,
        'dir_contents' => is_dir(CONTROLLER_DIR) ? scandir(CONTROLLER_DIR) : 'Directory not found'
    ]);
    header('HTTP/1.0 404 Not Found');
    echo 'Error 404: Controlador no encontrado.';
    exit;
}


// Crear instancia del controlador
Logger::info("=== CREATING CONTROLLER INSTANCE ===", [
    'controller_name' => $controllerName,
    'class_exists' => class_exists($controllerName)
]);

try {
    $controllerInstance = new $controllerName();
    Logger::info("=== CONTROLLER INSTANCE CREATED ===", [
        'controller_name' => $controllerName,
        'available_methods' => get_class_methods($controllerInstance)
    ]);
} catch (Exception $e) {
    Logger::error("=== ERROR CREATING CONTROLLER INSTANCE ===", [
        'controller_name' => $controllerName,
        'error_message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Error 500: Error creando instancia del controlador.';
    exit;
}

// Verificar si la acción existe en el controlador
if (!method_exists($controllerInstance, $action)) {
    Logger::error("=== ACTION NOT FOUND ===", [
        'action' => $action,
        'controller_name' => $controllerName,
        'available_methods' => get_class_methods($controllerInstance)
    ]);
    header('HTTP/1.0 404 Not Found');
    echo '[ERROR] Acción no encontrada: ' . $action;
    exit;
}

// Llamar a la acción del controlador con los parámetros
Logger::info("=== CALLING CONTROLLER ACTION ===", [
    'controller_name' => $controllerName,
    'action' => $action,
    'params' => $params,
    'params_count' => count($params)
]);

try {
    call_user_func_array([$controllerInstance, $action], $params);
    Logger::info("=== CONTROLLER ACTION COMPLETED ===", [
        'controller_name' => $controllerName,
        'action' => $action
    ]);
} catch (Exception $e) {
    Logger::error("=== ERROR IN CONTROLLER ACTION ===", [
        'controller_name' => $controllerName,
        'action' => $action,
        'error_message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Error 500: Error interno del servidor.';
    exit;
}

// Ruta especial para programar todas las citas de un evento
if (
    isset($urlParts[0], $urlParts[1]) &&
    $urlParts[0] === 'appointments' &&
    $urlParts[1] === 'scheduleAll'
) {
    require_once CONTROLLER_DIR . '/AppointmentController.php';
    $controller = new AppointmentController();
    $controller->scheduleAll();
    exit;
}

// --- Routing especial para endpoints AJAX tipo /controllers/MatchController.php?action=... ---
if (
    isset($_SERVER['SCRIPT_NAME']) &&
    strpos($_SERVER['SCRIPT_NAME'], '/controllers/MatchController.php') !== false &&
    isset($_GET['action'])
) {
    require_once MODEL_DIR . '/Match.php';
    require_once MODEL_DIR . '/Company.php';
    require_once MODEL_DIR . '/Event.php';
    require_once UTILS_DIR . '/Validator.php';
    require_once CONTROLLER_DIR . '/MatchController.php';
    $controller = new MatchController();
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Acción no encontrada']);
    }
    exit;
}

