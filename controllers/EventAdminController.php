<?php
/**
 * EventAdminController
 * Controlador para administradores de eventos específicos
 */

require_once 'BaseController.php';

class EventAdminController extends BaseController {
    private $eventModel;
    private $companyModel;
    private $matchModel;
    
    public function __construct() {
        parent::__construct();
        
        // Verificar autenticación de evento
        requireEventAuth();
        
        // Inicializar modelos
        $this->eventModel = new Event($this->db);
        $this->companyModel = new Company($this->db);
        $this->matchModel = new MatchModel($this->db);
    }
    
    /**
     * Dashboard principal para administradores de evento
     */
    public function dashboard() {
        $eventId = getEventId();
        
        // Cargar información del evento
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        $event = $this->eventModel;
        
        $data = [
            'pageTitle' => 'Dashboard - ' . $event->getEventName(),
            'moduleCSS' => 'event-dashboard',
            'moduleJS' => 'event-dashboard',
            'breadcrumbs' => [
                ['title' => 'Dashboard']
            ],
            'event' => $event,
            // Aquí irían las estadísticas del evento
            'stats' => [
                'total_companies' => 0,
                'total_matches' => 0,
                'total_appointments' => 0
            ]
        ];
        
        $this->render('event-dashboard/index', $data, 'event');
    }
    
    /**
     * Vista detallada del evento para event-admin
     */
    public function eventView() {
        $eventId = getEventId();
        
        // Verificar que sea el evento del usuario
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/event-dashboard');
            exit;
        }
        
        $event = $this->eventModel;
        
        // Cargar datos del evento
        $participants = $this->eventModel->getParticipants($eventId);
        $matches = method_exists($this->matchModel, 'findAllByEventId') ? $this->matchModel->findAllByEventId($eventId) : [];
        $schedules = $this->eventModel->getSchedules($eventId);
        
        $data = [
            'pageTitle' => $event->getEventName(),
            'moduleCSS' => 'events',
            'moduleJS' => 'events',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => BASE_URL . '/event-dashboard'],
                ['title' => 'Evento']
            ],
            'event' => $event,
            'eventModel' => $event, // Para compatibilidad con modales
            'participants' => $participants,
            'matches' => $matches,
            'schedules' => $schedules,
            'participantsCount' => count($participants),
            'matchCount' => count($matches),
            'scheduleCount' => count($schedules),
            'buyerCompaniesCount' => $this->eventModel->countCompaniesByRole($eventId, 'buyer'),
            'supplierCompaniesCount' => $this->eventModel->countCompaniesByRole($eventId, 'supplier'),
            'csrfToken' => generateCsrfToken()
        ];
        
        $this->render('events/view', $data, 'event');
    }
    
    /**
     * Alias para compatibilidad con snake_case URLs
     */
    public function event_view() {
        return $this->eventView();
    }
}