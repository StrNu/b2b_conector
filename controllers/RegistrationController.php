<?php
// RegistrationController.php
// Controlador para registro público de compradores y proveedores
require_once(__DIR__ . '/../models/Event.php');
require_once(__DIR__ . '/../models/Company.php');
require_once(__DIR__ . '/../models/Category.php');
require_once(__DIR__ . '/../models/Requirement.php');
require_once(__DIR__ . '/../models/AttendanceDay.php');
require_once(__DIR__ . '/../models/Assistant.php');
require_once(__DIR__ . '/../models/User.php');

require_once 'BaseController.php';

class RegistrationController extends BaseController {
        private $eventModel;
    private $companyModel;
    private $categoryModel;
    private $requirementModel;
    private $attendanceDayModel;
    private $assistantModel;
    private $userModel;

    public function __construct() {
        
        parent::__construct();
        
        // $this->db ya está disponible
        $this->eventModel = new Event($this->db);
        $this->companyModel = new Company($this->db);
        $this->categoryModel = new Category($this->db);
        $this->requirementModel = new Requirement($this->db);
        $this->attendanceDayModel = new AttendanceDay($this->db);
        $this->assistantModel = new Assistant($this->db);
        $this->userModel = new User($this->db);
    }

    // Formulario registro público de compradores
    public function buyersRegistration($eventId) {
        // Debug: verificar que se está ejecutando este método
        error_log("DEBUG: RegistrationController::buyersRegistration ejecutándose para eventId: $eventId");
        
        if (!$this->eventModel->findById($eventId)) {
            $data = [
                'pageTitle' => 'Evento no encontrado',
                'moduleCSS' => 'public_registration',
                'moduleJS' => 'registrationcontroller'
            ];
            
            $this->render('errors/404', $data, 'admin');
            return;
        }
        
        $event = $this->eventModel;
        $categories = $this->categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $this->categoryModel->getEventSubcategories($cat['event_category_id']);
        }
        
        // Obtener días del evento
        $eventDays = [];
        if ($event->getStartDate() && $event->getEndDate()) {
            $startDate = new DateTime($event->getStartDate());
            $endDate = new DateTime($event->getEndDate());
            $interval = $startDate->diff($endDate);
            $totalDays = $interval->days + 1;
            $currentDate = clone $startDate;
            for ($i = 0; $i < $totalDays; $i++) {
                $eventDays[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }
        }
        
        $csrfToken = generateCSRFToken();
        
        $data = [
            'pageTitle' => 'Registro de Compradores',
            'moduleCSS' => 'public_registration',
            'moduleJS' => 'registrationcontroller',
            'event' => $event,
            'eventId' => $eventId,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'eventDays' => $eventDays,
            'csrfToken' => $csrfToken
        ];
        
        // Extraer variables para la vista
        extract($data);
        
        // Incluir la vista directamente
        include VIEW_DIR . '/events/buyers_registration.php';
    }

    // Procesar registro público de compradores
    public function storeBuyersRegistration($eventId) {
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Solicitud inválida', 'danger');
            header('Location: ' . BASE_URL . "/buyers_registration/$eventId");
            exit;
        }
        
        $db = $this->db;
        $db->beginTransaction();
        
