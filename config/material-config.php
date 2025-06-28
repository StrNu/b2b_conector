<?php
/**
 * Material Design 3 Configuration
 * Configuración global para Material Design 3 en B2B Conector
 * 
 * @version 1.0.0
 */

// Flag global para habilitar Material Design 3
define('MATERIAL_DESIGN_ENABLED', true);

// Configuración de componentes Material
$materialConfig = [
    'enable_material_buttons' => true,
    'enable_material_cards' => true,
    'enable_material_forms' => true,
    'enable_material_navigation' => true,
    'enable_material_fab' => true,
    
    // Configuración de colores
    'primary_color' => '#9c27b0',
    'secondary_color' => '#673ab7',
    'surface_color' => '#fef7ff',
    
    // Configuración de typography
    'primary_font' => 'Poppins',
    'secondary_font' => 'Montserrat',
    
    // Configuración de elevación
    'enable_elevation' => true,
    'max_elevation_level' => 5,
];

/**
 * Helper function para determinar si usar componentes Material
 */
function useMaterialDesign() {
    return defined('MATERIAL_DESIGN_ENABLED') && MATERIAL_DESIGN_ENABLED;
}

/**
 * Helper function para obtener clases CSS Material
 */
function getMaterialClass($component, $variant = '') {
    if (!useMaterialDesign()) {
        return '';
    }
    
    $baseClass = '';
    switch ($component) {
        case 'button':
            $baseClass = 'btn-material';
            if ($variant) $baseClass .= ' btn-material--' . $variant;
            break;
            
        case 'card':
            $baseClass = 'card-material';
            if ($variant) $baseClass .= ' card-material--' . $variant;
            break;
            
        case 'textfield':
            $baseClass = 'textfield-material';
            break;
            
        case 'nav':
            $baseClass = 'nav-material';
            break;
            
        case 'hero':
            $baseClass = 'hero-material';
            break;
            
        case 'feature':
            $baseClass = 'feature-material';
            break;
            
        case 'fab':
            $baseClass = 'fab-material';
            break;
    }
    
    return $baseClass;
}

/**
 * Helper function para obtener CSS adicional Material
 */
function getMaterialCSS() {
    if (!useMaterialDesign()) {
        return [];
    }
    
    return ['material-theme.css'];
}

/**
 * Helper function para generar botón Material
 */
function materialButton($text, $variant = 'filled', $icon = '', $attributes = '') {
    if (!useMaterialDesign()) {
        return '<button class="btn btn--primary" ' . $attributes . '>' . 
               ($icon ? '<i class="' . $icon . '"></i> ' : '') . $text . '</button>';
    }
    
    return '<button class="' . getMaterialClass('button', $variant) . '" ' . $attributes . '>' . 
           ($icon ? '<i class="' . $icon . '"></i> ' : '') . $text . '</button>';
}

/**
 * Helper function para generar card Material
 */
function materialCard($title, $content, $variant = 'elevated', $actions = '') {
    if (!useMaterialDesign()) {
        return '<div class="card">
                    <div class="card__header"><h3 class="card__title">' . $title . '</h3></div>
                    <div class="card__body">' . $content . '</div>
                    ' . ($actions ? '<div class="card__footer">' . $actions . '</div>' : '') . '
                </div>';
    }
    
    return '<div class="' . getMaterialClass('card', $variant) . '">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--color-gray-200);">
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0;">' . $title . '</h3>
                </div>
                <div style="padding: 1.5rem;">' . $content . '</div>
                ' . ($actions ? '<div style="padding: 1.5rem; border-top: 1px solid var(--color-gray-200);">' . $actions . '</div>' : '') . '
            </div>';
}
?>