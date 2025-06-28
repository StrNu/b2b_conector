<?php
/**
 * Controlador de Citas (Appointments)
 * 
 * Este controlador maneja todas las operaciones relacionadas con las citas/agendas
 * entre compradores y proveedores, incluyendo creación, modificación, visualización,
 * eliminación y exportación de citas programadas para eventos.
 * 
 * @package B2B Conector
 * @version 1.0
 */

require_once(MODEL_DIR . '/Company.php');

require_once 'BaseController.php';

class AppointmentController extends BaseController {
        private $appointmentModel;
    private $eventModel;
    private $companyModel;
    private $matchModel;
    private $validator;
    
    /**
     * Constructor
     * 
     * Inicializa los modelos necesarios y otras dependencias
     */
    public function __construct() {
        
        parent::__construct();
        
        // La conexión ya se inicializa en BaseController
        // $this->db ya está disponible
        
// Inicializar conexión a la base de datos        // Inicializar modelos
        $this->appointmentModel = new Appointment($this->db);
        $this->eventModel = new Event($this->db);
        $this->companyModel = new Company($this->db);
        $this->matchModel = new MatchModel($this->db);
        
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
     * Listar todas las citas
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
        
        // Filtrar por evento si se especifica
        if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
            $filters['event_id'] = (int)$_GET['event_id'];
        }
        
        // Filtrar por estado si se especifica
        if (isset($_GET['status']) && in_array($_GET['status'], [
            Appointment::STATUS_SCHEDULED, 
            Appointment::STATUS_COMPLETED, 
            Appointment::STATUS_CANCELLED
        ])) {
            $filters['status'] = sanitize($_GET['status']);
        }
        
        // Filtrar por fecha si se especifica
        if (isset($_GET['date']) && !empty($_GET['date'])) {
            $date = dateToDatabase(sanitize($_GET['date']));
            $filters['date'] = $date;
        }
        
