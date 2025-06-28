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

require_once 'BaseController.php';

class CategoryController extends BaseController {
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
        
        parent::__construct();
        
        // La conexión ya se inicializa en BaseController
        // $this->db ya está disponible
        
// Inicializar conexión a la base de datos        // Inicializar modelos
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
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        
        // Obtener parámetros de paginación y filtros
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        // Configurar filtros
        $filters = $this->buildFilters([
            'search' => ['category_name', '%'],
            'is_active' => ['is_active', '']
        ]);
        
        // Obtener total de categorías según filtros
        $totalCategories = $this->categoryModel->count($filters);
        
        // Configurar paginación
        $pagination = paginate($totalCategories, $page, $perPage);
        
        // Obtener categorías para la página actual con filtros aplicados
        $categories = $this->categoryModel->getAll($filters, $pagination);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();

        // Configurar datos para la vista
        $pageData = [
            'pageTitle' => 'Categorías',
            'moduleCSS' => 'categories',
            'moduleJS' => ['categories', 'components/import_modal']
        ];
        
        // Cargar vista con los datos
        $this->renderView('categories/index', compact('categories', 'pagination', 'csrfToken', 'pageData'));
    }
    
    /**
     * Mostrar formulario para crear una nueva categoría
     * 
     * @return void
     */
    public function create() {
        // Verificar permisos
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER], '/categories');
        
        // Verificar si se especificó un evento
        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
        $event = null;
        
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

        // Configurar datos para la vista
        $pageData = [
            'pageTitle' => 'Crear Categoría',
            'moduleCSS' => 'categories',
            'moduleJS' => ['categories', 'components/import_modal']
        ];
        
        // Cargar vista del formulario
        $this->renderView('categories/create', compact('event', 'eventId', 'csrfToken', 'pageData'));
    }
    
    /**
     * Procesar la creación de una nueva categoría
     * 
     * @return void
     */
    public function store() {
        // Verificar permisos
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        
        // Verificar método de solicitud y token CSRF
        $this->validateRequest('POST', '/categories/create');
        
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
                $categoryId = $this->createEventCategory($eventId);
                
                // Redireccionar a la vista del evento
                $redirect = BASE_URL . '/events/view/' . $eventId;
            } else {
                // Crear categoría global
                $categoryId = $this->createGlobalCategory();
                
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
    $fileContents = file_get_contents($file);
    $bom = pack('H*', 'EFBBBF');
    if (strncmp($fileContents, $bom, 3) === 0) {
        $fileContents = substr($fileContents, 3);
    }
    $delimiters = [',', ';', "\t", '|'];
    $detectedDelimiter = ',';
    $firstLine = strtok($fileContents, "\r\n");
    foreach ($delimiters as $delimiter) {
        $count = substr_count($firstLine, $delimiter);
        if ($count > 0) {
            $detectedDelimiter = $delimiter;
            break;
        }
    }
    Logger::debug("Delimitador detectado: " . $detectedDelimiter);
    if (($handle = fopen($file, "r")) !== FALSE) {
        $this->db->beginTransaction();
        try {
            $lineCount = 0;
            $categoriesCreated = 0;
            $subcategoriesCreated = 0;
            $categories = [];
            $headers = fgetcsv($handle, 1000, $detectedDelimiter);
            $hasHeaders = false;
            $possibleHeaders = ['category_name', 'categoria', 'category', 'tipo', 'type', 'name'];
            $firstColumn = strtolower(trim($headers[0]));
            foreach ($possibleHeaders as $header) {
                if ($firstColumn === $header) {
                    $hasHeaders = true;
                    break;
                }
            }
            if (!$hasHeaders) {
                rewind($handle);
            } else {
                Logger::debug("Encabezados detectados: ", $headers);
            }
            while (($data = fgetcsv($handle, 1000, $detectedDelimiter)) !== FALSE) {
                $lineCount++;
                if (count($data) < 2 || empty(trim($data[0])) || empty(trim($data[1]))) {
                    continue;
                }
                $categoryName = trim($data[0]);
                $subcategoryName = trim($data[1]);
                // --- Cambios aquí: Lanzar excepción si falla la inserción ---
                if (!isset($categories[$categoryName])) {
                    $existingCategory = $this->categoryModel->getEventCategoryByName($eventId, $categoryName);
                    if ($existingCategory) {
                        $categories[$categoryName] = $existingCategory['event_category_id'];
                    } else {
                        $categoryId = $this->categoryModel->createEventCategory($eventId, $categoryName);
                        if (!$categoryId) {
                            throw new Exception("Error al crear la categoría: $categoryName");
                        }
                        $categories[$categoryName] = $categoryId;
                        $categoriesCreated++;
                        Logger::debug("Categoría creada: $categoryName, ID: $categoryId");
                    }
                }
                $categoryId = $categories[$categoryName];
                if (empty($categoryId)) {
                    throw new Exception("ID de categoría no válido para subcategoría: $subcategoryName");
                }
                $existingSubcategories = $this->categoryModel->getEventSubcategories($categoryId);
                $exists = false;
                foreach ($existingSubcategories as $subcat) {
                    if (strcasecmp($subcat['name'], $subcategoryName) === 0) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $subcategoryId = $this->categoryModel->createEventSubcategory($categoryId, $subcategoryName);
                    if (!$subcategoryId) {
                        throw new Exception("Error al crear la subcategoría: $subcategoryName");
                    }
                    $subcategoriesCreated++;
                    Logger::debug("Subcategoría creada: $subcategoryName para categoría ID: $categoryId");
                }
            }
            // --- Fin cambios ---
            $this->db->commit();
            if ($categoriesCreated > 0 || $subcategoriesCreated > 0) {
                setFlashMessage("Importación exitosa: $categoriesCreated categorías y $subcategoriesCreated subcategorías creadas.", 'success');
            } else {
                setFlashMessage("No se crearon nuevas categorías o subcategorías. Es posible que todas ya existan o que haya habido errores en el proceso.", 'warning');
            }
        } catch (Exception $e) {
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
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        // Verificar que el evento exista
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        // Obtener categorías del evento con sus subcategorías (centralizado)
        $categoriesWithSubcategories = $this->categoryModel->getEventCategoriesWithSubcategories($eventId);
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        // Configurar datos para la vista
        $pageData = [
            'pageTitle' => 'Categorías del Evento',
            'moduleCSS' => 'categories',
            'moduleJS' => ['categories', 'components/import_modal']
        ];
        // Cargar vista con los datos
        $this->renderView('events/categories', compact(
            'categoriesWithSubcategories', 
            'eventId', 
            'eventModel',
            'csrfToken', 
            'pageData'
        ));
    }
    
    /**
     * Eliminar una categoría específica de un evento
     * 
     * @param int $categoryId ID de la categoría
     * @return void
     */
    public function deleteEventCategory($eventId, $categoryId) {
        // Verificar permisos
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        
        // Verificar método de solicitud y token CSRF
        $this->validateRequest('POST', '/events');
        
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
    
    // MÉTODOS DESACTIVADOS PARA EVITAR CONFLICTOS CON EventController
    /*
    public function editCategory($categoryId) {
        // ...método original comentado...
    }
    public function deleteCategory($categoryId) {
        // ...método original comentado...
    }
    public function editSubcategory($subcategoryId) {
        // ...método original comentado...
    }
    public function deleteSubcategory($subcategoryId) {
        // ...método original comentado...
    }
    */
    
    /**
     * Obtener subcategorías por AJAX
     * 
     * @return void
     */
    public function getSubcategories() {
        // Verificar si la solicitud es AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendJsonResponse(['error' => 'Solicitud no válida'], 400);
        }
        
        // Verificar que se envió un ID de categoría
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        
        if (!$categoryId) {
            $this->sendJsonResponse(['error' => 'ID de categoría no válido'], 400);
        }
        
        // Obtener subcategorías según el tipo (evento o global)
        $subcategories = $this->getSubcategoriesByType($categoryId, $eventId);
        
        // Devolver subcategorías como JSON
        $this->sendJsonResponse([
            'success' => true,
            'subcategories' => $subcategories
        ]);
    }
    
    /*
     * Métodos auxiliares
     */
    
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
     * Validar método de solicitud y token CSRF
     * 
     * @param string $method Método HTTP esperado
     * @param string $redirect URL de redirección en caso de error
     * @return void
     */
    private function validateRequest($method, $redirect) {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            redirect(BASE_URL . $redirect);
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . $redirect);
            exit;
        }
    }
    
    /**
     * Validar archivo CSV subido
     * 
     * @return bool True si el archivo es válido, false en caso contrario
     */
    private function validateCsvFile() {
        // Verificar que se haya subido un archivo
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setFlashMessage('Debe seleccionar un archivo CSV válido', 'danger');
            return false;
        }
        
        // Verificar que el archivo sea un CSV
        $fileInfo = pathinfo($_FILES['csv_file']['name']);
        if (strtolower($fileInfo['extension']) !== 'csv') {
            setFlashMessage('El archivo debe ser un CSV (.csv)', 'danger');
            return false;
        }
        
        return true;
    }
    
    /**
     * Procesar datos del CSV
     * 
     * @param resource $handle Manejador del archivo CSV
     * @param int $eventId ID del evento
     * @return array Estadísticas de procesamiento
     */
    private function processCsvData($handle, $eventId) {
        $lineCount = 0;
        $currentCategoryId = null;
        $categoriesCreated = 0;
        $subcategoriesCreated = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $lineCount++;
            
            // Saltar línea de encabezados
            if ($lineCount === 1 && $this->isHeaderRow($data)) {
                continue;
            }
            
            // Verificar que haya al menos dos columnas
            if (count($data) < 2 || empty(trim($data[1]))) {
                continue;
            }
            
            // Procesar fila según el tipo (categoría o subcategoría)
            if ($this->isCategory($data[0])) {
                // Crear categoría
                $currentCategoryId = $this->insertEventCategory($eventId, $data, $categoriesCreated);
            } elseif ($this->isSubcategory($data[0])) {
                // Verificar que exista una categoría actual
                if (!$currentCategoryId) {
                    throw new Exception("Línea $lineCount: No hay una categoría padre para la subcategoría: " . trim($data[1]));
                }
                
                // Crear subcategoría
                $this->insertEventSubcategory($currentCategoryId, $data, $subcategoriesCreated);
            }
        }
        
        return [
            'categories' => $categoriesCreated,
            'subcategories' => $subcategoriesCreated
        ];
    }
    
    /**
     * Verificar si una fila es encabezado
     * 
     * @param array $data Datos de la fila
     * @return bool True si es fila de encabezado
     */
    private function isHeaderRow($data) {
        return strtolower($data[0]) === 'tipo' || 
               strtolower($data[0]) === 'type' || 
               strtolower($data[0]) === 'category_name' || 
               strtolower($data[0]) === 'categoria';
    }
    
    /**
     * Verificar si el tipo indica una categoría
     * 
     * @param string $type Tipo de fila
     * @return bool True si es una categoría
     */
    private function isCategory($type) {
        $type = strtolower(trim($type));
        return $type === 'categoría' || $type === 'categoria' || $type === 'category';
    }
    
    /**
     * Verificar si el tipo indica una subcategoría
     * 
     * @param string $type Tipo de fila
     * @return bool True si es una subcategoría
     */
    private function isSubcategory($type) {
        $type = strtolower(trim($type));
        return $type === 'subcategoría' || $type === 'subcategoria' || $type === 'subcategory';
    }
    
    /**
     * Insertar categoría para un evento
     * 
     * @param int $eventId ID del evento
     * @param array $data Datos de la categoría
     * @param int &$counter Contador de categorías creadas (por referencia)
     * @return int ID de la categoría creada
     */
    private function insertEventCategory($eventId, $data, &$counter) {
        $name = trim($data[1]);
        $description = isset($data[2]) ? trim($data[2]) : '';
        $categoryId = $this->categoryModel->createEventCategory($eventId, $name, $description);
        $counter++;
        return $categoryId;
    }
    
    /**
     * Insertar subcategoría para una categoría de evento
     * 
     * @param int $categoryId ID de la categoría
     * @param array $data Datos de la subcategoría
     * @param int &$counter Contador de subcategorías creadas (por referencia)
     * @return int ID de la subcategoría creada
     */
    private function insertEventSubcategory($categoryId, $data, &$counter) {
        $name = trim($data[1]);
        $description = isset($data[2]) ? trim($data[2]) : '';
        $this->categoryModel->createEventSubcategory($categoryId, $name, $description);
        $counter++;
        return true;
    }
    
    /**
     * Construir filtros para consultas
     * 
     * @param array $filterMap Mapeo de parámetros GET a campos de la base de datos
     * @return array Filtros procesados
     */
    private function buildFilters($filterMap) {
        $filters = [];
        
        foreach ($filterMap as $param => $settings) {
            [$field, $prefix] = $settings;
            
            if (isset($_GET[$param]) && $_GET[$param] !== '') {
                if ($prefix) {
                    $filters[$field] = $prefix . sanitize($_GET[$param]) . $prefix;
                } else {
                    $filters[$field] = (int)$_GET[$param];
                }
            }
        }
        
        return $filters;
    }
    
    /**
     * Crear categoría específica para un evento
     * 
     * @param int $eventId ID del evento
     * @return int ID de la categoría creada
     */
    private function createEventCategory($eventId) {
        $categoryData = [
            'event_id' => $eventId,
            'name' => sanitize($_POST['category_name']),
            'description' => sanitize($_POST['description'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1
        ];
        
        $query = "INSERT INTO event_categories (event_id, name, description, is_active) 
                  VALUES (:event_id, :name, :description, :is_active)";
        
        $this->db->query($query, $categoryData);
        $categoryId = $this->db->lastInsertId();
        
        // Crear subcategorías si se proporcionaron
        if (isset($_POST['subcategories']) && !empty($_POST['subcategories'])) {
            $this->createSubcategoriesForEvent($categoryId, $_POST['subcategories']);
        }
        
        return $categoryId;
    }
    
    /**
     * Crear subcategorías para una categoría de evento
     * 
     * @param int $categoryId ID de la categoría
     * @param string $subcategoriesText Texto con subcategorías (una por línea)
     * @return void
     */
    private function createSubcategoriesForEvent($categoryId, $subcategoriesText) {
        $subcategories = explode("\n", trim($subcategoriesText));
        
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
    
    /**
     * Crear categoría global
     * 
     * @return int ID de la categoría creada
     */
    private function createGlobalCategory() {
        $categoryData = [
            'category_name' => sanitize($_POST['category_name']),
            'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1
        ];
        
        $categoryId = $this->categoryModel->create($categoryData);
        
        if (!$categoryId) {
            throw new Exception('Error al crear la categoría');
        }
        
        // Crear subcategorías si se proporcionaron
        if (isset($_POST['subcategories']) && !empty($_POST['subcategories'])) {
            $this->createSubcategoriesForGlobal($categoryId, $_POST['subcategories']);
        }
        
        return $categoryId;
    }
    
    /**
     * Crear subcategorías para una categoría global
     * 
     * @param int $categoryId ID de la categoría
     * @param string $subcategoriesText Texto con subcategorías (una por línea)
     * @return void
     */
    private function createSubcategoriesForGlobal($categoryId, $subcategoriesText) {
        $subcategories = explode("\n", trim($subcategoriesText));
        
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
    
    /**
     * Obtener categorías de un evento con sus subcategorías
     * 
     * @param int $eventId ID del evento
     * @return array Categorías con subcategorías
     */
    private function getEventCategoriesWithSubcategories($eventId) {
        $categories = $this->categoryModel->getEventCategories($eventId);
        $categoriesWithSubcategories = [];
        foreach ($categories as $category) {
            $subcategories = $this->categoryModel->getEventSubcategories($category['event_category_id']);
            $categoriesWithSubcategories[] = [
                'category' => $category,
                'subcategories' => $subcategories
            ];
        }
        return $categoriesWithSubcategories;
    }
    
    /**
     * Obtener subcategorías según el tipo (evento o global)
     * 
     * @param int $categoryId ID de la categoría
     * @param int $eventId ID del evento (0 para categorías globales)
     * @return array Lista de subcategorías
     */
    private function getSubcategoriesByType($categoryId, $eventId) {
        if ($eventId) {
            // Subcategorías de evento
            $query = "SELECT * FROM event_subcategories 
                      WHERE event_category_id = :category_id 
                      ORDER BY name";
                      
            return $this->db->resultSet($query, [':category_id' => $categoryId]);
        } else {
            // Subcategorías globales
            return $this->subcategoryModel->getByCategory($categoryId, true);
        }
    }
    
    /**
     * Enviar respuesta JSON
     * 
     * @param array $data Datos a enviar
     * @param int $statusCode Código de estado HTTP
     * @return void
     */
    private function sendJsonResponse($data, $statusCode = 200) {
        if ($statusCode !== 200) {
            http_response_code($statusCode);
        }
        
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Renderizar vista
     * 
     * @param string $view Ruta de la vista relativa a la carpeta views
     * @param array $data Datos para la vista
     * @return void
     */
    private function renderView($view, $data = []) {
        // Agregar metadatos estándar
        $data = array_merge([
            'pageTitle' => 'Categorías',
            'moduleCSS' => 'categories',
            'moduleJS' => 'categories'
        ], $data);
        
        $this->render($view, $data, 'admin');
    }
    
    /**
     * Editar una categoría de evento (GET: mostrar formulario, POST: guardar cambios)
     * @param int $eventId
     * @param int $categoryId
     */
    public function editEventCategory($eventId, $eventCategoryId) {
        Logger::debug('[editEventCategory] eventId: ' . $eventId . ', eventCategoryId: ' . $eventCategoryId);
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            Logger::debug('[editEventCategory] Evento no encontrado: ' . $eventId);
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $category = $this->categoryModel->getEventCategory($eventCategoryId);
        Logger::debug('[editEventCategory] getEventCategory result: ' . print_r($category, true));
        if (!$category || $category['event_id'] != $eventId) {
            Logger::debug('[editEventCategory] Categoría no encontrada o no pertenece al evento. eventId: ' . $eventId . ', eventCategoryId: ' . $eventCategoryId);
            setFlashMessage('Categoría no encontrada', 'danger');
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
            Logger::debug('[editEventCategory] POST name: ' . $name . ', is_active: ' . $is_active);
            if ($name === '') {
                Logger::debug('[editEventCategory] Nombre vacío');
                setFlashMessage('El nombre es obligatorio', 'danger');
                redirect(BASE_URL . "/events/categories/$eventId");
                exit;
            }
            $update = $this->categoryModel->editEventCategory($eventCategoryId, [
                'name' => $name,
                'is_active' => $is_active
            ]);
            Logger::debug('[editEventCategory] update result: ' . print_r($update, true));
            if ($update) {
                setFlashMessage('Categoría actualizada correctamente', 'success');
            } else {
                setFlashMessage('No se pudo actualizar la categoría', 'danger');
            }
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        // GET: mostrar formulario
        $csrfToken = generateCSRFToken();
        $pageData = [
            'pageTitle' => 'Editar Categoría',
            'moduleCSS' => 'categories',
            'moduleJS' => ['categories']
        ];
        $this->renderView('events/edit_event_category', compact('category', 'eventId', 'csrfToken', 'pageData'));
    }

    /**
     * Editar una subcategoría de evento (GET: mostrar formulario, POST: guardar cambios)
     * @param int $eventId
     * @param int $subcategoryId
     */
    public function editEventSubcategory($eventId, $subcategoryId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $subcategory = $this->categoryModel->getEventSubcategory($subcategoryId);
        if (!$subcategory) {
            setFlashMessage('Subcategoría no encontrada', 'danger');
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
            if ($name === '') {
                setFlashMessage('El nombre es obligatorio', 'danger');
                redirect(BASE_URL . "/events/categories/$eventId");
                exit;
            }
            $update = $this->categoryModel->editEventSubcategory($subcategoryId, [
                'name' => $name,
                'is_active' => $is_active
            ]);
            if ($update) {
                setFlashMessage('Subcategoría actualizada correctamente', 'success');
            } else {
                setFlashMessage('No se pudo actualizar la subcategoría', 'danger');
            }
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        // GET: mostrar formulario (puedes adaptar la vista si lo necesitas)
        $csrfToken = generateCSRFToken();
        $pageData = [
            'pageTitle' => 'Editar Subcategoría',
            'moduleCSS' => 'categories',
            'moduleJS' => ['categories']
        ];
        $this->renderView('events/edit_event_subcategory', compact('subcategory', 'eventId', 'csrfToken', 'pageData'));
    }

    /**
     * Eliminar una subcategoría de evento
     * @param int $eventId
     * @param int $subcategoryId
     */
    public function deleteEventSubcategory($eventId, $subcategoryId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $deleted = $this->categoryModel->deleteEventSubcategory($subcategoryId);
        if ($deleted) {
            setFlashMessage('Subcategoría eliminada correctamente', 'success');
        } else {
            setFlashMessage('No se pudo eliminar la subcategoría', 'danger');
        }
        redirect(BASE_URL . "/events/categories/$eventId");
        exit;
    }
    
    /**
     * Agregar una categoría a un evento (GET: mostrar formulario, POST: guardar)
     * @param int $eventId
     */
    public function addEventCategory($eventId) {
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            if ($name === '') {
                setFlashMessage('El nombre es obligatorio', 'danger');
                redirect(BASE_URL . "/events/categories/$eventId");
                exit;
            }
            $categoryId = $this->categoryModel->addEventCategory($eventId, $name, $is_active);
            if ($categoryId) {
                setFlashMessage('Categoría agregada correctamente', 'success');
            } else {
                setFlashMessage('No se pudo agregar la categoría', 'danger');
            }
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        // GET: mostrar formulario
        $csrfToken = generateCSRFToken();
        $pageData = [
            'pageTitle' => 'Agregar Categoría',
            'moduleCSS' => 'categories',
            'moduleJS' => ['categories']
        ];
        $this->renderView('events/add_event_category', compact('eventId', 'csrfToken', 'pageData'));
    }

    /**
     * Agregar una subcategoría a una categoría de evento (GET: mostrar formulario, POST: guardar)
     * @param int $eventId
     * @param int $categoryId
     */
    public function addEventSubcategory($eventId, $eventCategoryId) {
        Logger::debug('[addEventSubcategory] eventId: ' . $eventId . ', eventCategoryId: ' . $eventCategoryId);
        $this->checkPermission([ROLE_ADMIN, ROLE_ORGANIZER]);
        if (!$this->eventModel->findById($eventId)) {
            Logger::debug('[addEventSubcategory] Evento no encontrado: ' . $eventId);
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/events');
            exit;
        }
        $category = $this->categoryModel->getEventCategory($eventCategoryId);
        Logger::debug('[addEventSubcategory] getEventCategory result: ' . print_r($category, true));
        if (!$category || $category['event_id'] != $eventId) {
            Logger::debug('[addEventSubcategory] Categoría no encontrada o no pertenece al evento. eventId: ' . $eventId . ', eventCategoryId: ' . $eventCategoryId);
            setFlashMessage('Categoría no encontrada', 'danger');
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            Logger::debug('[addEventSubcategory] POST name: ' . $name . ', is_active: ' . $is_active);
            if ($name === '') {
                Logger::debug('[addEventSubcategory] Nombre vacío');
                setFlashMessage('El nombre es obligatorio', 'danger');
                redirect(BASE_URL . "/events/categories/$eventId");
                exit;
            }
            $subcategoryId = $this->categoryModel->addEventSubcategory($eventCategoryId, $name, $is_active);
            Logger::debug('[addEventSubcategory] addEventSubcategory result: ' . print_r($subcategoryId, true));
            if ($subcategoryId) {
                setFlashMessage('Subcategoría agregada correctamente', 'success');
            } else {
                setFlashMessage('No se pudo agregar la subcategoría', 'danger');
            }
            redirect(BASE_URL . "/events/categories/$eventId");
            exit;
        }
        // GET: mostrar formulario
        $csrfToken = generateCSRFToken();
        $pageData = [
            'pageTitle' => 'Agregar Subcategoría',
            'moduleCSS' => 'categories',
            'moduleJS' => ['categories']
        ];
        $this->renderView('events/add_event_subcategory', compact('eventId', 'eventCategoryId', 'csrfToken', 'pageData'));
    }
}