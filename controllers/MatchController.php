<?php
/**
 * Controlador de Matches (Coincidencias)
 * 
 * Este controlador maneja todas las operaciones relacionadas con los matches
 * entre compradores y proveedores, incluyendo la generación automática, 
 * visualización, actualización y eliminación de coincidencias.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class MatchController {
    private $db;
    private $matchModel;
    private $companyModel;
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
        $this->matchModel = new MatchModel($this->db);
        $this->companyModel = new Company($this->db);
        $this->eventModel = new Event($this->db);
        
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
     * Listar todos los matches
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
        if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'accepted', 'rejected'])) {
            $filters['status'] = sanitize($_GET['status']);
        }
        
        // Filtrar por comprador si se especifica
        if (isset($_GET['buyer_id']) && !empty($_GET['buyer_id'])) {
            $filters['buyer_id'] = (int)$_GET['buyer_id'];
        }
        
        // Filtrar por proveedor si se especifica
        if (isset($_GET['supplier_id']) && !empty($_GET['supplier_id'])) {
            $filters['supplier_id'] = (int)$_GET['supplier_id'];
        }
        
        // Obtener total de matches según filtros
        $totalMatches = $this->matchModel->count($filters);
        
        // Configurar paginación
        $pagination = paginate($totalMatches, $page, $perPage);
        
        // Obtener matches para la página actual con filtros aplicados
        $matches = $this->matchModel->getAll($filters, $pagination);
        
        // Obtener eventos y empresas para los filtros
        $events = $this->eventModel->getActiveEvents();
        $buyers = $this->companyModel->getAll(['role' => 'buyer']);
        $suppliers = $this->companyModel->getAll(['role' => 'supplier']);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/matches/index.php');
    }
    
    /**
     * Mostrar detalles de un match específico
     * 
     * @param int $id ID del match
     * @return void
     */
    public function view($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER, ROLE_BUYER, ROLE_SUPPLIER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar match por ID
        if (!$this->matchModel->findById($id)) {
            setFlashMessage('Match no encontrado', 'danger');
            redirect(BASE_URL . '/matches');
            exit;
        }
        
        // Si es comprador o proveedor, verificar que el match le pertenezca
        if (hasRole([ROLE_BUYER])) {
            if ($this->matchModel->getBuyerId() != getUserCompanyId()) {
                setFlashMessage('No tiene permisos para ver este match', 'danger');
                redirect(BASE_URL . '/dashboard');
                exit;
            }
        } else if (hasRole([ROLE_SUPPLIER])) {
            if ($this->matchModel->getSupplierId() != getUserCompanyId()) {
                setFlashMessage('No tiene permisos para ver este match', 'danger');
                redirect(BASE_URL . '/dashboard');
                exit;
            }
        }
        
        // Obtener información adicional
        $buyerId = $this->matchModel->getBuyerId();
        $supplierId = $this->matchModel->getSupplierId();
        $eventId = $this->matchModel->getEventId();
        
        // Cargar información de comprador, proveedor y evento
        $buyer = $this->companyModel->findById($buyerId) ? $this->companyModel : null;
        $supplier = $this->companyModel->findById($supplierId) ? $this->companyModel : null;
        $event = $this->eventModel->findById($eventId) ? $this->eventModel : null;
        
        // Obtener categorías coincidentes
        $matchedCategories = $this->matchModel->getMatchedCategoriesArray();
        
        // Verificar si ya existe una cita programada para este match
        $appointmentModel = new Appointment($this->db);
        $hasSchedule = $appointmentModel->existsForMatch($id);
        
        if ($hasSchedule) {
            $schedule = $appointmentModel->getByMatch($id);
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/matches/view.php');
    }
    
    /**
     * Generar matches automáticamente para un evento
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
        
        // Verificar que el evento existe
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Opciones para la generación de matches
        $options = [
            'forceRegenerate' => isset($_POST['force_regenerate']) ? true : false
        ];
        
        // Generar matches
        try {
            $result = $this->matchModel->generateMatches($eventId, $options);
            
            if ($result['success']) {
                $message = "Matches generados exitosamente: {$result['new']} nuevos, {$result['existing']} existentes.";
                setFlashMessage($message, 'success');
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            setFlashMessage('Error al generar matches: ' . $e->getMessage(), 'danger');
        }
        
        redirect(BASE_URL . '/events/matches/' . $eventId);
    }
    
    /**
     * Mostrar formulario para crear un match manualmente
     * 
     * @param int $eventId ID del evento (opcional)
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
            
            // Cargar compradores y proveedores del evento
            $buyers = $this->companyModel->getByEvent($eventId, 'buyer');
            $suppliers = $this->companyModel->getByEvent($eventId, 'supplier');
            
            if (empty($buyers) || empty($suppliers)) {
                setFlashMessage('El evento debe tener al menos un comprador y un proveedor para crear matches', 'warning');
                redirect(BASE_URL . '/events/view/' . $eventId);
                exit;
            }
        } else {
            // Obtener eventos activos
            $events = $this->eventModel->getActiveEvents();
            
            if (empty($events)) {
                setFlashMessage('No hay eventos activos para crear matches', 'warning');
                redirect(BASE_URL . '/events');
                exit;
            }
        }
        
        // Obtener categorías para selección de coincidencias
        $categoryModel = new Category($this->db);
        $categories = $categoryModel->getAll(['is_active' => 1]);
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario
        include(VIEW_DIR . '/matches/create.php');
    }
    
    /**
     * Procesar la creación manual de un match
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
            redirect(BASE_URL . '/matches/create');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/matches/create');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('event_id', 'El evento es obligatorio')
                       ->required('buyer_id', 'El comprador es obligatorio')
                       ->required('supplier_id', 'El proveedor es obligatorio');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/matches/create/' . $_POST['event_id']);
            exit;
        }
        
        $eventId = (int)$_POST['event_id'];
        $buyerId = (int)$_POST['buyer_id'];
        $supplierId = (int)$_POST['supplier_id'];
        
        // Obtener categorías seleccionadas si se proporcionaron
        $matchedCategories = [];
        if (isset($_POST['categories']) && !empty($_POST['categories'])) {
            $subcategoryModel = new Subcategory($this->db);
            
            foreach ($_POST['categories'] as $subcategoryId) {
                $subcategoryId = (int)$subcategoryId;
                if ($subcategoryModel->findById($subcategoryId)) {
                    $categoryData = $subcategoryModel->getCategory();
                    $matchedCategories[] = [
                        'category_id' => $categoryData['category_id'],
                        'category_name' => $categoryData['category_name'],
                        'subcategory_id' => $subcategoryId,
                        'subcategory_name' => $subcategoryModel->getSubcategoryName()
                    ];
                }
            }
        }
        
        // Crear el match manual
        try {
            $matchId = $this->matchModel->createManualMatch($buyerId, $supplierId, $eventId, $matchedCategories);
            
            if ($matchId) {
                setFlashMessage('Match creado exitosamente', 'success');
                redirect(BASE_URL . '/matches/view/' . $matchId);
            } else {
                throw new Exception('Error al crear el match. Verifique que no exista ya un match entre estas empresas para este evento.');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al crear el match: ' . $e->getMessage(), 'danger');
            $_SESSION['form_data'] = $_POST;
            redirect(BASE_URL . '/matches/create/' . $eventId);
            exit;
        }
    }
    
    /**
     * Actualizar el estado de un match
     * 
     * @param int $id ID del match
     * @return void
     */
    public function updateStatus($id) {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/matches/view/' . $id);
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/matches/view/' . $id);
            exit;
        }
        
        // Buscar match por ID
        if (!$this->matchModel->findById($id)) {
            setFlashMessage('Match no encontrado', 'danger');
            redirect(BASE_URL . '/matches');
            exit;
        }
        
        // Verificar permisos
        $canUpdate = false;
        
        if (hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            $canUpdate = true;
        } else if (hasRole([ROLE_BUYER]) && $this->matchModel->getBuyerId() == getUserCompanyId()) {
            $canUpdate = true;
        } else if (hasRole([ROLE_SUPPLIER]) && $this->matchModel->getSupplierId() == getUserCompanyId()) {
            $canUpdate = true;
        }
        
        if (!$canUpdate) {
            setFlashMessage('No tiene permisos para actualizar este match', 'danger');
            redirect(BASE_URL . '/dashboard');
            exit;
        }
        
        // Verificar que se proporciona un estado válido
        if (!isset($_POST['status']) || !in_array($_POST['status'], ['pending', 'accepted', 'rejected'])) {
            setFlashMessage('Estado inválido', 'danger');
            redirect(BASE_URL . '/matches/view/' . $id);
            exit;
        }
        
        $newStatus = sanitize($_POST['status']);
        
        // Actualizar el estado
        try {
            $updated = $this->matchModel->updateStatus($id, $newStatus);
            
            if ($updated) {
                $statusMessages = [
                    'pending' => 'Match marcado como pendiente',
                    'accepted' => 'Match aceptado exitosamente',
                    'rejected' => 'Match rechazado'
                ];
                
                setFlashMessage($statusMessages[$newStatus], 'success');
            } else {
                throw new Exception('Error al actualizar el estado del match');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al actualizar el estado: ' . $e->getMessage(), 'danger');
        }
        
        redirect(BASE_URL . '/matches/view/' . $id);
    }
    
    /**
     * Eliminar un match
     * 
     * @param int $id ID del match a eliminar
     * @return void
     */
    public function delete($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para eliminar matches', 'danger');
            redirect(BASE_URL . '/matches');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/matches');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/matches');
            exit;
        }
        
        // Buscar match por ID
        if (!$this->matchModel->findById($id)) {
            setFlashMessage('Match no encontrado', 'danger');
            redirect(BASE_URL . '/matches');
            exit;
        }
        
        // Guardar el ID del evento para redirigir después
        $eventId = $this->matchModel->getEventId();
        
        // Verificar si hay citas asociadas a este match
        $appointmentModel = new Appointment($this->db);
        if ($appointmentModel->existsForMatch($id)) {
            setFlashMessage('No se puede eliminar el match porque tiene citas programadas', 'danger');
            redirect(BASE_URL . '/matches/view/' . $id);
            exit;
        }
        
        // Eliminar el match
        try {
            $deleted = $this->matchModel->delete($id);
            
            if ($deleted) {
                setFlashMessage('Match eliminado exitosamente', 'success');
                redirect(BASE_URL . '/events/matches/' . $eventId);
            } else {
                throw new Exception('Error al eliminar el match');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al eliminar el match: ' . $e->getMessage(), 'danger');
            redirect(BASE_URL . '/matches/view/' . $id);
        }
    }
    
    /**
     * Listar matches para compradores o proveedores
     * 
     * @return void
     */
    public function myMatches() {
        // Verificar permisos
        if (!hasRole([ROLE_BUYER, ROLE_SUPPLIER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Obtener ID de la empresa del usuario actual
        $companyId = getUserCompanyId();
        if (!$companyId) {
            setFlashMessage('No tiene una empresa asociada', 'danger');
            redirect(BASE_URL . '/dashboard');
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
        if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'accepted', 'rejected'])) {
            $filters['status'] = sanitize($_GET['status']);
        }
        
        // Determinar si es comprador o proveedor
        $userRole = $_SESSION['role'];
        $isComprador = ($userRole === ROLE_BUYER);
        
        // Obtener matches según el rol
        $matches = [];
        $totalMatches = 0;
        
        if ($isComprador) {
            $filters['buyer_id'] = $companyId;
            $totalMatches = $this->matchModel->count($filters);
            
            // Configurar paginación
            $pagination = paginate($totalMatches, $page, $perPage);
            
            $matches = $this->matchModel->getByBuyer($companyId, $filters['event_id'] ?? null, $filters['status'] ?? null, $pagination);
        } else {
            $filters['supplier_id'] = $companyId;
            $totalMatches = $this->matchModel->count($filters);
            
            // Configurar paginación
            $pagination = paginate($totalMatches, $page, $perPage);
            
            $matches = $this->matchModel->getBySupplier($companyId, $filters['event_id'] ?? null, $filters['status'] ?? null, $pagination);
        }
        
        // Obtener eventos en los que participa la empresa para el filtro
        $companyEvents = $this->companyModel->getEvents($companyId);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/matches/my_matches.php');
    }
    
    /**
     * Obtener subcategorías de una categoría para AJAX
     * 
     * @return void
     */
    public function getSubcategories() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            http_response_code(403);
            echo json_encode(['error' => 'No tiene permisos para acceder a esta funcionalidad']);
            exit;
        }
        
        // Verificar si la solicitud es AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['error' => 'Solicitud inválida']);
            exit;
        }
        
        // Verificar que se envíe el ID de categoría
        if (!isset($_POST['category_id']) || empty($_POST['category_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de categoría no proporcionado']);
            exit;
        }
        
        $categoryId = (int)$_POST['category_id'];
        
        // Obtener subcategorías
        $subcategoryModel = new Subcategory($this->db);
        $subcategories = $subcategoryModel->getByCategory($categoryId, true);
        
        // Responder con JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'subcategories' => $subcategories]);
        exit;
    }
    
    /**
     * Obtener función auxiliar para obtener el ID de la empresa del usuario actual
     * 
     * @return int|null ID de la empresa o null si no tiene
     */
    private function getUserCompanyId() {
        // Obtener ID del usuario actual
        $userId = $_SESSION['user_id'];
        
        // Obtener la empresa asociada al usuario
        $query = "SELECT company_id FROM company_users WHERE user_id = :user_id LIMIT 1";
        $result = $this->db->single($query, ['user_id' => $userId]);
        
        return $result ? $result['company_id'] : null;
    }
}