        // Filtrar por compañía (comprador o proveedor) si se especifica
        if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
            $companyId = (int)$_GET['company_id'];
            // Nota: Este filtro se aplicará de forma especial en la consulta
        }
        
        // Obtener total de citas según filtros
        $totalAppointments = 0;
        
        if (isset($companyId)) {
            $totalAppointments = count($this->appointmentModel->getByCompany($companyId, 
                $filters['event_id'] ?? null, 
                $filters['status'] ?? null));
        } else {
            $totalAppointments = $this->appointmentModel->count($filters);
        }
        
        // Configurar paginación
        $pagination = paginate($totalAppointments, $page, $perPage);
        
        // Obtener citas para la página current con filtros aplicados
        $appointments = [];
        
        if (isset($companyId)) {
            $appointments = $this->appointmentModel->getByCompany($companyId, 
                $filters['event_id'] ?? null, 
                $filters['status'] ?? null, 
                $pagination);
        } else {
            $appointments = $this->appointmentModel->getAll($filters, $pagination);
        }
        
        // Obtener eventos activos para el filtro
        $events = $this->eventModel->getActiveEvents();
        
        // Obtener empresas para el filtro
        $companies = $this->companyModel->getAll(['is_active' => 1]);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'appointmentcontroller',
            'moduleJS' => 'appointmentcontroller'
        ];
        
        $this->render('appointments/index', $data, 'admin');
    }
    
    /**
     * Mostrar detalles de una cita específica
     * 
     * @param int $id ID de la cita
     * @return void
     */
    public function view($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar cita por ID
        if (!$this->appointmentModel->findById($id)) {
            setFlashMessage('Cita no encontrada', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Obtener información adicional
        $matchId = $this->appointmentModel->getMatchId();
        
        // Obtener información del match
        $this->matchModel->findById($matchId);
        $buyerId = $this->matchModel->getBuyerId();
        $supplierId = $this->matchModel->getSupplierId();
        
        // Obtener información de comprador y proveedor
        $buyer = new Company($this->db);
        $supplier = new Company($this->db);
        
        $buyer->findById($buyerId);
        $supplier->findById($supplierId);
        
        // Obtener información del evento
        $eventId = $this->appointmentModel->getEventId();
        $event = new Event($this->db);
        $event->findById($eventId);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'appointmentcontroller',
            'moduleJS' => 'appointmentcontroller'
        ];
        
        $this->render('appointments/view', $data, 'admin');
    }
    
    /**
     * Mostrar formulario para crear una nueva cita
     * 
     * @param int $eventId ID del evento para el que se creará la cita
     * @return void
     */
    public function create($eventId = null) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Si se proporciona un evento, verificar que existe
        if ($eventId) {
            if (!$this->eventModel->findById($eventId)) {
                setFlashMessage('Evento no encontrado', 'danger');
                redirect(BASE_URL . '/events');
                exit;
            }
            
            // Obtener matches del evento que no tengan cita asignada
            $matches = $this->matchModel->getByEvent($eventId, 'accepted');
            $availableMatches = [];
            
            foreach ($matches as $match) {
                // Verificar si ya tiene cita asignada
                if (!$this->appointmentModel->existsForMatch($match['match_id'])) {
                    $availableMatches[] = $match;
                }
            }
            
            if (empty($availableMatches)) {
                setFlashMessage('No hay matches disponibles para asignar citas en este evento', 'warning');
                redirect(BASE_URL . '/events/view/' . $eventId);
                exit;
            }
        } else {
            // Obtener eventos activos
            $events = $this->eventModel->getActiveEvents();
            
            if (empty($events)) {
                setFlashMessage('No hay eventos activos para crear citas', 'warning');
                redirect(BASE_URL . '/events');
                exit;
            }
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'appointmentcontroller',
            'moduleJS' => 'appointmentcontroller'
        ];
        
        $this->render('appointments/create', $data, 'admin');
    }
    
    /**
     * Procesar la creación de una nueva cita
     * 
     * @return void
     */
    public function store() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/appointments/create');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/appointments/create');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('event_id', 'El evento es obligatorio')
                       ->required('match_id', 'El match es obligatorio')
                       ->required('date', 'La fecha es obligatoria')
                       ->required('start_time', 'La hora de inicio es obligatoria')
                       ->required('end_time', 'La hora de finalización es obligatoria')
                       ->required('table_number', 'El número de mesa es obligatorio')
                       ->numeric('table_number', 'El número de mesa debe ser un valor numérico')
                       ->date('date', 'd/m/Y', 'Formato de fecha inválido (dd/mm/yyyy)');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/appointments/create');
            exit;
        }
        
        $eventId = (int)$_POST['event_id'];
        $matchId = (int)$_POST['match_id'];
        $date = dateToDatabase(sanitize($_POST['date']));
        $startTime = sanitize($_POST['start_time']);
        $endTime = sanitize($_POST['end_time']);
        $tableNumber = (int)$_POST['table_number'];
        
        // Formatear fechas y horas completas
        $startDateTime = $date . ' ' . $startTime;
        $endDateTime = $date . ' ' . $endTime;
        
        try {
            // Verificar que el match exista y pertenezca al evento
            if (!$this->matchModel->findById($matchId)) {
                throw new Exception('Match no encontrado');
            }
            
            if ($this->matchModel->getEventId() != $eventId) {
                throw new Exception('El match no pertenece a este evento');
            }
            
            // Verificar que el match no tenga ya una cita asignada
            if ($this->appointmentModel->existsForMatch($matchId)) {
                throw new Exception('Este match ya tiene una cita asignada');
            }
            
            // Verificar disponibilidad del horario y mesa
            if (!$this->appointmentModel->isSlotAvailable($eventId, $startDateTime, $endDateTime, $tableNumber)) {
                throw new Exception('El horario o mesa seleccionados no están disponibles');
            }
            
            // Obtener IDs de comprador y proveedor
            $buyerId = $this->matchModel->getBuyerId();
            $supplierId = $this->matchModel->getSupplierId();
            
            // Verificar disponibilidad de comprador y proveedor
            if (!$this->appointmentModel->isCompanyAvailable($buyerId, $eventId, $startDateTime, $endDateTime)) {
                throw new Exception('El comprador no está disponible en este horario');
            }
            
            if (!$this->appointmentModel->isCompanyAvailable($supplierId, $eventId, $startDateTime, $endDateTime)) {
                throw new Exception('El proveedor no está disponible en este horario');
            }
            
            // Crear la cita
            $appointmentData = [
                'event_id' => $eventId,
                'match_id' => $matchId,
                'table_number' => $tableNumber,
                'start_datetime' => $startDateTime,
                'end_datetime' => $endDateTime,
                'status' => Appointment::STATUS_SCHEDULED,
                'is_manual' => 1
            ];
            
            $appointmentId = $this->appointmentModel->create($appointmentData);
            // Actualizar campo programed del match
            if ($appointmentId) {
                $this->matchModel->findById($matchId);
                $this->matchModel->update(['programed' => 1]);
                setFlashMessage('Cita creada exitosamente', 'success');
                redirect(BASE_URL . '/appointments/view/' . $appointmentId);
            } else {
                throw new Exception('Error al crear la cita');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al crear la cita: ' . $e->getMessage(), 'danger');
            $_SESSION['form_data'] = $_POST;
            redirect(BASE_URL . '/appointments/create');
            exit;
        }
    }
    
    /**
     * Mostrar formulario para editar una cita existente
     * 
     * @param int $id ID de la cita a editar
     * @return void
     */
    public function edit($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar cita por ID
        if (!$this->appointmentModel->findById($id)) {
            setFlashMessage('Cita no encontrada', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Obtener información adicional
        $matchId = $this->appointmentModel->getMatchId();
        $eventId = $this->appointmentModel->getEventId();
        
        // Obtener información del match
        $this->matchModel->findById($matchId);
        $buyerId = $this->matchModel->getBuyerId();
        $supplierId = $this->matchModel->getSupplierId();
        
        // Obtener información de comprador y proveedor
        $buyer = new Company($this->db);
        $supplier = new Company($this->db);
        
        $buyer->findById($buyerId);
        $supplier->findById($supplierId);
        
        // Obtener información del evento
        $event = new Event($this->db);
        $event->findById($eventId);
        
        // Obtener fechas del evento para el selector de fechas
        $startDate = new DateTime($event->getStartDate());
        $endDate = new DateTime($event->getEndDate());
        $eventDays = [];
        
        while ($startDate <= $endDate) {
            $eventDays[] = $startDate->format('d/m/Y');
            $startDate->modify('+1 day');
        }
        
        // Extraer fecha y hora de los datetime de la cita
        $startDateTime = new DateTime($this->appointmentModel->getStartDateTime());
        $endDateTime = new DateTime($this->appointmentModel->getEndDateTime());
        
        $selectedDate = $startDateTime->format('d/m/Y');
        $startTime = $startDateTime->format('H:i:s');
        $endTime = $endDateTime->format('H:i:s');
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'appointmentcontroller',
            'moduleJS' => 'appointmentcontroller'
        ];
        
        $this->render('appointments/edit', $data, 'admin');
    }
    
    /**
     * Procesar la actualización de una cita
     * 
     * @param int $id ID de la cita a actualizar
     * @return void
     */
    public function update($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/appointments/edit/' . $id);
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/appointments/edit/' . $id);
            exit;
        }
        
        // Buscar cita por ID
        if (!$this->appointmentModel->findById($id)) {
            setFlashMessage('Cita no encontrada', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('date', 'La fecha es obligatoria')
                       ->required('start_time', 'La hora de inicio es obligatoria')
                       ->required('end_time', 'La hora de finalización es obligatoria')
                       ->required('table_number', 'El número de mesa es obligatorio')
                       ->numeric('table_number', 'El número de mesa debe ser un valor numérico')
                       ->date('date', 'd/m/Y', 'Formato de fecha inválido (dd/mm/yyyy)');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/appointments/edit/' . $id);
            exit;
        }
        
        $date = dateToDatabase(sanitize($_POST['date']));
        $startTime = sanitize($_POST['start_time']);
        $endTime = sanitize($_POST['end_time']);
        $tableNumber = (int)$_POST['table_number'];
        
        // Formatear fechas y horas completas
        $startDateTime = $date . ' ' . $startTime;
        $endDateTime = $date . ' ' . $endTime;
        
        try {
            $eventId = $this->appointmentModel->getEventId();
            $matchId = $this->appointmentModel->getMatchId();
            
            // Verificar disponibilidad del horario y mesa
            if (!$this->appointmentModel->isSlotAvailable($eventId, $startDateTime, $endDateTime, $tableNumber, $id)) {
                throw new Exception('El horario o mesa seleccionados no están disponibles');
            }
            
            // Obtener IDs de comprador y proveedor
            $this->matchModel->findById($matchId);
            $buyerId = $this->matchModel->getBuyerId();
            $supplierId = $this->matchModel->getSupplierId();
            
            // Verificar disponibilidad de comprador y proveedor
            if (!$this->appointmentModel->isCompanyAvailable($buyerId, $eventId, $startDateTime, $endDateTime, $id)) {
                throw new Exception('El comprador no está disponible en este horario');
            }
            
            if (!$this->appointmentModel->isCompanyAvailable($supplierId, $eventId, $startDateTime, $endDateTime, $id)) {
                throw new Exception('El proveedor no está disponible en este horario');
            }
            
            // Actualizar la cita
            $appointmentData = [
                'table_number' => $tableNumber,
                'start_datetime' => $startDateTime,
                'end_datetime' => $endDateTime,
                'status' => sanitize($_POST['status'] ?? Appointment::STATUS_SCHEDULED)
            ];
            
            $updated = $this->appointmentModel->update($appointmentData);
            
            if ($updated) {
                setFlashMessage('Cita actualizada exitosamente', 'success');
                redirect(BASE_URL . '/appointments/view/' . $id);
            } else {
                throw new Exception('Error al actualizar la cita');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al actualizar la cita: ' . $e->getMessage(), 'danger');
            redirect(BASE_URL . '/appointments/edit/' . $id);
            exit;
        }
    }
    
    /**
     * Eliminar una cita
     * 
     * @param int $id ID de la cita a eliminar
     * @return void
     */
    public function delete($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para eliminar citas', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Buscar cita por ID
        if (!$this->appointmentModel->findById($id)) {
            setFlashMessage('Cita no encontrada', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Eliminar la cita
        try {
            $deleted = $this->appointmentModel->delete($id);
            // Si se eliminó la cita, actualizar el campo programed del match a 0
            if ($deleted) {
                $matchId = $this->appointmentModel->getMatchId();
                if ($matchId) {
                    $this->matchModel->findById($matchId);
                    $this->matchModel->update(['programed' => 0]);
                }
                setFlashMessage('Cita eliminada exitosamente', 'success');
            } else {
                throw new Exception('Error al eliminar la cita');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al eliminar la cita: ' . $e->getMessage(), 'danger');
        }
        
        redirect(BASE_URL . '/appointments');
    }
    
    /**
     * Cambiar el estado de una cita (programada, completada, cancelada)
     * 
     * @param int $id ID de la cita
     * @return void
     */
    public function toggleStatus($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para modificar citas', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Verificar que se proporciona un estado válido
        if (!isset($_POST['status']) || !in_array($_POST['status'], [
            Appointment::STATUS_SCHEDULED,
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELLED
        ])) {
            setFlashMessage('Estado inválido', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        // Buscar cita por ID
        if (!$this->appointmentModel->findById($id)) {
            setFlashMessage('Cita no encontrada', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
        
        $newStatus = sanitize($_POST['status']);
        
        // Actualizar estado
        try {
            $updated = $this->appointmentModel->updateStatus($id, $newStatus);
            
            if ($updated) {
                $statusMessages = [
                    Appointment::STATUS_SCHEDULED => 'Cita marcada como programada',
                    Appointment::STATUS_COMPLETED => 'Cita marcada como completada',
                    Appointment::STATUS_CANCELLED => 'Cita marcada como cancelada'
                ];
                
                setFlashMessage($statusMessages[$newStatus] ?? 'Estado de la cita actualizado', 'success');
            } else {
                throw new Exception('Error al cambiar el estado de la cita');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al cambiar el estado de la cita: ' . $e->getMessage(), 'danger');
        }
        
        redirect(BASE_URL . '/appointments/view/' . $id);
    }
    
    /**
     * Generar citas automáticamente para un evento
     * 
     * @param int $eventId ID del evento
     * @return void
     */
    public function generate($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/events/view/' . $eventId);
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/events/view/' . $eventId);
            exit;
        }
        
        // Buscar evento por ID
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Generar citas
        try {
            $result = $this->appointmentModel->generateSchedules($eventId);
            
            if ($result['success']) {
                setFlashMessage($result['message'], 'success');
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            setFlashMessage('Error al generar citas: ' . $e->getMessage(), 'danger');
        }
        
        redirect(BASE_URL . '/events/schedules/' . $eventId);
    }
    
  /**
 * Exportar citas a CSV
 * 
 * @param int $eventId ID del evento (opcional)
 * @return void
 */
public function export($eventId = null) {
    // [Código existente...]
    
    // Escribir datos
    foreach ($appointments as $appointment) {
        $datetime = new DateTime($appointment['start_datetime']);
        $date = $datetime->format('d/m/Y');
        $startTime = $datetime->format('H:i');
        
        $endDatetime = new DateTime($appointment['end_datetime']);
        $endTime = $endDatetime->format('H:i');
        
        fputcsv($output, [
            $appointment['schedule_id'],
            $appointment['event_name'] ?? $this->eventModel->findById($appointment['event_id']) ? $this->eventModel->getEventName() : 'N/A',
            $date,
            $startTime,
            $endTime,
            $appointment['table_number'],
            $appointment['buyer_name'],
            $appointment['supplier_name'],
            $appointment['status'],
            $appointment['is_manual'] ? 'Sí' : 'No'
        ]);
    }
    
    // Cerrar archivo y finalizar
    fclose($output);
    exit;
}

/**
 * Detectar y resolver conflictos de horarios
 *
 * @param int $eventId ID del evento
 * @return void
 */
public function conflicts($eventId) {
    // Verificar permisos
    if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
        setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
        redirect(BASE_URL);
        exit;
    }
    
    // Verificar que el evento existe
    if (!$this->eventModel->findById($eventId)) {
        setFlashMessage('Evento no encontrado', 'danger');
        redirect(BASE_URL . '/events');
        exit;
    }
    
    // Almacenar conflictos encontrados
    $conflictData = [];
    
    // Si se envió el formulario para resolver conflictos
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_conflicts'])) {
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/appointments/conflicts/' . $eventId);
            exit;
        }
        
        // Procesar cada conflicto seleccionado
        if (isset($_POST['conflict']) && is_array($_POST['conflict'])) {
            $resolvedCount = 0;
            
            foreach ($_POST['conflict'] as $scheduleId => $action) {
                if ($action === 'reschedule' && isset($_POST['new_date'][$scheduleId]) && isset($_POST['new_start_time'][$scheduleId]) && isset($_POST['new_end_time'][$scheduleId]) && isset($_POST['new_table'][$scheduleId])) {
                    
                    // Obtener y validar datos
                    $newDate = dateToDatabase(sanitize($_POST['new_date'][$scheduleId]));
                    $newStartTime = sanitize($_POST['new_start_time'][$scheduleId]);
                    $newEndTime = sanitize($_POST['new_end_time'][$scheduleId]);
                    $newTable = (int)$_POST['new_table'][$scheduleId];
                    
                    // Formatear fechas y horas completas
                    $newStartDateTime = $newDate . ' ' . $newStartTime;
                    $newEndDateTime = $newDate . ' ' . $newEndTime;
                    
                    // Buscar cita por ID
                    if ($this->appointmentModel->findById($scheduleId)) {
                        
                        // Verificar disponibilidad del nuevo horario y mesa
                        if ($this->appointmentModel->isSlotAvailable($eventId, $newStartDateTime, $newEndDateTime, $newTable, $scheduleId)) {
                            
                            // Actualizar la cita
                            $updated = $this->appointmentModel->update([
                                'start_datetime' => $newStartDateTime,
                                'end_datetime' => $newEndDateTime,
                                'table_number' => $newTable
                            ]);
                            
                            if ($updated) {
                                $resolvedCount++;
                            }
                        }
                    }
                } elseif ($action === 'cancel') {
                    // Cancelar la cita
                    if ($this->appointmentModel->findById($scheduleId)) {
                        $updated = $this->appointmentModel->updateStatus($scheduleId, Appointment::STATUS_CANCELLED);
                        
                        if ($updated) {
                            $resolvedCount++;
                        }
                    }
                }
            }
            
            if ($resolvedCount > 0) {
                setFlashMessage("Se han resuelto $resolvedCount conflictos correctamente", 'success');
                redirect(BASE_URL . '/appointments/conflicts/' . $eventId);
                exit;
            }
        }
    }
    
    // Detectar conflictos de mesas (misma mesa, horarios superpuestos)
    $tableConflicts = [];
    $query = "SELECT a1.schedule_id as schedule1, a2.schedule_id as schedule2, a1.table_number,
              a1.start_datetime as start1, a1.end_datetime as end1,
              a2.start_datetime as start2, a2.end_datetime as end2,
              b1.company_name as buyer1, s1.company_name as supplier1,
              b2.company_name as buyer2, s2.company_name as supplier2
              FROM event_schedules a1
              JOIN event_schedules a2 ON a1.event_id = a2.event_id AND a1.table_number = a2.table_number
              JOIN matches m1 ON a1.match_id = m1.match_id
              JOIN matches m2 ON a2.match_id = m2.match_id
              JOIN company b1 ON m1.buyer_id = b1.company_id
              JOIN company s1 ON m1.supplier_id = s1.company_id
              JOIN company b2 ON m2.buyer_id = b2.company_id
              JOIN company s2 ON m2.supplier_id = s2.company_id
              WHERE a1.event_id = :event_id AND a1.schedule_id < a2.schedule_id
              AND a1.status = 'scheduled' AND a2.status = 'scheduled'
              AND ((a1.start_datetime < a2.end_datetime AND a1.end_datetime > a2.start_datetime))
              ORDER BY a1.start_datetime";
    
    $params = ['event_id' => $eventId];
    $tableConflicts = $this->db->resultSet($query, $params);
    
    // Detectar conflictos de empresas (misma empresa, horarios superpuestos)
    $companyConflicts = [];
    $query = "SELECT a1.schedule_id as schedule1, a2.schedule_id as schedule2, 
              a1.table_number as table1, a2.table_number as table2,
              a1.start_datetime as start1, a1.end_datetime as end1,
              a2.start_datetime as start2, a2.end_datetime as end2,
              company.company_name as company_name, company.company_id as company_id
              FROM event_schedules a1
              JOIN event_schedules a2 ON a1.event_id = a2.event_id AND a1.schedule_id < a2.schedule_id
              JOIN matches m1 ON a1.match_id = m1.match_id
              JOIN matches m2 ON a2.match_id = m2.match_id
              JOIN company ON (m1.buyer_id = company.company_id AND m2.buyer_id = company.company_id)
                          OR (m1.supplier_id = company.company_id AND m2.supplier_id = company.company_id)
              WHERE a1.event_id = :event_id
              AND a1.status = 'scheduled' AND a2.status = 'scheduled'
              AND ((a1.start_datetime < a2.end_datetime AND a1.end_datetime > a2.start_datetime))
              ORDER BY company.company_name, a1.start_datetime";
    
    $companyConflicts = $this->db->resultSet($query, $params);
    
    // Combinar todos los conflictos
    $conflictData = [
        'tableConflicts' => $tableConflicts,
        'companyConflicts' => $companyConflicts
    ];
    
    // Obtener slots disponibles para reprogramación
    $availableSlots = [];
    $startDate = new DateTime($this->eventModel->getStartDate());
    $endDate = new DateTime($this->eventModel->getEndDate());
    
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        $dateStr = $currentDate->format('Y-m-d');
        $slots = $this->eventModel->generateTimeSlots($eventId, $dateStr);
        
        if (!empty($slots)) {
            $formattedDate = dateFromDatabase($dateStr);
            $availableSlots[$formattedDate] = $slots;
        }
        
        $currentDate->modify('+1 day');
    }
    
    // Token CSRF para los formularios
    $csrfToken = generateCSRFToken();
    
    // Cargar vista con los datos
            $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'appointmentcontroller',
            'moduleJS' => 'appointmentcontroller'
        ];
        
        $this->render('appointments/conflicts', $data, 'admin');
}

/**
 * Reprogramar una cita
 *
 * @param int $id ID de la cita a reprogramar
 * @return void
 */
public function reschedule($id) {
    // Verificar permisos
    if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
        setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
        redirect(BASE_URL);
        exit;
    }
    
    // Buscar cita por ID
    if (!$this->appointmentModel->findById($id)) {
        setFlashMessage('Cita no encontrada', 'danger');
        redirect(BASE_URL . '/appointments');
        exit;
    }
    
    $eventId = $this->appointmentModel->getEventId();
    $matchId = $this->appointmentModel->getMatchId();
    
    // Si se está procesando el formulario de reprogramación
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule'])) {
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/appointments/reschedule/' . $id);
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('date', 'La fecha es obligatoria')
                       ->required('start_time', 'La hora de inicio es obligatoria')
                       ->required('end_time', 'La hora de finalización es obligatoria')
                       ->required('table_number', 'El número de mesa es obligatorio')
                       ->numeric('table_number', 'El número de mesa debe ser un valor numérico')
                       ->date('date', 'd/m/Y', 'Formato de fecha inválido (dd/mm/yyyy)');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/appointments/reschedule/' . $id);
            exit;
        }
        
        $date = dateToDatabase(sanitize($_POST['date']));
        $startTime = sanitize($_POST['start_time']);
        $endTime = sanitize($_POST['end_time']);
        $tableNumber = (int)$_POST['table_number'];
        $notifyParticipants = isset($_POST['notify_participants']);
        
        // Formatear fechas y horas completas
        $startDateTime = $date . ' ' . $startTime;
        $endDateTime = $date . ' ' . $endTime;
        
        try {
            // Verificar disponibilidad del horario y mesa
            if (!$this->appointmentModel->isSlotAvailable($eventId, $startDateTime, $endDateTime, $tableNumber, $id)) {
                throw new Exception('El horario o mesa seleccionados no están disponibles');
            }
            
            // Obtener IDs de comprador y proveedor
            $this->matchModel->findById($matchId);
            $buyerId = $this->matchModel->getBuyerId();
            $supplierId = $this->matchModel->getSupplierId();
            
            // Verificar disponibilidad de comprador y proveedor
            if (!$this->appointmentModel->isCompanyAvailable($buyerId, $eventId, $startDateTime, $endDateTime, $id)) {
                throw new Exception('El comprador no está disponible en este horario');
            }
            
            if (!$this->appointmentModel->isCompanyAvailable($supplierId, $eventId, $startDateTime, $endDateTime, $id)) {
                throw new Exception('El proveedor no está disponible en este horario');
            }
            
            // Guardar datos anteriores para el registro
            $previousStart = $this->appointmentModel->getStartDateTime();
            $previousEnd = $this->appointmentModel->getEndDateTime();
            $previousTable = $this->appointmentModel->getTableNumber();
            
            // Actualizar la cita
            $appointmentData = [
                'table_number' => $tableNumber,
                'start_datetime' => $startDateTime,
                'end_datetime' => $endDateTime,
                'status' => Appointment::STATUS_SCHEDULED
            ];
            
            $updated = $this->appointmentModel->update($appointmentData);
            
            if ($updated) {
                // Registrar la reprogramación en un log (implementación simplificada)
                $logMessage = "Cita ID {$id} reprogramada. Anterior: {$previousStart} a {$previousEnd}, Mesa {$previousTable}. " .
                              "Nueva: {$startDateTime} a {$endDateTime}, Mesa {$tableNumber}";
                error_log($logMessage);
                
                // Si se activó la opción de notificar, aquí se implementaría el envío de notificaciones
                if ($notifyParticipants) {
                    // Esta parte requeriría una implementación adicional para enviar emails o notificaciones
                    // Por ahora solo registramos que se solicitó notificar
                    error_log("Se solicitó notificar a los participantes sobre la reprogramación de la cita ID {$id}");
                }
                
                setFlashMessage('Cita reprogramada exitosamente', 'success');
                redirect(BASE_URL . '/appointments/view/' . $id);
            } else {
                throw new Exception('Error al reprogramar la cita');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al reprogramar la cita: ' . $e->getMessage(), 'danger');
            redirect(BASE_URL . '/appointments/reschedule/' . $id);
            exit;
        }
    }
    
    // Obtener información de la cita actual
    $appointment = $this->appointmentModel->findById($id);
    
    // Obtener información del match
    $this->matchModel->findById($matchId);
    $buyerId = $this->matchModel->getBuyerId();
    $supplierId = $this->matchModel->getSupplierId();
    
    // Obtener información de comprador y proveedor
    $buyer = new Company($this->db);
    $supplier = new Company($this->db);
    
    $buyer->findById($buyerId);
    $supplier->findById($supplierId);
    
    // Obtener evento
    $this->eventModel->findById($eventId);
    
    // Obtener slots disponibles para el evento
    $availableSlots = [];
    $startDate = new DateTime($this->eventModel->getStartDate());
    $endDate = new DateTime($this->eventModel->getEndDate());
    
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        $dateStr = $currentDate->format('Y-m-d');
        $slots = $this->eventModel->generateTimeSlots($eventId, $dateStr);
        
        if (!empty($slots)) {
            $formattedDate = dateFromDatabase($dateStr);
            $availableSlots[$formattedDate] = $slots;
        }
        
        $currentDate->modify('+1 day');
    }
    
    // Extraer fecha y hora de los datetime de la cita actual
    $startDateTime = new DateTime($this->appointmentModel->getStartDateTime());
    $endDateTime = new DateTime($this->appointmentModel->getEndDateTime());
    
    $selectedDate = $startDateTime->format('d/m/Y');
    $startTime = $startDateTime->format('H:i:s');
    $endTime = $endDateTime->format('H:i:s');
    $tableNumber = $this->appointmentModel->getTableNumber();
    
    // Token CSRF para el formulario
    $csrfToken = generateCSRFToken();
    
    // Cargar vista
            $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'appointmentcontroller',
            'moduleJS' => 'appointmentcontroller'
        ];
        
        $this->render('appointments/reschedule', $data, 'admin');
}

/**
 * Ver citas de una empresa específica
 *
 * @param int $companyId ID de la empresa
 * @param int $eventId ID del evento (opcional)
 * @return void
 */
public function viewByCompany($companyId, $eventId = null) {
    // Verificar permisos
    if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
        setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
        redirect(BASE_URL);
        exit;
    }
    
    // Buscar empresa por ID
    if (!$this->companyModel->findById($companyId)) {
        setFlashMessage('Empresa no encontrada', 'danger');
        redirect(BASE_URL . '/companies');
        exit;
    }
    
    // Si se proporciona un evento, verificar que existe
    if ($eventId && !$this->eventModel->findById($eventId)) {
        setFlashMessage('Evento no encontrado', 'danger');
        redirect(BASE_URL . '/events');
        exit;
    }
    
    // Obtener parámetros de paginación
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 10;
    
    // Configurar filtros
    $filters = [];
    
    // Filtrar por estado si se especifica
    if (isset($_GET['status']) && in_array($_GET['status'], [
        Appointment::STATUS_SCHEDULED, 
        Appointment::STATUS_COMPLETED, 
        Appointment::STATUS_CANCELLED
    ])) {
        $filters['status'] = sanitize($_GET['status']);
    }
    
    // Configurar paginación
    $totalAppointments = count($this->appointmentModel->getByCompany($companyId, $eventId, $filters['status'] ?? null));
    $pagination = paginate($totalAppointments, $page, $perPage);
    
    // Obtener citas
    $appointments = $this->appointmentModel->getByCompany($companyId, $eventId, $filters['status'] ?? null, $pagination);
    
    // Agrupar citas por fecha para mejor visualización
    $appointmentsByDate = [];
    foreach ($appointments as $appointment) {
        $date = date('Y-m-d', strtotime($appointment['start_datetime']));
        if (!isset($appointmentsByDate[$date])) {
            $appointmentsByDate[$date] = [];
        }
        $appointmentsByDate[$date][] = $appointment;
    }
    
    // Obtener información de la empresa
    $companyInfo = [
        'name' => $this->companyModel->getCompanyName(),
        'role' => $this->companyModel->getRole(),
        'contact_name' => $this->companyModel->getContactFirstName() . ' ' . $this->companyModel->getContactLastName(),
        'email' => $this->companyModel->getEmail(),
        'phone' => $this->companyModel->getPhone()
    ];
    
    // Si se filtra por evento, obtener información del evento
    $eventInfo = null;
    if ($eventId) {
        $this->eventModel->findById($eventId);
        $eventInfo = [
            'name' => $this->eventModel->getEventName(),
            'venue' => $this->eventModel->getVenue(),
            'start_date' => dateFromDatabase($this->eventModel->getStartDate()),
            'end_date' => dateFromDatabase($this->eventModel->getEndDate())
        ];
    } else {
        // Si no se filtra por evento, obtener todos los eventos en los que participa la empresa
        $events = $this->companyModel->getEvents($companyId);
    }
    
    // Token CSRF para los formularios
    $csrfToken = generateCSRFToken();
    
    // Cargar vista
            $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'appointmentcontroller',
            'moduleJS' => 'appointmentcontroller'
        ];
        
        $this->render('appointments/company_schedule', $data, 'admin');
}

