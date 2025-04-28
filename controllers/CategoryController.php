<?php
/**
 * Controlador de Categorías
 * 
 * Este controlador maneja todas las operaciones relacionadas con las categorías y subcategorías
 * incluyendo creación, modificación, eliminación, visualización e importación.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class CategoryController {
    private $db;
    private $categoryModel;
    private $subcategoryModel;
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
        $this->categoryModel = new Category($this->db);
        $this->subcategoryModel = new Subcategory($this->db);
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
     * Listar todas las categorías
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
            $filters['category_name'] = '%' . sanitize($_GET['search']) . '%';
        }
        
        // Filtrar por estado si se especifica
        if (isset($_GET['is_active']) && in_array($_GET['is_active'], ['1', '0'])) {
            $filters['is_active'] = (int)$_GET['is_active'];
        }
        
        // Obtener total de categorías según filtros
        $totalCategories = $this->categoryModel->count($filters);
        
        // Configurar paginación
        $pagination = paginate($totalCategories, $page, $perPage);
        
        // Obtener categorías para la página actual con filtros aplicados
        $categories = $this->categoryModel->getAll($filters, $pagination);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/categories/index.php');
    }
    
    /**
     * Mostrar formulario para crear una nueva categoría
     * 
     * @return void
     */
    public function create() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para crear categorías', 'danger');
            redirect(BASE_URL . '/categories');
            exit;
        }
        
        // Verificar si se especificó un evento
        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
        
        if ($eventId) {
            // Verificar que el evento exista
            if (!$this->eventModel->findById($eventId)) {
                setFlashMessage('Evento no encontrado', 'danger');
                redirect(BASE_URL . '/events');
                exit;
            }
            
            $event = $this->eventModel;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario
        include(VIEW_DIR . '/categories/create.php');
    }
    
    /**
     * Procesar la creación de una nueva categoría
     * 
     * @return void
     */
    public function store() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para crear categorías', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/categories/create');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/categories/create');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('category_name', 'El nombre de la categoría es obligatorio');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/categories/create');
            exit;
        }
        
        // Verificar si se está asociando a un evento específico
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
        
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            if ($eventId) {
                // Crear categoría específica para el evento
                $categoryData = [
                    'event_id' => $eventId,
                    'name' => sanitize($_POST['category_name']),
                    'description' => sanitize($_POST['description'] ?? ''),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                $query = "INSERT INTO event_categories (event_id, name, description, is_active) 
                          VALUES (:event_id, :name, :description, :is_active)";
                
                $this->db->query($query, $categoryData);
                $categoryId = $this->db->lastInsertId();
                
                // Crear subcategorías si se proporcionaron
                if (isset($_POST['subcategories']) && !empty($_POST['subcategories'])) {
                    $subcategories = explode("\n", trim($_POST['subcategories']));
                    
                    foreach ($subcategories as $subcategory) {
                        $subcategory = trim($subcategory);
                        if (!empty($subcategory)) {
                            $subcategoryData = [
                                'event_category_id' => $categoryId,
                                'name' => $subcategory,
                                'is_active' => 1
                            ];
                            
                            $query = "INSERT INTO event_subcategories (event_category_id, name, is_active) 
                                      VALUES (:event_category_id, :name, :is_active)";
                            
                            $this->db->query($query, $subcategoryData);
                        }
                    }
                }
                
                // Redireccionar a la vista del evento
                $redirect = BASE_URL . '/events/view/' . $eventId;
            } else {
                // Crear categoría global
                $categoryData = [
                    'category_name' => sanitize($_POST['category_name']),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                $categoryId = $this->categoryModel->create($categoryData);
                
                if (!$categoryId) {
                    throw new Exception('Error al crear la categoría');
                }
                
                // Crear subcategorías si se proporcionaron
                if (isset($_POST['subcategories']) && !empty($_POST['subcategories'])) {
                    $subcategories = explode("\n", trim($_POST['subcategories']));
                    
                    foreach ($subcategories as $subcategory) {
                        $subcategory = trim($subcategory);
                        if (!empty($subcategory)) {
                            $subcategoryData = [
                                'subcategory_name' => $subcategory,
                                'category_id' => $categoryId,
                                'is_active' => 1
                            ];
                            
                            $this->subcategoryModel->create($subcategoryData);
                        }
                    }
                }
                
                // Redireccionar a la lista de categorías
                $redirect = BASE_URL . '/categories';
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            setFlashMessage('Categoría creada exitosamente', 'success');
            redirect($redirect);
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollback();
            
            Logger::error('Error al crear categoría: ' . $e->getMessage(), [
                'category_data' => $_POST,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            
            setFlashMessage('Error al crear la categoría: ' . $e->getMessage(), 'danger');
            $_SESSION['form_data'] = $_POST;
            
            redirect(BASE_URL . '/categories/create' . ($eventId ? '?event_id=' . $eventId : ''));
        }
    }
    
    /**
     * Importar categorías desde un archivo CSV
     * 
     * @param int $eventId ID del evento
     * @return void
     */
    public function import($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para importar categorías', 'danger');
            redirect(BASE_URL . '/events/view/' . $eventId);
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
        
        // Verificar que se haya subido un archivo
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setFlashMessage('Debe seleccionar un archivo CSV válido', 'danger');
            redirect(BASE_URL . '/events/view/' . $eventId);
            exit;
        }
        
        // Verificar que el archivo sea un CSV
        $fileInfo = pathinfo($_FILES['csv_file']['name']);
        if (strtolower($fileInfo['extension']) !== 'csv') {
            setFlashMessage('El archivo debe ser un CSV (.csv)', 'danger');
            redirect(BASE_URL . '/events/view/' . $eventId);
            exit;
        }
        
        // Procesar el archivo CSV
        $file = $_FILES['csv_file']['tmp_name'];
        
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                $lineCount = 0;
                $currentCategoryId = null;
                $categoriesCreated = 0;
                $subcategoriesCreated = 0;
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $lineCount++;
                    
                    // Saltar línea de encabezados
                    if ($lineCount === 1 && (strtolower($data[0]) === 'tipo' || strtolower($data[0]) === 'type')) {
                        continue;
                    }
                    
                    // Verificar que haya al menos dos columnas (tipo y nombre)
                    if (count($data) < 2) {
                        continue;
                    }
                    
                    // Limpiar los datos
                    $type = trim($data[0]);
                    $name = trim($data[1]);
                    $description = isset($data[2]) ? trim($data[2]) : '';
                    
                    // Si está vacío el nombre, saltar
                    if (empty($name)) {
                        continue;
                    }
                    
                    // Determinar si es categoría o subcategoría
                    if (strtolower($type) === 'categoría' || strtolower($type) === 'categoria' || strtolower($type) === 'category') {
                        // Insertar categoría
                        $query = "INSERT INTO event_categories (event_id, name, description, is_active) 
                                VALUES (:event_id, :name, :description, 1)";
                        
                        $params = [
                            ':event_id' => $eventId,
                            ':name' => $name,
                            ':description' => $description
                        ];
                        
                        $this->db->query($query, $params);
                        $currentCategoryId = $this->db->lastInsertId();
                        $categoriesCreated++;
                        
                    } elseif (strtolower($type) === 'subcategoría' || strtolower($type) === 'subcategoria' || strtolower($type) === 'subcategory') {
                        // Verificar que exista una categoría actual
                        if (!$currentCategoryId) {
                            throw new Exception("Línea $lineCount: No hay una categoría padre para la subcategoría: $name");
                        }
                        
                        // Insertar subcategoría
                        $query = "INSERT INTO event_subcategories (event_category_id, name, description, is_active) 
                                VALUES (:event_category_id, :name, :description, 1)";
                        
                        $params = [
                            ':event_category_id' => $currentCategoryId,
                            ':name' => $name,
                            ':description' => $description
                        ];
                        
                        $this->db->query($query, $params);
                        $subcategoriesCreated++;
                    }
                }
                
                // Confirmar transacción
                $this->db->commit();
                
                // Mensaje de éxito
                $message = "Importación exitosa: $categoriesCreated categorías y $subcategoriesCreated subcategorías creadas.";
                setFlashMessage($message, 'success');
                
            } catch (Exception $e) {
                // Revertir en caso de error
                $this->db->rollback();
                Logger::error("Error al importar categorías: " . $e->getMessage(), [
                    'event_id' => $eventId,
                    'file' => $_FILES['csv_file']['name']
                ]);
                setFlashMessage('Error al importar categorías: ' . $e->getMessage(), 'danger');
            }
            
            fclose($handle);
        } else {
            setFlashMessage('Error al abrir el archivo CSV', 'danger');
        }
        
        redirect(BASE_URL . '/events/view/' . $eventId);
    }
    
    /**
     * Mostrar categorías y subcategorías de un evento específico
     * 
     * @param int $eventId ID del evento
     * @return void
     */
    public function showEventCategories($eventId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar que el evento exista
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        // Obtener categorías del evento
        $query = "SELECT * FROM event_categories WHERE event_id = :event_id ORDER BY name";
        $categories = $this->db->resultSet($query, [':event_id' => $eventId]);
        
        // Obtener subcategorías para cada categoría
        $categoriesWithSubcategories = [];
        
        foreach ($categories as $category) {
            $query = "SELECT * FROM event_subcategories 
                      WHERE event_category_id = :category_id 
                      ORDER BY name";
                      
            $subcategories = $this->db->resultSet($query, [':category_id' => $category['event_category_id']]);
            
            $categoriesWithSubcategories[] = [
                'category' => $category,
                'subcategories' => $subcategories
            ];
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
        include(VIEW_DIR . '/categories/event_categories.php');
    }
    
    /**
     * Eliminar una categoría específica de un evento
     * 
     * @param int $categoryId ID de la categoría
     * @return void
     */
    public function deleteEventCategory($categoryId) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para eliminar categorías', 'danger');
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
        
        // Obtener información de la categoría
        $query = "SELECT * FROM event_categories WHERE event_category_id = :id LIMIT 1";
        $category = $this->db->single($query, [':id' => $categoryId]);
        
        if (!$category) {
            setFlashMessage('Categoría no encontrada', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        
        $eventId = $category['event_id'];
        
        // Iniciar transacción
        $this->db->beginTransaction();
        
        try {
            // Eliminar subcategorías asociadas
            $query = "DELETE FROM event_subcategories WHERE event_category_id = :id";
            $this->db->query($query, [':id' => $categoryId]);
            
            // Eliminar la categoría
            $query = "DELETE FROM event_categories WHERE event_category_id = :id";
            $result = $this->db->query($query, [':id' => $categoryId]);
            
            if (!$result) {
                throw new Exception('Error al eliminar la categoría');
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            setFlashMessage('Categoría eliminada exitosamente', 'success');
            
        } catch (Exception $e) {
            // Revertir en caso de error
            $this->db->rollback();
            
            Logger::error('Error al eliminar categoría: ' . $e->getMessage(), [
                'category_id' => $categoryId,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            
            setFlashMessage('Error al eliminar la categoría: ' . $e->getMessage(), 'danger');
        }
        
        redirect(BASE_URL . '/events/view/' . $eventId);
    }
    
    /**
     * Obtener subcategorías por AJAX
     * 
     * @return void
     */
    public function getSubcategories() {
        // Verificar si la solicitud es AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Solicitud no válida']);
            exit;
        }
        
        // Verificar que se envió un ID de categoría
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        
        if (!$categoryId) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'ID de categoría no válido']);
            exit;
        }
        
        // Verificar si es una categoría específica de evento
        if ($eventId) {
            $query = "SELECT * FROM event_subcategories 
                      WHERE event_category_id = :category_id 
                      ORDER BY name";
                      
            $subcategories = $this->db->resultSet($query, [':category_id' => $categoryId]);
        } else {
            // Obtener subcategorías para una categoría global
            $subcategories = $this->subcategoryModel->getByCategory($categoryId, true);
        }
        
        // Devolver subcategorías como JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'subcategories' => $subcategories
        ]);
        exit;
    }
}