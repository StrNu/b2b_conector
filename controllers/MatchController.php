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
        if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'matched', 'rejected'])) {
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

        // --- AGREGAR DÍAS DE ASISTENCIA DE CADA EMPRESA AL ARRAY DE MATCHES (como strings, no JSON) ---
        require_once(MODEL_DIR . '/AttendanceDay.php');
        $attendanceModel = new AttendanceDay($this->db);
        foreach ($matches as &$match) {
            $buyer_days = $attendanceModel->getByCompanyAndEvent($match['buyer_id'], $match['event_id']);
            $supplier_days = $attendanceModel->getByCompanyAndEvent($match['supplier_id'], $match['event_id']);
            // Asegurarse de que sean arrays de strings (fechas)
            $match['buyer_days'] = array_map('strval', $buyer_days);
            $match['supplier_days'] = array_map('strval', $supplier_days);
        }
        unset($match);
        // Obtener eventos y empresas para los filtros
        $events = $this->eventModel->getActiveEvents();
        $buyers = $this->companyModel->getAll(['role' => 'buyer']);
        $suppliers = $this->companyModel->getAll(['role' => 'supplier']);
        
        // LOG: Filtros y evento seleccionado
        error_log('[MATCHES] Filtros: ' . print_r($filters, true));
        // Obtener compradores y proveedores sin matches para el evento seleccionado (optimizado con SQL)
        $unmatchedCompanies = [];
        if (isset($filters['event_id'])) {
            $eventId = $filters['event_id'];
            error_log('[MATCHES] Consultando empresas sin matches para event_id=' . $eventId);
            // NUEVO: Usar la VIEW unmatched_companies
            $query = "SELECT * FROM unmatched_companies WHERE event_id = :event_id ORDER BY role";
            $params = [':event_id' => $eventId];
            $results = $this->db->query($query, $params);
            foreach ($results as $row) {
                $unmatchedCompanies[] = [
                    'id' => $row['company_id'],
                    'name' => $row['company_name'],
                    'role' => $row['role'],
                    'categories' => $row['categories'] ?? '-',
                    'keywords' => $row['keywords'] ?? '-',
                    'description' => $row['description'] ?? '-',
                ];
            }
            error_log('[MATCHES] Empresas sin matches encontradas (VIEW): ' . print_r($unmatchedCompanies, true));
        }
        
        // --- SUGERENCIAS PARA EMPRESAS SIN MATCH ---
        $unmatchedSuggestions = [
            'keywords' => [],
            'categories' => [],
            'subcategories' => [],
            'description_words' => []
        ];
        // Intentar leer estadísticas guardadas
        $stats = $this->matchModel->getEventStatistics($eventId);
        if ($stats && (count($stats['keywords']) || count($stats['categories']) || count($stats['subcategories']) || count($stats['descriptions']))) {
            $unmatchedSuggestions['keywords'] = $stats['keywords'];
            $unmatchedSuggestions['categories'] = $stats['categories'];
            $unmatchedSuggestions['subcategories'] = $stats['subcategories'];
            $unmatchedSuggestions['description_words'] = $stats['descriptions'];
        } else {
            // Calcular y guardar si no existen
            $allKeywords = [];
            $allCategories = [];
            $allSubcategories = [];
            $allDescriptionWords = [];
            foreach ($unmatchedCompanies as $company) {
                // Palabras clave (keywords)
                if (!empty($company['keywords']) && $company['keywords'] !== '-') {
                    $keywordsArr = preg_split('/[,;\|]/', $company['keywords']);
                    foreach ($keywordsArr as $kw) {
                        $kw = trim(mb_strtolower($kw));
                        if ($kw) $allKeywords[] = $kw;
                    }
                }
                // Categorías y subcategorías
                if (!empty($company['categories']) && $company['categories'] !== '-') {
                    $categoriesArr = preg_split('/[,;\|]/', $company['categories']);
                    foreach ($categoriesArr as $cat) {
                        $cat = trim(mb_strtolower($cat));
                        if ($cat) {
                            // Si tiene formato cat > subcat
                            if (strpos($cat, '>') !== false) {
                                list($catName, $subcatName) = array_map('trim', explode('>', $cat, 2));
                                if ($catName) $allCategories[] = $catName;
                                if ($subcatName) $allSubcategories[] = $subcatName;
                            } else {
                                $allCategories[] = $cat;
                            }
                        }
                    }
                }
                // Palabras frecuentes de la descripción
                if (!empty($company['description']) && $company['description'] !== '-') {
                    $desc = mb_strtolower($company['description']);
                    $desc = preg_replace('/[.,;:!¡¿?\(\)\[\]\{\}"\'\-]/u', ' ', $desc);
                    $words = preg_split('/\s+/', $desc);
                    foreach ($words as $w) {
                        $w = trim($w);
                        if (mb_strlen($w) > 3 && !in_array($w, ['para','con','por','una','las','los','que','del','sus','este','esta','como','más','muy','sin','son','los','las','una','unos','unas','pero','sobre','entre','cada','tiene','tienen','además','donde','desde','hace','todo','todos','todas','aqui','aquí','ello','ellos','ellas','nosotros','vosotros','usted','ustedes','ser','está','están','estamos','están','esté','estés','estemos','estén','estaba','estabas','estábamos','estaban','estuve','estuviste','estuvo','estuvimos','estuvieron','estando','estado','estada','estados','estadas','estad'])) {
                            $allDescriptionWords[] = $w;
                        }
                    }
                }
            }
            // Contar frecuencia y tomar los más comunes
            $limit = 7;
            $unmatchedSuggestions['keywords'] = array_slice(array_keys(array_count_values($allKeywords)), 0, $limit);
            $unmatchedSuggestions['categories'] = array_slice(array_keys(array_count_values($allCategories)), 0, $limit);
            $unmatchedSuggestions['subcategories'] = array_slice(array_keys(array_count_values($allSubcategories)), 0, $limit);
            $descWordCounts = array_count_values($allDescriptionWords);
            arsort($descWordCounts);
            $unmatchedSuggestions['description_words'] = array_slice(array_keys($descWordCounts), 0, $limit);
            // Guardar en la tabla para futuras consultas
            $this->matchModel->saveEventStatistics($eventId, [
                'keywords' => $unmatchedSuggestions['keywords'],
                'categories' => $unmatchedSuggestions['categories'],
                'subcategories' => $unmatchedSuggestions['subcategories'],
                'descriptions' => $unmatchedSuggestions['description_words'],
            ]);
        }
        
        // --- SUGERENCIAS POR ROL PARA EMPRESAS SIN MATCH ---
        $buyerSuggestions = [];
        $supplierSuggestions = [];
        if (isset($eventId)) {
            $sugByRole = $this->matchModel->getUnmatchedSuggestionsByRole($eventId, 7);
            $buyerSuggestions = $sugByRole['buyer'];
            $supplierSuggestions = $sugByRole['supplier'];
        }
        error_log('[SUG-ROL-BUYER] ' . print_r($buyerSuggestions, true));
        error_log('[SUG-ROL-SUPPLIER] ' . print_r($supplierSuggestions, true));
        error_log('[SUG-ROL-UNMATCHED] ' . print_r($unmatchedCompanies, true));
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // LOG extra para depuración de sugerencias
        error_log('[MATCHES] unmatchedCompanies: ' . print_r($unmatchedCompanies, true));
        error_log('[MATCHES] unmatchedSuggestions: ' . print_r($unmatchedSuggestions, true));
        // Cargar vista con los datos
        include(VIEW_DIR . '/events/matches.php');
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
        
        // Si hay errores de validación, volver ao formulario
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
                // Detect AJAX (XMLHttpRequest) or explicit 'ajax' param
                $isAjax = (
                    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                    (isset($_POST['ajax']) && $_POST['ajax'])
                );
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Match creado exitosamente.']);
                    exit;
                } else {
                    setFlashMessage('Match creado exitosamente', 'success');
                    redirect(BASE_URL . '/events/matches/' . $eventId);
                    exit;
                }
            } else {
                $errorMsg = 'Error al crear el match. Verifique que no exista ya un match entre estas empresas para este evento.';
                if (
                    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                    (isset($_POST['ajax']) && $_POST['ajax'])
                ) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                } else {
                    throw new Exception($errorMsg);
                }
            }
        } catch (Exception $e) {
            if (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_POST['ajax']) && $_POST['ajax'])
            ) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            } else {
                setFlashMessage('Error al crear el match: ' . $e->getMessage(), 'danger');
                $_SESSION['form_data'] = $_POST;
                redirect(BASE_URL . '/matches/create/' . $eventId);
                exit;
            }
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
        if (!isset($_POST['status']) || !in_array($_POST['status'], ['pending', 'matched', 'rejected'])) {
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
                    'matched' => 'Match aceptado exitosamente',
                    'rejected' => 'Match rechazado'
                ];
                
                setFlashMessage($statusMessages[$newStatus], 'success');
            } else {
                throw new Exception('Error al actualizar el estado del match');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al actualizar el estado: ' . $e->getMessage(), 'danger');
        }
        
        // En vez de redirigir a la vista del match, redirigir siempre a la lista de matches del evento
        $eventId = $this->matchModel->getEventId();
        redirect(BASE_URL . '/events/matches/' . $eventId);
        exit;
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
     * Eliminar todos los matches existentes para un evento (usado en Otros matches)
     */
    public function deleteAllExisting() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        if (!$eventId) {
            echo json_encode(['success' => false, 'message' => 'Evento no especificado']);
            exit;
        }
        // Eliminar todos los matches del evento
        require_once(MODEL_DIR . '/Match.php');
        $matchModel = new MatchModel($this->db);
        $deleted = $matchModel->deleteAllByEvent($eventId);
        if ($deleted) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudieron eliminar los matches']);
        }
        exit;
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
        if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'matched', 'rejected'])) {
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
    
    /**
     * Aceptar un match desde la vista pública de matches
     * Permite cambiar el estado a 'matched' desde el formulario de matches.php
     */
    public function acceptMatch() {
        // Solo permitir POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Método no permitido', 'danger');
            redirect(BASE_URL . '/');
            exit;
        }
        // Validar match_id
        $matchId = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;
        if (!$matchId) {
            setFlashMessage('Match inválido', 'danger');
            redirect(BASE_URL . '/');
            exit;
        }
        // Buscar el match
        if (!$this->matchModel->findById($matchId)) {
            setFlashMessage('Match no encontrado', 'danger');
            redirect(BASE_URL . '/');
            exit;
        }
        // Actualizar estado a 'matched' (o 'guardado')
        $updated = $this->matchModel->updateStatus($matchId, MatchModel::STATUS_MATCHED);
        if ($updated) {
            setFlashMessage('Match exitoso', 'success');
        } else {
            setFlashMessage('No se pudo agregar el match', 'danger');
        }
        // Redirigir de vuelta a la página de matches del evento
        $eventId = $this->matchModel->getEventId();
        redirect(BASE_URL . '/events/matches/' . $eventId);
    }
    
    /**
     * Generar matches manualmente para un comprador desde la vista de matches
     */
    public function generateForBuyer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Solicitud inválida', 'danger');
            redirect(BASE_URL . '/');
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $buyerId = isset($_POST['buyer_id']) ? (int)$_POST['buyer_id'] : 0;
        if (!$eventId || !$buyerId) {
            setFlashMessage('Datos incompletos para generar matches', 'danger');
            redirect(BASE_URL . '/events/matches/' . $eventId);
            exit;
        }
        $result = $this->matchModel->generateMatches($eventId, ['buyerId' => $buyerId, 'forceRegenerate' => true]);
        if ($result['success']) {
            setFlashMessage('Matches generados para el comprador.', 'success');
        } else {
            setFlashMessage('No se pudieron generar matches: ' . $result['message'], 'danger');
        }
        redirect(BASE_URL . '/events/matches/' . $eventId);
        exit;
    }

    /**
     * Generar matches manualmente para un proveedor desde la vista de matches
     */
    public function generateForSupplier() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Solicitud inválida', 'danger');
            redirect(BASE_URL . '/');
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        if (!$eventId || !$supplierId) {
            setFlashMessage('Datos incompletos para generar matches', 'danger');
            redirect(BASE_URL . '/events/matches/' . $eventId);
            exit;
        }
        $result = $this->matchModel->generateMatches($eventId, ['supplierId' => $supplierId, 'forceRegenerate' => true]);
        if ($result['success']) {
            setFlashMessage('Matches generados para el proveedor.', 'success');
        } else {
            setFlashMessage('No se pudieron generar matches: ' . $result['message'], 'danger');
        }
        redirect(BASE_URL . '/events/matches/' . $eventId);
        exit;
    }

    /**
     * Generar matches manualmente para todo el evento (botón global)
     */
    public function generateAll() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Solicitud inválida', 'danger');
            redirect(BASE_URL . '/');
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        // Fallback: intentar recuperar event_id de la URL de referencia si no viene en POST
        if (!$eventId && isset($_SERVER['HTTP_REFERER'])) {
            if (preg_match('#/events/matches/(\d+)#', $_SERVER['HTTP_REFERER'], $m)) {
                $eventId = (int)$m[1];
            }
        }
        if (!$eventId) {
            setFlashMessage('Evento no especificado. No se pudo determinar el evento para generar matches.', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Generar matches nuevos con status 'matched'
        $result = $this->matchModel->generateMatches($eventId, ['forceRegenerate' => true, 'status' => 'matched']);
        // Cambiar todos los matches 'pending' a 'matched' para el evento
        $pendingMatches = $this->matchModel->getByEvent($eventId, 'pending');
        foreach ($pendingMatches as $pending) {
            $this->matchModel->updateStatus($pending['match_id'], 'matched');
        }
        if ($result['success']) {
            setFlashMessage('Matches generados y aceptados para todo el evento.', 'success');
        } else {
            setFlashMessage('No se pudieron generar matches: ' . $result['message'], 'danger');
        }
        redirect(BASE_URL . '/events/matches/' . $eventId);
        exit;
    }

    /**
     * Buscar matches sugeridos por similitud de descripción (AJAX)
     */
    public function searchByDescriptionAjax() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
            exit;
        }
        $companyId = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        if (!$companyId || !$eventId || !in_array($role, ['buyer', 'supplier'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        // Obtener la empresa seleccionada
        if (!$this->companyModel->findById($companyId)) {
            echo json_encode(['success' => false, 'message' => 'Empresa no encontrada']);
            exit;
        }
        $selectedCompany = [
            'company_id' => $companyId,
            'company_name' => $this->companyModel->company_name,
            'description' => $this->companyModel->description
        ];
        $selectedDesc = strtolower(trim($selectedCompany['description'] ?? ''));
        if (!$selectedDesc) {
            echo json_encode(['success' => false, 'matches' => [], 'message' => 'La empresa no tiene descripción.']);
            exit;
        }
        // Buscar empresas del otro grupo
        $targetRole = $role === 'buyer' ? 'supplier' : 'buyer';
        $targetCompanies = $this->companyModel->getByEvent($eventId, $targetRole);
        $matches = [];
        foreach ($targetCompanies as $target) {
            if (empty($target['description'])) continue;
            $desc = strtolower(trim($target['description']));
            // Similitud por similar_text
            similar_text($selectedDesc, $desc, $percent);
            // Similitud por palabras en común
            $wordsA = array_unique(explode(' ', preg_replace('/[^\wáéíóúüñ]+/u', ' ', $selectedDesc)));
            $wordsB = array_unique(explode(' ', preg_replace('/[^\wáéíóúüñ]+/u', ' ', $desc)));
            $common = array_intersect($wordsA, $wordsB);
            $wordSim = count($wordsA) > 0 ? round(count($common) / count($wordsA) * 100) : 0;
            // Promedio simple
            $finalSim = round(($percent + $wordSim) / 2);
            if ($finalSim >= 20) { // Solo sugerir si hay al menos 20% de similitud
                $matches[] = [
                    'company_id' => $target['company_id'],
                    'company_name' => $target['company_name'],
                    'description' => $target['description'],
                    'similarity' => $finalSim
                ];
            }
        }
        // Ordenar por similitud descendente
        usort($matches, function($a, $b) { return $b['similarity'] <=> $a['similarity']; });
        // Limitar a 10 sugerencias
        $matches = array_slice($matches, 0, 10);
        echo json_encode(['success' => true, 'matches' => $matches]);
        exit;
    }

    /**
     * Endpoint para generar reunión manual desde la pestaña "Otros matches"
     * Guarda en matches y event_schedules, y registra el nivel del match
     */
    public function generarReunionManual() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        // Validar datos mínimos
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $buyerId = isset($_POST['buyer_id']) ? (int)$_POST['buyer_id'] : 0;
        $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $fechaReunion = isset($_POST['fecha_reunion']) ? trim($_POST['fecha_reunion']) : '';
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $nivel = null;
        // Determinar nivel del match (si viene por POST, úsalo; si no, intenta inferirlo)
        if (isset($_POST['match_level']) && $_POST['match_level'] !== '') {
            $nivel = (int)$_POST['match_level'];
        } elseif (isset($_POST['razon'])) {
            // Inferir por la razón (texto) si viene
            $razon = strtolower($_POST['razon']);
            if (strpos($razon, 'subcategor') !== false) $nivel = 0;
            elseif (strpos($razon, 'fecha') !== false) $nivel = 1;
            elseif (strpos($razon, 'palabra') !== false) $nivel = 2;
            elseif (strpos($razon, 'descrip') !== false) $nivel = 3;
            elseif (strpos($razon, 'categor') !== false) $nivel = 4;
        }
        if ($nivel === null) $nivel = 1; // Por defecto: fechas
        // Validar datos
        if (!$eventId || !$buyerId || !$supplierId || !$fechaReunion) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        // Verificar que no exista ya el match
        if ($this->matchModel->exists($buyerId, $supplierId, $eventId)) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un match entre estas empresas para este evento.']);
            exit;
        }
        // Crear el match
        $matchData = [
            'buyer_id' => $buyerId,
            'supplier_id' => $supplierId,
            'event_id' => $eventId,
            'status' => 'matched', // Asumimos que es un match exitoso
            'created_at' => date('Y-m-d H:i:s'),
            'match_level' => $nivel,
            'matched_categories' => json_encode([]),
        ];
        $matchId = $this->matchModel->create($matchData);
        if (!$matchId) {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el match.']);
            exit;
        }
        // Crear la cita en event_schedules
        require_once(MODEL_DIR . '/Appointment.php');
        $appointmentModel = new Appointment($this->db);
        $start = date('Y-m-d H:i:s', strtotime($fechaReunion));
        $end = date('Y-m-d H:i:s', strtotime($fechaReunion) + 25*60); // 25 minutos por default
        $appointmentData = [
            'event_id' => $eventId,
            'match_id' => $matchId,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'status' => 'scheduled',
            'is_manual' => 1,
        ];
        if (!empty($observaciones)) {
            $appointmentData['notes'] = $observaciones;
        }
        $scheduleId = $appointmentModel->create($appointmentData);
        if (!$scheduleId) {
            // Si falla la cita, elimina el match para no dejar basura
            $this->matchModel->delete($matchId);
            echo json_encode(['success' => false, 'message' => 'No se pudo crear la cita.']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'Reunión generada correctamente.']);
        exit;
    }

    /**
     * Ocultar (ignorar) un match sugerido por otros criterios (no elimina matches ni agendas)
     */
    public function ignoreSuggested() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $buyerId = isset($_POST['buyer_id']) ? (int)$_POST['buyer_id'] : 0;
        $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        if (!$eventId || !$buyerId || !$supplierId) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        require_once(MODEL_DIR . '/IgnoredMatch.php');
        $ignoredModel = new IgnoredMatchModel($this->db);
        $ignoredModel->ignore($eventId, $buyerId, $supplierId);
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Ocultar (ignorar) todos los matches sugeridos existentes (bulk)
     */
    public function ignoreAllSuggested() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $pairs = isset($_POST['pairs']) && is_array($_POST['pairs']) ? $_POST['pairs'] : [];
        if (!$eventId || !$pairs) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        require_once(MODEL_DIR . '/IgnoredMatch.php');
        $ignoredModel = new IgnoredMatchModel($this->db);
        $ignoredModel->ignoreBulk($eventId, $pairs);
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Obtener matches encontrados (coincidencia de subcategoría y días) para un evento (AJAX)
     * Devuelve matches con todas las subcategorías agregadas por match (para Matches encontrados)
     */
    public function getConfirmedMatchesAjax() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        if (!$eventId) {
            echo json_encode(['success' => false, 'message' => 'Evento no especificado']);
            exit;
        }
        // Buscar matches confirmados (matched o accepted)
        $matches = $this->matchModel->getAll([
            'event_id' => $eventId,
        ]);
        // Filtrar por status matched o accepted
        $confirmed = array_filter($matches, function($m) {
            return in_array($m['status'], [MatchModel::STATUS_MATCHED, MatchModel::STATUS_ACCEPTED]);
        });
        $result = [];
        foreach ($confirmed as $row) {
            $result[] = [
                'match_id' => $row['match_id'] ?? null,
                'buyer_id' => $row['buyer_id'] ?? '-',
                'supplier_id' => $row['supplier_id'] ?? '-',
                'event_id' => $row['event_id'] ?? '-',
                'match_strength' => $row['match_strength'] ?? 0,
                'created_at' => $row['created_at'] ?? null,
                'status' => $row['status'] ?? '-',
                'matched_categories' => $row['matched_categories'] ?? '-',
                'programed' => $row['programed'] ?? '-',
                'match_level' => $row['match_level'] ?? '-',
                'buyer_subcategories' => $row['buyer_subcategories'] ?? '-',
                'supplier_subcategories' => $row['supplier_subcategories'] ?? '-',
                'buyer_dates' => $row['buyer_dates'] ?? '-',
                'supplier_dates' => $row['supplier_dates'] ?? '-',
                'buyer_keywords' => $row['buyer_keywords'] ?? '-',
                'supplier_keywords' => $row['supplier_keywords'] ?? '-',
                'buyer_description' => $row['buyer_description'] ?? '-',
                'supplier_description' => $row['supplier_description'] ?? '-',
                'reason' => $row['reason'] ?? '-',
                'keywords_match' => $row['keywords_match'] ?? '-',
                'coincidence_of_dates' => $row['coincidence_of_dates'] ?? '-',
            ];
        }
        echo json_encode(['success' => true, 'matches' => $result]);
        exit;
    }

    /**
     * Obtener matches potenciales para un evento (AJAX, usando la tabla matches)
     */
    public function getPotentialMatchesAjax() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
            exit;
        }
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        if (!$eventId) {
            echo json_encode(['success' => false, 'message' => 'Evento no especificado']);
            exit;
        }
        // Buscar matches con status 'pending' (potenciales)
        $matches = $this->matchModel->getByEvent($eventId, MatchModel::STATUS_PENDING);
        $result = [];
        foreach ($matches as $row) {
            $result[] = [
                'match_id' => $row['match_id'] ?? null,
                'buyer_id' => $row['buyer_id'] ?? '-',
                'buyer_name' => $row['buyer_name'] ?? '-',
                'supplier_id' => $row['supplier_id'] ?? '-',
                'supplier_name' => $row['supplier_name'] ?? '-',
                'reason' => $row['reason'] ?? '-',
                'keywords_match' => $row['keywords_match'] ?? '-',
                'coincidence_of_dates' => $row['coincidence_of_dates'] ?? '-',
                'buyer_description' => $row['buyer_description'] ?? '-',
                'supplier_description' => $row['supplier_description'] ?? '-',
                'buyer_dates' => $row['buyer_dates'] ?? '-',
                'supplier_dates' => $row['supplier_dates'] ?? '-',
                'buyer_subcategories' => $row['buyer_subcategories'] ?? '-',
                'supplier_subcategories' => $row['supplier_subcategories'] ?? '-',
                'buyer_keywords' => $row['buyer_keywords'] ?? '-',
                'supplier_keywords' => $row['supplier_keywords'] ?? '-',
                'match_strength' => $row['match_strength'] ?? 0,
                'created_at' => $row['created_at'] ?? null,
                'status' => $row['status'] ?? '-',
            ];
        }
        echo json_encode(['success' => true, 'matches' => $result]);
        exit;
    }
}

