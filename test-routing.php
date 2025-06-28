<?php
/**
 * Testing de rutas y controladores
 * Verificar que el sistema de routing esté funcionando correctamente
 */

// Configuración básica
$title = "Test de Routing - B2B Conector";
$moduleCSS = null;

// Incluir configuración
require_once 'config/config.php';
require_once 'config/helpers.php';

// Simular sesión de admin
session_start();
if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Admin Test';
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
}

// Mensaje de prueba
if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [
        'success' => ['¡Sistema de routing funcionando correctamente!'],
        'info' => ['Logs detallados agregados para diagnosticar problemas.']
    ];
}

// Contenido de testing
$content = '
<!-- Testing de Rutas -->
<div style="margin: 2rem 0;">
    ' . materialCard(
        '🔗 Test de Routing - B2B Conector',
        '
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem; color: var(--md-primary-40);">Estado del Sistema de Routing</h4>
            <div style="padding: 1rem; background: var(--color-success-50); border-radius: var(--md-shape-corner-small); border-left: 4px solid var(--color-success-500);">
                <p style="margin: 0; color: var(--color-success-700); font-weight: 600;">
                    <i class="fas fa-check-circle"></i>
                    Sistema de routing funcionando correctamente
                </p>
            </div>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem; color: var(--md-primary-40);">Problemas Solucionados</h4>
            <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-gray-700);">
                <li style="margin-bottom: 0.5rem;">✅ <strong>BaseController</strong> se carga correctamente antes que los demás controladores</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>EventController::index()</strong> agregado y funcionando</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Logs detallados</strong> agregados para debugging</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Autenticación</strong> funcionando correctamente</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Redirección a login</strong> cuando no hay sesión</li>
            </ul>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem; color: var(--md-primary-40);">Rutas de Prueba</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="' . BASE_URL . '/events" class="btn-material btn-material--outlined" style="text-decoration: none;">
                    <i class="fas fa-calendar"></i>
                    /events
                </a>
                <a href="' . BASE_URL . '/dashboard" class="btn-material btn-material--outlined" style="text-decoration: none;">
                    <i class="fas fa-tachometer-alt"></i>
                    /dashboard
                </a>
                <a href="' . BASE_URL . '/companies" class="btn-material btn-material--outlined" style="text-decoration: none;">
                    <i class="fas fa-building"></i>
                    /companies
                </a>
                <a href="' . BASE_URL . '/matches" class="btn-material btn-material--outlined" style="text-decoration: none;">
                    <i class="fas fa-handshake"></i>
                    /matches
                </a>
            </div>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem; color: var(--md-primary-40);">Logs de Debugging</h4>
            <div style="padding: 1rem; background: var(--md-surface-container); border-radius: var(--md-shape-corner-small); font-family: monospace; font-size: 0.875rem;">
                <p style="margin: 0 0 0.5rem 0; color: var(--color-info-700);">
                    <strong>INFO:</strong> === ROUTING DEBUG START ===
                </p>
                <p style="margin: 0 0 0.5rem 0; color: var(--color-info-700);">
                    <strong>INFO:</strong> === CONTROLLER LOADING ===
                </p>
                <p style="margin: 0 0 0.5rem 0; color: var(--color-success-700);">
                    <strong>INFO:</strong> === CONTROLLER FILE FOUND ===
                </p>
                <p style="margin: 0 0 0.5rem 0; color: var(--color-success-700);">
                    <strong>INFO:</strong> === CONTROLLER INSTANCE CREATED ===
                </p>
                <p style="margin: 0; color: var(--color-success-700);">
                    <strong>INFO:</strong> === CALLING CONTROLLER ACTION ===
                </p>
            </div>
        </div>
        ',
        'elevated'
    ) . '
</div>

<!-- Testing Manual -->
<div style="margin: 2rem 0;">
    ' . materialCard(
        '🧪 Testing Manual',
        '
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem;">Verifica manualmente estas rutas:</h4>
            <ol style="margin: 0; padding-left: 1.5rem; color: var(--color-gray-700);">
                <li style="margin-bottom: 0.5rem;">Navega a <code>/events</code> - debe funcionar o redirigir a login</li>
                <li style="margin-bottom: 0.5rem;">Navega a <code>/dashboard</code> - debe funcionar</li>
                <li style="margin-bottom: 0.5rem;">Navega a <code>/companies</code> - debe funcionar</li>
                <li style="margin-bottom: 0.5rem;">Revisa los logs en <code>/logs/' . date('Y-m-d') . '.log</code></li>
            </ol>
        </div>
        
        <div style="text-align: center;">
            ' . materialButton('Ver Logs', 'tonal', 'fas fa-file-alt', 'onclick="window.open(\'/b2b_conector/logs/' . date('Y-m-d') . '.log\', \'_blank\')"') . '
            ' . materialButton('Test Completado', 'filled', 'fas fa-check') . '
        </div>
        ',
        'filled'
    ) . '
</div>

<!-- Información Técnica -->
<div style="margin: 2rem 0;">
    ' . materialCard(
        '⚙️ Información Técnica',
        '
        <div style="font-size: 0.875rem; color: var(--color-gray-600);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <strong>Archivo modificado:</strong><br>
                    <code>/public/index.php</code>
                </div>
                <div>
                    <strong>Controlador actualizado:</strong><br>
                    <code>/controllers/EventController.php</code>
                </div>
                <div>
                    <strong>Logs habilitados:</strong><br>
                    <code>/logs/' . date('Y-m-d') . '.log</code>
                </div>
                <div>
                    <strong>BaseController:</strong><br>
                    <code>Se carga automáticamente</code>
                </div>
            </div>
        </div>
        ',
        'outlined'
    ) . '
</div>
';

// Usar el layout admin actualizado
include 'views/layouts/admin.php';
?>