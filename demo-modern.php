<?php
/**
 * Demostración del Nuevo Diseño Moderno
 * Inspirado en diseños clean y modernos
 */

// Configuración básica
$title = "B2B Conector - Diseño Moderno";
$moduleCSS = null;
$additionalCSS = ['modern-components.css', 'material-theme.css'];

// Simular sesión de usuario para testing
session_start();
if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Demo User';
    $_SESSION['user_id'] = 999;
}

// Incluir configuración básica
require_once 'config/config.php';
require_once 'config/helpers.php';

// Mensaje de bienvenida
if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [
        'success' => ['¡Bienvenido al nuevo diseño de B2B Conector!'],
        'info' => ['Explora todas las funcionalidades con el nuevo estilo moderno y limpio.']
    ];
}

// Contenido de la demostración
$content = '
<!-- Material Design 3 Hero Section -->
<div class="hero-material">
    <div class="hero-material__content">
        <h1 class="hero-material__title">B2B Conector</h1>
        <p class="hero-material__subtitle">La plataforma más moderna para conectar compradores y proveedores de manera eficiente y profesional</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem;">
            <button class="btn-material btn-material--filled">
                <i class="fas fa-rocket"></i>
                Comenzar Ahora
            </button>
            <button class="btn-material btn-material--tonal">
                <i class="fas fa-play"></i>
                Ver Demo
            </button>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="stats-container">
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-number">125+</span>
            <span class="stat-label">Eventos Activos</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">2.5K</span>
            <span class="stat-label">Empresas</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">15K</span>
            <span class="stat-label">Conexiones</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">98%</span>
            <span class="stat-label">Satisfacción</span>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container" style="margin: 4rem auto;">
    <div style="text-align: center; margin-bottom: 4rem;">
        <h2 style="font-size: 2.5rem; font-weight: bold; color: var(--color-gray-900); margin-bottom: 1rem; letter-spacing: -0.02em;">
            Nuestras Características
        </h2>
        <p style="font-size: 1.125rem; color: var(--color-gray-600); max-width: 600px; margin: 0 auto;">
            Descubre todas las herramientas que hemos diseñado para hacer que tus eventos B2B sean extraordinarios
        </p>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
        <div class="feature-material">
            <div class="feature-material__icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <h3 class="feature-material__title">Gestión de Eventos</h3>
            <p class="feature-material__description">Organiza y administra eventos B2B de manera eficiente con herramientas intuitivas, cronogramas automáticos y gestión de participantes.</p>
        </div>
        
        <div class="feature-material">
            <div class="feature-material__icon">
                <i class="fas fa-handshake"></i>
            </div>
            <h3 class="feature-material__title">Matching Inteligente</h3>
            <p class="feature-material__description">Conecta automáticamente compradores y proveedores basado en sus necesidades, productos y preferencias comerciales.</p>
        </div>
        
        <div class="feature-material">
            <div class="feature-material__icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="feature-material__title">Analytics Avanzados</h3>
            <p class="feature-material__description">Obtén insights detallados sobre el rendimiento de tus eventos, conexiones exitosas y ROI de participantes.</p>
        </div>
        
        <div class="feature-material">
            <div class="feature-material__icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h3 class="feature-material__title">Responsive Design</h3>
            <p class="feature-material__description">Accede desde cualquier dispositivo con nuestro diseño completamente responsive y optimizado para móviles.</p>
        </div>
        
        <div class="feature-material">
            <div class="feature-material__icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 class="feature-material__title">Seguridad Avanzada</h3>
            <p class="feature-material__description">Protección de datos empresariales con encriptación de nivel bancario y cumplimiento de normativas internacionales.</p>
        </div>
        
        <div class="feature-material">
            <div class="feature-material__icon">
                <i class="fas fa-clock"></i>
            </div>
            <h3 class="feature-material__title">Tiempo Real</h3>
            <p class="feature-material__description">Actualizaciones instantáneas, notificaciones en vivo y sincronización automática en todos los dispositivos.</p>
        </div>
    </div>
</div>

<!-- Project Showcase -->
<div class="container" style="margin: 4rem auto;">
    <div style="text-align: center; margin-bottom: 4rem;">
        <h2 style="font-size: 2.5rem; font-weight: bold; color: var(--color-gray-900); margin-bottom: 1rem; letter-spacing: -0.02em;">
            Proyectos Destacados
        </h2>
        <p style="font-size: 1.125rem; color: var(--color-gray-600);">
            Casos de éxito de eventos B2B realizados en nuestra plataforma
        </p>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
        <div class="card-material card-material--elevated">
            <div style="height: 200px; background: var(--gradient-primary); border-radius: var(--md-shape-corner-medium) var(--md-shape-corner-medium) 0 0;"></div>
            <div style="padding: 1.5rem;">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--color-gray-900);">Expo Tech 2024</h3>
                <p style="color: var(--color-gray-600); margin-bottom: 1rem;">Evento tecnológico que conectó 500+ empresas del sector TI con compradores internacionales.</p>
                <div style="display: flex; gap: 0.5rem; font-size: 0.875rem; color: var(--md-primary-40);">
                    <span>Tecnología</span>
                    <span>• 500+ Participantes</span>
                </div>
            </div>
        </div>
        
        <div class="card-material card-material--elevated">
            <div style="height: 200px; background: var(--gradient-secondary); border-radius: var(--md-shape-corner-medium) var(--md-shape-corner-medium) 0 0;"></div>
            <div style="padding: 1.5rem;">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--color-gray-900);">Industrial Summit</h3>
                <p style="color: var(--color-gray-600); margin-bottom: 1rem;">Encuentro industrial que generó más de $2M en negocios entre proveedores y compradores.</p>
                <div style="display: flex; gap: 0.5rem; font-size: 0.875rem; color: var(--md-primary-40);">
                    <span>Industrial</span>
                    <span>• $2M+ en Negocios</span>
                </div>
            </div>
        </div>
        
        <div class="card-material card-material--elevated">
            <div style="height: 200px; background: var(--gradient-material); border-radius: var(--md-shape-corner-medium) var(--md-shape-corner-medium) 0 0;"></div>
            <div style="padding: 1.5rem;">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--color-gray-900);">Green Business Fair</h3>
                <p style="color: var(--color-gray-600); margin-bottom: 1rem;">Feria de sostenibilidad que promovió negocios verdes y responsables con el medio ambiente.</p>
                <div style="display: flex; gap: 0.5rem; font-size: 0.875rem; color: var(--md-primary-40);">
                    <span>Sostenibilidad</span>
                    <span>• 100% Verde</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials -->
