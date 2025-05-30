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
    private $categoryModel;
    private $breakModel;
    private $assistantModel;
    private $eventScheduleModel;
    private $requirementModel;
    private $attendanceDayModel;
    private $validator;

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
        $this->matchModel = new MatchModel($this->db); // Corregido: era Match
        $this->categoryModel = new Category($this->db); // Inicializado el modelo de categorías
        
        // Inicializar validador
        $this->validator = new Validator();
        
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
            redirect(BASE_URL . '/auth/login');
            exit;
        }
    }

    private function _showNotFoundError($message = "Recurso no encontrado.") {
        Logger::getInstance()->error($message);
        http_response_code(404);
        $this->loadView('errors/404', ['error_message' => $message]); 
        exit;
    }
    
    /**
     * Verificar permisos del usuario
     * 
     * @param array $roles Roles permitidos
     * @param string $redirect URL de redirección en caso de no tener permisos
     * @return void
     */
    private function checkPermission($roles, $redirect = BASE_URL) {
        if (!hasRole($roles)) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect($redirect);
            exit;
        }
    }

    /**
 * Mostrar formulario para crear un nuevo evento
 * 
 * @return void
 */
public function create() {
    // Verificar si el usuario está autenticado
    if (!isAuthenticated()) {
        setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
        redirect(BASE_URL . '/auth/login');
        exit;
    }
    
    // Verificar permisos (solo administradores y organizadores pueden crear eventos)
    if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
        setFlashMessage('No tiene permisos para crear eventos', 'danger');
        redirect(BASE_URL . '/events');
        exit;
    }
    
    // Generar token CSRF para el formulario
    $csrfToken = generateCSRFToken();
    
    // Recuperar datos del formulario en caso de error (para repoblar el formulario)
    $formData = $_SESSION['form_data'] ?? [
        'event_name' => '',
        'venue' => '',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+3 days')),
        'meeting_duration' => DEFAULT_MEETING_DURATION,
        'available_tables' => 10,
        'description' => '',
        'is_active' => true
    ];
    
    // Limpiar datos del formulario de la sesión
    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']);
    }
    
    // Obtener categorías disponibles para el evento
    $categories = $this->categoryModel->getAll();

    $pageTitle = 'Eventos';
    $moduleCSS = 'events';
    $moduleJS = 'events';
    $additionalCSS = 'components/datepicker.css';
    $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
    
    // Incluir la vista
    include(VIEW_DIR . '/events/create.php');
}

/**
 * Procesar la creación de un nuevo evento
 * 
 * @return void
 */
