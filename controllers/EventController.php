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
        $this->breakModel = new BreakModel($this->db);
        $this->assistantModel = new Assistant($this->db);
        $this->eventScheduleModel = new Appointment($this->db);
        $this->requirementModel = new Requirement($this->db);
        $this->attendanceDayModel = new AttendanceDay($this->db);
        
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
            redirect(BASE_URL);
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
     * Listar eventos (vista tipo tarjetas)
     * Ruta: /events/list
     */
    public function list() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Filtros de búsqueda y estado
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $filters = [];
        if ($status !== '') {
            $filters['is_active'] = $status;
        }
        // Obtener eventos desde el modelo
        $events = $this->eventModel->getAll($filters);
        // Pasar datos a la vista
        include(VIEW_DIR . '/events/list.php');
    }
    
    /**
     * Mostrar la vista de matches para un evento (AJAX-driven tabs)
     * Ruta: /events/matches
     * Permite a admin/organizer ver y gestionar matches, potenciales y empresas sin match
     */
    public function matches() {
        // Verificar permisos (solo administradores y organizadores)
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }

        // Obtener event_id desde GET, POST, o sesión
        $eventId = null;
        if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
            $eventId = (int)$_GET['event_id'];
            $_SESSION['event_id'] = $eventId;
        } elseif (isset($_SESSION['event_id']) && is_numeric($_SESSION['event_id'])) {
            $eventId = (int)$_SESSION['event_id'];
        }
        if (!$eventId) {
            setFlashMessage('Evento no especificado', 'danger');
            redirect(BASE_URL . '/events/list');
            exit;
        }

        // Generar token CSRF para formularios y AJAX
        $csrfToken = generateCSRFToken();

        // Título y recursos de la página
        $pageTitle = 'Matches del Evento';
        $moduleCSS = 'events';
        $moduleJS = 'events';
        $additionalCSS = 'components/tabs.css';
        $additionalJS = [];

        // Cargar vista de matches (AJAX tabs)
        include(VIEW_DIR . '/events/matches.php');
    }
    
    /**
     * Mostrar lista de empresas registradas para un evento (legacy: /events/event_list?event_id=ID)
     */
    public function event_list() {
        // Permitir solo admin/organizer
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Obtener event_id de GET o sesión
        $eventId = null;
        if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
            $eventId = (int)$_GET['event_id'];
            $_SESSION['event_id'] = $eventId;
        } elseif (isset($_SESSION['event_id']) && is_numeric($_SESSION['event_id'])) {
            $eventId = (int)$_SESSION['event_id'];
        }
        if (!$eventId) {
            setFlashMessage('Evento no especificado', 'danger');
            redirect(BASE_URL . '/events/list');
            exit;
        }
        // Filtros de búsqueda
        $filters = ['event_id' => $eventId];
        if (!empty($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        // Obtener empresas del evento
        $companies = $this->companyModel->getAll($filters);
        // Pasar datos a la vista
        include(VIEW_DIR . '/events/event_list.php');
    }
    
    /**
     * Mostrar el registro completo de una empresa para un evento
     * Ruta: /events/view_full_registration/{event_id}/{company_id}
     */
    public function viewFullRegistration($eventId, $companyId) {
        // Permitir solo admin/organizer
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Validar IDs
        $eventId = (int)$eventId;
        $companyId = (int)$companyId;
        if ($eventId <= 0 || $companyId <= 0) {
            setFlashMessage('Datos inválidos', 'danger');
            redirect(BASE_URL . '/events/list');
            exit;
        }
        // Buscar evento y empresa
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events/list');
            exit;
        }
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/events/event_list?event_id=' . $eventId);
            exit;
        }
        // Verificar que la empresa pertenece al evento
        if ($this->companyModel->getEventId() != $eventId) {
            setFlashMessage('La empresa no pertenece a este evento', 'danger');
            redirect(BASE_URL . '/events/event_list?event_id=' . $eventId);
            exit;
        }
        $event = $this->eventModel;
        $company = $this->companyModel;
        // Cargar vista de registro completo
        include(VIEW_DIR . '/events/view_full_registration.php');
    }
    
    /**
     * Editar los datos de una empresa para un evento
     * Ruta: /events/editCompany/{event_id}/{company_id} o /events/companies/{event_id}/edit/{company_id}
     */
    public function editCompany($eventId, $companyId) {
        // Permitir solo admin/organizer
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Validar IDs
        $eventId = (int)$eventId;
        $companyId = (int)$companyId;
        if ($eventId <= 0 || $companyId <= 0) {
            setFlashMessage('Datos inválidos', 'danger');
            redirect(BASE_URL . '/events/list');
            exit;
        }
        // Buscar evento y empresa
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events/list');
            exit;
        }
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/events/event_list?event_id=' . $eventId);
            exit;
        }
        // Verificar que la empresa pertenece al evento
        if ($this->companyModel->getEventId() != $eventId) {
            setFlashMessage('La empresa no pertenece a este evento', 'danger');
            redirect(BASE_URL . '/events/event_list?event_id=' . $eventId);
            exit;
        }
        $event = $this->eventModel;
        $company = $this->companyModel;
        $eventModel = $event; // Para compatibilidad con la vista
        $csrfToken = generateCSRFToken();
        // Cargar vista de edición de empresa (ubicación correcta)
        include(VIEW_DIR . '/companies/edit.php');
    }

    /**
     * Mostrar participantes de un evento
     * @param int $eventId
     * @return void
     */
    public function participants($eventId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            $this->_showNotFoundError('Evento no encontrado');
        }
        $participants = $this->eventModel->getParticipants($eventId);
        $event = $this->eventModel;
        $companies = $this->companyModel->getAll(['event_id' => $eventId]);
        $csrfToken = generateCSRFToken();
        include(VIEW_DIR . '/events/participants.php');
    }
    
    /**
     * Editar participante de un evento
     * @param int $eventId
     * @param int $assistantId
     * @return void
     */