// Registrar endpoint AJAX si la URL lo requiere (solo si el router es simple)
if (isset($_GET['action']) && $_GET['action'] === 'searchByDescriptionAjax') {
    $controller = new MatchController();
    $controller->searchByDescriptionAjax();
    exit;
}

// Registrar endpoint AJAX para getConfirmedMatchesAjax si la URL lo requiere
if (isset($_GET['action']) && $_GET['action'] === 'getConfirmedMatchesAjax') {
    $controller = new MatchController();
    $controller->getConfirmedMatchesAjax();
    exit;
}

// Registrar endpoint AJAX para getUnmatchedCompaniesAjax si la URL lo requiere
if (isset($_GET['action']) && $_GET['action'] === 'getUnmatchedCompaniesAjax') {
    $controller = new MatchController();
    $controller->getUnmatchedCompaniesAjax();
    exit;
}

// Registrar endpoint AJAX para getConfirmedMatchesSimpleAjax si la URL lo requiere
if (isset($_GET['action']) && $_GET['action'] === 'getConfirmedMatchesSimpleAjax') {
    $controller = new MatchController();
    $controller->getConfirmedMatchesSimpleAjax();
    exit;
}

// Registrar endpoint AJAX para getPotentialMatchesAjax si la URL lo requiere
if (isset($_GET['action']) && $_GET['action'] === 'getPotentialMatchesAjax') {
    $controller = new MatchController();
    $controller->getPotentialMatchesAjax();
    exit;
}