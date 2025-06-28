<?php
/**
 * Controlador del Dashboard de Eventos
 * 
 * Este controlador maneja el dashboard específico para usuarios de eventos
 * (administradores de eventos y asistentes) con acceso restringido a su evento.
 * 
 * @package B2B Conector
 * @version 1.0
 */

require_once 'BaseController.php';

class EventDashboardController extends BaseController {
        private $eventModel;
    private $companyModel;
    private $assistantModel;
    private $matchModel;
    private $appointmentModel;

    /**
     * Constructor
     */
    public function __construct() {
        
        parent::__construct();
        
        // La conexión ya se inicializa en BaseController
        // $this->db ya está disponible
        
// Verificar autenticación de usuario de evento
        requireEventAuth();
        
        // Inicializar conexión a la base de datos        // Inicializar modelos
        $this->eventModel = new Event($this->db);
        $this->companyModel = new Company($this->db);
        $this->assistantModel = new Assistant($this->db);
        $this->matchModel = new MatchModel($this->db);
        $this->appointmentModel = new Appointment($this->db);
    }

    /**
     * Dashboard principal para usuarios de eventos
     * 
     * @return void
     */
    public function index() {
        $eventId = getEventId();
        $userType = getEventUserType();
        $companyId = getEventUserCompanyId();
        
        // Cargar información del evento
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        $event = $this->eventModel;
        
        // Preparar datos según el tipo de usuario
        $dashboardData = [];
        
        if (isEventAdmin()) {
            // Datos para administrador de evento
            $dashboardData = $this->getAdminDashboardData($eventId);
        } elseif (isEventAssistant()) {
            // Datos para asistente de evento
            $dashboardData = $this->getAssistantDashboardData($eventId, $companyId);
        }
        
        // Variables para la vista
        $pageTitle = 'Dashboard - ' . $event->getEventName();
        $moduleCSS = 'event-dashboard';
        $moduleJS = 'event-dashboard';
        
        // Cargar vista del dashboard
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'eventdashboardcontroller',
            'moduleJS' => 'eventdashboardcontroller'
        ];
        
