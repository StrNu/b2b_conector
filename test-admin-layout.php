<?php
/**
 * Testing del Layout Admin actualizado con Material Design 3
 * Verifica que el nuevo layout admin.php funcione correctamente
 */

// Configuración básica
$title = "Test Layout Admin - Material Design 3";
$currentPage = "dashboard"; // Para highlighting del nav
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
        'success' => ['¡Layout Admin actualizado con Material Design 3!'],
        'info' => ['Nuevo diseño implementado correctamente.']
    ];
}

// Contenido de prueba que simula una página real del admin
$content = '
<!-- Hero Section para Dashboard -->
<div class="hero-material" style="margin: -2rem -2rem 2rem -2rem;">
    <div class="container">
        <div class="hero-material__content">
            <h1 class="hero-material__title">Panel de Administración</h1>
            <p class="hero-material__subtitle">Gestiona tu plataforma B2B con la nueva experiencia Material Design 3</p>
        </div>
    </div>
</div>

<!-- Dashboard Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    ' . materialCard(
        '📊 Eventos Activos',
        '<div style="text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--md-primary-40); margin-bottom: 0.5rem;">24</div>
            <p style="color: var(--color-gray-600); margin: 0; font-size: 0.875rem;">+3 este mes</p>
        </div>',
        'elevated'
    ) . '
    
    ' . materialCard(
        '🏢 Empresas Registradas',
        '<div style="text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--md-primary-40); margin-bottom: 0.5rem;">847</div>
            <p style="color: var(--color-gray-600); margin: 0; font-size: 0.875rem;">+15 esta semana</p>
        </div>',
        'filled'
    ) . '
    
    ' . materialCard(
        '🤝 Matches Generados',
        '<div style="text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--md-primary-40); margin-bottom: 0.5rem;">3,241</div>
            <p style="color: var(--color-gray-600); margin: 0; font-size: 0.875rem;">+127 hoy</p>
        </div>',
        'outlined'
    ) . '
    
    ' . materialCard(
        '💰 Ingresos',
        '<div style="text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--md-primary-40); margin-bottom: 0.5rem;">$45,200</div>
            <p style="color: var(--color-gray-600); margin: 0; font-size: 0.875rem;">+8.2% vs mes anterior</p>
        </div>',
        'elevated'
    ) . '
</div>

<!-- Recent Activity -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <div>
        ' . materialCard(
            'Actividad Reciente',
            '
            <div style="space-y: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--color-gray-200);">
                    <div style="width: 40px; height: 40px; background: var(--gradient-primary); border-radius: var(--md-shape-corner-medium); display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-size: 0.875rem; font-weight: 600;">Nuevo evento creado</h4>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--color-gray-600);">Expo Tech 2024 - hace 2 horas</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--color-gray-200);">
                    <div style="width: 40px; height: 40px; background: var(--gradient-secondary); border-radius: var(--md-shape-corner-medium); display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-size: 0.875rem; font-weight: 600;">Empresa registrada</h4>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--color-gray-600);">TechCorp S.A. - hace 4 horas</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--color-gray-200);">
                    <div style="width: 40px; height: 40px; background: var(--gradient-material); border-radius: var(--md-shape-corner-medium); display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-size: 0.875rem; font-weight: 600;">Matches generados</h4>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--color-gray-600);">15 nuevas conexiones - hace 6 horas</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0;">
                    <div style="width: 40px; height: 40px; background: var(--color-success-500); border-radius: var(--md-shape-corner-medium); display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-size: 0.875rem; font-weight: 600;">Evento completado</h4>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--color-gray-600);">Business Summit 2024 - ayer</p>
                    </div>
                </div>
            </div>
            ',
            'filled'
        ) . '
    </div>
    
    <div>
        ' . materialCard(
            'Acciones Rápidas',
            '
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                ' . materialButton('Crear Evento', 'filled', 'fas fa-plus', 'style="width: 100%;"') . '
                ' . materialButton('Ver Reportes', 'tonal', 'fas fa-chart-bar', 'style="width: 100%;"') . '
                ' . materialButton('Gestionar Usuarios', 'outlined', 'fas fa-users', 'style="width: 100%;"') . '
                ' . materialButton('Configuración', 'text', 'fas fa-cog', 'style="width: 100%;"') . '
            </div>
            ',
            'elevated'
        ) . '
        
        <div style="margin-top: 1.5rem;">
            ' . materialCard(
                'Sistema',
                '
                <div style="font-size: 0.875rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Versión:</span>
                        <span style="font-weight: 600;">v' . APP_VERSION . '</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Tema:</span>
                        <span style="color: var(--md-primary-40); font-weight: 600;">Material Design 3</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Estado:</span>
                        <span style="color: var(--color-success-600); font-weight: 600;">● Online</span>
                    </div>
                </div>
                ',
                'outlined'
            ) . '
        </div>
    </div>
</div>

<!-- Testing Controls -->
<div style="margin-top: 2rem;">
    ' . materialCard(
        '🧪 Controles de Testing',
        '
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 0.5rem;">Prueba el nuevo layout admin</h4>
            <p style="color: var(--color-gray-600); font-size: 0.875rem; margin: 0;">Verifica que todos los componentes Material Design 3 funcionen correctamente</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            ' . materialButton('Test Notificaciones', 'filled', 'fas fa-bell', 'onclick="B2B.showNotification(\'¡Layout admin con Material Design 3 funcionando!\', \'success\')"') . '
            ' . materialButton('Test Warning', 'tonal', 'fas fa-exclamation-triangle', 'onclick="B2B.showNotification(\'Sistema de alertas funcionando\', \'warning\')"') . '
            ' . materialButton('Test Info', 'outlined', 'fas fa-info-circle', 'onclick="B2B.showNotification(\'Información del sistema\', \'info\')"') . '
            ' . materialButton('Test Error', 'text', 'fas fa-times-circle', 'onclick="B2B.showNotification(\'Simulando error del sistema\', \'error\')"') . '
        </div>
        
        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--md-surface-container); border-radius: var(--md-shape-corner-small);">
            <p style="margin: 0; font-size: 0.875rem; text-align: center; color: var(--md-primary-40);">
                <i class="fas fa-check-circle"></i>
                Layout admin.php actualizado exitosamente con Material Design 3
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