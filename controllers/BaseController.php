<?php
/**
 * BaseController
 * Controlador base con sistema de layouts y renders dinámicos
 */

class BaseController {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance();

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
     * Renderizar vista con layout dinámico
     * 
     * @param string $view Ruta de la vista (ej: 'events/index')
     * @param array $data Datos para la vista
     * @param string $layout Layout a usar ('admin' o 'event')
     * @throws Exception Si la vista o layout no existen
     */
    protected function render($view, $data = [], $layout = 'admin') {
        try {
            // Validar datos de entrada
            if (!is_array($data)) {
                throw new Exception("Los datos deben ser un array");
            }
            
            // Determinar layout automáticamente si no se especifica
            if ($layout === 'auto') {
                if (function_exists('isEventUserAuthenticated')) {
                    $layout = isEventUserAuthenticated() ? 'event' : 'admin';
                } else {
                    Logger::warning('Función isEventUserAuthenticated no encontrada, usando layout admin por defecto');
                    $layout = 'admin';
                }
            }
            
            // Construir rutas
            $viewPath = $this->buildViewPath($view);
            $layoutPath = VIEW_DIR . '/layouts/' . $layout . '.php';
            
            // Verificar que los archivos existan
            if (!file_exists($viewPath)) {
                Logger::error("Vista no encontrada", ['view' => $view, 'path' => $viewPath]);
                throw new Exception("Vista no encontrada");
            }
            
            if (!file_exists($layoutPath)) {
                Logger::error("Layout no encontrado", ['layout' => $layout, 'path' => $layoutPath]);
                throw new Exception("Layout no encontrado");
            }
            
            // Extraer datos de forma controlada (solo variables permitidas)
            $allowedVars = $this->sanitizeDataForExtract($data);
            extract($allowedVars, EXTR_SKIP); // EXTR_SKIP previene sobrescribir variables existentes
            
            // Capturar contenido de la vista
            ob_start();
            include $viewPath;
            $content = ob_get_clean();
            
            // Verificar que se generó contenido
            if ($content === false) {
                Logger::error("Error al capturar contenido de la vista", ['view' => $view]);
                throw new Exception("Error al procesar la vista");
            }
            
            // Renderizar con layout
            include $layoutPath;
            
        } catch (Exception $e) {
            Logger::error("Error en render()", [
                'view' => $view,
                'layout' => $layout,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Renderizar solo vista sin layout (para AJAX)
     * 
     * @param string $view Ruta de la vista
     * @param array $data Datos para la vista
     * @throws Exception Si la vista no existe
     */
    protected function renderPartial($view, $data = []) {
        try {
            // Validar datos de entrada
            if (!is_array($data)) {
                throw new Exception("Los datos deben ser un array");
            }
            
            $viewPath = $this->buildViewPath($view);
            
            if (!file_exists($viewPath)) {
                Logger::error("Vista parcial no encontrada", ['view' => $view, 'path' => $viewPath]);
                throw new Exception("Vista no encontrada");
            }
            
            // Extraer datos de forma controlada
            $allowedVars = $this->sanitizeDataForExtract($data);
            extract($allowedVars, EXTR_SKIP);
            
            include $viewPath;
            
        } catch (Exception $e) {
            Logger::error("Error en renderPartial()", [
                'view' => $view,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Sanitizar datos antes de usar extract()
     * 
     * @param array $data Datos a sanitizar
     * @return array Datos sanitizados
     */
    protected function sanitizeDataForExtract($data) {
        // Lista de variables peligrosas que no deben ser sobrescritas
        $dangerous_vars = [
            '_GET', '_POST', '_SESSION', '_COOKIE', '_FILES', '_SERVER', '_ENV',
            'GLOBALS', 'this', 'db', 'content', '__FILE__', '__DIR__', '__LINE__',
            '__FUNCTION__', '__CLASS__', '__METHOD__', '__NAMESPACE__'
        ];
        
        $sanitized = [];
        foreach ($data as $key => $value) {
            // Filtrar variables peligrosas
            if (in_array($key, $dangerous_vars)) {
                Logger::warning("Variable peligrosa filtrada en extract()", ['variable' => $key]);
                continue;
            }
            
            // Validar que la clave sea un nombre de variable válido
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $key)) {
                Logger::warning("Nombre de variable inválido filtrado", ['variable' => $key]);
                continue;
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }
}