<?php
/**
 * Layout Dinámico para Testing
 * Permite alternar entre original y refactorizado via URL
 */

// Detectar si usar la versión refactorizada
$useRefactored = isset($_GET['refactored']) || isset($_GET['test']) || isset($_GET['new']);

if ($useRefactored) {
    // Cargar la versión refactorizada
    include __DIR__ . '/admin-refactored.php';
} else {
    // Cargar la versión original
    include __DIR__ . '/admin-original.php';
}
?>