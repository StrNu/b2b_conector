<?php
// Configuración inicial
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Incluir archivos necesarios
require_once __DIR__ . '/config/config.php';  // Ajusta la ruta según tu estructura
require_once __DIR__ .'/config/database.php';
require_once __DIR__ .'/utils/Security.php';
require_once __DIR__ .'/utils/Logger.php';
require_once __DIR__ .'/models/User.php';
require_once __DIR__ .'/utils/Validator.php';
require_once __DIR__ .'/utils/error_handler.php';
require_once __DIR__ .'/utils/ReflectionClass.php';

echo "DIR: " . __DIR__ . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "BASE_URL: " . BASE_URL . "<br>";
echo "ROOT_DIR: " . ROOT_DIR . "<br>";

echo __DIR__ . '/config/config.php' . "<br>";
echo __DIR__ .'/config/database.php' . "<br>";
echo __DIR__ .'/utils/Security.php' . "<br>";
echo __DIR__ .'/models/User.php' . "<br>";


// Verificar si la clase Database existe
if (!class_exists('Database')) {
    die("Error: La clase Database no se encuentra.");
}
// Verificar si la clase User existe
if (!class_exists('User')) {
    die("Error: La clase User no se encuentra.");
}
// Verificar si la clase Security existe
if (!class_exists('Security')) {
    die("Error: La clase Security no se encuentra.");
}
// Verificar si la clase Logger existe
if (!class_exists('Logger')) {
    die("Error: La clase Logger no se encuentra.");
}
// Verificar si la clase Validator existe
if (!class_exists('Validator')) {
    die("Error: La clase Validator no se encuentra.");
}
// Verificar si la clase ReflectionClass existe
if (!class_exists('ReflectionClass')) {
    die("Error: La clase ReflectionClass no se encuentra.");
}
// Verificar si la clase Session existe
if (!class_exists('Session')) {
    die("Error: La clase Session no se encuentra.");
}
// Verificar si la clase ErrorHandler existe
if (!class_exists('ErrorHandler')) {
    die("Error: La clase ErrorHandler no se encuentra.");
}
// Verificar si la clase Logger existe
if (!class_exists('Logger')) {
    die("Error: La clase Logger no se encuentra.");
}

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Prueba de Autenticación</h1>";



// Credenciales a probar
$username = 'admin';  // Usuario que sabemos que existe
$password = 'admin123';  // Contraseña que creemos correcta

// Crear instancia de base de datos
$db = Database::getInstance();

// Crear instancia del modelo de usuario
$user = new User($db);

// Probar búsqueda por nombre de usuario
echo "<h2>Prueba 1: Buscar usuario</h2>";
$found = $user->findByUsername($username);
echo "Búsqueda de usuario '$username': " . ($found ? "ENCONTRADO" : "NO ENCONTRADO") . "<br>";

if ($found) {
    echo "Datos del usuario:<br>";
    echo "ID: " . $user->getId() . "<br>";
    echo "Nombre: " . $user->getName() . "<br>";
    echo "Email: " . $user->getEmail() . "<br>";
    echo "Activo: " . ($user->isActive() ? "SÍ" : "NO") . "<br>";
    
    // Acceder a la propiedad password directamente solo para depuración
    // Esto normalmente no se haría en código de producción
    $reflection = new ReflectionClass($user);
    $property = $reflection->getProperty('password');
    $property->setAccessible(true);
    $storedHash = $property->getValue($user);
    
    echo "Hash almacenado: " . $storedHash . "<br>";
    
    // Probar verificación de contraseña
    echo "<h2>Prueba 2: Verificar contraseña</h2>";
    $passwordMatches = Security::verifyPassword($password, $storedHash);
    echo "La contraseña '$password' " . ($passwordMatches ? "COINCIDE" : "NO COINCIDE") . " con el hash almacenado<br>";
    
    // Probar el método authenticate completo
    echo "<h2>Prueba 3: Método authenticate</h2>";
    $authenticated = $user->authenticate($username, $password);
    echo "Resultado de authenticate(): " . ($authenticated ? "ÉXITO" : "FALLIDO") . "<br>";
}
?>