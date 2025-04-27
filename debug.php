// Crea un archivo debug.php en la raíz de tu proyecto
<?php
require_once 'config/config.php';
require_once 'config/helpers.php';

session_start();

echo "<h1>Información de Depuración</h1>";
echo "<p>Estado de autenticación: " . (isAuthenticated() ? 'Autenticado' : 'No autenticado') . "</p>";
echo "<h2>Contenido de SESSION:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";