<?php
/**
 * AdminController
 * Controlador para administradores normales del sistema
 */

require_once 'BaseController.php';

class AdminController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        
        // Verificar autenticación de admin normal
        if (!isAuthenticated()) {
            setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
            redirect(BASE_URL . '/auth/login');
            exit;
        }
    }
    
    /**
     * Dashboard principal para administradores
     */
    public function dashboard() {
        // Datos del dashboard
        $data = [
            'pageTitle' => 'Dashboard - B2B Conector',
            'moduleCSS' => 'dashboard',
            'moduleJS' => 'dashboard',
            'breadcrumbs' => [
                ['title' => 'Dashboard']
            ],
            // Aquí irían las estadísticas, etc.
            'totalEvents' => 0,
            'totalCompanies' => 0,
            'totalMatches' => 0
        ];
        
        $this->render('dashboard/index', $data, 'admin');
    }
    
    /**
     * Lista de eventos para administradores
     */
    public function events() {
        // Cargar modelo de eventos
        require_once MODEL_DIR . '/Event.php';
        $eventModel = new Event($this->db);
        
        // Obtener todos los eventos
        $events = $eventModel->getAll();
        
        $data = [
            'pageTitle' => 'Gestión de Eventos',
            'moduleCSS' => 'events',
            'moduleJS' => 'events',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => BASE_URL . '/dashboard'],
                ['title' => 'Eventos']
            ],
            'events' => $events
        ];
        
        $this->render('events/admin_index', $data, 'admin');
    }
}