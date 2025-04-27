<?php
/**
 * Controlador de Descansos (Breaks)
 * 
 * Este controlador maneja todas las operaciones relacionadas con los descansos
 * programados durante los eventos de networking, incluyendo creación, consulta,
 * edición y eliminación de periodos de descanso.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class BreakController {
    private $db;
    private $breakModel;
    private $eventModel;
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
        $this->breakModel = new BreakModel($this->db);
        $this->eventModel = new Event($this->db);
        
        // Inicializar validador
        $this->validator = new Validator();
        
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            setFlashMessage('Debe iniciar sesión para acceder a esta sección', 'danger');
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Verificar permisos (solo administradores y organizadores)
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
    }
    
    /**
     * Listar todos los descansos
     * 
     * @return void
     */
    public function index() {
        // Obtener parámetros de paginación y filtros
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        // Configurar filtros
        $filters = [];
        
        // Filtrar por evento si se especifica
        if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
            $filters['event_id'] = (int)$_GET['event_id'];
            
            // Verificar que el evento existe
            if (!$this->eventModel->findById($filters['event_id'])) {
                setFlashMessage('Evento no encontrado', 'danger');
                redirect(BASE_URL . '/events');
                exit;
            }
        }
        
        // Obtener total de descansos según filtros
        $totalBreaks = $this->breakModel->count($filters);
        
        // Configurar paginación
        $pagination = paginate($totalBreaks, $page, $perPage);
        
        // Obtener descansos para la página actual con filtros aplicados
        $breaks = $this->breakModel->getAll($filters, $pagination);
        
        // Obtener eventos para el filtro
        $events = $this->eventModel->getActiveEvents();
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/breaks/index.php');
    }
    
    /**
     * Listar descansos de un evento específico
     * 
     * @param int $eventId ID del evento
     * @return void
     */
    public function eventBreaks($eventId) {
        // Verificar que el evento existe
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Obtener parámetros de paginación
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        // Obtener total de descansos para este evento
        $totalBreaks = $this->breakModel->count(['event_id' => $eventId]);
        
        // Configurar paginación
        $pagination = paginate($totalBreaks, $page, $perPage);
        
        // Obtener descansos para la página actual
        $breaks = $this->breakModel->getByEvent($eventId, $pagination);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Verificar si la solicitud es AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // Para solicitud AJAX, responder con JSON
            header('Content-Type: application/json');
            echo json_encode(['breaks' => $breaks]);
            exit;
        }
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/breaks/event_breaks.php');
    }
    
    /**
     * Mostrar formulario para crear un nuevo descanso
     * 
     * @param int $eventId ID del evento para el que se creará el descanso (opcional)
     * @return void
     */
    public function create($eventId = null) {
        // Si se proporciona un evento, verificar que existe
        if ($eventId) {
            if (!$this->eventModel->findById($eventId)) {
                setFlashMessage('Evento no encontrado', 'danger');
                redirect(BASE_URL . '/events');
                exit;
            }
            
            // Cargar información del evento
            $event = $this->eventModel;
        } else {
            // Obtener eventos activos para el selector
            $events = $this->eventModel->getActiveEvents();
            
            if (empty($events)) {
                setFlashMessage('No hay eventos activos para crear descansos', 'warning');
                redirect(BASE_URL . '/events');
                exit;
            }
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario
        include(VIEW_DIR . '/breaks/create.php');
    }
    
    /**
     * Procesar la creación de un nuevo descanso
     * 
     * @return void
     */
    public function store() {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/breaks/create');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/breaks/create');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('event_id', 'El evento es obligatorio')
                       ->required('start_time', 'La hora de inicio es obligatoria')
                       ->required('end_time', 'La hora de finalización es obligatoria');
        
        // Validar formatos de hora
        if (!BreakModel::isValidTimeFormat($_POST['start_time'])) {
            $this->validator->errors['start_time'] = 'Formato de hora de inicio inválido (HH:MM:SS)';
        }
        
        if (!BreakModel::isValidTimeFormat($_POST['end_time'])) {
            $this->validator->errors['end_time'] = 'Formato de hora de finalización inválido (HH:MM:SS)';
        }
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/breaks/create/' . $_POST['event_id']);
            exit;
        }
        
        // Preparar datos para el modelo
        $breakData = [
            'event_id' => (int)$_POST['event_id'],
            'start_time' => sanitize($_POST['start_time']),
            'end_time' => sanitize($_POST['end_time'])
        ];
        
        // Crear el descanso
        try {
            $breakId = $this->breakModel->create($breakData);
            
            if ($breakId) {
                setFlashMessage('Descanso creado exitosamente', 'success');
                redirect(BASE_URL . '/events/breaks/' . $breakData['event_id']);
            } else {
                // El modelo devolvió falso, podría ser por restricciones de validación internas
                throw new Exception('Error al crear el descanso. Verifique que el horario no se superponga con otros descansos y esté dentro del horario del evento.');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al crear el descanso: ' . $e->getMessage(), 'danger');
            $_SESSION['form_data'] = $_POST;
            redirect(BASE_URL . '/breaks/create/' . $breakData['event_id']);
            exit;
        }
    }
    
    /**
     * Mostrar formulario para editar un descanso existente
     * 
     * @param int $id ID del descanso a editar
     * @return void
     */
    public function edit($id) {
        // Buscar descanso por ID
        if (!$this->breakModel->findById($id)) {
            setFlashMessage('Descanso no encontrado', 'danger');
            redirect(BASE_URL . '/breaks');
            exit;
        }
        
        // Obtener información del evento asociado
        $eventId = $this->breakModel->getEventId();
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento asociado no encontrado', 'danger');
            redirect(BASE_URL . '/breaks');
            exit;
        }
        
        // Formatear horas para el formulario (formato H:i)
        $startTime = BreakModel::formatTimeForDisplay($this->breakModel->getStartTime());
        $endTime = BreakModel::formatTimeForDisplay($this->breakModel->getEndTime());
        
        // Obtener todos los eventos activos para el selector (por si quieren cambiar el evento)
        $events = $this->eventModel->getActiveEvents();
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario
        include(VIEW_DIR . '/breaks/edit.php');
    }
    
    /**
     * Procesar la actualización de un descanso
     * 
     * @param int $id ID del descanso a actualizar
     * @return void
     */
    public function update($id) {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/breaks/edit/' . $id);
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/breaks/edit/' . $id);
            exit;
        }
        
        // Buscar descanso por ID
        if (!$this->breakModel->findById($id)) {
            setFlashMessage('Descanso no encontrado', 'danger');
            redirect(BASE_URL . '/breaks');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('event_id', 'El evento es obligatorio')
                       ->required('start_time', 'La hora de inicio es obligatoria')
                       ->required('end_time', 'La hora de finalización es obligatoria');
        
        // Validar formatos de hora
        if (!BreakModel::isValidTimeFormat($_POST['start_time'])) {
            $this->validator->errors['start_time'] = 'Formato de hora de inicio inválido (HH:MM:SS)';
        }
        
        if (!BreakModel::isValidTimeFormat($_POST['end_time'])) {
            $this->validator->errors['end_time'] = 'Formato de hora de finalización inválido (HH:MM:SS)';
        }
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/breaks/edit/' . $id);
            exit;
        }
        
        // Obtener el evento actual antes de la actualización
        $currentEventId = $this->breakModel->getEventId();
        
        // Preparar datos para el modelo
        $breakData = [
            'event_id' => (int)$_POST['event_id'],
            'start_time' => sanitize($_POST['start_time']),
            'end_time' => sanitize($_POST['end_time'])
        ];
        
        // Actualizar el descanso
        try {
            $updated = $this->breakModel->update($breakData);
            
            if ($updated) {
                setFlashMessage('Descanso actualizado exitosamente', 'success');
                
                // Redirigir a la página de breaks del evento
                // (puede ser el nuevo evento si se cambió)
                redirect(BASE_URL . '/events/breaks/' . $breakData['event_id']);
            } else {
                // El modelo devolvió falso, podría ser por restricciones de validación internas
                throw new Exception('Error al actualizar el descanso. Verifique que el horario no se superponga con otros descansos y esté dentro del horario del evento.');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al actualizar el descanso: ' . $e->getMessage(), 'danger');
            redirect(BASE_URL . '/breaks/edit/' . $id);
            exit;
        }
    }
    
    /**
     * Eliminar un descanso
     * 
     * @param int $id ID del descanso a eliminar
     * @return void
     */
    public function delete($id) {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/breaks');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/breaks');
            exit;
        }
        
        // Buscar descanso por ID
        if (!$this->breakModel->findById($id)) {
            setFlashMessage('Descanso no encontrado', 'danger');
            redirect(BASE_URL . '/breaks');
            exit;
        }
        
        // Guardar el ID del evento antes de eliminar para redirigir después
        $eventId = $this->breakModel->getEventId();
        
        // Eliminar el descanso
        try {
            $deleted = $this->breakModel->delete($id);
            
            if ($deleted) {
                setFlashMessage('Descanso eliminado exitosamente', 'success');
            } else {
                throw new Exception('Error al eliminar el descanso');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al eliminar el descanso: ' . $e->getMessage(), 'danger');
        }
        
        // Redirigir a la página de breaks del evento
        redirect(BASE_URL . '/events/breaks/' . $eventId);
    }
    
    /**
     * Eliminar todos los descansos de un evento
     * 
     * @param int $eventId ID del evento
     * @return void
     */
    public function deleteAllByEvent($eventId) {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/events/breaks/' . $eventId);
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/events/breaks/' . $eventId);
            exit;
        }
        
        // Verificar que el evento existe
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Eliminar todos los descansos del evento
        try {
            $deleted = $this->breakModel->deleteByEvent($eventId);
            
            if ($deleted) {
                setFlashMessage('Todos los descansos del evento han sido eliminados exitosamente', 'success');
            } else {
                throw new Exception('Error al eliminar los descansos del evento');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al eliminar los descansos: ' . $e->getMessage(), 'danger');
        }
        
        redirect(BASE_URL . '/events/breaks/' . $eventId);
    }
    
    /**
     * Verificar disponibilidad de un horario (para AJAX)
     * 
     * @return void
     */
    public function checkAvailability() {
        // Verificar si la solicitud es AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['error' => 'Solicitud inválida']);
            exit;
        }
        
        // Verificar token CSRF para AJAX
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Token de seguridad inválido']);
            exit;
        }
        
        // Validar datos recibidos
        if (!isset($_POST['event_id']) || !isset($_POST['start_time']) || !isset($_POST['end_time'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit;
        }
        
        $eventId = (int)$_POST['event_id'];
        $startTime = sanitize($_POST['start_time']);
        $endTime = sanitize($_POST['end_time']);
        $breakId = isset($_POST['break_id']) ? (int)$_POST['break_id'] : null;
        
        // Validar formatos de hora
        if (!BreakModel::isValidTimeFormat($startTime) || !BreakModel::isValidTimeFormat($endTime)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de hora inválido']);
            exit;
        }
        
        // Verificar si el horario está dentro del rango del evento
        $isWithinEventHours = $this->breakModel->isWithinEventHours($eventId, $startTime, $endTime);
        
        // Verificar si hay superposición con otros descansos
        $hasOverlap = $this->breakModel->hasOverlap($eventId, $startTime, $endTime, $breakId);
        
        // Responder con el resultado
        echo json_encode([
            'success' => true,
            'isAvailable' => $isWithinEventHours && !$hasOverlap,
            'isWithinEventHours' => $isWithinEventHours,
            'hasOverlap' => $hasOverlap
        ]);
        exit;
    }
}