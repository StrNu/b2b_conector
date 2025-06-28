<?php
/**
 * Ejemplo de uso de Material Design 3 en B2B Conector
 * Muestra cómo implementar componentes Material en páginas reales
 */

// Configuración básica
$title = "Ejemplo de Uso - Material Design 3";
$moduleCSS = null;

// Incluir configuración
require_once '../config/config.php';
require_once '../config/helpers.php';

// Simular sesión para el ejemplo
session_start();
if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Usuario Ejemplo';
    $_SESSION['user_id'] = 999;
}

// Ejemplo de contenido usando helpers Material
$content = '
<div class="container" style="margin: 2rem auto;">
    
    <!-- Ejemplo 1: Hero Section Material -->
    <div class="' . getMaterialClass('hero') . '">
        <div class="container">
            <div class="hero-material__content">
                <h1 class="hero-material__title">Gestión de Eventos B2B</h1>
                <p class="hero-material__subtitle">Organiza y gestiona tus eventos empresariales con la nueva experiencia Material Design 3</p>
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                    ' . materialButton('Crear Evento', 'filled', 'fas fa-plus') . '
                    ' . materialButton('Ver Eventos', 'tonal', 'fas fa-list') . '
                </div>
            </div>
        </div>
    </div>

    <!-- Ejemplo 2: Cards con contenido real -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 3rem 0;">
        
        ' . materialCard(
            'Eventos Activos',
            '<div style="text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; color: var(--md-primary-40); margin-bottom: 0.5rem;">12</div>
                <p style="color: var(--color-gray-600); margin: 0;">Eventos en progreso</p>
            </div>',
            'elevated',
            materialButton('Ver Todos', 'text', 'fas fa-arrow-right')
        ) . '
        
        ' . materialCard(
            'Empresas Registradas',
            '<div style="text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; color: var(--md-primary-40); margin-bottom: 0.5rem;">247</div>
                <p style="color: var(--color-gray-600); margin: 0;">Empresas participantes</p>
            </div>',
            'filled',
            materialButton('Gestionar', 'text', 'fas fa-cog')
        ) . '
        
        ' . materialCard(
            'Matches Generados',
            '<div style="text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; color: var(--md-primary-40); margin-bottom: 0.5rem;">1,234</div>
                <p style="color: var(--color-gray-600); margin: 0;">Conexiones empresariales</p>
            </div>',
            'outlined',
            materialButton('Analizar', 'text', 'fas fa-chart-bar')
        ) . '
    </div>

    <!-- Ejemplo 3: Formulario Material -->
    <div style="max-width: 600px; margin: 3rem auto;">
        ' . materialCard(
            'Crear Nuevo Evento',
            '
            <form>
                <div class="textfield-material">
                    <input class="textfield-material__input" type="text" id="event-name" placeholder=" " required>
                    <label class="textfield-material__label" for="event-name">Nombre del Evento</label>
                    <div class="textfield-material__supporting-text">Introduce un nombre descriptivo para tu evento</div>
                </div>
                
                <div class="textfield-material">
                    <input class="textfield-material__input" type="date" id="event-date" placeholder=" " required>
                    <label class="textfield-material__label" for="event-date">Fecha del Evento</label>
                </div>
                
                <div class="textfield-material">
                    <input class="textfield-material__input" type="text" id="event-location" placeholder=" ">
                    <label class="textfield-material__label" for="event-location">Ubicación</label>
                    <div class="textfield-material__supporting-text">Ciudad o dirección del evento</div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    ' . materialButton('Crear Evento', 'filled', 'fas fa-save', 'type="submit" style="flex: 1;"') . '
                    ' . materialButton('Cancelar', 'outlined', 'fas fa-times') . '
                </div>
            </form>
            ',
            'elevated'
        ) . '
    </div>

    <!-- Ejemplo 4: Lista con acciones Material -->
    <div style="margin: 3rem 0;">
        ' . materialCard(
            'Eventos Recientes',
            '
            <div style="space-y: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--color-gray-200);">
                    <div>
                        <h4 style="font-weight: 600; margin: 0 0 0.25rem 0;">Expo Tech 2024</h4>
                        <p style="color: var(--color-gray-600); font-size: 0.875rem; margin: 0;">15 Marzo 2024 • Ciudad de México</p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        ' . materialButton('Ver', 'text', 'fas fa-eye') . '
                        ' . materialButton('Editar', 'text', 'fas fa-edit') . '
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--color-gray-200);">
                    <div>
                        <h4 style="font-weight: 600; margin: 0 0 0.25rem 0;">Business Summit 2024</h4>
                        <p style="color: var(--color-gray-600); font-size: 0.875rem; margin: 0;">20 Abril 2024 • Guadalajara</p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        ' . materialButton('Ver', 'text', 'fas fa-eye') . '
                        ' . materialButton('Editar', 'text', 'fas fa-edit') . '
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0;">
                    <div>
                        <h4 style="font-weight: 600; margin: 0 0 0.25rem 0;">Innovation Forum</h4>
                        <p style="color: var(--color-gray-600); font-size: 0.875rem; margin: 0;">5 Mayo 2024 • Monterrey</p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        ' . materialButton('Ver', 'text', 'fas fa-eye') . '
                        ' . materialButton('Editar', 'text', 'fas fa-edit') . '
                    </div>
                </div>
            </div>
            ',
            'filled'
        ) . '
    </div>

    <!-- Ejemplo 5: Testing de Notificaciones -->
    <div style="text-align: center; margin: 3rem 0;">
        <h3 style="margin-bottom: 1.5rem;">Testing de Componentes Material</h3>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            ' . materialButton('Notificación Éxito', 'filled', 'fas fa-check', 'onclick="B2B.showNotification(\'¡Material Design 3 implementado correctamente!\', \'success\')"') . '
            ' . materialButton('Notificación Info', 'tonal', 'fas fa-info', 'onclick="B2B.showNotification(\'Información Material Design 3\', \'info\')"') . '
            ' . materialButton('Notificación Warning', 'outlined', 'fas fa-exclamation-triangle', 'onclick="B2B.showNotification(\'Advertencia del sistema\', \'warning\')"') . '
            ' . materialButton('Notificación Error', 'text', 'fas fa-times', 'onclick="B2B.showNotification(\'Error de prueba\', \'error\')"') . '
        </div>
    </div>

</div>

<!-- FAB para scroll to top -->
<button class="' . getMaterialClass('fab') . '" onclick="window.scrollTo({top: 0, behavior: \'smooth\'})">
    <i class="fas fa-arrow-up"></i>
</button>
';

// Usar el layout refactorizado con Material Design 3
include '../views/layouts/admin-refactored.php';
?>