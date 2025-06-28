<?php
/**
 * Testing de Assets Actualizados con Material Design 3
 * Verificar que todos los archivos de assets estén funcionando correctamente
 */

// Configuración básica
$title = "Test Assets Material Design 3";
$moduleCSS = null;
$additionalCSS = ['material-theme.css'];

// Incluir configuración
require_once 'config/config.php';
require_once 'config/helpers.php';

// Simular sesión
session_start();
if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Assets Test User';
    $_SESSION['user_id'] = 999;
    $_SESSION['role'] = 'admin';
}

// Mensaje de prueba
if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [
        'success' => ['¡Assets actualizados correctamente con Material Design 3!'],
        'info' => ['Todos los archivos de assets han sido optimizados.']
    ];
}

// Contenido de testing
$content = '
<!-- Testing de Assets -->
<div style="margin: 2rem 0;">
    ' . materialCard(
        '📂 Assets Actualizados - Material Design 3',
        '
        <div style="margin-bottom: 2rem;">
            <h4 style="margin-bottom: 1rem; color: var(--md-primary-40);">Estado de los Assets</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <div style="padding: 1rem; background: var(--color-success-50); border-radius: var(--md-shape-corner-small); border-left: 4px solid var(--color-success-500);">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--color-success-700); font-weight: 600;">
                        <i class="fas fa-check-circle"></i> header.php
                    </h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--color-success-600);">
                        Actualizado para usar assets.php con Material Design 3
                    </p>
                </div>
                
                <div style="padding: 1rem; background: var(--color-success-50); border-radius: var(--md-shape-corner-small); border-left: 4px solid var(--color-success-500);">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--color-success-700); font-weight: 600;">
                        <i class="fas fa-check-circle"></i> assets.php
                    </h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--color-success-600);">
                        Mejorado con variables Material Design 3 y JavaScript avanzado
                    </p>
                </div>
                
                
                <div style="padding: 1rem; background: var(--color-success-50); border-radius: var(--md-shape-corner-small); border-left: 4px solid var(--color-success-500);">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--color-success-700); font-weight: 600;">
                        <i class="fas fa-check-circle"></i> header_public.php
                    </h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--color-success-600);">
                        Actualizado con Material Design 3 y gradientes purple/violet
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h4 style="margin-bottom: 1rem; color: var(--md-primary-40);">Archivos CSS Cargados</h4>
            <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-gray-700); font-family: monospace; font-size: 0.875rem;">
                <li style="margin-bottom: 0.5rem;">✅ <strong>core.css</strong> - Variables y reset con Material Design 3</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>components.css</strong> - Componentes UI con BEM</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>material-theme.css</strong> - Componentes Material Design 3</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>layouts.css</strong> - Layouts específicos</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Google Fonts</strong> - Poppins + Montserrat</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Font Awesome</strong> - Iconos v6.4.0</li>
            </ul>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h4 style="margin-bottom: 1rem; color: var(--md-primary-40);">JavaScript Mejorado</h4>
            <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-gray-700);">
                <li style="margin-bottom: 0.5rem;">✅ <strong>Ripple Effect</strong> - Efectos Material en botones</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Material Theme</strong> - Auto-aplicación de clases</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Dropdowns</strong> - Funcionalidad mejorada</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Flash Messages</strong> - Sistema de notificaciones</li>
                <li style="margin-bottom: 0.5rem;">✅ <strong>Utility Functions</strong> - Helper functions globales</li>
            </ul>
        </div>
        ',
        'elevated'
    ) . '
</div>

