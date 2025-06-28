<?php
/**
 * PublicBaseController
 * Controlador base para páginas públicas sin autenticación
 */

class PublicBaseController {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance();

        // Iniciar sesión solo si es necesario para flash messages o datos de formulario
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Función auxiliar para compatibilidad con PHP < 8.0
     * 
     * @param string $haystack La cadena en la que buscar
     * @param string $needle La subcadena a buscar
     * @return bool True si haystack termina con needle, false en caso contrario
     */
    private function endsWithPhp($haystack, $needle) {
        if (function_exists('str_ends_with')) {
            return str_ends_with($haystack, $needle);
        }
        $length = strlen($needle);
        return $length === 0 || substr($haystack, -$length) === $needle;
    }
    
    /**
     * Construir ruta de vista de forma segura
     * 
     * @param string $view Nombre de la vista
     * @return string Ruta completa de la vista
     */
    private function buildViewPath($view) {
        return VIEW_DIR . '/' . $view . ($this->endsWithPhp($view, '.php') ? '' : '.php');
    }
    
    /**
     * Renderizar vista con layout dinámico para páginas públicas
     * 
     * @param string $view Ruta de la vista (ej: 'events/buyers_registration')
     * @param array $data Datos para la vista
     * @param string $layout Layout a usar (por defecto 'public')
     * @throws Exception Si la vista o layout no existen
     */
    public function render($view, $data = [], $layout = 'admin') {
        // Extraer datos para las vistas
        extract($data);
        
        // Verificar si la vista existe
        $viewPath = $this->buildViewPath($view);
        if (!file_exists($viewPath)) {
            throw new Exception("Vista no encontrada: $viewPath");
        }
        
        // Verificar si el layout existe
        $layoutPath = VIEW_DIR . "/layouts/{$layout}.php";
        if (!file_exists($layoutPath)) {
            throw new Exception("Layout no encontrado: $layoutPath");
        }
        
        // Asegurar que el contenido de la vista se capture
        ob_start();
        include $viewPath;
        $content = ob_get_clean();
        
        // Incluir el layout
        include $layoutPath;
    }
}