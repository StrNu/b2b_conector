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
    // Muestra un mensaje genérico al usuario o redirige a una página de error.
    // Evita mostrar detalles sensibles como la ruta del servidor.
    die("Error crítico del sistema. No se pudo inicializar el sistema de registro. Contacte al administrador.");
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

// --- 5. Routing ---
// Procesar la URL
$rawUrl = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$url = filter_var($rawUrl, FILTER_SANITIZE_URL);
$urlParts = explode('/', $url);

// Determinar controlador, acción y parámetros
if (!empty($urlParts[0]) && $urlParts[0] === 'public') {
    Logger::debug("Adjusting route: Removing leading 'public' segment from URL parts.", ['original_parts' => $urlParts]);
    array_shift($urlParts); // Elimina 'public' del inicio del array
}

$controllerSlug = !empty($urlParts[0]) ? $urlParts[0] : 'auth'; // 'auth' como default
// Establecer la acción predeterminada según el controlador
if (!empty($urlParts[1])) {
    $action = $urlParts[1];
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

Logger::debug("Routing request", [
    'controller' => $controllerSlug, 
    'action' => $action, 
    'params' => $params,
    'original_url' => $url,
    'method' => $_SERVER['REQUEST_METHOD']
]);

// Mapa de controladores (slug => NombreClase)
$controllerMap = [
    'auth' => 'AuthController',
    'dashboard' => 'DashboardController',
    'events' => 'EventController',
    'companies' => 'CompanyController',
    'categories' => 'CategoryController',
    'matches' => 'MatchController',
    'appointments' => 'AppointmentController',
    'users' => 'UserController'
    // Añade más según sea necesario
];

$controllerName = $controllerMap[$controllerSlug] ?? ucfirst($controllerSlug) . 'Controller'; // Usa ?? para default
$controllerFile = CONTROLLER_DIR . '/' . $controllerName . '.php';

// --- 6. Cargar Modelos Base y Verificar Autenticación ---
require_once MODEL_DIR . '/User.php'; // Modelo de usuario siempre necesario

$publicRoutes = [ // Rutas que NO requieren autenticación
    'auth/login',
    'auth/register',
    'auth/forgot', 
    'auth/recover',
    'auth/authenticate'
];

$publicPaths = [
    'assets/', // Para todos los archivos en la carpeta assets
    'public/',  // Para todos los archivos en la carpeta public
];

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

if (!in_array($currentRoute, $publicRoutes) && !$isPublicPath && !isAuthenticated()) {
    Logger::warning('Authentication required, redirecting to login.', ['route' => $currentRoute]);
    setFlashMessage('Debe iniciar sesión para acceder a esta sección.', 'danger');
    header('Location: ' . BASE_URL . '/auth/login');
    exit;
}

// --- 7. Despachar al Controlador ---
if (file_exists($controllerFile)) {
    require_once $controllerFile;

     // Cargar modelos adicionales según el controlador
     if ($controllerName === 'EventController') {
        require_once '../models/Event.php';
        require_once '../models/Company.php';
        require_once '../models/Match.php';
        require_once '../models/Category.php';
        require_once '../models/Appointment.php';
    } elseif ($controllerName === 'DashboardController') {
        require_once '../models/Event.php';
        require_once '../models/Company.php';
        require_once '../models/Match.php';
        require_once '../models/Appointment.php';
        require_once '../models/User.php';
        require_once '../models/Category.php';
    } elseif ($controllerName === 'CompanyController') {
        require_once '../models/Company.php';
    } elseif ($controllerName === 'MatchController') {
        require_once '../models/Match.php';
        require_once '../models/Company.php';
        require_once '../models/Event.php';
    } elseif ($controllerName === 'AppointmentController') {
        require_once '../models/Appointment.php';
        require_once '../models/Match.php';
        require_once '../models/Event.php';
    } elseif ($controllerName === 'CategoryController') {
        require_once '../models/Category.php';
        require_once '../models/Subcategory.php';
    }

    if (class_exists($controllerName)) {
        $controllerInstance = new $controllerName();

        if (method_exists($controllerInstance, $action)) {
            Logger::info("Dispatching to controller action.", ['controller' => $controllerName, 'action' => $action]);
            try {
                // Llamar a la acción del controlador
                call_user_func_array([$controllerInstance, $action], is_array($params) ? $params : []);
                Logger::info("Request handled successfully.");
            } catch (Exception $e) {
                Logger::exception($e, ['controller' => $controllerName, 'action' => $action]);
                // Mostrar página de error genérica o manejar de otra forma
                header("HTTP/1.1 500 Internal Server Error");
                // include VIEW_DIR . '/errors/500.php'; // O una vista de error
                 echo "Ocurrió un error inesperado procesando su solicitud.";
                 exit;
            }
        } else {
            Logger::error("Action not found in controller.", ['controller' => $controllerName, 'action' => $action]);
            header("HTTP/1.0 404 Not Found");
            echo "Error 404: Acción no encontrada ($action).";
            // include VIEW_DIR . '/errors/404.php';
            exit;
        }
    } else {
        Logger::error("Controller class not found.", ['controller' => $controllerName, 'file' => $controllerFile]);
        header("HTTP/1.0 404 Not Found");
        echo "Error 404: Controlador no encontrado ($controllerName).";
        // include VIEW_DIR . '/errors/404.php';
        exit;
    }
} else {
    Logger::error("Controller file not found.", ['controller' => $controllerName, 'file' => $controllerFile]);
    header("HTTP/1.0 404 Not Found");
    echo "Error 404: Archivo del controlador no encontrado.";
    // include VIEW_DIR . '/errors/404.php';
    exit;
}