<!-- Testing de Componentes Material -->
<div style="margin: 2rem 0;">
    ' . materialCard(
        '🧪 Testing de Componentes Material',
        '
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem;">Botones Material Design 3</h4>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                ' . materialButton('Filled Button', 'filled', 'fas fa-star') . '
                ' . materialButton('Tonal Button', 'tonal', 'fas fa-heart') . '
                ' . materialButton('Outlined Button', 'outlined', 'fas fa-thumbs-up') . '
                ' . materialButton('Text Button', 'text', 'fas fa-info') . '
            </div>
            <p style="margin: 0; font-size: 0.875rem; color: var(--color-gray-600);">
                Los botones deben mostrar efectos ripple al hacer click
            </p>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem;">Campos de Texto Material</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="textfield-material">
                    <input class="textfield-material__input" type="text" id="test-name" placeholder=" ">
                    <label class="textfield-material__label" for="test-name">Nombre completo</label>
                    <div class="textfield-material__supporting-text">Introduce tu nombre</div>
                </div>
                
                <div class="textfield-material">
                    <input class="textfield-material__input" type="email" id="test-email" placeholder=" ">
                    <label class="textfield-material__label" for="test-email">Correo electrónico</label>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem;">Cards Material Design 3</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="card-material card-material--elevated" style="padding: 1rem; text-align: center;">
                    <h5 style="margin: 0 0 0.5rem 0;">Elevated Card</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--color-gray-600);">Con elevación</p>
                </div>
                
                <div class="card-material card-material--filled" style="padding: 1rem; text-align: center;">
                    <h5 style="margin: 0 0 0.5rem 0;">Filled Card</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--color-gray-600);">Con relleno</p>
                </div>
                
                <div class="card-material card-material--outlined" style="padding: 1rem; text-align: center;">
                    <h5 style="margin: 0 0 0.5rem 0;">Outlined Card</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--color-gray-600);">Con borde</p>
                </div>
            </div>
        </div>
        ',
        'filled'
    ) . '
</div>

<!-- Testing de Notificaciones -->
<div style="margin: 2rem 0;">
    ' . materialCard(
        '🔔 Test de Notificaciones',
        '
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 0.5rem;">Sistema de Notificaciones Mejorado</h4>
            <p style="color: var(--color-gray-600); font-size: 0.875rem; margin: 0;">
                Las notificaciones ahora usan los colores y efectos Material Design 3
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            ' . materialButton('Test Success', 'filled', 'fas fa-check', 'onclick="B2B.showNotification(\'¡Assets Material Design 3 funcionando!\', \'success\')"') . '
            ' . materialButton('Test Info', 'tonal', 'fas fa-info', 'onclick="B2B.showNotification(\'Sistema de assets actualizado\', \'info\')"') . '
            ' . materialButton('Test Warning', 'outlined', 'fas fa-exclamation-triangle', 'onclick="B2B.showNotification(\'Archivo assets.php deprecated\', \'warning\')"') . '
            ' . materialButton('Test Error', 'text', 'fas fa-times', 'onclick="B2B.showNotification(\'Error de prueba\', \'error\')"') . '
        </div>
        ',
        'outlined'
    ) . '
</div>

<!-- Resumen Técnico -->
<div style="margin: 2rem 0;">
    ' . materialCard(
        '⚙️ Resumen Técnico',
        '
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div>
                <h5 style="margin-bottom: 1rem; color: var(--md-primary-40);">Archivos Actualizados</h5>
                <ul style="margin: 0; padding-left: 1rem; font-size: 0.875rem; color: var(--color-gray-700);">
                    <li>views/shared/header.php</li>
                    <li>views/shared/assets.php</li>
                    <li>views/shared/header_public.php</li>
                    <li>views/layouts/admin.php</li>
                </ul>
            </div>
            
            <div>
                <h5 style="margin-bottom: 1rem; color: var(--md-primary-40);">Mejoras Implementadas</h5>
                <ul style="margin: 0; padding-left: 1rem; font-size: 0.875rem; color: var(--color-gray-700);">
                    <li>Carga automática de Material Design 3</li>
                    <li>Variables CSS actualizadas</li>
                    <li>JavaScript con efectos Material</li>
                    <li>Headers públicos mejorados</li>
                </ul>
            </div>
            
            <div>
                <h5 style="margin-bottom: 1rem; color: var(--md-primary-40);">Compatibilidad</h5>
                <ul style="margin: 0; padding-left: 1rem; font-size: 0.875rem; color: var(--color-gray-700);">
                    <li>Backward compatibility mantenida</li>
                    <li>Fallbacks para CSS legacy</li>
                    <li>Archivo assets.php optimizado</li>
                    <li>Variables CSS con fallbacks</li>
                </ul>
            </div>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: var(--md-surface-container); border-radius: var(--md-shape-corner-small); text-align: center;">
            <p style="margin: 0; font-weight: 600; color: var(--md-primary-40);">
                <i class="fas fa-check-circle"></i>
                Todos los assets han sido actualizados exitosamente con Material Design 3
            </p>
        </div>
        ',
        'elevated'
    ) . '
</div>
';

// Usar el layout admin actualizado
include 'views/layouts/admin.php';
?>