<?php
/**
 * Controlador de Eventos
 * 
 * Este controlador maneja todas las operaciones relacionadas con los eventos
 * incluyendo creación, modificación, visualización y eliminación de eventos
 * así como la gestión de participantes, breaks y horarios de citas.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class EventController {
    private $db;
    private $eventModel;
    private $companyModel;
    private $matchModel;
    private $categoryModel; // Añadido el modelo de categorías
    private $validator;
    private $assistantModel; // Añadido
    private $requirementModel; // Añadido
    private $attendanceDayModel; // Añadido
    
    /**
     * Constructor
     * 
     * Inicializa los modelos necesarios y otras dependencias
     */
    public function __construct() {
        // Inicializar conexión a la base de datos
        $this->db = Database::getInstance();
        
        // Inicializar modelos
        $this->eventModel = new Event($this->db);
        $this->companyModel = new Company($this->db);
        $this->matchModel = new MatchModel($this->db);
        $this->categoryModel = new Category($this->db); // Inicializado el modelo de categorías
        $this->assistantModel = new Assistant($this->db); // Inicializado
        $this->requirementModel = new Requirement($this->db); // Inicializado
        $this->attendanceDayModel = new AttendanceDay($this->db); // Inicializado
        
        // Inicializar validador
        $this->validator = new Validator();
        
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
            redirect(BASE_URL . '/auth/login');
            exit;
        }
    }
    
    /**
     * Listar todos los eventos
     * 
     * @return void
     */
    public function index() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Obtener parámetros de paginación y filtros
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        // Configurar filtros
        $filters = [];
        
        // Filtrar por nombre si se especifica
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['event_name'] = '%' . sanitize($_GET['search']) . '%';
        }
        
        // Filtrar por estado si se especifica
        if (isset($_GET['status']) && in_array($_GET['status'], ['1', '0'])) {
            $filters['is_active'] = (int)$_GET['status'];
        }
        
        // Obtener total de eventos según filtros
        $totalEvents = $this->eventModel->count($filters);
        
        // Configurar paginación
        $pagination = paginate($totalEvents, $page, $perPage);
        
        // Obtener eventos para la página actual con filtros aplicados
        $events = $this->eventModel->getAll($filters, $pagination);

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/events/index.php');
    }

    /**
     * Mostrar el registro completo de una empresa para un evento.
     * Muestra todos los datos recopilados en el formulario de buyers_registration.
     *
     * @param int $eventId ID del evento
     * @param int $companyId ID de la empresa
     * @return void
     */
    public function viewFullRegistration($eventId, $companyId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }

        // Cargar evento
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $event = $this->eventModel;

        // Cargar empresa
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/events/view/' . $eventId);
            exit;
        }
        // Verificar que la empresa pertenece al evento (si companyModel tiene event_id como propiedad)
        if ($this->companyModel->getEventId() != $eventId) {
            setFlashMessage('La empresa no pertenece a este evento.', 'danger');
            redirect(BASE_URL . '/events/view/' . $eventId);
            exit;
        }
        $company = $this->companyModel;

        // Obtener datos adicionales
        $assistants = $this->companyModel->getAssistants($companyId);
        
        // Para los requerimientos, necesitamos los nombres de categorías/subcategorías
        $rawRequirements = $this->companyModel->getRequirements($companyId);
        $requirements = [];
        if ($rawRequirements) {
            foreach ($rawRequirements as $req) {
                // Asumimos que getRequirements ya trae category_name y subcategory_name
                // Si no, necesitaríamos cargarlos aquí usando CategoryModel
                $requirements[] = $req; 
            }
        }

        $attendanceDays = $this->companyModel->getAttendanceDays($eventId, $companyId);

        // Obtener el username (email) de la tabla event_users
        $eventUsername = null;
        $userQuery = "SELECT username FROM event_users WHERE company_id = :company_id AND event_id = :event_id LIMIT 1";
        $userResult = $this->db->single($userQuery, ['company_id' => $companyId, 'event_id' => $eventId]);
        if ($userResult && isset($userResult['username'])) {
            $eventUsername = $userResult['username'];
        }
        
        $pageTitle = 'Detalle de Registro de Empresa';
        $moduleCSS = 'events'; // Puedes usar el mismo CSS o crear uno nuevo
        $moduleJS = 'events';

        include(VIEW_DIR . '/events/view_full_registration.php');
    }

    // ...rest of the methods...
}