/**
 * Ver citas de una fecha específica
 *
 * @param string $date Fecha en formato Y-m-d
 * @param int $eventId ID del evento (opcional)
 * @return void
 */
public function viewByDate($date, $eventId = null) {
    // Verificar permisos
    if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
        setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
        redirect(BASE_URL);
        exit;
    }
    
    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        // Si la fecha está en formato dd/mm/yyyy, convertirla
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            $date = dateToDatabase($date);
        } else {
            setFlashMessage('Formato de fecha inválido', 'danger');
            redirect(BASE_URL . '/appointments');
            exit;
        }
    }
    
    // Si se proporciona un evento, verificar que existe
    if ($eventId && !$this->eventModel->findById($eventId)) {
        setFlashMessage('Evento no encontrado', 'danger');
        redirect(BASE_URL . '/events');
        exit;
    }
    
    // Obtener citas para esta fecha
    $appointments = $this->appointmentModel->getByDate($date, $eventId);
    
    // Agrupar citas por hora para mejor visualización
    $appointmentsByHour = [];
    foreach ($appointments as $appointment) {
        $hour = date('H:i', strtotime($appointment['start_datetime']));
        if (!isset($appointmentsByHour[$hour])) {
            $appointmentsByHour[$hour] = [];
        }
        $appointmentsByHour[$hour][] = $appointment;
    }
    
    // Ordenar por hora
    ksort($appointmentsByHour);
    
    // Si se filtra por evento, obtener información del evento
    $eventInfo = null;
    if ($eventId) {
        $this->eventModel->findById($eventId);
        $eventInfo = [
            'name' => $this->eventModel->getEventName(),
            'venue' => $this->eventModel->getVenue(),
            'start_date' => dateFromDatabase($this->eventModel->getStartDate()),
            'end_date' => dateFromDatabase($this->eventModel->getEndDate())
        ];
        
        // Obtener slots disponibles para esta fecha
        $availableSlots = $this->eventModel->generateTimeSlots($eventId, $date);
        
        // Obtener mesas del evento
        $availableTables = $this->eventModel->getAvailableTables();
        
        // Matriz de ocupación por mesa y hora
        $tableOccupation = [];
        for ($i = 1; $i <= $availableTables; $i++) {
            $tableOccupation[$i] = [];
        }
        
        // Marcar qué mesas están ocupadas en qué horarios
        foreach ($appointments as $appointment) {
            $startTime = date('H:i', strtotime($appointment['start_datetime']));
            $endTime = date('H:i', strtotime($appointment['end_datetime']));
            $table = $appointment['table_number'];
            
            if (isset($tableOccupation[$table])) {
                $tableOccupation[$table][] = [
                    'start' => $startTime,
                    'end' => $endTime,
                    'id' => $appointment['schedule_id'],
                    'buyer' => $appointment['buyer_name'],
                    'supplier' => $appointment['supplier_name']
                ];
            }
        }
    } else {
        // Si no se filtra por evento, obtener eventos con citas en esta fecha
        $eventQuery = "SELECT DISTINCT e.* FROM events e 
                      JOIN event_schedules es ON e.event_id = es.event_id 
                      WHERE DATE(es.start_datetime) = :date";
        $events = $this->db->resultSet($eventQuery, ['date' => $date]);
    }
    
    // Formatear fecha para mostrar
    $formattedDate = dateFromDatabase($date);
    
    // Token CSRF para los formularios
    $csrfToken = generateCSRFToken();
    
    // Cargar vista
            $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'appointmentcontroller',
            'moduleJS' => 'appointmentcontroller'
        ];
        
        $this->render('appointments/date_schedule', $data, 'admin');
}