public function editParticipant($eventId, $assistantId) {
    $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
    if (!$this->eventModel->findById($eventId)) {
        $this->_showNotFoundError('Evento no encontrado');
    }
    if (!$this->assistantModel->findById($assistantId)) {
        $this->_showNotFoundError('Asistente no encontrado');
    }
    $assistant = $this->assistantModel;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token CSRF inválido', 'danger');
            redirect(BASE_URL . "/events/editParticipant/$eventId/$assistantId");
            exit;
        }
        // Validar datos mínimos
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobilePhone = trim($_POST['mobile_phone'] ?? '');
        $companyId = (int)($_POST['company_id'] ?? 0);
        $errors = [];
        if ($firstName === '') $errors['first_name'] = 'El nombre es obligatorio';
        if ($lastName === '') $errors['last_name'] = 'El apellido es obligatorio';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email inválido';
        if ($companyId <= 0) $errors['company_id'] = 'Empresa inválida';
        if ($errors) {
            $_SESSION['validation_errors'] = $errors;
            redirect(BASE_URL . "/events/editParticipant/$eventId/$assistantId");
            exit;
        }
        // Guardar cambios
        $updateData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'mobile_phone' => $mobilePhone,
            'company_id' => $companyId
        ];
        if ($assistant->update($updateData)) {
            setFlashMessage('Participante actualizado correctamente', 'success');
            redirect(BASE_URL . "/events/participants/$eventId");
            exit;
        } else {
            setFlashMessage('No se pudo actualizar el participante', 'danger');
            redirect(BASE_URL . "/events/editParticipant/$eventId/$assistantId");
            exit;
        }
    }
    $companies = $this->companyModel->getAll(['event_id' => $eventId]);
    $event = $this->eventModel;
    $csrfToken = generateCSRFToken();
    include(VIEW_DIR . '/events/edit_participant.php');
}

