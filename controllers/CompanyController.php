<?php
require_once(__DIR__ . '/../models/Event.php');

/**
 * Controlador para gestionar las empresas
 * 
 * @package B2B Conector
 * @version 1.0
 */

require_once 'BaseController.php';

class CompanyController extends BaseController {
    private $companyModel;
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
        
        // Inicializar modelos
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
     * Listar todas las empresas
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
        
        // Filtrar por rol si se especifica
        if (isset($_GET['role']) && in_array($_GET['role'], ['buyer', 'supplier'])) {
            $filters['role'] = sanitize($_GET['role']);
        }
        
        // Filtrar por búsqueda si se especifica
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = '%' . sanitize($_GET['search']) . '%';
        }
        
        // Obtener total de empresas según filtros
        $totalCompanies = $this->companyModel->count($filters);
        
        // Configurar paginación
        $pagination = paginate($totalCompanies, $page, $perPage);
        
        // Obtener empresas para la página actual con filtros aplicados
        $companies = $this->companyModel->getAll($filters, $pagination);
        
        // Obtener eventos para el filtro de eventos
        $events = $this->eventModel->getActiveEvents();
        
        $data = [
            'pageTitle' => 'Gestión de Empresas',
            'moduleCSS' => 'companies',
            'moduleJS' => 'companies',
            'companies' => $companies,
            'events' => $events,
            'filters' => $filters,
            'pagination' => $pagination,
            'totalCompanies' => $totalCompanies,
            'currentPage' => $page
        ];
        
        $this->render('companies/index', $data, 'admin');
    }
    
    /**
     * Mostrar detalles de una empresa específica
     * 
     * @param int $id ID de la empresa
     * @return void
     */
    public function view($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        $companyFound = $this->companyModel->findById($id);
        if (!$companyFound) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Obtener información adicional de la empresa
        $company = $this->companyModel;
        $eventId = $company->getEventId();
        $assistants = [];
        $attendanceDays = [];
        
        // Verificar que tenemos los datos necesarios
        if ($eventId) {
            try {
                $attendanceDays = $company->getAttendanceDays($eventId, $id);
                $assistants = $company->getAssistants($id);
            } catch (Exception $e) {
                // Log error but continue
                if (class_exists('Logger')) {
                    Logger::warning('Error obteniendo datos adicionales de empresa', [
                        'company_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Obtener evento relacionado
        $event = new Event($this->db);
        $eventModel = null;
        if ($eventId && $event->findById($eventId)) {
            $eventModel = $event;
        }

        // Inicializar variables
        $requirements = [];
        $offers = [];
        $categories = [];
        
        // Obtener ofertas o requerimientos según el rol de la empresa
        $role = $this->companyModel->getRole() ?? 'unknown';
        
        try {
            if ($role == 'buyer') {
                $requirements = $this->companyModel->getRequirements($id) ?? [];
                
                // Agrupar requerimientos por categoría
                foreach ($requirements as $req) {
                    if (!isset($categories[$req['category_id']])) {
                        $categories[$req['category_id']] = [
                            'category_name' => $req['category_name'],
                            'subcategories' => []
                        ];
                    }
                    
                    $categories[$req['category_id']]['subcategories'][] = [
                        'id' => $req['subcategory_id'],
                        'name' => $req['subcategory_name'],
                        'requirement_id' => $req['requirement_id'],
                        'budget_usd' => $req['budget_usd'],
                        'quantity' => $req['quantity'],
                        'unit_of_measurement' => $req['unit_of_measurement']
                    ];
                }
            } else {
                $offers = $this->companyModel->getOffers($id) ?? [];
                
                // Agrupar ofertas por categoría
                foreach ($offers as $offer) {
                    if (!isset($categories[$offer['category_id']])) {
                        $categories[$offer['category_id']] = [
                            'category_name' => $offer['category_name'],
                            'subcategories' => []
                        ];
                    }
                    
                    $categories[$offer['category_id']]['subcategories'][] = [
                        'id' => $offer['subcategory_id'],
                        'name' => $offer['subcategory_name'],
                        'offer_id' => $offer['offer_id']
                    ];
                }
            }
        } catch (Exception $e) {
            // Log error but continue with empty arrays
            if (class_exists('Logger')) {
                Logger::warning('Error obteniendo requerimientos/ofertas de empresa', [
                    'company_id' => $id,
                    'role' => $role,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Obtener matches de la empresa
        $matchModel = new MatchModel($this->db);
        $matches = [];
        
        if ($role == 'buyer') {
            $matches = $matchModel->getByBuyer($id, $eventId);
        } else {
            $matches = $matchModel->getBySupplier($id, $eventId);
        }
        
        // Obtener citas programadas
        $appointmentModel = new Appointment($this->db);
        $schedules = $appointmentModel->getByCompany($id, $eventId);
        
        // Verificación final antes del render
        if (!$company || !is_object($company)) {
            if (class_exists('Logger')) {
                Logger::error('Company object is null or invalid in view method', [
                    'company_id' => $id,
                    'company_object' => $company,
                    'company_found' => $companyFound
                ]);
            }
            setFlashMessage('Error interno: Datos de empresa no disponibles', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        $data = [
            'pageTitle' => 'Detalles de Empresa - ' . ($company ? $company->getCompanyName() : 'N/A'),
            'moduleCSS' => 'companies',
            'moduleJS' => 'companies',
            'additionalCSS' => ['components/layouts.css'],
            'company' => $company,
            'eventModel' => $eventModel,
            'requirements' => $requirements ?? [],
            'offers' => $offers ?? [],
            'matches' => $matches ?? [],
            'schedules' => $schedules ?? [],
            'eventId' => $eventId ?? null,
            'role' => $role ?? 'unknown'
        ];
        
        $this->render('companies/view', $data, 'admin');
    }
    
    /**
     * Mostrar formulario para crear una nueva empresa
     * 
     * @return void
     */
    public function create() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Obtener eventos activos para el formulario
        $events = $this->eventModel->getActiveEvents();
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        $data = [
            'pageTitle' => 'Crear Nueva Empresa',
            'moduleCSS' => 'companies',
            'moduleJS' => 'companies',
            'additionalCSS' => ['forms.css'],
            'events' => $events,
            'csrfToken' => $csrfToken
        ];
        
        $this->render('companies/create', $data, 'admin');
    }
    
    /**
     * Procesar la creación de una nueva empresa
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
            redirect(BASE_URL . '/companies/create');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/companies/create');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('company_name', 'El nombre de la empresa es obligatorio')
                       ->required('email', 'El email es obligatorio')
                       ->required('event_id', 'El evento es obligatorio')
                       ->required('role', 'El rol es obligatorio')
                       ->email('email', 'El formato de email no es válido')
                       ->in('role', ['buyer', 'supplier'], 'El rol debe ser comprador o proveedor');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/companies/create');
            exit;
        }
        
        // Preparar datos para el modelo
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
            'event_id' => (int)$_POST['event_id'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'description' => sanitize($_POST['description'] ?? '')
        ];
        
        // Manejar subida de logo si se proporciona
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            // Validar archivo
            if (!isAllowedExtension($_FILES['company_logo']['name'], ALLOWED_EXTENSIONS)) {
                setFlashMessage('Tipo de archivo no permitido para el logo. Formatos permitidos: ' . implode(', ', ALLOWED_EXTENSIONS), 'danger');
                $_SESSION['form_data'] = $_POST;
                redirect(BASE_URL . '/companies/create');
                exit;
            }
            
            if ($_FILES['company_logo']['size'] > MAX_UPLOAD_SIZE) {
                setFlashMessage('El tamaño del archivo excede el límite permitido (' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB)', 'danger');
                $_SESSION['form_data'] = $_POST;
                redirect(BASE_URL . '/companies/create');
                exit;
            }
            
            // Generar nombre único para el archivo
            $logoName = generateUniqueFileName($_FILES['company_logo']['name']);
            $companyData['company_logo'] = $logoName;
        }
        
        // Crear la empresa
        try {
            $companyId = $this->companyModel->create($companyData);
            
            if ($companyId) {
                // Si hay archivo de logo, moverlo a la ubicación final
                if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                    // Crear directorio si no existe
                    if (!is_dir(LOGO_DIR)) {
                        mkdir(LOGO_DIR, 0755, true);
                    }
                    
                    // Mover el archivo
                    if (!move_uploaded_file($_FILES['company_logo']['tmp_name'], LOGO_DIR . '/' . $logoName)) {
                        setFlashMessage('Error al subir el logo, pero la empresa fue creada', 'warning');
                    }
                }
                
                // Procesar días de asistencia si se proporcionaron
                if (isset($_POST['attendance_dates']) && is_array($_POST['attendance_dates'])) {
                    foreach ($_POST['attendance_dates'] as $date) {
                        if (!empty($date)) {
                            $this->companyModel->addAttendanceDay((int)$_POST['event_id'], $date, $companyId);
                        }
                    }
                }
                
                setFlashMessage('Empresa creada exitosamente', 'success');
                redirect(BASE_URL . '/companies/view/' . $companyId);
            } else {
                throw new Exception('Error al crear la empresa');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al crear la empresa: ' . $e->getMessage(), 'danger');
            $_SESSION['form_data'] = $_POST;
            redirect(BASE_URL . '/companies/create');
            exit;
        }
    }
    
    /**
     * Editar y guardar empresa (fusionado)
     * @param int $id
     * @return void
     */
    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // --- GUARDAR ---
            if (class_exists('Logger')) {
                Logger::info('Entrando a CompanyController::edit (POST)', ['POST' => $_POST, 'id' => $id]);
            }
            if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
                setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
                redirect(BASE_URL);
                exit;
            }
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/edit/' . $id);
                exit;
            }
            if (!$this->companyModel->findById($id)) {
                setFlashMessage('Empresa no encontrada', 'danger');
                redirect(BASE_URL . '/companies');
                exit;
            }
            $this->validator->setData($_POST);
            $this->validator->required('company_name', 'El nombre de la empresa es obligatorio')
                           ->required('email', 'El email es obligatorio')
                           ->required('event_id', 'El evento es obligatorio')
                           ->required('role', 'El rol es obligatorio')
                           ->email('email', 'El formato de email no es válido')
                           ->in('role', ['buyer', 'supplier'], 'El rol debe ser comprador o proveedor');
            if ($this->validator->hasErrors()) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                redirect(BASE_URL . '/companies/edit/' . $id);
                exit;
            }
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
                'event_id' => (int)$_POST['event_id'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'description' => sanitize($_POST['description'] ?? '')
            ];
            if (isset($_POST['keywords'])) {
                $raw = trim($_POST['keywords']);
                if ($raw === '') {
                    $companyData['keywords'] = null;
                } else {
                    $keywords = array_map('trim', explode(',', $raw));
                    $keywords = array_filter($keywords, fn($k) => $k !== '');
                    $companyData['keywords'] = $keywords ? json_encode(array_values($keywords), JSON_UNESCAPED_UNICODE) : null;
                }
            } else {
                $companyData['keywords'] = null;
            }
            $certifications = $_POST['certifications'] ?? [];
            $otros = trim($_POST['certifications_otros'] ?? '');
            if ($otros !== '') {
                $certifications[] = $otros;
            }
            if (empty($certifications) || (count($certifications) === 1 && trim($certifications[0]) === '')) {
                $companyData['certifications'] = null;
            } else {
                $companyData['certifications'] = json_encode(array_values($certifications), JSON_UNESCAPED_UNICODE);
            }
            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                if (!isAllowedExtension($_FILES['company_logo']['name'], ALLOWED_EXTENSIONS)) {
                    setFlashMessage('Tipo de archivo no permitido para el logo. Formatos permitidos: ' . implode(', ', ALLOWED_EXTENSIONS), 'danger');
                    $_SESSION['form_data'] = $_POST;
                    redirect(BASE_URL . '/companies/edit/' . $id);
                    exit;
                }
                if ($_FILES['company_logo']['size'] > MAX_UPLOAD_SIZE) {
                    setFlashMessage('El tamaño del archivo excede el límite permitido (' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB)', 'danger');
                    $_SESSION['form_data'] = $_POST;
                    redirect(BASE_URL . '/companies/edit/' . $id);
                    exit;
                }
                $this->companyModel->updateLogo($_FILES['company_logo'], $id);
            }
            try {
                $this->companyModel->findById($id);
                if (class_exists('Logger')) {
                    Logger::info('Datos preparados para update', ['companyData' => $companyData, 'company_id' => $id]);
                }
                $updated = $this->companyModel->update($companyData);
                if (class_exists('Logger')) {
                    Logger::info('Resultado de update empresa', ['updated' => $updated, 'company_id' => $id]);
                }
                if ($updated) {
                    $eventId = (int)$_POST['event_id'];
                    $currentAttendanceDays = $this->companyModel->getAttendanceDays($eventId, $id);
                    foreach ($currentAttendanceDays as $day) {
                        $formattedDay = dateFromDatabase($day);
                        if (!isset($_POST['attendance_dates']) || !in_array($formattedDay, $_POST['attendance_dates'])) {
                            $this->companyModel->removeAttendanceDay($eventId, $day, $id);
                        }
                    }
                    if (isset($_POST['attendance_dates']) && is_array($_POST['attendance_dates'])) {
                        foreach ($_POST['attendance_dates'] as $date) {
                            if (!empty($date)) {
                                $this->companyModel->addAttendanceDay($eventId, $date, $id);
                            }
                        }
                    }
                    setFlashMessage('Empresa actualizada exitosamente', 'success');
                    $redirectTo = $_POST['redirect_to'] ?? (BASE_URL . '/companies/view/' . $id);
                    redirect($redirectTo);
                } else {
                    throw new Exception('Error al actualizar la empresa');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al actualizar la empresa: ' . $e->getMessage(), 'danger');
                redirect(BASE_URL . '/companies/edit/' . $id);
                exit;
            }
        } else {
            // --- MOSTRAR FORMULARIO ---
            if (class_exists('Logger')) {
                Logger::info('Entrando a CompanyController::edit (GET)', ['id' => $id]);
            }
            if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
                setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
                redirect(BASE_URL);
                exit;
            }
            if (!$this->companyModel->findById($id)) {
                setFlashMessage('Empresa no encontrada', 'danger');
                redirect(BASE_URL . '/companies');
                exit;
            }
            $events = $this->eventModel->getActiveEvents();
            $eventId = $this->companyModel->getEventId();
            $attendanceDays = $this->companyModel->getAttendanceDays($eventId, $id);
            $formattedDays = [];
            foreach ($attendanceDays as $day) {
                $formattedDays[] = dateFromDatabase($day);
            }
            $csrfToken = generateCSRFToken();
            $company = $this->companyModel;
            
            $data = [
                'pageTitle' => 'Editar Empresa',
                'moduleCSS' => 'companies',
                'moduleJS' => 'companies',
                'additionalCSS' => ['forms.css'],
                'company' => $company,
                'events' => $events,
                'eventId' => $eventId,
                'attendanceDays' => $attendanceDays,
                'formattedDays' => $formattedDays,
                'csrfToken' => $csrfToken
            ];
            
            $this->render('companies/edit', $data, 'admin');
        }
    }
    
    
    /**
     * Eliminar una empresa
     * 
     * @param int $id ID de la empresa a eliminar
     * @return void
     */
    public function delete($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para eliminar empresas', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }

        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/companies');
            exit;
        }

        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }

        // Buscar empresa por ID
        if (!$this->companyModel->findById($id)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }

        // Eliminar la empresa
        try {
            $deleted = $this->companyModel->delete($id);
            if ($deleted) {
                setFlashMessage('Empresa eliminada exitosamente', 'success');
                // Redirigir a la lista de empresas del evento si se envió event_id
                $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
                if ($eventId) {
                    redirect(BASE_URL . '/events/companies/' . $eventId);
                    exit;
                }
            } else {
                throw new Exception('Error al eliminar la empresa');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al eliminar la empresa: ' . $e->getMessage(), 'danger');
        }
        redirect(BASE_URL . '/companies');
    }
    
    /**
     * Cambiar el estado de una empresa (activar/desactivar)
     * 
     * @param int $id ID de la empresa
     * @return void
     */
    public function toggleActive($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para modificar empresas', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($id)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Obtener estado actual y cambiarlo
        $currentState = $this->companyModel->isActive();
        $newState = $currentState ? 0 : 1;
        
        // Actualizar estado
        try {
            $updated = $this->companyModel->update(['is_active' => $newState]);
            
            if ($updated) {
                $message = $newState ? 'Empresa activada exitosamente' : 'Empresa desactivada exitosamente';
                setFlashMessage($message, 'success');
            } else {
                throw new Exception('Error al cambiar el estado de la empresa');
            }
        } catch (Exception $e) {
            setFlashMessage('Error al cambiar el estado de la empresa: ' . $e->getMessage(), 'danger');
        }
        
        redirect(BASE_URL . '/companies');
    }
    
    /**
     * Gestionar asistentes de una empresa
     * 
     * @param int $id ID de la empresa
     * @return void
     */
    public function assistants($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($id)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Procesar eliminación de asistente si se solicita
        if (isset($_POST['delete_assistant']) && isset($_POST['assistant_id'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/assistants/' . $id);
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
            
            redirect(BASE_URL . '/companies/assistants/' . $id);
            exit;
        }
        
        // Procesar creación de nuevo asistente
        if (isset($_POST['add_assistant'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/assistants/' . $id);
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
                
                redirect(BASE_URL . '/companies/assistants/' . $id);
                exit;
            }
            
            try {
                $assistantData = [
                    'first_name' => sanitize($_POST['first_name']),
                    'last_name' => sanitize($_POST['last_name']),
                    'email' => sanitize($_POST['email']),
                    'mobile_phone' => sanitize($_POST['mobile_phone'] ?? '')
                ];
                
                $assistantId = $this->companyModel->addAssistant($assistantData, $id);
                
                if ($assistantId) {
                    setFlashMessage('Asistente agregado exitosamente', 'success');
                } else {
                    throw new Exception('Error al agregar el asistente');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al agregar el asistente: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/companies/assistants/' . $id);
            exit;
        }
        
        // Obtener asistentes de la empresa
        $assistants = $this->companyModel->getAssistants($id);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        $data = [
            'pageTitle' => 'Asistentes de Empresa',
            'moduleCSS' => 'companies',
            'moduleJS' => 'companies',
            'company' => $company,
            'assistants' => $assistants,
            'eventId' => $eventId,
            'csrfToken' => $csrfToken
        ];
        
        $this->render('companies/assistants', $data, 'admin');
    }
    
    /**
     * Gestionar días de asistencia de una empresa a un evento
     * 
     * @param int $id ID de la empresa
     * @return void
     */
    public function attendance($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($id)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        $eventId = $this->companyModel->getEventId();
        
        // Buscar evento por ID
        $event = new Event($this->db);
        if (!$event->findById($eventId)) {
            setFlashMessage('Evento no encontrado', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Procesar eliminación de día de asistencia
        if (isset($_POST['delete_attendance']) && isset($_POST['date'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/attendance/' . $id);
                exit;
            }
            
            $date = sanitize($_POST['date']);
            
            try {
                $deleted = $this->companyModel->removeAttendanceDay($eventId, $date, $id);
                
                if ($deleted) {
                    setFlashMessage('Día de asistencia eliminado exitosamente', 'success');
                } else {
                    throw new Exception('Error al eliminar el día de asistencia');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al eliminar el día de asistencia: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/companies/attendance/' . $id);
            exit;
        }
        
        // Procesar adición de día de asistencia
        if (isset($_POST['add_attendance'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/attendance/' . $id);
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
                
                redirect(BASE_URL . '/companies/attendance/' . $id);
                exit;
            }
            
            try {
                $date = sanitize($_POST['date']);
                $added = $this->companyModel->addAttendanceDay($eventId, $date, $id);
                
                if ($added) {
                    setFlashMessage('Día de asistencia agregado exitosamente', 'success');
                } else {
                    throw new Exception('Error al agregar el día de asistencia');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al agregar el día de asistencia: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/companies/attendance/' . $id);
            exit;
        }
        
        // Obtener días de asistencia actuales
        $attendanceDays = $this->companyModel->getAttendanceDays($eventId, $id);
        
        // Formatear días para el formulario
        $formattedDays = [];
        foreach ($attendanceDays as $day) {
            $formattedDays[] = dateFromDatabase($day);
        }
        
        // Obtener días del evento
        $eventStartDate = $event->getStartDate();
        $eventEndDate = $event->getEndDate();
        
        // Generar rango de fechas del evento
        $startDate = new DateTime($eventStartDate);
        $endDate = new DateTime($eventEndDate);
        $eventDays = [];
        
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $eventDays[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        $data = [
            'pageTitle' => 'Días de Asistencia',
            'moduleCSS' => 'companies',
            'moduleJS' => 'companies',
            'company' => $company,
            'attendanceDays' => $attendanceDays,
            'eventModel' => $eventModel,
            'csrfToken' => $csrfToken
        ];
        
        $this->render('companies/attendance', $data, 'admin');
    }
    
    /**
     * Gestionar requerimientos para una empresa compradora
     * 
     * @param int $id ID de la empresa
     * @return void
     */
    public function requirements($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($id)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Verificar que la empresa es un comprador
        if ($this->companyModel->getRole() !== 'buyer') {
            setFlashMessage('Esta sección solo está disponible para empresas compradoras', 'danger');
            redirect(BASE_URL . '/companies/view/' . $id);
            exit;
        }
        
        // Obtener evento de la empresa
        $eventId = $this->companyModel->getEventId();
        
        // Obtener categorías y subcategorías para el formulario
        $categoryModel = new Category($this->db);
        $categories = $categoryModel->getAll(['is_active' => 1]);
        
        // Procesar eliminación de requerimiento si se solicita
        if (isset($_POST['delete_requirement']) && isset($_POST['requirement_id'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/requirements/' . $id);
                exit;
            }
            
            $requirementId = (int)$_POST['requirement_id'];
            
            try {
                $deleted = $this->companyModel->removeRequirement($requirementId);
                
                if ($deleted) {
                    setFlashMessage('Requerimiento eliminado exitosamente', 'success');
                } else {
                    throw new Exception('Error al eliminar el requerimiento');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al eliminar el requerimiento: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/companies/requirements/' . $id);
            exit;
        }
        
        // Procesar creación de nuevo requerimiento
        if (isset($_POST['add_requirement'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/requirements/' . $id);
                exit;
            }
            
            // Validar datos
            $this->validator->setData($_POST);
            $this->validator->required('subcategory_id', 'La subcategoría es obligatoria');
            
            // Si hay errores de validación
            if ($this->validator->hasErrors()) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                
                redirect(BASE_URL . '/companies/requirements/' . $id);
                exit;
            }
            
            try {
                $requirementData = [
                    'subcategory_id' => (int)$_POST['subcategory_id'],
                    'unit_of_measurement' => sanitize($_POST['unit_of_measurement'] ?? ''),
                    'budget_usd' => !empty($_POST['budget_usd']) ? (float)$_POST['budget_usd'] : null,
                    'quantity' => !empty($_POST['quantity']) ? (int)$_POST['quantity'] : null
                ];
                
                $requirementId = $this->companyModel->addRequirement($requirementData, $id);
                
                if ($requirementId) {
                    setFlashMessage('Requerimiento agregado exitosamente', 'success');
                } else {
                    throw new Exception('Error al agregar el requerimiento');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al agregar el requerimiento: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/companies/requirements/' . $id);
            exit;
        }
        
        // Obtener requerimientos actuales
        $requirements = $this->companyModel->getRequirements($id);
        
        // Agrupar requerimientos por categoría
        $groupedRequirements = [];
        foreach ($requirements as $req) {
            if (!isset($groupedRequirements[$req['category_id']])) {
                $groupedRequirements[$req['category_id']] = [
                    'category_name' => $req['category_name'],
                    'subcategories' => []
                ];
            }
            
            $groupedRequirements[$req['category_id']]['subcategories'][] = [
                'id' => $req['subcategory_id'],
                'name' => $req['subcategory_name'],
                'requirement_id' => $req['requirement_id'],
                'budget_usd' => $req['budget_usd'],
                'quantity' => $req['quantity'],
                'unit_of_measurement' => $req['unit_of_measurement']
            ];
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        $data = [
            'pageTitle' => 'Requerimientos de Empresa',
            'moduleCSS' => 'companies',
            'moduleJS' => 'companies',
            'company' => $company,
            'requirements' => $requirements,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'eventModel' => $eventModel,
            'csrfToken' => $csrfToken
        ];
        
        $this->render('companies/requirements', $data, 'admin');
    }
    
    /**
     * Gestionar ofertas para una empresa proveedora
     * 
     * @param int $id ID de la empresa
     * @return void
     */
    public function offers($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($id)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Verificar que la empresa es un proveedor
        if ($this->companyModel->getRole() !== 'supplier') {
            setFlashMessage('Esta sección solo está disponible para empresas proveedoras', 'danger');
            redirect(BASE_URL . '/companies/view/' . $id);
            exit;
        }
        
        // Obtener evento de la empresa
        $eventId = $this->companyModel->getEventId();
        
        // Obtener categorías y subcategorías para el formulario
        $categoryModel = new Category($this->db);
        $categories = $categoryModel->getAll(['is_active' => 1]);
        
        // Procesar eliminación de oferta si se solicita
        if (isset($_POST['delete_offer']) && isset($_POST['offer_id'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/offers/' . $id);
                exit;
            }
            
            $offerId = (int)$_POST['offer_id'];
            
            try {
                $deleted = $this->companyModel->removeOffer($offerId);
                
                if ($deleted) {
                    setFlashMessage('Oferta eliminada exitosamente', 'success');
                } else {
                    throw new Exception('Error al eliminar la oferta');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al eliminar la oferta: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/companies/offers/' . $id);
            exit;
        }
        
        // Procesar creación de nueva oferta
        if (isset($_POST['add_offer'])) {
            // Verificar token CSRF
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
                redirect(BASE_URL . '/companies/offers/' . $id);
                exit;
            }
            
            // Validar datos
            $this->validator->setData($_POST);
            $this->validator->required('subcategory_id', 'La subcategoría es obligatoria');
            
            // Si hay errores de validación
            if ($this->validator->hasErrors()) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['validation_errors'] = $this->validator->getErrors();
                
                redirect(BASE_URL . '/companies/offers/' . $id);
                exit;
            }
            
            try {
                $offerData = [
                    'subcategory_id' => (int)$_POST['subcategory_id']
                ];
                
                $offerId = $this->companyModel->addOffer($offerData, $id);
                
                if ($offerId) {
                    setFlashMessage('Oferta agregada exitosamente', 'success');
                } else {
                    throw new Exception('Error al agregar la oferta');
                }
            } catch (Exception $e) {
                setFlashMessage('Error al agregar la oferta: ' . $e->getMessage(), 'danger');
            }
            
            redirect(BASE_URL . '/companies/offers/' . $id);
            exit;
        }
        
        // Obtener ofertas actuales
        $offers = $this->companyModel->getOffers($id);
        
        // Agrupar ofertas por categoría
        $groupedOffers = [];
        foreach ($offers as $offer) {
            if (!isset($groupedOffers[$offer['category_id']])) {
                $groupedOffers[$offer['category_id']] = [
                    'category_name' => $offer['category_name'],
                    'subcategories' => []
                ];
            }
            
            $groupedOffers[$offer['category_id']]['subcategories'][] = [
                'id' => $offer['subcategory_id'],
                'name' => $offer['subcategory_name'],
                'offer_id' => $offer['offer_id']
            ];
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        $data = [
            'pageTitle' => 'Ofertas de Empresa',
            'moduleCSS' => 'companies',
            'moduleJS' => 'companies',
            'company' => $company,
            'offers' => $offers,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'eventModel' => $eventModel,
            'csrfToken' => $csrfToken
        ];
        
        $this->render('companies/offers', $data, 'admin');
    }
    
    /**
     * Ver citas programadas para una empresa
     * 
     * @param int $id ID de la empresa
     * @return void
     */
    public function schedules($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($id)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Obtener evento de la empresa
        $eventId = $this->companyModel->getEventId();
        
        // Obtener citas programadas
        $appointmentModel = new Appointment($this->db);
        $schedules = $appointmentModel->getByCompany($id, $eventId);
        
        // Agrupar citas por día
        $scheduledDays = [];
        foreach ($schedules as $schedule) {
            $date = date('Y-m-d', strtotime($schedule['start_datetime']));
            
            if (!isset($scheduledDays[$date])) {
                $scheduledDays[$date] = [];
            }
            
            $scheduledDays[$date][] = $schedule;
        }
        
        // Ordenar días
        ksort($scheduledDays);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        $data = [
            'pageTitle' => 'Horarios de Empresa',
            'moduleCSS' => 'companies',
            'moduleJS' => 'companies',
            'company' => $company,
            'schedules' => $schedules,
            'eventModel' => $eventModel
        ];
        
        $this->render('companies/schedules', $data, 'admin');
    }
    
    /**
     * Exportar agenda de citas de una empresa a CSV
     * 
     * @param int $id ID de la empresa
     * @return void
     */
    public function exportSchedules($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN, ROLE_ORGANIZER])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar empresa por ID
        if (!$this->companyModel->findById($id)) {
            setFlashMessage('Empresa no encontrada', 'danger');
            redirect(BASE_URL . '/companies');
            exit;
        }
        
        // Obtener evento de la empresa
        $eventId = $this->companyModel->getEventId();
        
        // Obtener citas programadas
        $appointmentModel = new Appointment($this->db);
        $schedules = $appointmentModel->getByCompany($id, $eventId);
        
        if (empty($schedules)) {
            setFlashMessage('No hay citas programadas para exportar', 'warning');
            redirect(BASE_URL . '/companies/schedules/' . $id);
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
}