/**
     * Programar automáticamente una cita para un match (desde la vista de matches)
     * URL: /b2b_conector/index.php?controller=Appointment&action=schedule
     * Método: POST
     */
    public function schedule() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        // Solo aceptar POST
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
        $matchId = isset($_POST['match_id']) ? (int)$_POST['match_id'] : null;
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
        if (!$matchId) {
            setFlashMessage('Match no especificado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Si no viene el event_id, obtenerlo desde el match
        if (!$eventId) {
            if (!$this->matchModel->findById($matchId)) {
                setFlashMessage('Match no encontrado', 'danger');
                redirect(BASE_URL . '/events');
                exit;
            }
            $eventId = $this->matchModel->getEventId();
        }
        // Verificar que el match pertenece al evento
        if (!$this->matchModel->findById($matchId) || $this->matchModel->getEventId() != $eventId) {
            setFlashMessage('El match no pertenece a este evento', 'danger');
            redirect(BASE_URL . '/events/matches/' . $eventId);
            exit;
        }
        // Intentar programar la cita
        $appointmentId = $this->appointmentModel->scheduleMatch($eventId, $matchId);
        if ($appointmentId) {
            setFlashMessage('Cita programada exitosamente', 'success');
        } else {
            setFlashMessage('No se pudo programar la cita (no hay slots disponibles o ya existe una cita para este match)', 'danger');
        }
        redirect(BASE_URL . '/events/matches/' . $eventId);
        exit;
    }
    
    /**
     * Programar automáticamente todas las citas posibles para los matches de un evento
     */
    public function scheduleAll() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Solicitud inválida', 'danger');
            redirect(BASE_URL . '/');
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        if (!$eventId) {
            setFlashMessage('Evento no especificado.', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Programar todas las citas posibles para los matches aceptados del evento
        $result = $this->appointmentModel->generateSchedules($eventId);
        if ($result['success']) {
            // Actualizar campo programed de todos los matches programados
            // Usar estado 'matched' para consistencia con getConfirmedMatchesAjax
            $matches = $this->matchModel->getByEvent($eventId, 'matched');
            $programedCount = 0;
            
            foreach ($matches as $match) {
                if ($this->appointmentModel->existsForMatch($match['match_id'])) {
                    $this->matchModel->findById($match['match_id']);
                    $updateResult = $this->matchModel->update(['programed' => 1]);
                    
                    if ($updateResult) {
                        $programedCount++;
                        Logger::info("Campo programed actualizado por scheduleAll", [
                            'match_id' => $match['match_id'],
                            'programed' => 1
                        ]);
                    } else {
                        Logger::warning("Error actualizando campo programed por scheduleAll", [
                            'match_id' => $match['match_id']
                        ]);
                    }
                }
            }
            
            if (isset($result['scheduled']) && (int)$result['scheduled'] > 0) {
                setFlashMessage($result['scheduled'] . ' nuevas citas programadas y ' . $programedCount . ' matches marcados como programados.', 'success');
            } else {
                setFlashMessage('No hay citas que programar.', 'info');
            }
        } else {
            setFlashMessage('No se pudieron programar todas las citas: ' . $result['message'], 'danger');
        }
        redirect(BASE_URL . '/events/matches/' . $eventId);
        exit;
    }
}