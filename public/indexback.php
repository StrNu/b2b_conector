<?php
// public/index.php

// Incluir archivos de configuración y funciones necesarias
require_once '../config/config.php';
// Cargar la clase Logger
require_once '../utils/Logger.php';

// Inicializar el Logger
Logger::init(LOG_DIR, 'miapp');

// Cargar el manejador de errores personalizado
require_once '../utils/error_handler.php';
require_once '../config/database.php';
require_once '../config/helpers.php';
require_once '../utils/Security.php';
require_once '../utils/Validator.php';

// Iniciar sesión con configuración segura
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Procesar la URL
$url = isset($_GET['url']) ? $_GET['url'] : '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Determinar controlador y acción
$controller = isset($url[0]) && !empty($url[0]) ? $url[0] : 'auth';
$action = isset($url[1]) && !empty($url[1]) ? $url[1] : 'login';
$params = array_slice($url, 2);

// Mapa de controladores y sus archivos
$controllerMap = [
    'auth' => 'AuthController',
    'dashboard' => 'DashboardController',
    'events' => 'EventController',
    'companies' => 'CompanyController',
    'categories' => 'CategoryController',
    'matches' => 'MatchController',
    'appointments' => 'AppointmentController',
    'users' => 'UserController'
];

// Obtener el nombre del controlador
$controllerName = isset($controllerMap[$controller]) ? $controllerMap[$controller] : ucfirst($controller) . 'Controller';
$controllerFile = '../controllers/' . $controllerName . '.php';

// Cargar los modelos base primero (los necesarios para la mayoría de operaciones)
require_once '../models/User.php';

// Verificar rutas protegidas
$publicRoutes = [
    'auth/login',
    'auth/register',
    'auth/forgot',
    'auth/recover'
];

$currentRoute = $controller . '/' . $action;

// Verificar si hay sesión activa para rutas protegidas
if (!in_array($currentRoute, $publicRoutes) && !isAuthenticated()) {
    // Redirigir al login si intenta acceder a rutas protegidas sin autenticación
    setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
    header('Location: ' . BASE_URL . '/auth/login');
    exit;
}

// Verificar si el archivo del controlador existe
if (file_exists($controllerFile)) {
    // Cargar el controlador específico
    require_once $controllerFile;
    
    // Cargar modelos adicionales según el controlador
    if ($controllerName === 'EventController') {
        require_once '../models/Event.php';
    }elseif ($controllerName === 'DashboardController') {
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
    
    // Verificar si la clase existe 
    if (class_exists($controllerName)) {
        $controllerInstance = new $controllerName();
        
        // Verificar si el método existe
        if (method_exists($controllerInstance, $action)) {
            // Llamar al método del controlador con los parámetros
            call_user_func_array([$controllerInstance, $action], $params);
        } else {
            // Método no encontrado, mostrar error 404
            header("HTTP/1.0 404 Not Found");
            echo "Error 404: Método no encontrado";
            // Incluir vista de error si existe: include '../views/errors/404.php';
        }
    } else {
        // Clase no encontrada
        header("HTTP/1.0 404 Not Found");
        echo "Error 404: Controlador no encontrado";
    }
} else {
    // Archivo no encontrado
    header("HTTP/1.0 404 Not Found");
    echo "Error 404: Archivo del controlador no encontrado";
}