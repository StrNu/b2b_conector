<?php
/**
 * Script para crear un usuario de prueba en la base de datos
 * 
 * Este script debe ejecutarse desde la línea de comandos o mediante el navegador
 * directamente para crear un usuario administrador de prueba.
 */

// Cargar configuración de base de datos
require_once 'config/database.php';
require_once 'utils/Security.php';

// Inicializar conexión a la base de datos
$db = Database::getInstance();

// Datos del usuario de prueba
$testUser = [
    'username' => 'admin',
    'email' => 'admin@test.com',
    'password_hash' => Security::hashPassword('admin123'), // Usar la función de hash de seguridad
    'role' => 'admin',
    'is_active' => 1,
    'name' => 'Administrador de Prueba',
    'registration_date' => date('Y-m-d H:i:s')
];

// Verificar si el usuario ya existe
$query = "SELECT COUNT(*) FROM users WHERE username = :username OR email = :email";
$params = [
    'username' => $testUser['username'],
    'email' => $testUser['email']
];

$count = $db->query($query, $params)->fetchColumn();

if ($count > 0) {
    echo "El usuario ya existe en la base de datos.";
    exit;
}

// Insertar el usuario de prueba
$fields = array_keys($testUser);
$placeholders = array_map(function($field) {
    return ":$field";
}, $fields);

$query = "INSERT INTO users (" . implode(', ', $fields) . ") 
          VALUES (" . implode(', ', $placeholders) . ")";

if ($db->query($query, $testUser)) {
    echo "Usuario de prueba creado exitosamente:";
    echo "<br>Username: " . $testUser['username'];
    echo "<br>Password: admin123";
    echo "<br>Rol: " . $testUser['role'];
} else {
    echo "Error al crear el usuario de prueba.";
}