        $this->render('event-dashboard/index', $data, 'admin');
    }
    
    /**
     * Obtener datos del dashboard para administradores de evento
     * 
     * @param int $eventId ID del evento
     * @return array Datos del dashboard
     */
    private function getAdminDashboardData($eventId) {
        // Estadísticas generales del evento
        $companies = $this->companyModel->getAll(['event_id' => $eventId]);
        $totalCompanies = count($companies);
        $buyerCompanies = count(array_filter($companies, fn($c) => $c['role'] === 'buyer'));
        $supplierCompanies = count(array_filter($companies, fn($c) => $c['role'] === 'supplier'));
        
        // Asistentes registrados
        $totalAssistants = $this->assistantModel->countByEvent($eventId);
        
        // Matches generados
        $totalMatches = $this->matchModel->countByEvent($eventId);
        $confirmedMatches = $this->matchModel->countByEventAndStatus($eventId, 'accepted');
        
        // Citas programadas
        $totalAppointments = $this->appointmentModel->countByEvent($eventId);
        $completedAppointments = $this->appointmentModel->countByEventAndStatus($eventId, 'completed');
        
        // Empresas recientes (últimas 5)
        $recentCompanies = array_slice($companies, -5);
        
        // Próximas citas (siguientes 5)
        $upcomingAppointments = $this->appointmentModel->getUpcomingByEvent($eventId, 5);
        
        return [
            'stats' => [
                'total_companies' => $totalCompanies,
                'buyer_companies' => $buyerCompanies,
                'supplier_companies' => $supplierCompanies,
                'total_assistants' => $totalAssistants,
                'total_matches' => $totalMatches,
                'confirmed_matches' => $confirmedMatches,
                'total_appointments' => $totalAppointments,
                'completed_appointments' => $completedAppointments
            ],
            'recent_companies' => $recentCompanies,
            'upcoming_appointments' => $upcomingAppointments,
            'quick_actions' => [
                'manage_companies' => BASE_URL . '/events/companies/' . $eventId,
                'view_matches' => BASE_URL . '/events/matches/' . $eventId,
                'view_schedules' => BASE_URL . '/events/schedules/' . $eventId,
                'export_data' => BASE_URL . '/events/export/' . $eventId
            ]
        ];
    }
    
    /**
     * Obtener datos del dashboard para asistentes de evento
     * 
     * @param int $eventId ID del evento
     * @param int $companyId ID de la empresa
     * @return array Datos del dashboard
     */
    private function getAssistantDashboardData($eventId, $companyId) {
        // Información de la empresa
        if (!$this->companyModel->findById($companyId)) {
            return [];
        }
        
        $company = $this->companyModel;
        
        // Matches de la empresa
        $companyMatches = $this->matchModel->getByCompany($companyId);
        $confirmedMatches = array_filter($companyMatches, fn($m) => $m['status'] === 'accepted');
        
        // Citas de la empresa
        $companyAppointments = $this->appointmentModel->getByCompany($companyId);
        $upcomingAppointments = array_filter($companyAppointments, function($a) {
            return strtotime($a['start_datetime']) > time();
        });
        
        // Asistentes de la empresa
        $companyAssistants = $this->assistantModel->findByCompany($companyId);
        
        return [
            'company' => $company,
            'stats' => [
                'total_matches' => count($companyMatches),
                'confirmed_matches' => count($confirmedMatches),
                'total_appointments' => count($companyAppointments),
                'upcoming_appointments' => count($upcomingAppointments),
                'company_assistants' => count($companyAssistants)
            ],
            'recent_matches' => array_slice($companyMatches, -5),
            'upcoming_appointments' => array_slice($upcomingAppointments, 0, 5),
            'company_assistants' => $companyAssistants,
            'quick_actions' => [
                'view_agenda' => BASE_URL . '/event-dashboard/attendee_agenda',
                'edit_company' => BASE_URL . '/events/edit_participant/' . $eventId . '/' . $companyId,
                'manage_assistants' => BASE_URL . '/event-dashboard/assistants',
                'view_matches' => BASE_URL . '/matches/index?event_id=' . $eventId . '&company_id=' . $companyId
            ]
        ];
    }
    
    /**
     * Ver agenda personal de la empresa
     * 
     * @return void
     */
    public function agenda() {
        requireEventRole('assistant');
        
        $eventId = getEventId();
        $companyId = getEventUserCompanyId();
        
        if (!$companyId) {
            setFlashMessage('No se pudo identificar su empresa', 'danger');
            redirect(BASE_URL . '/event-dashboard');
            exit;
        }
        
        // Cargar información del evento y empresa
        $this->eventModel->findById($eventId);
        $this->companyModel->findById($companyId);
        
        $event = $this->eventModel;
        $company = $this->companyModel;
        
        // Obtener citas de la empresa
        $appointments = $this->appointmentModel->getByCompany($companyId);
        
        // Agrupar por día
        $appointmentsByDay = [];
        foreach ($appointments as $appointment) {
            $day = date('Y-m-d', strtotime($appointment['start_datetime']));
            if (!isset($appointmentsByDay[$day])) {
                $appointmentsByDay[$day] = [];
            }
            $appointmentsByDay[$day][] = $appointment;
        }
        
        $pageTitle = 'Mi Agenda - ' . $event->getEventName();
        $moduleCSS = 'calendar';
        $moduleJS = 'calendar';
        
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'eventdashboardcontroller',
            'moduleJS' => 'eventdashboardcontroller'
        ];
        
        $this->render('event-dashboard/agenda', $data, 'admin');
    }
    
    /**
     * Vista pública de agenda para asistentes (buyers/suppliers)
     * Esta vista muestra solo la agenda sin menú administrativo
     * 
     * @return void
     */
    public function attendee_agenda() {
        requireEventRole('assistant');
        
        $eventId = getEventId();
        $companyId = getEventUserCompanyId();
        
        if (!$companyId) {
            setFlashMessage('No se pudo identificar su empresa', 'danger');
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        // Cargar información del evento y empresa
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        $event = $this->eventModel;
        $company = $this->companyModel;
        
        // Obtener citas de la empresa con información detallada
        $appointments = $this->appointmentModel->getByCompany($companyId, $eventId);
        
        // Agrupar por día
        $appointmentsByDay = [];
        if (is_array($appointments)) {
            foreach ($appointments as $appointment) {
                if (isset($appointment['start_datetime'])) {
                    $day = date('Y-m-d', strtotime($appointment['start_datetime']));
                    if (!isset($appointmentsByDay[$day])) {
                        $appointmentsByDay[$day] = [];
                    }
                    $appointmentsByDay[$day][] = $appointment;
                }
            }
        }
        
        // Extraer variables para la vista standalone
        $pageTitle = 'Mi Agenda - ' . $event->getEventName();
        
        // Verificar que el archivo de vista existe antes de incluirlo
        $viewFile = VIEW_DIR . '/event-dashboard/attendee_agenda.php';
        if (!file_exists($viewFile)) {
            Logger::error('Vista attendee_agenda.php no encontrada');
            setFlashMessage('Vista no encontrada', 'danger');
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        // Debug information
        Logger::debug('Renderizando attendee_agenda', [
            'event_name' => $event->getEventName(),
            'company_name' => $company->getCompanyName(),
            'appointments_count' => count($appointments),
            'days_with_appointments' => count($appointmentsByDay)
        ]);
        
        // Renderizar vista standalone (sin layout administrativo)
        include $viewFile;
    }
    
    /**
     * Ver matches de la empresa
     * 
     * @return void
     */
    public function matches() {
        requireEventRole('assistant');
        
        $eventId = getEventId();
        $companyId = getEventUserCompanyId();
        
        if (!$companyId) {
            setFlashMessage('No se pudo identificar su empresa', 'danger');
            redirect(BASE_URL . '/event-dashboard');
            exit;
        }
        
        // Cargar matches de la empresa
        $matches = $this->matchModel->getByCompany($companyId);
        
        // Cargar información del evento y empresa
        $this->eventModel->findById($eventId);
        $this->companyModel->findById($companyId);
        
        $event = $this->eventModel;
        $company = $this->companyModel;
        
        $pageTitle = 'Mis Matches - ' . $event->getEventName();
        $moduleCSS = 'matches';
        $moduleJS = 'matches';
        
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'eventdashboardcontroller',
            'moduleJS' => 'eventdashboardcontroller'
        ];
        
        $this->render('event-dashboard/matches', $data, 'admin');
    }
    
    /**
     * Gestionar asistentes de la empresa
     * 
     * @return void
     */
    public function assistants() {
        requireEventRole('assistant');
        
        $eventId = getEventId();
        $companyId = getEventUserCompanyId();
        
        if (!$companyId) {
            setFlashMessage('No se pudo identificar su empresa', 'danger');
            redirect(BASE_URL . '/event-dashboard');
            exit;
        }
        
        // Cargar asistentes de la empresa
        $assistants = $this->assistantModel->findByCompany($companyId);
        
        // Cargar información del evento y empresa
        $this->eventModel->findById($eventId);
        $this->companyModel->findById($companyId);
        
        $event = $this->eventModel;
        $company = $this->companyModel;
        
        $pageTitle = 'Mis Asistentes - ' . $event->getEventName();
        $moduleCSS = 'assistants';
        $moduleJS = 'assistants';
        
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'eventdashboardcontroller',
            'moduleJS' => 'eventdashboardcontroller'
        ];
        
        $this->render('event-dashboard/assistants', $data, 'admin');
    }
    
    /**
     * Cerrar sesión de usuario de evento
     * 
     * @return void
     */
    public function logout() {
        requireEventAuth();
        
        // Cerrar sesión específica de evento
        logoutEventUser();
        
        setFlashMessage('Ha cerrado sesión exitosamente', 'success');
        redirect(BASE_URL . '/auth/event-login');
    }
}