<?php
/**
 * Página de Testing para CSS Refactorizado
 * Permite probar la nueva arquitectura CSS sin afectar el sistema principal
 */

// Configuración básica
$title = "Test Material Design 3 - B2B Conector";
$moduleCSS = null;
$additionalCSS = ['modern-components.css', 'material-theme.css'];

// Simular sesión de usuario para testing
session_start();
if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Usuario Test';
    $_SESSION['user_id'] = 999;
}

// Incluir configuración básica
require_once 'config/config.php';
require_once 'config/helpers.php';

// Agregar algunos mensajes flash para testing
if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [
        'success' => ['¡Material Design 3 cargado correctamente!'],
        'info' => ['Esta es una página de testing para probar Material Design 3.'],
        'warning' => ['Recuerda revisar todos los componentes Material.']
    ];
}

// Contenido de testing
$content = '
<div style="text-align: center; padding: 3rem 0; background: var(--gradient-material); color: white; border-radius: 0 0 2rem 2rem; margin-bottom: 2rem;">
    <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">Test Material Design 3</h1>
    <p style="font-size: 1.125rem; opacity: 0.9;">Prueba todos los componentes del nuevo sistema Material Design 3</p>
</div>

<div class="dashboard-grid">
    <!-- Card 1: Estadísticas -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">📊 Estadísticas</h3>
        </div>
        <div class="card__body">
            <div class="dashboard-stat">
                <div class="dashboard-stat__title">Total Eventos</div>
                <div class="dashboard-stat__value">125</div>
                <div class="dashboard-stat__change dashboard-stat__change--positive">+12% este mes</div>
            </div>
        </div>
    </div>

    <!-- Card 2: Botones -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">🔘 Botones</h3>
        </div>
        <div class="card__body">
            <div class="flex gap-3 flex-wrap">
                <button class="btn btn--primary">Primario</button>
                <button class="btn btn--secondary">Secundario</button>
                <button class="btn btn--success">Éxito</button>
                <button class="btn btn--danger">Peligro</button>
            </div>
            <div class="flex gap-3 flex-wrap" style="margin-top: 1rem;">
                <button class="btn btn--primary btn--sm">Pequeño</button>
                <button class="btn btn--primary btn--lg">Grande</button>
                <button class="btn btn--outline">Outline</button>
            </div>
        </div>
    </div>

    <!-- Card 3: Formulario -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">📝 Formulario</h3>
        </div>
        <div class="card__body">
            <form>
                <div class="form-group">
                    <label class="form-label" for="test-email">Email</label>
                    <input class="form-control" type="email" id="test-email" placeholder="test@ejemplo.com">
                    <div class="form-help">Introduce tu email de testing</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="test-password">Contraseña</label>
                    <div class="password-field">
                        <input class="form-control" type="password" id="test-password" placeholder="••••••••">
                        <button type="button" class="password-field__toggle" aria-label="Mostrar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="remember-field">
                    <label class="remember-field__checkbox">
                        <input type="checkbox"> Recordarme
                    </label>
                    <a href="#" class="remember-field__forgot">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn btn--primary btn--full">Iniciar Sesión de Prueba</button>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de testing -->
<div class="card" style="margin-top: 2rem;">
    <div class="card__header">
        <h3 class="card__title">📋 Tabla de Datos</h3>
    </div>
    <div class="card__body">
        <table class="table table--striped">
            <thead class="table__header">
                <tr>
                    <th class="table__header-cell">ID</th>
                    <th class="table__header-cell">Nombre</th>
                    <th class="table__header-cell">Estado</th>
                    <th class="table__header-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table__row">
                    <td class="table__cell">1</td>
                    <td class="table__cell">Evento Test 1</td>
                    <td class="table__cell"><span class="badge badge--success">Activo</span></td>
                    <td class="table__cell">
                        <button class="btn btn--sm btn--primary">Ver</button>
                        <button class="btn btn--sm btn--secondary">Editar</button>
                    </td>
                </tr>
                <tr class="table__row">
                    <td class="table__cell">2</td>
                    <td class="table__cell">Evento Test 2</td>
                    <td class="table__cell"><span class="badge badge--warning">Pendiente</span></td>
                    <td class="table__cell">
                        <button class="btn btn--sm btn--primary">Ver</button>
                        <button class="btn btn--sm btn--secondary">Editar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Testing de notificaciones -->
<div class="card" style="margin-top: 2rem;">
    <div class="card__header">
        <h3 class="card__title">🔔 Test de Notificaciones</h3>
    </div>
    <div class="card__body">
        <div class="flex gap-3 flex-wrap">
            <button class="btn-material btn-material--filled" onclick="B2B.showNotification(\'¡Material Design 3 éxito!\', \'success\')">
                Mostrar Éxito
            </button>
            <button class="btn-material btn-material--tonal" onclick="B2B.showNotification(\'Mensaje de error Material\', \'error\')">
                Mostrar Error
            </button>
            <button class="btn-material btn-material--outlined" onclick="B2B.showNotification(\'Mensaje informativo Material\', \'info\')">
                Mostrar Info
            </button>
            <button class="btn-material btn-material--text" onclick="B2B.showNotification(\'Advertencia Material Design\', \'warning\')">
                Mostrar Warning
            </button>
        </div>
    </div>
</div>

<!-- Información del sistema -->
<div class="card" style="margin-top: 2rem;">
    <div class="card__header">
        <h3 class="card__title">ℹ️ Información del Testing</h3>
    </div>
    <div class="card__body">
        <div class="text-sm">
            <p><strong>Versión CSS:</strong> Refactorizada v2.0</p>
            <p><strong>Archivos cargados:</strong></p>
            <ul style="margin-left: 1rem; list-style: disc;">
                <li>core.css - Sistema base y tokens de diseño</li>
                <li>components.css - Componentes UI con BEM</li>
                <li>layouts.css - Layouts específicos</li>
                <li>assets.php - Carga optimizada</li>
            </ul>
            <p><strong>Funcionalidades testear:</strong></p>
            <ul style="margin-left: 1rem; list-style: disc;">
                <li>✅ Navegación responsive</li>
                <li>✅ Mensajes flash</li>
                <li>✅ Botones y formularios</li>
                <li>✅ Tablas y cards</li>
                <li>✅ Notificaciones dinámicas</li>
                <li>✅ Dropdown de usuario</li>
            </ul>
        </div>
    </div>
    <div class="card__footer">
        <a href="' . BASE_URL . '/dashboard" class="btn btn--primary">
            Volver al Dashboard Original
        </a>
        <button class="btn btn--secondary" onclick="window.location.reload()">
            Recargar Test
        </button>
    </div>
</div>
';

// Usar el layout con Material Design 3
include 'views/layouts/admin.php';
?>