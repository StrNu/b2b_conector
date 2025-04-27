<?php
// Iniciar sesión
session_start();

// Definir constantes necesarias
define('BASE_URL', 'http://localhost/b2b_conector');
define('ROOT_DIR', __DIR__);

// Simular un usuario autenticado para probar
$_SESSION['user_id'] = 1;
$_SESSION['name'] = 'Usuario Prueba';
$_SESSION['role'] = 'admin';

// Definir el título de la página
$title = 'Página de Prueba';

// Mensajes flash de ejemplo
$_SESSION['flash_messages'] = [
    'success' => ['Este es un mensaje de éxito'],
    'danger' => ['Este es un mensaje de error'],
    'warning' => ['Este es un mensaje de advertencia'],
    'info' => ['Este es un mensaje informativo']
];

// Incluir componentes
include_once('views/shared/header.php');
?>

<!-- Contenido de prueba -->
<div>
    <h1>Contenido de Prueba</h1>
    <p>Esta es una página de prueba para verificar que los componentes se muestren correctamente.</p>
</div>

<?php
// Incluir footer
include_once('views/shared/footer.php');
?>