public function store() {
    // Verificar permisos
    if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
        setFlashMessage('No tiene permisos para crear eventos', 'danger');
        redirect(BASE_URL);
        exit;
    }
    
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(BASE_URL . '/events/create');
        exit;
    }
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
        redirect(BASE_URL . '/events/create');
        exit;
    }
    
    // Validar datos del formulario
    $this->validator->setData($_POST);
    $this->validator->required('event_name', 'El nombre del evento es obligatorio')
                   ->required('venue', 'La sede es obligatoria')
                   ->required('start_date', 'La fecha de inicio es obligatoria')
                   ->required('end_date', 'La fecha de finalización es obligatoria')
                   ->required('start_time', 'La hora de inicio es obligatoria')
                   ->required('end_time', 'La hora de finalización es obligatoria')
                   ->required('available_tables', 'El número de mesas es obligatorio')
                   ->required('meeting_duration', 'La duración de reuniones es obligatoria')
                   ->numeric('available_tables', 'El número de mesas debe ser un valor numérico')
                   ->numeric('meeting_duration', 'La duración de reuniones debe ser un valor numérico');
    
    // Validar fechas y horas
    $startDate = sanitize($_POST['start_date']);
    $endDate = sanitize($_POST['end_date']);
    $startTime = sanitize($_POST['start_time']);
    $endTime = sanitize($_POST['end_time']);
    
    // Convertir fechas al formato de base de datos (yyyy-mm-dd)
    $startDateDb = dateToDatabase($startDate);
    $endDateDb = dateToDatabase($endDate);
    
    // Validar que la fecha de fin sea posterior o igual a la fecha de inicio
    if (strtotime($endDateDb) < strtotime($startDateDb)) {
        $this->validator->errors['end_date'] = 'La fecha de finalización debe ser posterior o igual a la fecha de inicio';
    }
    
    // Validar que la hora de fin sea posterior a la hora de inicio si es el mismo día
    if ($startDateDb === $endDateDb && strtotime($endTime) <= strtotime($startTime)) {
        $this->validator->errors['end_time'] = 'La hora de finalización debe ser posterior a la hora de inicio';
    }
    
    // Si hay errores de validación, volver al formulario
    if ($this->validator->hasErrors()) {
        $_SESSION['form_data'] = $_POST;
        $_SESSION['validation_errors'] = $this->validator->getErrors();
        
        redirect(BASE_URL . '/events/create');
        exit;
    }
    
    try {
        // Iniciar transacción
        $this->db->beginTransaction();
        
        // Procesar logos si se han subido
        $eventLogoFilename = null;
        $companyLogoFilename = null;
        
        // Procesar logo del evento
        if (isset($_FILES['event_logo']) && $_FILES['event_logo']['error'] === UPLOAD_ERR_OK) {
            $eventLogoFilename = $this->processLogo($_FILES['event_logo'], 'event');
        }
        
        // Procesar logo de la empresa
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $companyLogoFilename = $this->processLogo($_FILES['company_logo'], 'company');
        }
        
        // Preparar datos del evento
        $eventData = [
            'event_name' => sanitize($_POST['event_name']),
            'venue' => sanitize($_POST['venue']),
            'start_date' => $startDateDb,
            'end_date' => $endDateDb,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'available_tables' => (int)$_POST['available_tables'],
            'meeting_duration' => (int)$_POST['meeting_duration'],
            'has_break' => isset($_POST['has_break']) ? 1 : 0,
            'company_name' => sanitize($_POST['company_name'] ?? ''),
            'contact_name' => sanitize($_POST['contact_name'] ?? ''),
            'contact_phone' => sanitize($_POST['contact_phone'] ?? ''),
            'contact_email' => sanitize($_POST['contact_email'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'event_logo' => $eventLogoFilename,
            'company_logo' => $companyLogoFilename
        ];
        
        // Crear el evento
        $eventId = $this->eventModel->create($eventData);
        
        if (!$eventId) {
            throw new Exception('Error al crear el evento');
        }
        
        // Procesar breaks si están habilitados
        if (isset($_POST['has_break']) && isset($_POST['break_start_time']) && isset($_POST['break_end_time'])) {
            $startTimes = $_POST['break_start_time'];
            $endTimes = $_POST['break_end_time'];
            
            if (is_array($startTimes) && is_array($endTimes)) {
                $count = min(count($startTimes), count($endTimes));
                
                for ($i = 0; $i < $count; $i++) {
                    $breakStartTime = sanitize($startTimes[$i]);
                    $breakEndTime = sanitize($endTimes[$i]);
                    
                    // Validar que horas de break sean válidas
                    if (!empty($breakStartTime) && !empty($breakEndTime) && strtotime($breakEndTime) > strtotime($breakStartTime)) {
                        $this->eventModel->addBreak($breakStartTime, $breakEndTime, $eventId);
                    }
                }
            }
        }
        
        // Confirmar transacción
        $this->db->commit();
        
        // Mensaje de éxito y redirección
        setFlashMessage('Evento creado exitosamente', 'success');
        redirect(BASE_URL . '/events/view/' . $eventId);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $this->db->rollback();
        
        Logger::error('Error al crear evento: ' . $e->getMessage(), [
            'event_data' => $eventData ?? [],
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        
        setFlashMessage('Error al crear el evento: ' . $e->getMessage(), 'danger');
        
        // Guardar datos del formulario para recuperarlos
        $_SESSION['form_data'] = $_POST;
        redirect(BASE_URL . '/events/create');
    }
}

/**
 * Procesar y guardar un logo (evento o empresa)
 * 
 * @param array $fileData Datos del archivo subido
 * @param string $type Tipo de logo ('event' o 'company')
 * @return string|null Nombre del archivo guardado o null si hay error
 * @throws Exception Si hay error en el procesamiento
 */
private function processLogo($fileData, $type = 'event') {
    // Validar tamaño máximo
    if ($fileData['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('El tamaño del logo excede el límite permitido (' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB)');
    }
    
    // Validar extensión
    $extension = getFileExtension($fileData['name']);
    if (!isAllowedExtension($extension)) {
        throw new Exception('Formato de archivo no permitido. Formatos aceptados: ' . implode(', ', ALLOWED_EXTENSIONS));
    }
    
    // Generar prefijo según tipo
    $prefix = $type === 'event' ? 'event_' : 'company_';
    
    // Generar nombre único para el archivo
    $filename = $prefix . uniqid() . '.' . $extension;
    
    // Asegurar que existe el directorio de logos
    if (!is_dir(LOGO_DIR)) {
        if (!mkdir(LOGO_DIR, 0755, true)) {
            throw new Exception('Error al crear el directorio para almacenar logos');
        }
    }
    
    // Ruta completa del archivo
    $filepath = LOGO_DIR . '/' . $filename;
    
    // Mover el archivo subido a su ubicación final
    if (!move_uploaded_file($fileData['tmp_name'], $filepath)) {
        throw new Exception('Error al guardar el logo');
    }
    
    return $filename;
}
    
    /**
     * Mostrar detalles de un evento específico
     * 
     * @param int $id ID del evento
     * @return void
     */
    public function view($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        $eventId = filter_var($id, FILTER_VALIDATE_INT);
        if ($eventId === false || $eventId <= 0) {
            Logger::warning('Intento de acceso a evento con ID inválido.', ['id_proporcionado' => $id, 'user_id' => $_SESSION['user_id'] ?? null]);
            setFlashMessage('ID de evento inválido', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }

        // Cargar el evento. findById() en Event.php carga los datos en $this->eventModel
        if (!$this->eventModel->findById($eventId)) {
            Logger::notice('Evento no encontrado al intentar ver.', ['event_id' => $eventId, 'user_id' => $_SESSION['user_id'] ?? null]);
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        $_SESSION['event_id'] = $id;
        $event = $this->eventModel;
        $eventModel = $event; // Para compatibilidad con modals/import_categories.php
        $breaks = $this->eventModel->getBreaks($eventId); 
        $participants = $this->eventModel->getParticipants($eventId);
        $matches = method_exists($this->matchModel, 'findAllByEventId') ? $this->matchModel->findAllByEventId($eventId) : [];
        $schedules = $this->eventModel->getSchedules($eventId);
        // CORRECCIÓN: Construir correctamente categoriesWithSubcategories
        $eventCategories = $this->categoryModel->getEventCategories($eventId);
        $categoriesWithSubcategories = [];
        foreach ($eventCategories as $category) {
            $subcategories = $this->categoryModel->getEventSubcategories($category['event_category_id']);
            $categoriesWithSubcategories[] = [
                'category' => $category,
                'subcategories' => $subcategories
            ];
        }
        $hasCategories = !empty($categoriesWithSubcategories);
        $csrfToken = Security::generateCsrfToken();
        $viewData = [
            'event' => $event,
            'eventModel' => $eventModel,
            'breaks' => $breaks,
            'participants' => $participants,
            'matches' => $matches,
            'schedules' => $schedules,
            'hasCategories' => $hasCategories,
            'categoriesWithSubcategories' => $categoriesWithSubcategories,
            'csrfToken' => $csrfToken
        ];
        foreach ($viewData as $key => $value) {
            $$key = $value;
        }
        include(VIEW_DIR . '/events/view.php');
    }
        
     /**
     * Mostrar solo las citas reales programadas para un evento (no slots teóricos)
     * @param int $id ID del evento
     * @return void
     */
    public function schedules($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $event = $this->eventModel;
        $appointmentModel = new Appointment($this->db);
        // Obtener días del evento
        $startDate = new DateTime($event->getStartDate());
        $endDate = new DateTime($event->getEndDate());
        $days = [];
        $current = clone $startDate;
        while ($current <= $endDate) {
            $days[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
        // Obtener todas las citas reales del evento
        $appointments = $appointmentModel->getByEvent($id);
        // Agrupar por día
        $schedulesByDay = [];
        foreach ($days as $day) {
            $schedulesByDay[$day] = [];
        }
        foreach ($appointments as $appt) {
            $day = substr($appt['start_datetime'], 0, 10);
            if (isset($schedulesByDay[$day])) {
                $schedulesByDay[$day][] = $appt;
            }
        }
        // Obtener matches para mostrar nombres de empresas
        $matches = [];
        $matchList = $this->matchModel->getByEvent($id);
        foreach ($matchList as $m) {
            $matches[$m['match_id']] = $m;
        }
        $csrfToken = generateCSRFToken();
        include(VIEW_DIR . '/events/schedules.php');
    }
    
    /**
     * Mostrar y gestionar los matches para un evento
     * 
     * @param int $id ID del evento
     * @return void
     */
    public function matches($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Obtener parámetros de filtrado
        $status = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'accepted', 'rejected']) 
                ? $_GET['status'] : null;
        
        // Procesar generación de matches si se solicita
        if (isset($_POST['generate_matches'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/matches/' . $id);
                exit;
            }
            
            try {
                // Configurar opciones de generación
                $options = [
                    'forceRegenerate' => isset($_POST['force_regenerate']) ? true : false
                ];
                
                // Generar matches
                $result = $this->matchModel->generateMatches($id, $options);
                
                if ($result['success']) {
                    setFlashMessage($result['message'], 'success');
                } else {
                    throw new Exception($result['message']);
                }
            } catch (Exception $e) {
                setFlashMessage('Error al generar matches: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/events/matches/' . $id);
            exit;
        }
        
        // Procesar actualización de estado de match si se solicita
        if (isset($_POST['update_match_status'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/matches/' . $id);
                exit;
            }
            
            if (isset($_POST['match_id']) && isset($_POST['status'])) {
                $matchId = (int)$_POST['match_id'];
                $newStatus = sanitize($_POST['status']);
                
                try {
                    $updated = $this->matchModel->updateStatus($matchId, $newStatus);
                    
                    if ($updated) {
                        setFlashMessage('Estado del match actualizado exitosamente', 'success');
                    } else {
                        throw new Exception('Error al actualizar el estado del match');
                    }
                } catch (Exception $e) {
                    setFlashMessage('Error al actualizar el match: ' . $e->getMessage(), 'danger');
                }
                
                redirect(BASE_URL . '/events/matches/' . $id);
                exit;
            }
        }
        
        // Obtener matches según filtros
        $matches = $this->matchModel->getByEvent($id, $status);
        // --- AGREGAR DÍAS DE ASISTENCIA DE CADA EMPRESA AL ARRAY DE MATCHES (como strings, no JSON) ---
        require_once(MODEL_DIR . '/AttendanceDay.php');
        require_once(MODEL_DIR . '/Requirement.php');
        $attendanceModel = new AttendanceDay($this->db);
        $requirementModel = new Requirement($this->db);
        foreach ($matches as &$match) {
            $buyer_days = $attendanceModel->getByCompanyAndEvent($match['buyer_id'], $match['event_id']);
            $supplier_days = $attendanceModel->getByCompanyAndEvent($match['supplier_id'], $match['event_id']);
            $match['buyer_days'] = array_map('strval', $buyer_days);
            $match['supplier_days'] = array_map('strval', $supplier_days);
            // Adjuntar total de requerimientos del comprador
            $reqs = $requirementModel->findByBuyer($match['buyer_id']);
            $match['total_buyer_requirements'] = count($reqs);
        }
        unset($match);
        $customMatches = $matches; // <--- Asegura que la variable esté disponible para la vista
        
        // Contadores de matches por estado
        $countPending = $this->matchModel->count(['event_id' => $id, 'status' => 'pending']);
        $countAccepted = $this->matchModel->count(['event_id' => $id, 'status' => 'accepted']);
        $countRejected = $this->matchModel->count(['event_id' => $id, 'status' => 'rejected']);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
        
        // --- NUEVO: OBTENER COMPRADORES Y PROVEEDORES SIN MATCHES (CON DATOS COMPLETOS) ---
        $buyers = $this->companyModel->getBuyersByEvent($id);
        $suppliers = $this->companyModel->getSuppliersByEvent($id);
        $buyersWithoutMatches = [];
        $suppliersWithoutMatches = [];
        foreach ($buyers as $buyer) {
            $buyerMatches = $this->matchModel->getByBuyer($buyer['company_id'], $id);
            if (empty($buyerMatches)) {
                $companyObj = new Company($this->db);
                $companyObj->findById($buyer['company_id']);
                // Ya no es necesario forzar el rol, getRequirements lo consulta correctamente
                $requirements = $companyObj->getRequirements($buyer['company_id'], $id);
                $attendanceDays = $this->companyModel->getAttendanceDays($id, $buyer['company_id']);
                $buyersWithoutMatches[] = [
                    'company' => ['company_name' => $buyer['company_name']],
                    'requirements' => $requirements,
                    'attendance_days' => $attendanceDays
                ];
            }
        }
        foreach ($suppliers as $supplier) {
            $supplierMatches = $this->matchModel->getBySupplier($supplier['company_id'], $id);
            if (empty($supplierMatches)) {
                $offers = $this->companyModel->getOffers($supplier['company_id']);
                $attendanceDays = $this->companyModel->getAttendanceDays($id, $supplier['company_id']);
                $suppliersWithoutMatches[] = [
                    'company' => ['company_name' => $supplier['company_name']],
                    'offers' => $offers,
                    'attendance_days' => $attendanceDays
                ];
            }
        }
        include(VIEW_DIR . '/events/matches.php');
    }
    
    /**
     * Crear un nuevo match manual para un evento
     * 
     * @param int $id ID del evento
     * @return void
     */
    public function createMatch($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Procesar creación de match si se solicita
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/create-match/' . $id);
                exit;
            }
            
            // Validar datos
            $this->validator->setData($_POST);
            $this->validator->required('buyer_id', 'El comprador es obligatorio')
                           ->required('supplier_id', 'El proveedor es obligatorio');
            
            // Si hay errores de validación
            if ($this->validator->hasErrors()) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                
                redirect(BASE_URL . '/events/create-match/' . $id);
                exit;
            }
            
            try {
                $buyerId = (int)$_POST['buyer_id'];
                $supplierId = (int)$_POST['supplier_id'];
                
                // Crear match manual
                $matchId = $this->matchModel->createManualMatch($buyerId, $supplierId, $id);
                
                if ($matchId) {
                    setFlashMessage('Match creado exitosamente', 'success');
                    redirect(BASE_URL . '/events/matches/' . $id);
                    exit;
                } else {
                    throw new Exception('Error al crear el match');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al crear el match: ' . $e->getMessage(), 'danger');
                $_SESSION['form_data'] = $_POST;
                redirect(BASE_URL . '/events/create-match/' . $id);
                exit;
            }
        }
        
        // Obtener compradores y proveedores para el formulario
        $buyers = $this->eventModel->getBuyers($id);
        $suppliers = $this->eventModel->getSuppliers($id);
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
        
        // Cargar vista
        include(VIEW_DIR . '/events/create_match.php');
    }
    
    /**
     * Ver y gestionar detalles de un match específico
     * 
     * @param int $id ID del evento
     * @param int $matchId ID del match
     * @return void
     */
    public function viewMatch($id, $matchId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar match por ID
        if (!$this->matchModel->findById($matchId)) {
            setFlashMessage('Match no encontrado', 'danger');
            redirect(BASE_URL . '/events/matches/' . $id);
            exit;
        }
        
        // Verificar que el match pertenece al evento
        if ($this->matchModel->getEventId() != $id) {
            setFlashMessage('El match no pertenece a este evento', 'danger');
            redirect(BASE_URL . '/events/matches/' . $id);
            exit;
        }
        
        // Procesar actualización de estado si se solicita
        if (isset($_POST['update_status'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/view-match/' . $id . '/' . $matchId);
                exit;
            }
            
            if (isset($_POST['status'])) {
                $newStatus = sanitize($_POST['status']);
                
                try {
                    $updated = $this->matchModel->updateStatus($matchId, $newStatus);
                    
                    if ($updated) {
                        setFlashMessage('Estado del match actualizado exitosamente', 'success');
                    } else {
                        throw new Exception('Error al actualizar el estado del match');
                    }
                } catch (Exception $e) {
                    setFlashMessage('Error al actualizar el match: ' . $e->getMessage(), 'danger');
                }
                
                redirect(BASE_URL . '/events/view-match/' . $id . '/' . $matchId);
                exit;
            }
        }
        
        // Obtener información adicional del match
        $buyerId = $this->matchModel->getBuyerId();
        $supplierId = $this->matchModel->getSupplierId();
        
        // Obtener información de comprador y proveedor
        $buyer = new Company($this->db);
        $supplier = new Company($this->db);
        
        $buyer->findById($buyerId);
        $supplier->findById($supplierId);
        
        // Obtener categorías coincidentes
        $matchedCategories = $this->matchModel->getMatchedCategoriesArray();
        
        // Verificar si ya existe una cita para este match
        $appointmentModel = new Appointment($this->db);
        $hasSchedule = $appointmentModel->existsForMatch($matchId);
        
        if ($hasSchedule) {
            // Obtener detalles de la cita
            $schedule = $appointmentModel->getByMatch($matchId);
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
        
        // Cargar vista
        include(VIEW_DIR . '/events/view_match.php');
    }
    
    /**
     * Crear manualmente una cita para un match
     * 
     * @param int $id ID del evento
     * @param int $matchId ID del match
     * @return void
     */
    public function createSchedule($id, $matchId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar match por ID
        if (!$this->matchModel->findById($matchId)) {
            setFlashMessage('Match no encontrado', 'danger');
            redirect(BASE_URL . '/events/matches/' . $id);
            exit;
        }
        
        // Verificar que el match pertenece al evento
        if ($this->matchModel->getEventId() != $id) {
            setFlashMessage('El match no pertenece a este evento', 'danger');
            redirect(BASE_URL . '/events/matches/' . $id);
            exit;
        }
        
        // Verificar si ya existe una cita para este match
        $appointmentModel = new Appointment($this->db);
        $hasSchedule = $appointmentModel->existsForMatch($matchId);
        
        if ($hasSchedule) {
            // Obtener detalles de la cita
            $schedule = $appointmentModel->getByMatch($matchId);
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
        
        // Cargar vista
        include(VIEW_DIR . '/events/create_schedule.php');
    }
    
    /**
     * Exportar agenda de citas de un evento a CSV
     * 
     * @param int $id ID del evento
     * @return void
     */
    public function exportSchedules($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Obtener todas las citas para el evento
        $appointmentModel = new Appointment($this->db);
        $schedules = $appointmentModel->getByEvent($id);
        
        if (empty($schedules)) {
            setFlashMessage('No hay citas programadas para exportar', 'warning');
            redirect(BASE_URL . '/events/schedules/' . $id);
            exit;
        }
        
        // Configurar cabeceras para descarga de CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="agenda_' . $id . '_' . date('Ymd') . '.csv"');
        
        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // Establecer el separador de columnas y la codificación UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escribir cabeceras de columnas
        fputcsv($output, [
            'ID',
            'Fecha',
            'Hora Inicio',
            'Hora Fin',
            'Mesa',
            'Comprador',
            'Proveedor',
            'Estado'
        ]);
        
        // Escribir datos
        foreach ($schedules as $schedule) {
            $datetime = new DateTime($schedule['start_datetime']);
            $date = $datetime->format('d/m/Y');
            $startTime = $datetime->format('H:i');
            
            $endDatetime = new DateTime($schedule['end_datetime']);
            $endTime = $endDatetime->format('H:i');
            
            fputcsv($output, [
                $schedule['schedule_id'],
                $date,
                $startTime,
                $endTime,
                $schedule['table_number'],
                $schedule['buyer_name'],
                $schedule['supplier_name'],
                $schedule['status']
            ]);
        }
        
        // Cerrar archivo y finalizar
        fclose($output);
        exit;
    }
    
    /**
     * Ver y gestionar asistentes de una empresa para un evento
     * 
     * @param int $id ID del evento
     * @param int $companyId ID de la empresa
     * @return void
     */
    public function companyAssistants($id, $companyId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/events/participants/' . $id);
            exit;
        }
        
        // Verificar que la empresa pertenece al evento
        if ($this->companyModel->getEventId() != $id) {
            setFlashMessage('La empresa no pertenece a este evento', 'danger');
            redirect(BASE_URL . '/events/participants/' . $id);
            exit;
        }
        
        // Procesar eliminación de asistente si se solicita
        if (isset($_POST['delete_assistant']) && isset($_POST['assistant_id'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/company-assistants/' . $id . '/' . $companyId);
                exit;
            }
            
            $assistantId = (int)$_POST['assistant_id'];
            
            try {
                $deleted = $this->companyModel->removeAssistant($assistantId);
                
                if ($deleted) {
                    setFlashMessage('Asistente eliminado exitosamente', 'success');
                } else {
                    throw new Exception('Error al eliminar el asistente');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al eliminar el asistente: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/events/company-assistants/' . $id . '/' . $companyId);
            exit;
        }
        
        // Procesar creación de nuevo asistente
        if (isset($_POST['add_assistant'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/company-assistants/' . $id . '/' . $companyId);
                exit;
            }
            
            // Validar datos
            $this->validator->setData($_POST);
            $this->validator->required('first_name', 'El nombre es obligatorio')
                           ->required('last_name', 'El apellido es obligatorio')
                           ->required('email', 'El email es obligatorio')
                           ->email('email', 'El email no es válido');
            
            // Si hay errores de validación
            if ($this->validator->hasErrors()) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                
                redirect(BASE_URL . '/events/company-assistants/' . $id . '/' . $companyId);
                exit;
            }
            
            try {
                $assistantData = [
                    'first_name' => sanitize($_POST['first_name']),
                    'last_name' => sanitize($_POST['last_name']),
                    'email' => sanitize($_POST['email']),
                    'mobile_phone' => sanitize($_POST['mobile_phone'] ?? '')
                ];
                
                $assistantId = $this->companyModel->addAssistant($assistantData, $companyId);
                
                if ($assistantId) {
                    setFlashMessage('Asistente agregado exitosamente', 'success');
                } else {
                    throw new Exception('Error al agregar el asistente');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al agregar el asistente: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/events/company-assistants/' . $id . '/' . $companyId);
            exit;
        }
        
        // Obtener asistentes de la empresa
        $assistants = $this->companyModel->getAssistants($companyId);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
        
        // Cargar vista
        include(VIEW_DIR . '/events/company_assistants.php');
    }
    
    /**
     * Ver y gestionar días de asistencia de una empresa a un evento
     * 
     * @param int $id ID del evento
     * @param int $companyId ID de la empresa
     * @return void
     */
    public function companyAttendance($id, $companyId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/events/participants/' . $id);
            exit;
        }
        
        // Verificar que la empresa pertenece al evento
        if ($this->companyModel->getEventId() != $id) {
            setFlashMessage('La empresa no pertenece a este evento', 'danger');
            redirect(BASE_URL . '/events/participants/' . $id);
            exit;
        }
        
        // Procesar eliminación de día de asistencia
        if (isset($_POST['delete_attendance']) && isset($_POST['date'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/company-attendance/' . $id . '/' . $companyId);
                exit;
            }
            
            $date = sanitize($_POST['date']);
            
            try {
                $deleted = $this->eventModel->removeAttendanceDay($companyId, $date, $id);
                
                if ($deleted) {
                    setFlashMessage('Día de asistencia eliminado exitosamente', 'success');
                } else {
                    throw new Exception('Error al eliminar el día de asistencia');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al eliminar el día de asistencia: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/events/company-attendance/' . $id . '/' . $companyId);
            exit;
        }
        
        // Procesar adición de día de asistencia
        if (isset($_POST['add_attendance'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/company-attendance/' . $id . '/' . $companyId);
                exit;
            }
            
            // Validar datos
            $this->validator->setData($_POST);
            $this->validator->required('date', 'La fecha es obligatoria')
                           ->date('date', 'd/m/Y', 'Formato de fecha inválido (dd/mm/yyyy)');

            // Si hay errores de validación
            if ($this->validator->hasErrors()) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                
                redirect(BASE_URL . '/events/company-attendance/' . $id . '/' . $companyId);
                exit;
            }
            
            // Obtener la fecha
            $date = sanitize($_POST['date']);

            try {
                // Añadir el día de asistencia
                $added = $this->eventModel->addAttendanceDay($companyId, $date, $id);
                
                if ($added) {
                    setFlashMessage('Día de asistencia agregado exitosamente', 'success');
                } else {
                    throw new Exception('Error al agregar el día de asistencia. Verifique que la fecha esté dentro del rango del evento y no esté duplicada.');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al agregar el día de asistencia: ' . $e->getMessage(), 'danger');
            }

            redirect(BASE_URL . '/events/company-attendance/' . $id . '/' . $companyId);
            exit;
        }
        
        // Obtener días de asistencia actuales
        $attendanceDays = $this->eventModel->getAttendanceDays($companyId, $id);

        // Formatear fechas para la vista
        $formattedDays = [];
        foreach ($attendanceDays as $day) {
            $formattedDays[] = dateFromDatabase($day);
        }

        // Obtener fechas del evento para el selector de fechas
        // Crear un rango de fechas entre la fecha de inicio y fin del evento
        $startDate = new DateTime($this->eventModel->getStartDate());
        $endDate = new DateTime($this->eventModel->getEndDate());
        $interval = $startDate->diff($endDate);
        $totalDays = $interval->days + 1;

        $eventDays = [];
        $currentDate = clone $startDate;

        for ($i = 0; $i < $totalDays; $i++) {
            $eventDays[] = $currentDate->format('d/m/Y');
            $currentDate->modify('+1 day');
        }

        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];

        // Cargar vista
        include(VIEW_DIR . '/events/company_attendance.php');
}

/**
 * Duplicar un evento existente
 * 
 * @param int $id ID del evento a duplicar
 * @return void
 */
public function duplicate($id) {
    // Verificar permisos
    if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
        setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
        redirect(BASE_URL);
        exit;
    }
    
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(BASE_URL . '/events');
        exit;
    }
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
        redirect(BASE_URL . '/events');
        exit;
    }
    
    // Buscar evento por ID
    if (!$this->eventModel->findById($id)) {
        setFlashMessage('Evento no encontrado', 'danger');
        redirect(BASE_URL . '/events');
        exit;
    }
    
    try {
        // Iniciar transacción
        $this->db->beginTransaction();
        
        // Obtener datos del evento
        $eventData = [
            'event_name' => $this->eventModel->getEventName() . ' (Copia)',
            'venue' => $this->eventModel->getVenue(),
            'start_date' => $this->eventModel->getStartDate(),
            'end_date' => $this->eventModel->getEndDate(),
            'available_tables' => $this->eventModel->getAvailableTables(),
            'meeting_duration' => $this->eventModel->getMeetingDuration(),
            'is_active' => 0, // La copia se crea inactiva por defecto
            'start_time' => $this->eventModel->getStartTime(),
            'end_time' => $this->eventModel->getEndTime(),
            'has_break' => $this->eventModel->hasBreak(),
            'company_name' => $this->eventModel->getCompanyName(),
            'contact_first_name' => $this->eventModel->getContactFirstName(),
            'contact_last_name' => $this->eventModel->getContactLastName(),
            'email' => $this->eventModel->getEmail(),
            'phone' => $this->eventModel->getPhone()
        ];
        
        // Crear el nuevo evento
        $newEventId = $this->eventModel->create($eventData);
        
        if (!$newEventId) {
            throw new Exception('Error al duplicar el evento');
        }
        
        // Duplicar breaks
        $breaks = $this->eventModel->getBreaks($id);
        foreach ($breaks as $break) {
            $breakData = [
                'event_id' => $newEventId,
                'start_time' => $break['start_time'],
                'end_time' => $break['end_time']
            ];
            
            $this->eventModel->addBreak($breakData['start_time'], $breakData['end_time'], $newEventId);
        }
        
        // Confirmar transacción
        $this->db->commit();
        
        setFlashMessage('Evento duplicado exitosamente', 'success');
        redirect(BASE_URL . '/events/view/' . $newEventId);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $this->db->rollback();
        
        setFlashMessage('Error al duplicar el evento: ' . $e->getMessage(), 'danger');
        redirect(BASE_URL . '/events/view/' . $id);
    }
}

/**
 * Generar reporte de estadísticas del evento
 * 
 * @param int $id ID del evento
 * @return void
 */
public function report($id) {
    // Verificar permisos
    if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
        setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
        redirect(BASE_URL);
        exit;
    }
    
    // Buscar evento por ID
    if (!$this->eventModel->findById($id)) {
        setFlashMessage('Evento no encontrado', 'danger');
        redirect(BASE_URL . '/events');
        exit;
    }
    
    // Recopilar estadísticas
    $stats = [
        'event' => [
            'id' => $id,
            'name' => $this->eventModel->getEventName(),
            'start_date' => dateFromDatabase($this->eventModel->getStartDate()),
            'end_date' => dateFromDatabase($this->eventModel->getEndDate()),
            'venue' => $this->eventModel->getVenue()
        ],
        'participants' => [
            'total' => count($this->eventModel->getParticipants($id)),
            'buyers' => count($this->eventModel->getBuyers($id)),
            'suppliers' => count($this->eventModel->getSuppliers($id))
        ],
        'matches' => [
            'total' => $this->matchModel->count(['event_id' => $id]),
            'pending' => $this->matchModel->count(['event_id' => $id, 'status' => 'pending']),
            'accepted' => $this->matchModel->count(['event_id' => $id, 'status' => 'accepted']),
            'rejected' => $this->matchModel->count(['event_id' => $id, 'status' => 'rejected'])
        ],
        'appointments' => [
            'total' => 0,
            'scheduled' => 0,
            'completed' => 0,
            'cancelled' => 0
        ]
    ];
    
    // Obtener datos de citas
    $appointmentModel = new Appointment($this->db);
    $stats['appointments']['total'] = $appointmentModel->count(['event_id' => $id]);
    $stats['appointments']['scheduled'] = $appointmentModel->count(['event_id' => $id, 'status' => Appointment::STATUS_SCHEDULED]);
    $stats['appointments']['completed'] = $appointmentModel->count(['event_id' => $id, 'status' => Appointment::STATUS_COMPLETED]);
    $stats['appointments']['cancelled'] = $appointmentModel->count(['event_id' => $id, 'status' => Appointment::STATUS_CANCELLED]);
    
    // Calcular algunas métricas
    $stats['metrics'] = [
        'match_rate' => $stats['participants']['total'] > 0 ? 
            round(($stats['matches']['total'] / $stats['participants']['total']) * 100, 2) : 0,
        'acceptance_rate' => $stats['matches']['total'] > 0 ? 
            round(($stats['matches']['accepted'] / $stats['matches']['total']) * 100, 2) : 0,
        'appointment_completion_rate' => $stats['appointments']['total'] > 0 ? 
            round(($stats['appointments']['completed'] / $stats['appointments']['total']) * 100, 2) : 0
    ];
    
    // Token CSRF para los formularios
    $csrfToken = generateCsrfToken();

    $pageTitle = 'Eventos';
    $moduleCSS = 'events';
    $moduleJS = 'events';
    $additionalCSS = 'components/datepicker.css';
    $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
    
    // Cargar vista
    include(VIEW_DIR . '/events/report.php');
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
        
        // Obtener eventos para la página current con filtros aplicados
        $events = $this->eventModel->getAll($filters, $pagination);

        $csrfToken = Security::generateCsrfToken();

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
        // Usar el método público getEventId() en vez de acceder directamente a la propiedad privada
        if (method_exists($this->companyModel, 'getEventId') && $this->companyModel->getEventId() != $eventId) {
            setFlashMessage('La empresa no pertenece a este evento (verificación de método).', 'danger');
            redirect(BASE_URL . '/events/view/' . $eventId);
            exit;
        }
        $company = $this->companyModel;

        // Obtener datos adicionales
        $assistants = $this->companyModel->getAssistants($companyId);
        
        $rawRequirements = $this->companyModel->getRequirements($companyId);
        $requirements = [];
        if ($rawRequirements) {
            foreach ($rawRequirements as $req) {
                if (isset($req['event_subcategory_id'])) {
                    $requirements[$req['event_subcategory_id']] = $req;
                }
            }
        }

        $attendanceDays = $this->companyModel->getAttendanceDays($eventId, $companyId);

        // No existe la columna 'email' en event_users, así que solo obtenemos el rol o el id
        $eventUserRole = null;
        $eventUserEmail = null;
        $userQuery = "SELECT id, role, email FROM event_users WHERE company_id = :company_id AND event_id = :event_id LIMIT 1";
        $userResult = $this->db->single($userQuery, ['company_id' => $companyId, 'event_id' => $eventId]);
        if ($userResult) {
            $eventUserRole = $userResult['role'] ?? null;
            $eventUserEmail = $userResult['email'] ?? null;
        }
        
        // Obtener categorías y subcategorías del evento para la vista de detalle
        $categories = $this->categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $this->categoryModel->getEventSubcategories($cat['event_category_id']);
        }
        
        $csrfToken = Security::generateCsrfToken(); // Añadido para consistencia si se necesitan formularios

        $pageTitle = 'Detalle de Registro de Empresa';
        $moduleCSS = 'events'; 
        $moduleJS = 'events';

        $viewData = [
            'event' => $event,
            'company' => $company,
            'assistants' => $assistants,
            'requirements' => $requirements,
            'attendanceDays' => $attendanceDays,
            'eventUsername' => $eventUserRole,
            'eventUserEmail' => $eventUserEmail,
            'csrfToken' => $csrfToken,
            'pageTitle' => $pageTitle,
            'moduleCSS' => $moduleCSS,
            'moduleJS' => $moduleJS,
            'categories' => $categories,
            'subcategories' => $subcategories
        ];
        
        // Asumiendo que tienes una vista en views/events/view_full_registration.php
        $this->loadView('events/view_full_registration', $viewData);
    }

    /**
     * Listar todos los eventos en formato de tarjetas (o alternativo al index)
     * 
     * @return void
     */
    public function list() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Obtener parámetros de paginación y filtros
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 9; // Número de eventos por página para la vista de tarjetas
        
        // Configurar filtros
        $filters = [];
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['event_name'] = '%' . sanitize($_GET['search']) . '%';
        }
        if (isset($_GET['status']) && in_array($_GET['status'], ['1', '0'])) {
            $filters['is_active'] = (int)$_GET['status'];
        }
        
        // Obtener total de eventos según filtros
        $totalEvents = $this->eventModel->count($filters);
        
        // Configurar paginación
        $pagination = paginate($totalEvents, $page, $perPage);
        
        // Obtener eventos para la página actual con filtros aplicados
        $events = $this->eventModel->getAll($filters, $pagination);

        $pageTitle = 'Listado de Eventos (Tarjetas)';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $csrfToken = Security::generateCsrfToken();
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/events/list.php');
    }

    /**
     * Listar todas las empresas asociadas a un evento
     * @param int $eventId
     * @return void
     */
    public function companies($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Buscar evento por ID
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Obtener empresas asociadas al evento
        $companies = $this->companyModel->getByEvent($eventId);
        $pageTitle = 'Empresas del Evento';
        $moduleCSS = 'companies';
        $moduleJS = 'companies';
        $csrfToken = Security::generateCsrfToken();
        include(VIEW_DIR . '/events/companies.php');
    }

    /**
     * Mostrar y gestionar descansos (breaks) de un evento
     * @param int $eventId
     * @return void
     */
    public function breaks($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Buscar evento por ID
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Obtener descansos asociados al evento
        $breaks = $this->eventModel->getBreaks($eventId);
        $pageTitle = 'Descansos del Evento';
        $moduleCSS = 'breaks';
        $moduleJS = 'breaks';
        $csrfToken = Security::generateCsrfToken();
        include(VIEW_DIR . '/events/breaks.php');
    }

    /**
     * Mostrar y gestionar categorías de un evento
     * @param int $eventId
     * @return void
     */
    public function categories($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Buscar evento por ID
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Obtener categorías y subcategorías asociadas al evento
        $categories = $this->categoryModel->getEventCategories($eventId);
        $categoriesWithSubcategories = [];
        foreach ($categories as $category) {
            $subcategories = $this->categoryModel->getEventSubcategories($category['event_category_id']);
            $categoriesWithSubcategories[] = [
                'category' => $category,
                'subcategories' => $subcategories
            ];
        }
        $eventModel = $this->eventModel;
        $pageTitle = 'Categorías del Evento';
        $moduleCSS = 'categories';
        $moduleJS = 'categories';
        $csrfToken = Security::generateCsrfToken();
        include(VIEW_DIR . '/events/categories.php');
    }

    /**
     * Listar participantes (asistentes) de un evento
     * @param int $eventId
     * @return void
     */
    public function participants($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Buscar evento por ID
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Obtener participantes (asistentes) del evento
        $participants = $this->eventModel->getParticipants($eventId);
        // Obtener empresas asociadas al evento para el select
        $companies = $this->companyModel->getByEvent($eventId);
        $pageTitle = 'Participantes del Evento';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $csrfToken = Security::generateCsrfToken();
        include(VIEW_DIR . '/events/participants.php');
    }

    /**
     * Editar un participante (asistente) de un evento
     * @param int $eventId
     * @param int $participantId
     * @return void
     */
    public function editParticipant($eventId, $participantId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Buscar evento por ID
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Buscar participante (asistente) por ID
        $assistantModel = new Assistant($this->db);
        if (!$assistantModel->findById($participantId)) {
            setFlashMessage('Participante no encontrado', 'danger');
            redirect(BASE_URL . '/events/participants/' . $eventId);
            exit;
        }
        $participant = $assistantModel;

        // Procesar formulario POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . "/events/editParticipant/$eventId/$participantId");
                exit;
            }
            // Validar datos
            $this->validator->setData($_POST);
            $this->validator->required('first_name', 'El nombre es obligatorio')
                ->required('last_name', 'El apellido es obligatorio')
                ->required('email', 'El email es obligatorio')
                ->email('email', 'El email no es válido');
            if ($this->validator->hasErrors()) {
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                redirect(BASE_URL . "/events/editParticipant/$eventId/$participantId");
                exit;
            }
            // Actualizar datos
            $data = [
                'first_name' => sanitize($_POST['first_name']),
                'last_name' => sanitize($_POST['last_name']),
                'email' => sanitize($_POST['email']),
                'mobile_phone' => sanitize($_POST['mobile_phone'] ?? ''),
                // company_id no editable, pero se envía como hidden
                'company_id' => $participant->getCompanyId(),
            ];
            $updated = $participant->update($data);
            if ($updated) {
                setFlashMessage('Participante actualizado exitosamente', 'success');
                redirect(BASE_URL . "/events/participants/$eventId");
                exit;
            } else {
                setFlashMessage('No se pudo actualizar el participante. Verifique los datos.', 'danger');
                redirect(BASE_URL . "/events/editParticipant/$eventId/$participantId");
                exit;
            }
        }

        $csrfToken = Security::generateCsrfToken();
        $pageTitle = 'Editar Participante';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        include(VIEW_DIR . '/events/edit_participant.php');
    }

    /**
     * Eliminar un participante (asistente) de un evento
     * @param int $eventId
     * @param int $participantId
     * @return void
     */
    public function deleteParticipant($eventId, $participantId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . "/events/participants/$eventId");
            exit;
        }
        // Validar CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . "/events/participants/$eventId");
            exit;
        }
        // Buscar participante (asistente) por ID
        $assistantModel = new Assistant($this->db);
        if (!$assistantModel->findById($participantId)) {
            setFlashMessage('Participante no encontrado', 'danger');
            redirect(BASE_URL . "/events/participants/$eventId");
            exit;
        }
        // Eliminar participante
        if ($assistantModel->delete()) {
            setFlashMessage('Participante eliminado exitosamente', 'success');
        } else {
            setFlashMessage('No se pudo eliminar el participante', 'danger');
        }
        redirect(BASE_URL . "/events/participants/$eventId");
        exit;
    }

    /**
     * Agregar un nuevo participante (asistente) a un evento
     * @param int $eventId
     * @return void
     */
    public function addParticipant($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para agregar participantes', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . "/events/participants/$eventId");
            exit;
        }
        // Validar CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . "/events/participants/$eventId");
            exit;
        }
        // Validar datos
        $this->validator->setData($_POST);
        $this->validator->required('first_name', 'El nombre es obligatorio')
            ->required('last_name', 'El apellido es obligatorio')
            ->required('email', 'El email es obligatorio')
            ->email('email', 'El email no es válido')
            ->required('company_id', 'Debe seleccionar una empresa');
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            redirect(BASE_URL . "/events/participants/$eventId");
            exit;
        }
        // Insertar asistente
        $assistantModel = new Assistant($this->db);
        $data = [
            'first_name' => sanitize($_POST['first_name']),
            'last_name' => sanitize($_POST['last_name']),
            'email' => sanitize($_POST['email']),
            'mobile_phone' => sanitize($_POST['mobile_phone'] ?? ''),
            'company_id' => (int)$_POST['company_id']
        ];
        $created = $assistantModel->create($data);
        if ($created) {
            setFlashMessage('Participante agregado exitosamente', 'success');
        } else {
            setFlashMessage('No se pudo agregar el participante. Verifique los datos.', 'danger');
        }
        redirect(BASE_URL . "/events/participants/$eventId");
        exit;
    }

    /**
     * Agrega una nueva categoría a un evento (POST)
     * Espera: event_id (en URL o POST), name (POST)
     */
    public function addEventCategory($eventId) {
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para agregar categorías', 'danger');
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if ($name === '') {
            setFlashMessage('El nombre de la categoría es obligatorio', 'danger');
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        // Insertar en event_categories
        $data = [
            'event_id' => (int)$eventId,
            'name' => $name,
            'is_active' => 1
        ];
        $result = $this->categoryModel->addEventCategory($eventId, $name);
        if ($result) {
            setFlashMessage('Categoría agregada correctamente', 'success');
        } else {
            setFlashMessage('No se pudo agregar la categoría', 'danger');
        }
        redirect(BASE_URL . '/events/categories/' . $eventId);
        exit;
    }

    /**
     * Agrega una nueva subcategoría a una categoría de evento (POST)
     * Espera: eventId (en URL), categoryId (en URL), subcategory_name (POST)
     */
    public function addEventSubCategory($eventId, $categoryId) {
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para agregar subcategorías', 'danger');
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        $name = isset($_POST['subcategory_name']) ? trim($_POST['subcategory_name']) : '';
        if ($name === '') {
            setFlashMessage('El nombre de la subcategoría es obligatorio', 'danger');
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        $result = $this->categoryModel->addEventSubcategory($categoryId, $name);
        if ($result) {
            setFlashMessage('Subcategoría agregada correctamente', 'success');
        } else {
            setFlashMessage('No se pudo agregar la subcategoría', 'danger');
        }
        redirect(BASE_URL . "/events/categories/$eventId");
        exit;
    }

    // Método genérico para cargar vistas
    protected function loadView($viewName, $data = []) {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $viewName . '.php';

        if (file_exists($viewFile)) {
           
           
            if (file_exists(__DIR__ . '/../views/shared/header.php')) {
                include __DIR__ . '/../views/shared/header.php';
            }
            
            include $viewFile;
            
            if (file_exists(__DIR__ . '/../views/shared/footer.php')) {
                include __DIR__ . '/../views/shared/footer.php';
            }
        } else {
            Logger::getInstance()->error("Archivo de vista no encontrado: {$viewFile}");
            http_response_code(500);
            echo "Error: la vista '{$viewName}' no se encontró.";
        }
    }

     /**
     * Editar una categoría de evento
     * @param int $categoryId
     * @param int $eventId
     * @return void
     */
    public function editEventCategory($categoryId, $eventId) {
        Logger::debug('Entrando a editEventCategory', ['categoryId' => $categoryId, 'eventId' => $eventId, 'POST' => $_POST]);
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::warning('Método no permitido en editEventCategory', ['method' => $_SERVER['REQUEST_METHOD']]);
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        $name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
        if ($name === '') {
            Logger::warning('Nombre de categoría vacío en editEventCategory', ['POST' => $_POST]);
            setFlashMessage('El nombre de la categoría es obligatorio', 'danger');
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        $result = $this->categoryModel->editEventCategory($categoryId, ['name' => $name]);
        Logger::debug('Resultado de editEventCategory', ['result' => $result]);
        if ($result) {
            setFlashMessage('Categoría actualizada correctamente', 'success');
        } else {
            setFlashMessage('No se pudo actualizar la categoría', 'danger');
        }
        Logger::debug('Redirigiendo tras editar categoría', ['eventId' => $eventId]);
        redirect(BASE_URL . '/events/categories/' . $eventId);
        exit;
    }

    /**
     * Eliminar una categoría de evento
     * @param int $categoryId
     * @return void
     */
    public function deleteEventCategory($categoryId) {
        Logger::debug('Entrando a deleteEventCategory', ['categoryId' => $categoryId, 'POST' => $_POST]);
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::warning('Método no permitido en deleteEventCategory', ['method' => $_SERVER['REQUEST_METHOD']]);
            setFlashMessage('Método no permitido', 'danger');
            $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
            if (!$eventId) {
                $category = $this->categoryModel->getEventCategory($categoryId);
                $eventId = $category['event_id'] ?? null;
            }
            Logger::debug('Redirigiendo desde deleteEventCategory (no POST)', ['eventId' => $eventId]);
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
        if (!$eventId) {
            $category = $this->categoryModel->getEventCategory($categoryId);
            $eventId = $category['event_id'] ?? null;
        }
        if (!$eventId) {
            Logger::error('No se pudo determinar el evento de la categoría en deleteEventCategory', ['categoryId' => $categoryId]);
            setFlashMessage('No se pudo determinar el evento de la categoría', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        try {
            $result = $this->categoryModel->deleteEventCategory($categoryId);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlashMessage('No se puede eliminar la categoría porque hay requerimientos asociados.', 'danger');
            } else {
                setFlashMessage('No se pudo eliminar la categoría: ' . $e->getMessage(), 'danger');
            }
            Logger::error('Error al eliminar categoría', ['error' => $e->getMessage(), 'categoryId' => $categoryId]);
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        // Si la eliminación no fue exitosa, verificar si hay requerimientos asociados
        if (!$result) {
            // Comprobar si existen requerimientos asociados a la categoría
            if (method_exists($this->categoryModel, 'hasRequirementsForEventCategory') && $this->categoryModel->hasRequirementsForEventCategory($categoryId)) {
                setFlashMessage('No se puede eliminar la categoría porque hay requerimientos asociados.', 'danger');
            } else {
                setFlashMessage('No se pudo eliminar la categoría', 'danger');
            }
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        setFlashMessage('Categoría eliminada correctamente', 'success');
        redirect(BASE_URL . '/events/categories/' . $eventId);
        exit;
    }

    /**
     * Editar una subcategoría de evento
     * @param int $subcategoryId
     * @param int $eventId
     * @return void
     */
    public function editEventSubcategory($subcategoryId, $eventId) {
        Logger::debug('Entrando a editEventSubcategory', ['subcategoryId' => $subcategoryId, 'eventId' => $eventId, 'POST' => $_POST]);
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::warning('Método no permitido en editEventSubcategory', ['method' => $_SERVER['REQUEST_METHOD']]);
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        $name = isset($_POST['subcategory_name']) ? trim($_POST['subcategory_name']) : '';
        if ($name === '') {
            Logger::warning('Nombre de subcategoría vacío en editEventSubcategory', ['POST' => $_POST]);
            setFlashMessage('El nombre de la subcategoría es obligatorio', 'danger');
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        try {
            $result = $this->categoryModel->updateEventSubcategory($subcategoryId, $name);
        } catch (PDOException $e) {
            setFlashMessage('No se pudo actualizar la subcategoría: ' . $e->getMessage(), 'danger');
            Logger::error('Error al actualizar subcategoría', ['error' => $e->getMessage(), 'subcategoryId' => $subcategoryId]);
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        if ($result) {
            setFlashMessage('Subcategoría actualizada correctamente', 'success');
        } else {
            setFlashMessage('No se pudo actualizar la subcategoría', 'danger');
        }
        Logger::debug('Redirigiendo tras editar subcategoría', ['eventId' => $eventId]);
        redirect(BASE_URL . '/events/categories/' . $eventId);
        exit;
    }

    /**
     * Eliminar una subcategoría de evento
     * @param int $subcategoryId
     * @return void
     */
    public function deleteEventSubcategory($subcategoryId, $eventId) {
        Logger::debug('Entrando a deleteEventSubcategory', ['subcategoryId' => $subcategoryId, 'eventId' => $eventId, 'POST' => $_POST]);
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::warning('Método no permitido en deleteEventSubcategory', ['method' => $_SERVER['REQUEST_METHOD']]);
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        try {
            $result = $this->categoryModel->deleteEventSubcategory($subcategoryId);
        } catch (PDOException $e) {
            setFlashMessage('No se pudo eliminar la subcategoría: ' . $e->getMessage(), 'danger');
            Logger::error('Error al eliminar subcategoría', ['error' => $e->getMessage(), 'subcategoryId' => $subcategoryId]);
            redirect(BASE_URL . '/events/categories/' . $eventId);
            exit;
        }
        if ($result) {
            setFlashMessage('Subcategoría eliminada correctamente', 'success');
        } else {
            setFlashMessage('No se pudo eliminar la subcategoría', 'danger');
        }
        Logger::debug('Redirigiendo tras eliminar subcategoría', ['eventId' => $eventId]);
        redirect(BASE_URL . '/events/categories/' . $eventId);
        exit;
    }

    // Ver empresa
    public function viewCompany($eventId, $companyId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . "/events/companies/$eventId");
            exit;
        }
        $company = $this->companyModel;
        $eventModel = $this->eventModel; // <-- Asegura que la vista tenga $eventModel
        $csrfToken = Security::generateCsrfToken();
        $pageTitle = 'Ver Empresa';
        $moduleCSS = 'companies';
        $moduleJS = 'companies';
        include(VIEW_DIR . '/companies/view.php');
    }

    // Editar empresa
    public function editCompany($eventId, $companyId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL);
            exit;
        }
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . "/events/companies/$eventId");
            exit;
        }
        // Verifica que la empresa pertenezca al evento
        if (method_exists($this->companyModel, 'getEventId') && $this->companyModel->getEventId() != $eventId) {
            setFlashMessage('La empresa no pertenece a este evento.', 'danger');
            redirect(BASE_URL . "/events/companies/$eventId");
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . "/events/companies/$eventId/edit/$companyId");
                exit;
            }
            $this->validator->setData($_POST);
            $this->validator->required('company_name', 'El nombre de la empresa es obligatorio');
            if ($this->validator->hasErrors()) {
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                redirect(BASE_URL . "/events/companies/$eventId/edit/$companyId");
                exit;
            }
            $data = [
                'company_name' => sanitize($_POST['company_name']),
                'address' => sanitize($_POST['address'] ?? ''),
                'city' => sanitize($_POST['city'] ?? ''),
                'country' => sanitize($_POST['country'] ?? ''),
                'website' => sanitize($_POST['website'] ?? ''),
                'contact_first_name' => sanitize($_POST['contact_first_name'] ?? ''),
                'contact_last_name' => sanitize($_POST['contact_last_name'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'email' => sanitize($_POST['email'] ?? ''),
                'role' => sanitize($_POST['role'] ?? ''),
                'event_id' => (int)$eventId,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'description' => sanitize($_POST['description'] ?? '')
            ];
            // Procesar logo si se sube uno nuevo
            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                try {
                    $companyLogoFilename = $this->processLogo($_FILES['company_logo'], 'company');
                    $data['company_logo'] = $companyLogoFilename;
                } catch (Exception $e) {
                    setFlashMessage('Error al subir el logo: ' . $e->getMessage(), 'danger');
                    redirect(BASE_URL . "/events/companies/$eventId/edit/$companyId");
                    exit;
                }
            }
            $updated = $this->companyModel->update($data);
            if ($updated) {
                setFlashMessage('Empresa actualizada correctamente', 'success');
                redirect(BASE_URL . "/events/companies/$eventId");
                exit;
            } else {
                setFlashMessage('No se pudo actualizar la empresa', 'danger');
                redirect(BASE_URL . "/events/companies/$eventId/edit/$companyId");
                exit;
            }
        }
        // Recargar siempre los datos actualizados de la empresa antes de mostrar la vista
        $this->companyModel->findById($companyId);
        $company = $this->companyModel;
        $eventModel = $this->eventModel;
        $csrfToken = Security::generateCsrfToken();
        $pageTitle = 'Editar Empresa';
        $moduleCSS = 'companies';
        $moduleJS = 'companies';
        include(VIEW_DIR . '/companies/edit.php');
    }

    // Eliminar empresa
    public function deleteCompany($eventId, $companyId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . "/events/companies/$eventId");
            exit;
        }
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . "/events/companies/$eventId");
            exit;
        }
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL);
            exit;
        }
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . "/events/companies/$eventId");
            exit;
        }
        try {
            $deleted = $this->companyModel->delete($companyId);
            if ($deleted) {
                setFlashMessage('Empresa eliminada correctamente', 'success');
            } else {
                setFlashMessage('No se pudo eliminar la empresa', 'danger');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al eliminar la empresa: ' . $e->getMessage(), 'danger');
        }
        redirect(BASE_URL . "/events/companies/$eventId");
        exit;
    }

    /**
     * Alta de empresa desde el contexto de un evento
     * @param int $eventId
     * @return void
     */
    public function createCompany($eventId) {
        // Permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para crear empresas', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Validar evento
        $eventId = (int)$eventId;
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $eventModel = $this->eventModel;
        $categories = $this->categoryModel->getAll(['is_active' => 1]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Log de depuración para CSRF
            Logger::debug('[DEBUG][CSRF] POST csrf_token: ' . ($_POST['csrf_token'] ?? '[NO POST TOKEN]') . ' | SESSION csrf_token: ' . ($_SESSION['csrf_token'] ?? '[NO SESSION TOKEN]'));
            // Validar CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['form_data'] = $_POST;
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/companies/' . $eventId . '/create-company');
                exit;
            }
            // Validar datos mínimos
            $this->validator->setData($_POST);
            $this->validator->required('company_name', 'El nombre de la empresa es obligatorio')
                ->required('email', 'El email es obligatorio')
                ->required('role', 'El rol es obligatorio')
                ->email('email', 'El formato de email no es válido');
            if ($this->validator->hasErrors()) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                redirect(BASE_URL . '/events/companies/' . $eventId . '/create-company');
                exit;
            }
            // Preparar datos
            $companyData = [
                'company_name' => sanitize($_POST['company_name']),
                'address' => sanitize($_POST['address'] ?? ''),
                'city' => sanitize($_POST['city'] ?? ''),
                'country' => sanitize($_POST['country'] ?? ''),
                'website' => sanitize($_POST['website'] ?? ''),
                'contact_first_name' => sanitize($_POST['contact_first_name'] ?? ''),
                'contact_last_name' => sanitize($_POST['contact_last_name'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'email' => sanitize($_POST['email']),
                'role' => sanitize($_POST['role']),
                'event_id' => $eventId,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'description' => sanitize($_POST['description'] ?? ''),
            ];
            // Procesar logo si se sube uno
            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                try {
                    $companyLogoFilename = $this->processLogo($_FILES['company_logo'], 'company');
                    $companyData['company_logo'] = $companyLogoFilename;
                } catch (Exception $e) {
                    setFlashMessage('Error al subir el logo: ' . $e->getMessage(), 'danger');
                    $_SESSION['form_data'] = $_POST;
                    redirect(BASE_URL . '/events/companies/' . $eventId . '/create-company');
                    exit;
                }
            }
            try {
                $companyId = $this->companyModel->createForEvent($companyData);
                if (!$companyId) throw new Exception('Error al crear la empresa');
                unset($_SESSION['form_data'], $_SESSION['validation_errors']); // Limpiar solo si es éxito
                setFlashMessage('Empresa creada exitosamente', 'success');
                redirect(BASE_URL . '/events/companies/' . $eventId);
                exit;
            } catch (Exception $e) {
                setFlashMessage('Error al crear la empresa: ' . $e->getMessage(), 'danger');
                $_SESSION['form_data'] = $_POST;
                redirect(BASE_URL . '/events/companies/' . $eventId . '/create-company');
                exit;
            }
        }
        $formData = $_SESSION['form_data'] ?? [
            'company_name' => '',
            'address' => '',
            'city' => '',
            'country' => '',
            'website' => '',
            'company_logo' => '',
            'contact_first_name' => '',
            'contact_last_name' => '',
            'phone' => '',
            'email' => '',
            'is_active' => 1,
            'role' => '',
            'description' => '',
            'type' => ''
        ];
        unset($_SESSION['form_data'], $_SESSION['validation_errors']);
        $csrfToken = generateCSRFToken();
        include(VIEW_DIR . '/events/create_company.php');
    }

    public function registration_details() {
        // Obtener parámetros
        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
        $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
        if (!$eventId || !$companyId) {
            setFlashMessage('Faltan parámetros para mostrar el registro completo', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Redirigir a la acción correcta (viewFullRegistration)
        $this->viewFullRegistration($eventId, $companyId);
    }

    /**
     * Listar todos los registros completos de empresas para un evento
     * Muestra un resumen de cada empresa y acceso al detalle completo
     * @return void
     */
    public function event_list() {
        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
        $filters = [];
        $order = $_GET['order'] ?? 'asc';
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $filters['search'] = $_GET['search'];
        }
        // Log de depuración
        Logger::debug('[event_list] event_id=' . $eventId . ' | filters=' . json_encode($filters) . ' | order=' . $order);
        if (!$eventId) {
            setFlashMessage('Falta el parámetro event_id', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Cargar evento
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $event = $this->eventModel;
        // Obtener empresas del evento (corregido)
        $companies = $this->companyModel->getByEvent($eventId, null, null, $filters, $order);
        Logger::debug('[event_list] companies count: ' . count($companies));
        $pageTitle = 'Registros completos de empresas';
        include(VIEW_DIR . '/events/event_list.php');
    }

    /**
     * Editar requerimientos de una empresa para un evento
     */
    public function editRequirements($eventId, $companyId) {
        // Permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para editar requerimientos', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Cargar evento y empresa
        if (!$this->eventModel->findById($eventId) || !$this->companyModel->findById($companyId)) {
            setFlashMessage('Evento o empresa no encontrados', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $event = $this->eventModel;
        $company = $this->companyModel;
        // Obtener categorías y subcategorías
        $categories = $this->categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $this->categoryModel->getEventSubcategories($cat['event_category_id']);
        }
        // Obtener requerimientos actuales
        $rawRequirements = $this->companyModel->getRequirements($companyId);
        $requirements = [];
        if ($rawRequirements) {
            foreach ($rawRequirements as $req) {
                if (isset($req['event_subcategory_id'])) {
                    $requirements[$req['event_subcategory_id']] = $req;
                }
            }
        }
        $csrfToken = Security::generateCsrfToken();
        // Guardar cambios
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido', 'danger');
                redirect(BASE_URL . "/events/editRequirements/$eventId/$companyId");
                exit;
            }
            // Procesar requerimientos enviados
            $reqs = $_POST['requirements'] ?? [];
            // Eliminar todos los requerimientos actuales
            $this->companyModel->deleteAllRequirements($companyId, $eventId);
            // Insertar los nuevos requerimientos seleccionados
            foreach ($reqs as $subcatId => $data) {
                if (!empty($data['selected'])) {
                    $newReq = [
                        'event_subcategory_id' => (int)$subcatId,
                        'budget_usd' => isset($data['budget']) ? (float)$data['budget'] : null,
                        'quantity' => isset($data['quantity']) ? (int)$data['quantity'] : null,
                        'unit_of_measurement' => isset($data['unit']) ? trim($data['unit']) : null,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $this->companyModel->addRequirement($newReq, $companyId);
                }
            }
            setFlashMessage('Requerimientos actualizados correctamente', 'success');
            redirect(BASE_URL . "/events/registration_details?event_id=$eventId&company_id=$companyId");
            exit;
        }
        // Cargar vista de edición
        $this->loadView('events/edit_requirements', [
            'event' => $event,
            'company' => $company,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'requirements' => $requirements,
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Editar días de asistencia de una empresa para un evento (formulario simple)
     * @param int $eventId
     * @param int $companyId
     * @return void
     */
    public function edit_days_attendance($eventId, $companyId) {
        // Permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para editar días de asistencia', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Cargar evento y empresa
        if (!$this->eventModel->findById($eventId) || !$this->companyModel->findById($companyId)) {
            setFlashMessage('Evento o empresa no encontrados', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $event = $this->eventModel;
        $company = $this->companyModel;
        // Obtener rango de días del evento
        $startDate = new DateTime($event->getStartDate());
        $endDate = new DateTime($event->getEndDate());
        $interval = $startDate->diff($endDate);
        $totalDays = $interval->days + 1;
        $eventDays = [];
        $currentDate = clone $startDate;
        for ($i = 0; $i < $totalDays; $i++) {
            $eventDays[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }
        // Obtener días seleccionados actuales
        $selectedDays = $this->companyModel->getAttendanceDays($companyId, $eventId);
        // Procesar formulario POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . "/events/edit_days_attendance/$eventId/$companyId");
                exit;
            }
            $newDays = isset($_POST['attendance_days']) ? $_POST['attendance_days'] : [];
            if (!is_array($newDays)) $newDays = [];
            // Eliminar todos los días actuales
            $attendanceDayModel = new AttendanceDay($this->db);
            $attendanceDayModel->deleteByCompanyAndEvent($companyId, $eventId);
            // Agregar los nuevos días seleccionados
            $added = 0;
            foreach ($newDays as $date) {
                $date = trim($date);
                if (!$date) continue;
                if ($this->companyModel->addAttendanceDay($eventId, $date, $companyId)) {
                    $added++;
                }
            }
            setFlashMessage('Días de asistencia actualizados correctamente', 'success');
            redirect(BASE_URL . "/events/view_full_registration/$eventId/$companyId");
            exit;
        }
        // Preparar datos para la vista
        $csrfToken = Security::generateCsrfToken();
        $this->loadView('events/edit_days_attendance', [
            'event' => $event,
            'company' => $company,
            'eventDays' => $eventDays,
            'selectedDays' => $selectedDays,
            'csrfToken' => $csrfToken
        ]);
    }

    // Alias para soportar rutas tipo /events/view_full_registration/{eventId}/{companyId}
    public function view_full_registration($eventId, $companyId) {
        return $this->viewFullRegistration($eventId, $companyId);
    }

    /**
     * Mostrar los slots teóricos de horarios y capacidad del evento
     * @param int $id ID del evento
     * @return void
     */
    public function time_slots($id) {
        // Permisos solo admin/organizador
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Buscar evento
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $event = $this->eventModel;
        $eventModel = $event;
        $eventId = $event->getId();
        // Fechas del evento
        $startDate = new DateTime($event->getStartDate());
        $endDate = new DateTime($event->getEndDate());
        $eventDurationDays = $startDate->diff($endDate)->days + 1;
        // Mesas y duración
        $availableTables = $event->getAvailableTables();
        $meetingDuration = $event->getMeetingDuration();
        $startTime = $event->getStartTime();
        $endTime = $event->getEndTime();
        // Breaks
        $breaks = $event->getBreaks($eventId);
        // Generar slots teóricos por día
        $slotsByDate = [];
        $slotsPerDay = 0;
        for ($i = 0; $i < $eventDurationDays; $i++) {
            $date = $startDate->format('Y-m-d');
            $slots = $event->generateTimeSlots($eventId, $date); // Debe devolver todos los slots posibles para ese día
            $slotsByDate[$date] = $slots;
            if ($i === 0) $slotsPerDay = count($slots) / max(1, $availableTables); // slots por día = total slots / mesas
            $startDate->modify('+1 day');
        }
        // Citas reales (para marcar ocupados)
        $appointmentModel = new Appointment($this->db);
        $appointments = $appointmentModel->getByEvent($eventId);
        // Pasar a la vista
        include(VIEW_DIR . '/events/time_slots.php');
    }
} // End of EventController class