        try {
            // 1. Guardar empresa y contacto
            $companyData = [
                'company_name' => trim($_POST['company_name'] ?? ''),
                'website' => trim($_POST['website'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'keywords' => trim($_POST['keywords'] ?? ''),
                'contact_first_name' => trim($_POST['contact_first_name'] ?? ''),
                'contact_last_name' => trim($_POST['contact_last_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'country' => trim($_POST['country'] ?? ''),
                'role' => 'buyer',
                'event_id' => $eventId,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            // Procesar keywords como JSON
            $keywords = trim($_POST['keywords'] ?? '');
            if ($keywords !== '') {
                $keywordsArray = array_map('trim', explode(',', $keywords));
                $companyData['keywords'] = json_encode($keywordsArray, JSON_UNESCAPED_UNICODE);
            } else {
                $companyData['keywords'] = json_encode([]);
            }

            // Procesar certificaciones como JSON
            $certifications = $_POST['certifications'] ?? [];
            $otros = trim($_POST['certifications_otros'] ?? '');
            if ($otros !== '') {
                $certifications[] = $otros;
            }
            $companyData['certifications'] = json_encode($certifications, JSON_UNESCAPED_UNICODE);

            // Manejo de logo
            $uploadedLogoName = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['logo']['name'];
                $fileSize = $_FILES['logo']['size'];
                $fileTmpName = $_FILES['logo']['tmp_name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']) && $fileSize <= 5000000) {
                    if (!is_dir(UPLOAD_DIR . '/logos')) {
                        mkdir(UPLOAD_DIR . '/logos', 0755, true);
                    }
                    
                    $uniqueLogoName = uniqid('logo_', true) . '.' . $fileExtension;
                    $destinationPath = UPLOAD_DIR . '/logos/' . $uniqueLogoName;

                    if (move_uploaded_file($fileTmpName, $destinationPath)) {
                        $uploadedLogoName = $uniqueLogoName;
                    }
                }
            }

            if ($uploadedLogoName) {
                $companyData['company_logo'] = $uploadedLogoName;
            }

            $companyId = $this->companyModel->createForEvent($companyData);
            if (!$companyId) throw new Exception('No se pudo registrar la empresa.');

            // 2. Crear usuario
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            if ($username && $password) {
                $this->userModel->createEventUser([
                    'company_id' => $companyId,
                    'event_id' => $eventId,
                    'role' => 'buyer',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'email' => $username,
                    'password' => $password,
                ]);
            }

            // 3. Guardar asistentes
            if (!empty($_POST['assistants']) && is_array($_POST['assistants'])) {
                foreach ($_POST['assistants'] as $assistant) {
                    if (!empty($assistant['first_name']) && !empty($assistant['last_name']) && !empty($assistant['email'])) {
                        $assistantData = [
                            'first_name' => trim($assistant['first_name']),
                            'last_name' => trim($assistant['last_name']),
                            'email' => trim($assistant['email']),
                            'mobile_phone' => trim($assistant['phone'] ?? ''),
                        ];
                        $this->companyModel->addAssistant($assistantData, $companyId);
                    }
                }
            }

            // 4. Guardar requerimientos
            if (!empty($_POST['requirements']) && is_array($_POST['requirements'])) {
                $this->companyModel->findById($companyId); // Para setear el rol
                foreach ($_POST['requirements'] as $eventSubcategoryId => $req) {
                    if (isset($req['selected']) && $req['selected']) {
                        $reqData = [
                            'subcategory_id' => (int)$eventSubcategoryId,
                            'budget_usd' => isset($req['budget']) && $req['budget'] !== '' ? (float)$req['budget'] : null,
                            'quantity' => isset($req['quantity']) && $req['quantity'] !== '' ? (int)$req['quantity'] : null,
                            'unit_of_measurement' => isset($req['unit']) ? trim($req['unit']) : null,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        $this->companyModel->addRequirement($reqData, $companyId);
                    }
                }
            }

            // 5. Guardar días de asistencia
            if (!empty($_POST['attendance_days']) && is_array($_POST['attendance_days'])) {
                foreach ($_POST['attendance_days'] as $date) {
                    $this->companyModel->addAttendanceDay($eventId, trim($date), $companyId);
                }
            }

            // 6. Generar matches automáticos para este comprador
            require_once(__DIR__ . '/../models/Match.php');
            $matchModel = new MatchModel($this->db);
            $matchResult = $matchModel->generateMatches($eventId, ['buyerId' => $companyId]);
            if (class_exists('Logger')) {
                Logger::info('Generación automática de matches tras registro comprador', [
                    'eventId' => $eventId,
                    'buyerId' => $companyId,
                    'matchResult' => $matchResult
                ]);
            }

            $db->commit();
            setFlashMessage('Registro enviado correctamente. Pronto nos pondremos en contacto.', 'success');
        } catch (Exception $e) {
            $db->rollback();
            setFlashMessage('Error al registrar: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: ' . BASE_URL . "/buyers_registration/$eventId");
        exit;
    }

    // Formulario registro público de proveedores
    public function suppliersRegistration($eventId) {
        if (!$this->eventModel->findById($eventId)) {
            $data = [
                'pageTitle' => 'Evento no encontrado',
                'moduleCSS' => 'public_registration',
                'moduleJS' => 'registrationcontroller'
            ];
            
            $this->render('errors/404', $data, 'admin');
            return;
        }
        
        $event = $this->eventModel;
        $categories = $this->categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $this->categoryModel->getEventSubcategories($cat['event_category_id']);
        }
        
        // Obtener días del evento
        $eventDays = [];
        if ($event->getStartDate() && $event->getEndDate()) {
            $startDate = new DateTime($event->getStartDate());
            $endDate = new DateTime($event->getEndDate());
            $interval = $startDate->diff($endDate);
            $totalDays = $interval->days + 1;
            $currentDate = clone $startDate;
            for ($i = 0; $i < $totalDays; $i++) {
                $eventDays[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }
        }
        
        $csrfToken = generateCSRFToken();
        
        $data = [
            'pageTitle' => 'Registro de Proveedores',
            'moduleCSS' => 'public_registration',
            'moduleJS' => 'registrationcontroller',
            'event' => $event,
            'eventId' => $eventId,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'eventDays' => $eventDays,
            'csrfToken' => $csrfToken
        ];
        
        // Extraer variables para la vista
        extract($data);
        
        // Incluir la vista directamente
        include VIEW_DIR . '/events/suppliers_registration.php';
    }

    // Procesar registro público de proveedores
    public function storeSuppliersRegistration($eventId) {
        // Eliminar verificación de sesión y CSRF para registro público
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (class_exists('Logger')) {
                Logger::warning('Solicitud inválida en storeSuppliersRegistration', [
                    'eventId' => $eventId,
                    'POST' => $_POST
                ]);
            }
            setFlashMessage('Solicitud inválida', 'danger');
            header('Location: ' . BASE_URL . "/suppliers_registration/$eventId");
            exit;
        }
        // LOG: Entrada y datos recibidos
        if (class_exists('Logger')) {
            Logger::debug('Entrando a storeSuppliersRegistration', [
                'eventId' => $eventId,
                'POST' => $_POST,
                'FILES' => $_FILES
            ]);
        }
        $db = $this->db;
        $db->beginTransaction();
        try {
            // 1. Guardar empresa y contacto en company
            $companyData = [
                'company_name' => trim($_POST['company_name'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'country' => trim($_POST['country'] ?? ''),
                'website' => trim($_POST['website'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'contact_first_name' => trim($_POST['contact_first_name'] ?? ''),
                'contact_last_name' => trim($_POST['contact_last_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'role' => 'supplier',
                'event_id' => $eventId,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            // Procesar palabras clave (keywords)
            if (isset($_POST['keywords'])) {
                $keywords = array_map('trim', explode(',', $_POST['keywords']));
                $keywords = array_filter($keywords, fn($k) => $k !== '');
                $companyData['keywords'] = $keywords ? json_encode(array_values($keywords), JSON_UNESCAPED_UNICODE) : null;
            }
            // Procesar certificaciones
            $certifications = $_POST['certifications'] ?? [];
            $otros = trim($_POST['certifications_otros'] ?? '');
            if ($otros !== '') {
                $certifications[] = $otros;
            }
            $companyData['certifications'] = $certifications ? json_encode(array_values($certifications), JSON_UNESCAPED_UNICODE) : null;
            // Manejo de logo
            $uploadedLogoName = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['logo']['name'];
                $fileSize = $_FILES['logo']['size'];
                $fileTmpName = $_FILES['logo']['tmp_name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
                    setFlashMessage('Error al subir el logo: Extensión de archivo no permitida.', 'danger');
                } elseif ($fileSize > MAX_UPLOAD_SIZE) {
                    setFlashMessage('Error al subir el logo: El archivo es demasiado grande.', 'danger');
                } else {
                    if (!is_dir(LOGO_DIR)) {
                        mkdir(LOGO_DIR, 0755, true);
                    }
                    $uniqueLogoName = uniqid('logo_', true) . '.' . $fileExtension;
                    $destinationPath = LOGO_DIR . '/' . $uniqueLogoName;
                    if (move_uploaded_file($fileTmpName, $destinationPath)) {
                        $uploadedLogoName = $uniqueLogoName;
                    }
                }
            }
            if ($uploadedLogoName) {
                $companyData['company_logo'] = $uploadedLogoName;
            }
            $companyId = $this->companyModel->createForEvent($companyData);
            if (!$companyId) throw new Exception('No se pudo registrar la empresa.');
            // 2. Crear usuario en event_users
            $username = trim($_POST['account_email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            if ($username && $password) {
                $this->userModel->createEventUser([
                    'company_id' => $companyId,
                    'event_id' => $eventId,
                    'role' => 'supplier',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'email' => $username,
                    'password' => $password,
                ]);
            }
            // 3. Guardar asistentes en assistants
            if (!empty($_POST['assistant_first_name']) && is_array($_POST['assistant_first_name'])) {
                $count = count($_POST['assistant_first_name']);
                for ($i = 0; $i < $count; $i++) {
                    $firstName = trim($_POST['assistant_first_name'][$i] ?? '');
                    $lastName = trim($_POST['assistant_last_name'][$i] ?? '');
                    $email = trim($_POST['assistant_email'][$i] ?? '');
                    $phone = trim($_POST['assistant_phone'][$i] ?? '');
                    if ($firstName && $lastName && $email) {
                        $assistantData = [
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $email,
                            'mobile_phone' => $phone,
                        ];
                        $this->companyModel->addAssistant($assistantData, $companyId);
                    }
                }
            }
            // 4. Guardar ofertas en supplier_offers
            if (!empty($_POST['supplier_offers']) && is_array($_POST['supplier_offers'])) {
                $this->companyModel->findById($companyId); // Para setear el rol
                foreach ($_POST['supplier_offers'] as $eventSubcategoryId) {
                    $offerData = [
                        'event_subcategory_id' => (int)$eventSubcategoryId
                    ];
                    $this->companyModel->addOffer($offerData, $companyId);
                }
            }
            // 5. Guardar días de asistencia en attendance_days
            if (!empty($_POST['attendance_days']) && is_array($_POST['attendance_days'])) {
                foreach ($_POST['attendance_days'] as $date) {
                    $this->companyModel->addAttendanceDay($eventId, trim($date), $companyId);
                }
            }
            // 6. Generar matches automáticos para este proveedor
            require_once(__DIR__ . '/../models/Match.php');
            $matchModel = new MatchModel($this->db);
            $matchResult = $matchModel->generateMatches($eventId, ['supplierId' => $companyId]);
            if (class_exists('Logger')) {
                Logger::info('Generación automática de matches tras registro proveedor', [
                    'eventId' => $eventId,
                    'supplierId' => $companyId,
                    'matchResult' => $matchResult
                ]);
            }
            $db->commit();
            if (class_exists('Logger')) {
                Logger::info('Registro de proveedor guardado correctamente', [
                    'eventId' => $eventId,
                    'POST' => $_POST
                ]);
            }
            setFlashMessage('Registro enviado correctamente. Pronto nos pondremos en contacto.', 'success');
        } catch (Exception $e) {
            $db->rollback();
            if (class_exists('Logger')) {
                Logger::error('Error al guardar registro de proveedor', [
                    'eventId' => $eventId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            setFlashMessage('Ocurrió un error al guardar el registro: ' . $e->getMessage(), 'danger');
        }
        header('Location: ' . BASE_URL . "/suppliers_registration/$eventId");
        exit;
    }
}
