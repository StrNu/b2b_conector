<?php
/**
 * Demostración Material Design 3 - B2B Conector
 * Implementación completa del sistema Material Design 3
 */

// Configuración básica
$title = "B2B Conector - Material Design 3";
$moduleCSS = null;
$additionalCSS = ['material-theme.css'];

// Simular sesión de usuario para testing
session_start();
if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Material User';
    $_SESSION['user_id'] = 999;
}

// Incluir configuración básica
require_once 'config/config.php';
require_once 'config/helpers.php';

// Mensaje de bienvenida
if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [
        'success' => ['¡Bienvenido a B2B Conector con Material Design 3!'],
        'info' => ['Disfruta de la nueva experiencia visual inspirada en Material Design 3.']
    ];
}

// Contenido de la demostración
$content = '
<div class="material-theme">
    <!-- Material Design 3 Hero Section -->
    <div class="hero-material">
        <div class="container">
            <div class="hero-material__content">
                <h1 class="hero-material__title">B2B Conector</h1>
                <p class="hero-material__subtitle">Conectando el futuro empresarial con Material Design 3 - Una experiencia moderna, intuitiva y hermosa</p>
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
    </div>

    <!-- Material Features Grid -->
    <div class="container" style="margin: 4rem auto;">
        <div style="text-align: center; margin-bottom: 4rem;">
            <h2 style="font-size: 2.5rem; font-weight: 700; color: var(--color-gray-900); margin-bottom: 1rem; letter-spacing: -0.02em;">
                Características Material Design 3
            </h2>
            <p style="font-size: 1.125rem; color: var(--color-gray-600); max-width: 600px; margin: 0 auto;">
                Experimenta la nueva generación de diseño con componentes que se adaptan a tus necesidades
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            <div class="feature-material">
                <div class="feature-material__icon">
                    <i class="fas fa-palette"></i>
                </div>
                <h3 class="feature-material__title">Sistema de Color Dinámico</h3>
                <p class="feature-material__description">Paleta de colores purple/violet que se adapta automáticamente para crear experiencias visuales coherentes y accesibles.</p>
            </div>
            
            <div class="feature-material">
                <div class="feature-material__icon">
                    <i class="fas fa-shapes"></i>
                </div>
                <h3 class="feature-material__title">Formas Personalizadas</h3>
                <p class="feature-material__description">Bordes redondeados y formas orgánicas que crean una experiencia visual más natural y moderna.</p>
            </div>
            
            <div class="feature-material">
                <div class="feature-material__icon">
                    <i class="fas fa-hand-pointer"></i>
                </div>
                <h3 class="feature-material__title">Interacciones Naturales</h3>
                <p class="feature-material__description">Micro-interacciones y efectos de hover que responden de manera intuitiva a las acciones del usuario.</p>
            </div>
            
            <div class="feature-material">
                <div class="feature-material__icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="feature-material__title">Responsive por Diseño</h3>
                <p class="feature-material__description">Adaptación perfecta a cualquier tamaño de pantalla con componentes que escalan elegantemente.</p>
            </div>
        </div>
    </div>

    <!-- Material Cards Showcase -->
    <div class="container" style="margin: 4rem auto;">
        <div style="text-align: center; margin-bottom: 4rem;">
            <h2 style="font-size: 2.5rem; font-weight: 700; color: var(--color-gray-900); margin-bottom: 1rem;">
                Componentes Material
            </h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- Elevated Card -->
            <div class="card-material card-material--elevated">
                <div style="padding: 1.5rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--md-primary-10);">Card Elevada</h3>
                    <p style="color: var(--color-gray-600); margin-bottom: 1.5rem;">Card con elevación que flota sobre la superficie, perfecta para contenido destacado.</p>
                    <button class="btn-material btn-material--filled">Acción Principal</button>
                </div>
            </div>
            
            <!-- Filled Card -->
            <div class="card-material card-material--filled">
                <div style="padding: 1.5rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--md-primary-10);">Card Rellena</h3>
                    <p style="color: var(--color-gray-600); margin-bottom: 1.5rem;">Card con fondo relleno, ideal para contenido secundario o complementario.</p>
                    <button class="btn-material btn-material--tonal">Acción Secundaria</button>
                </div>
            </div>
            
            <!-- Outlined Card -->
            <div class="card-material card-material--outlined">
                <div style="padding: 1.5rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--md-primary-10);">Card con Borde</h3>
                    <p style="color: var(--color-gray-600); margin-bottom: 1.5rem;">Card con borde definido, perfecta para contenido que necesita separación visual clara.</p>
                    <button class="btn-material btn-material--outlined">Ver Más</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Material Form Demo -->
    <div class="container" style="margin: 4rem auto;">
        <div class="card-material card-material--elevated" style="max-width: 600px; margin: 0 auto;">
            <div style="padding: 2rem;">
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 2rem; text-align: center; color: var(--md-primary-10);">
                    Formulario Material Design 3
                </h3>
                
                <div class="textfield-material">
                    <input class="textfield-material__input" type="text" id="demo-name" placeholder=" ">
                    <label class="textfield-material__label" for="demo-name">Nombre completo</label>
                    <div class="textfield-material__supporting-text">Introduce tu nombre completo</div>
                </div>
                
                <div class="textfield-material">
                    <input class="textfield-material__input" type="email" id="demo-email" placeholder=" ">
                    <label class="textfield-material__label" for="demo-email">Correo electrónico</label>
                </div>
                
                <div class="textfield-material">
                    <input class="textfield-material__input" type="text" id="demo-company" placeholder=" ">
                    <label class="textfield-material__label" for="demo-company">Empresa</label>
                    <div class="textfield-material__supporting-text">Nombre de tu empresa u organización</div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button class="btn-material btn-material--filled" style="flex: 1;">
                        <i class="fas fa-check"></i>
                        Enviar
                    </button>
                    <button class="btn-material btn-material--outlined">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Testing Controls -->
    <div class="container" style="margin: 4rem auto;">
        <div class="card-material card-material--filled">
            <div style="padding: 2rem;">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; text-align: center;">
                    🧪 Controles de Testing Material Design 3
                </h3>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <button class="btn-material btn-material--filled" onclick="B2B.showNotification(\'¡Material Design 3 funcionando perfectamente!\', \'success\')">
                        Test Éxito
                    </button>
                    <button class="btn-material btn-material--tonal" onclick="B2B.showNotification(\'Información Material Design 3\', \'info\')">
                        Test Info
                    </button>
                    <button class="btn-material btn-material--outlined" onclick="B2B.showNotification(\'Advertencia Material\', \'warning\')">
                        Test Warning
                    </button>
                    <button class="btn-material btn-material--text" onclick="B2B.showNotification(\'Error Material Design\', \'error\')">
                        Test Error
                    </button>
                </div>
                
                <div style="margin-top: 2rem; padding: 1.5rem; background: var(--md-surface-container); border-radius: var(--md-shape-corner-medium);">
                    <h4 style="margin-bottom: 1rem; color: var(--md-primary-10);">📊 Características Material Design 3:</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.875rem;">
                        <div>
                            <strong>Colores:</strong><br>
                            <span style="color: var(--md-primary-40);">Purple/Violet Palette</span>
                        </div>
                        <div>
                            <strong>Tipografía:</strong><br>
                            <span style="color: var(--md-primary-40);">Poppins + Montserrat</span>
                        </div>
                        <div>
                            <strong>Componentes:</strong><br>
                            <span style="color: var(--md-primary-40);">Material Design 3</span>
                        </div>
                        <div>
                            <strong>Elevación:</strong><br>
                            <span style="color: var(--md-primary-40);">Sistema MD3</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab-material" onclick="window.scrollTo({top: 0, behavior: \'smooth\'})">
        <i class="fas fa-arrow-up"></i>
    </button>
</div>
';

// Usar el layout refactorizado
include 'views/layouts/admin-refactored.php';
?>