/**
     * Listar empresas de un evento
     * Ruta: /events/companies/{event_id}
     */
    public function companies($eventId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            $this->_showNotFoundError('Evento no encontrado');
        }
        $companies = $this->companyModel->getAll(['event_id' => $eventId]);
        $event = $this->eventModel;
        include(VIEW_DIR . '/events/companies.php');
    }

    /**
     * Mostrar el detalle de una empresa y sus asistentes en un evento
     */
    public function viewCompany($eventId, $companyId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }

        // Buscar empresa
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/events/companies/' . $eventId);
            exit;
        }
        $company = $this->companyModel;

        // Buscar asistentes de la empresa
        $assistants = $this->assistantModel->findByCompany($companyId);

        // Cargar modelo de evento
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $eventModel = $this->eventModel;

        // Pasar datos a la vista
        include(VIEW_DIR . '/companies/view.php');
    }

    /**
     * Eliminar una empresa de un evento
     */
    public function deleteCompany($eventId, $companyId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para eliminar empresas', 'danger');
            redirect(BASE_URL);
            exit;
        }

        // Buscar empresa
        if (!$this->companyModel->findById($companyId)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/events/companies/' . $eventId);
            exit;
        }

        // Eliminar empresa
        if ($this->companyModel->delete($companyId)) {
            setFlashMessage('Empresa eliminada correctamente', 'success');
        } else {
            setFlashMessage('No se pudo eliminar la empresa', 'danger');
        }

        redirect(BASE_URL . '/events/companies/' . $eventId);
        exit;
    }
    
    /**
     * Agregar un participante a un evento (procesa el formulario y redirige)
     */
    public function addParticipant($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para agregar participantes', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Validar ID de evento
        $eventId = (int)$eventId;
        if ($eventId <= 0 || !$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                // 'event_id' => $eventId, // Quitar event_id, no existe en la tabla assistants
                'first_name' => sanitize($_POST['first_name'] ?? ''),
                'last_name' => sanitize($_POST['last_name'] ?? ''),
                'email' => sanitize($_POST['email'] ?? ''),
                'mobile_phone' => sanitize($_POST['mobile_phone'] ?? ''),
                'company_id' => sanitize($_POST['company_id'] ?? null),
            ];
            // Guardar datos en sesión para repoblar el formulario en caso de error
            $_SESSION['form_data'] = $data;
            $errors = [];
            if (empty($data['first_name'])) $errors['first_name'] = 'El nombre es obligatorio';
            if (empty($data['last_name'])) $errors['last_name'] = 'El apellido es obligatorio';
            if (empty($data['email'])) $errors['email'] = 'El email es obligatorio';
            if (empty($data['company_id'])) $errors['company_id'] = 'Debe seleccionar una empresa';
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'El email no es válido';
            // Validar empresa
            $companyModel = new Company($this->db);
            if (!empty($data['company_id']) && !$companyModel->findById($data['company_id'])) {
                $errors['company_id'] = 'La empresa seleccionada no existe';
            }
            // Validar email duplicado
            $assistantModel = new Assistant($this->db);
            if (!empty($data['email']) && !empty($data['company_id']) && $assistantModel->exists($data['email'], $data['company_id'])) {
                $errors['email'] = 'Ya existe un asistente con ese email en la empresa seleccionada';
            }
            if ($errors) {
                $_SESSION['validation_errors'] = $errors;
                setFlashMessage('Corrija los errores del formulario', 'danger');
                redirect(BASE_URL . '/events/participants/' . $eventId);
                exit;
            }
            $assistant = new Assistant($this->db);
            $result = $assistant->create($data);
            if ($result) {
                unset($_SESSION['form_data'], $_SESSION['validation_errors']);
                setFlashMessage('Participante agregado correctamente', 'success');
            } else {
                // Revisar logs para mensaje más específico
                setFlashMessage('No se pudo agregar el participante. Revise los datos o contacte al administrador.', 'danger');
            }
            redirect(BASE_URL . '/events/participants/' . $eventId);
            exit;
        }
        // Si no es POST, redirigir a la vista de participantes
        redirect(BASE_URL . '/events/participants/' . $eventId);
        exit;
    }

    /**
     * Mostrar los slots (horarios teóricos) de un evento
     * Ruta: /events/time_slots/{event_id}
     */
    public function time_slots($eventId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        $eventId = (int)$eventId;
        if ($eventId <= 0 || !$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $eventModel = $this->eventModel;
        // Obtener datos necesarios para la vista
        $availableTables = $eventModel->getAvailableTables();
        $eventDurationDays = $eventModel->getDurationDays ? $eventModel->getDurationDays() : 1;
        $slotsPerDay = method_exists($eventModel, 'getSlotsPerDay') ? $eventModel->getSlotsPerDay() : 0;
        $slotsByDate = method_exists($eventModel, 'getSlotsByDate') ? $eventModel->getSlotsByDate() : [];
        $breaks = method_exists($eventModel, 'getBreaks') ? $eventModel->getBreaks($eventId) : [];
        // Pasar variables a la vista
        include(VIEW_DIR . '/events/time_slots.php');
    }

    /**
     * Mostrar categorías y subcategorías de un evento específico
     * Ruta: /events/categories/{event_id}
     * @param int $eventId
     * @return void
     */
    public function categories($eventId) {
        // Verificar permisos
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        // Verificar que el evento exista
        if (!$this->eventModel->findById($eventId)) {
            $this->_showNotFoundError('Evento no encontrado');
        }
        // Obtener categorías del evento con sus subcategorías (centralizado)
        $categoriesWithSubcategories = $this->categoryModel->getEventCategoriesWithSubcategories($eventId);
        $csrfToken = generateCSRFToken();
        $eventModel = $this->eventModel;
        $pageData = [
            'pageTitle' => 'Categorías del Evento',
            'moduleCSS' => 'categories',
            'moduleJS' => ['categories', 'components/import_modal']
        ];
        // Cargar vista (usa la misma que CategoryController)
        include(VIEW_DIR . '/events/categories.php');
    }
} // End of EventController class