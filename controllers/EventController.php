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
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($id)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }

        $eventModel = $this->eventModel;
        
        // Obtener información adicional del evento
        $breaks = $this->eventModel->getBreaks($id);
        $buyers = $this->eventModel->getBuyers($id);
        $suppliers = $this->eventModel->getSuppliers($id);
        
        // Contar matches y citas
        $matches = $this->matchModel->getByEvent($id);
        $matchCount = count($matches);
        
        // Obtener citas programadas
        $appointmentModel = new Appointment($this->db);
        $schedules = $appointmentModel->getByEvent($id);
        $scheduleCount = count($schedules);
        
        // Token CSRF para posibles formularios en la vista
        $csrfToken = generateCSRFToken();

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/events/view.php');
    }
        
     /**
     * Generar y mostrar disponibilidad de slots de tiempo para un evento
     * 
     * @param int $id ID del evento
     * @return void
     */
    public function timeSlots($id) {
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
        
        // Obtener fecha específica si se proporciona (o usar la fecha de inicio del evento)
        $date = isset($_GET['date']) ? sanitize($_GET['date']) : null;
        
        // Generar slots de tiempo
        $timeSlots = $this->eventModel->generateTimeSlots($id, $date);
        
        // Obtener citas programadas para esta fecha
        $appointmentModel = new Appointment($this->db);
        $schedules = [];
        
        if ($date) {
            $formattedDate = dateToDatabase($date);
            $schedules = $appointmentModel->getByDate($formattedDate, $id);
        }
        
        // Preparar datos para la vista
        $startDate = dateFromDatabase($this->eventModel->getStartDate());
        $endDate = dateFromDatabase($this->eventModel->getEndDate());
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();

        $pageTitle = 'Eventos';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/datepicker.css';
        $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
        
        // Cargar vista
        include(VIEW_DIR . '/events/time_slots.php');
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
        
        // Cargar vista
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
            redirect(BASE_URL . '/events');
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
            redirect(BASE_URL . '/events');
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
        if ($appointmentModel->existsForMatch($matchId)) {
            setFlashMessage('Ya existe una cita para este match', 'warning');
            redirect(BASE_URL . '/events/view-match/' . $id . '/' . $matchId);
            exit;
        }
        
        // Procesar creación de cita si se solicita
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/events/create-schedule/' . $id . '/' . $matchId);
                exit;
            }
            
            // Validar datos
            $this->validator->setData($_POST);
            $this->validator->required('date', 'La fecha es obligatoria')
                           ->required('start_time', 'La hora de inicio es obligatoria')
                           ->required('end_time', 'La hora de finalización es obligatoria')
                           ->required('table_number', 'El número de mesa es obligatorio')
                           ->numeric('table_number', 'El número de mesa debe ser un valor numérico');
            
            // Si hay errores de validación
            if ($this->validator->hasErrors()) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                
                redirect(BASE_URL . '/events/create-schedule/' . $id . '/' . $matchId);
                exit;
            }
            
            try {
                $date = dateToDatabase(sanitize($_POST['date']));
                $startTime = sanitize($_POST['start_time']);
                $endTime = sanitize($_POST['end_time']);
                $tableNumber = (int)$_POST['table_number'];
                
                // Formatear fechas y horas completas
                $startDateTime = $date . ' ' . $startTime;
                $endDateTime = $date . ' ' . $endTime;
                
                // Verificar disponibilidad del horario y mesa
                if (!$appointmentModel->isSlotAvailable($id, $startDateTime, $endDateTime, $tableNumber)) {
                    throw new Exception('El horario o mesa seleccionados no están disponibles');
                }
                
                // Obtener IDs de comprador y proveedor
                $buyerId = $this->matchModel->getBuyerId();
                $supplierId = $this->matchModel->getSupplierId();
                
                // Verificar disponibilidad de comprador y proveedor
                if (!$appointmentModel->isCompanyAvailable($buyerId, $id, $startDateTime, $endDateTime)) {
                    throw new Exception('El comprador no está disponible en este horario');
                }
                
                if (!$appointmentModel->isCompanyAvailable($supplierId, $id, $startDateTime, $endDateTime)) {
                    throw new Exception('El proveedor no está disponible en este horario');
                }
                
                // Crear la cita
                $appointmentData = [
                    'event_id' => $id,
                    'match_id' => $matchId,
                    'table_number' => $tableNumber,
                    'start_datetime' => $startDateTime,
                    'end_datetime' => $endDateTime,
                    'status' => Appointment::STATUS_SCHEDULED,
                    'is_manual' => 1
                ];
                
                $scheduleId = $appointmentModel->create($appointmentData);
                
                if ($scheduleId) {
                    setFlashMessage('Cita creada exitosamente', 'success');
                    redirect(BASE_URL . '/events/view-match/' . $id . '/' . $matchId);
                    exit;
                } else {
                    throw new Exception('Error al crear la cita');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al crear la cita: ' . $e->getMessage(), 'danger');
                $_SESSION['form_data'] = $_POST;
                redirect(BASE_URL . '/events/create-schedule/' . $id . '/' . $matchId);
                exit;
            }
        }
        
        // Obtener información adicional para el formulario
        $buyerId = $this->matchModel->getBuyerId();
        $supplierId = $this->matchModel->getSupplierId();
        
        $buyer = new Company($this->db);
        $supplier = new Company($this->db);
        
        $buyer->findById($buyerId);
        $supplier->findById($supplierId);
        
        // Obtener días comunes de asistencia
        $buyerDays = $this->eventModel->getAttendanceDays($buyerId, $id);
        $supplierDays = $this->eventModel->getAttendanceDays($supplierId, $id);
        $commonDays = array_intersect($buyerDays, $supplierDays);
        
        // Formatear días para el formulario
        $formattedDays = [];
        foreach ($commonDays as $day) {
            $formattedDays[] = dateFromDatabase($day);
        }
        
        // Obtener información del evento
        $availableTables = $this->eventModel->getAvailableTables();
        $meetingDuration = $this->eventModel->getMeetingDuration();
        
        // Token CSRF para el formulario
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
            redirect(BASE_URL . '/events');
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
            redirect(BASE_URL . '/events');
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
            'contact_name' => $this->eventModel->getContactName(),
            'contact_phone' => $this->eventModel->getContactPhone(),
            'contact_email' => $this->eventModel->getContactEmail()
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
    $csrfToken = generateCSRFToken();

    $pageTitle = 'Eventos';
    $moduleCSS = 'events';
    $moduleJS = 'events';
    $additionalCSS = 'components/datepicker.css';
    $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
    
    // Cargar vista
    include(VIEW_DIR . '/events/report.php');
}

/**
 * Mostrar lista de eventos en formato de tarjetas
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
    $perPage = 9; // Mostrar 9 cards por página (3x3)
    
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
    
    // Token CSRF para formularios
    $csrfToken = generateCSRFToken();

    $pageTitle = 'Eventos';
    $moduleCSS = 'events';
    $moduleJS = 'events';
    $additionalCSS = 'components/datepicker.css';
    $additionalJS = ['lib/flatpickr.min.js', 'lib/choices.min.js'];
    
    // Cargar vista con los datos
    include(VIEW_DIR . '/events/list.php');
}



}