<div class="testimonials-section">
    <h2 class="testimonials-title">Lo que dicen nuestros clientes</h2>
    <div class="testimonials-grid">
        <div class="testimonial-card">
            <div class="testimonial-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
            </div>
            <div class="testimonial-content">
                La plataforma transformó completamente la manera en que organizamos nuestros eventos B2B. El nuevo diseño es intuitivo y profesional.
            </div>
            <div class="testimonial-author">
                <div class="testimonial-avatar">MG</div>
                <div class="testimonial-info">
                    <h4>María García</h4>
                    <p>Directora de Eventos, TechCorp</p>
                </div>
            </div>
        </div>
        
        <div class="testimonial-card">
            <div class="testimonial-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
            </div>
            <div class="testimonial-content">
                El matching automático nos ha ahorrado horas de trabajo y ha aumentado significativamente la calidad de las conexiones empresariales.
            </div>
            <div class="testimonial-author">
                <div class="testimonial-avatar">JR</div>
                <div class="testimonial-info">
                    <h4>Juan Rodríguez</h4>
                    <p>CEO, InnovaCorp</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div style="text-align: center; margin: 4rem 0; padding: 3rem 2rem; background: linear-gradient(135deg, var(--color-gray-50) 0%, var(--color-white) 100%); border-radius: 2rem;">
    <h2 style="font-size: 2rem; font-weight: bold; color: var(--color-gray-900); margin-bottom: 1rem;">
        ¿Listo para comenzar?
    </h2>
    <p style="font-size: 1.125rem; color: var(--color-gray-600); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
        Únete a miles de empresas que ya confían en B2B Conector para sus eventos empresariales
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <button class="btn btn--primary btn--lg">
            <i class="fas fa-user-plus"></i>
            Crear Cuenta Gratuita
        </button>
        <button class="btn btn--outline btn--lg">
            <i class="fas fa-phone"></i>
            Contactar Ventas
        </button>
    </div>
</div>

<!-- Testing Controls -->
<div class="card" style="margin-top: 3rem;">
    <div class="card__header">
        <h3 class="card__title">🧪 Controles de Testing</h3>
    </div>
    <div class="card__body">
        <div class="flex gap-3 flex-wrap">
            <button class="btn-material btn-material--filled" onclick="B2B.showNotification(\'¡Material Design 3 funcionando perfectamente!\', \'success\')">
                Test Notificación Éxito
            </button>
            <button class="btn-material btn-material--tonal" onclick="B2B.showNotification(\'Información sobre Material Design 3\', \'info\')">
                Test Notificación Info
            </button>
            <button class="btn-material btn-material--outlined" onclick="B2B.showNotification(\'Advertencia de testing\', \'warning\')">
                Test Warning
            </button>
        </div>
        
        <div style="margin-top: 2rem; padding: 1.5rem; background: var(--color-gray-50); border-radius: 1rem;">
            <h4 style="margin-bottom: 1rem;">📊 Métricas del Nuevo Diseño:</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <strong>Rendimiento:</strong><br>
                    <span style="color: var(--color-success-700);">+40% más rápido</span>
                </div>
                <div>
                    <strong>Experiencia:</strong><br>
                    <span style="color: var(--color-success-700);">Material Design 3</span>
                </div>
                <div>
                    <strong>Responsive:</strong><br>
                    <span style="color: var(--color-success-700);">100% Mobile-friendly</span>
                </div>
                <div>
                    <strong>Fuentes:</strong><br>
                    <span style="color: var(--color-primary-700);">Poppins + Montserrat</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card__footer">
        <a href="' . BASE_URL . '/dashboard" class="btn btn--secondary">
            <i class="fas fa-arrow-left"></i>
            Volver al Dashboard
        </a>
        <a href="test-components.html" class="btn btn--outline">
            <i class="fas fa-code"></i>
            Ver Componentes
        </a>
        <button class="btn-material btn-material--filled" onclick="window.location.reload()">
            <i class="fas fa-sync"></i>
            Recargar Demo
        </button>
    </div>
</div>
';

// Usar el layout refactorizado
include 'views/layouts/admin-refactored.php';
?>