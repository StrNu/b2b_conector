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

class RegistrationController {
    private $db;
    private $eventModel;
    private $companyModel;
    private $categoryModel;
    private $requirementModel;
    private $attendanceDayModel;
    private $assistantModel;
    private $userModel;

    public function __construct() {
        $this->db = Database::getInstance();
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
        if (!$this->eventModel->findById($eventId)) {
            include(VIEW_DIR . '/errors/404.php');
            return;
        }
        $event = $this->eventModel;
        $categories = $this->categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $this->categoryModel->getEventSubcategories($cat['event_category_id']);
        }
        $csrfToken = generateCSRFToken();
        include(VIEW_DIR . '/events/buyers_registration.php');
    }

    // Procesar registro público de compradores
    public function storeBuyersRegistration($eventId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Solicitud inválida', 'danger');
            header('Location: ' . BASE_URL . "/buyers_registration/$eventId");
            exit;
        }
        Logger::debug('storeBuyersRegistration: POST data', ['post' => $_POST]);
        $db = $this->db;
        $db->beginTransaction();
        try {
            Logger::debug('storeBuyersRegistration: companyData', ['companyData' => [
                'company_name' => trim($_POST['company_name'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'country' => trim($_POST['country'] ?? ''),
                'website' => trim($_POST['website'] ?? ''),
                'contact_first_name' => trim($_POST['contact_first_name'] ?? ''),
                'contact_last_name' => trim($_POST['contact_last_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'role' => 'buyer',
                'event_id' => $eventId,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]]);
            $companyId = $this->companyModel->create($companyData);
            Logger::debug('storeBuyersRegistration: companyId', ['companyId' => $companyId]);
            if (!$companyId) throw new Exception('No se pudo registrar la empresa.');
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            if ($username && $password) {
                Logger::debug('storeBuyersRegistration: userData', ['username' => $username]);
            }
            if (!empty($_POST['assistants']) && is_array($_POST['assistants'])) {
                Logger::debug('storeBuyersRegistration: assistants', ['assistants' => $_POST['assistants']]);
            }
            if (!empty($_POST['requirements']) && is_array($_POST['requirements'])) {
                Logger::debug('storeBuyersRegistration: requirements', ['requirements' => $_POST['requirements']]);
                // Save requirements for the new buyer
                $this->companyModel->findById($companyId); // Ensure model is loaded with correct company
                foreach ($_POST['requirements'] as $eventSubcategoryId) {
                    $requirementData = [
                        'event_subcategory_id' => (int)$eventSubcategoryId
                    ];
                    $this->companyModel->addRequirement($requirementData, $companyId);
                }
            }
            if (!empty($_POST['attendance_days']) && is_array($_POST['attendance_days'])) {
                Logger::debug('storeBuyersRegistration: attendance_days', ['attendance_days' => $_POST['attendance_days']]);
            }
            // 6. Generar matches automáticos para este comprador
            require_once(__DIR__ . '/../models/Match.php');
            $matchModel = new MatchModel($this->db);
            $matchResult = $matchModel->generateMatches($eventId, ['buyerId' => $companyId]);
            Logger::info('storeBuyersRegistration: matchResult', ['matchResult' => $matchResult]);
            if (class_exists('Logger')) {
                Logger::info('Generación automática de matches tras registro comprador', [
                    'eventId' => $eventId,
                    'buyerId' => $companyId,
                    'matchResult' => $matchResult
                ]);
            }
            $db->commit();
            setFlashMessage('Registro enviado correctamente. Pronto nos pondremos en contacto.', 'success');
            header('Location: ' . BASE_URL . "/buyers_registration/$eventId");
            exit;
        } catch (Exception $e) {
            $db->rollback();
            setFlashMessage('Error al registrar: ' . $e->getMessage(), 'danger');
            // Volver a mostrar el formulario con los datos ingresados
            $event = $this->eventModel;
            $categories = $this->categoryModel->getEventCategories($eventId);
            $subcategories = [];
            foreach ($categories as $cat) {
                $subcategories[$cat['event_category_id']] = $this->categoryModel->getEventSubcategories($cat['event_category_id']);
            }
            $csrfToken = generateCSRFToken();
            $form_data = $_POST;
            include(VIEW_DIR . '/events/buyers_registration.php');
            exit;
        }
    }

    // Formulario registro público de proveedores
    public function suppliersRegistration($eventId) {
        if (!$this->eventModel->findById($eventId)) {
            include(VIEW_DIR . '/errors/404.php');
            return;
        }
        $event = $this->eventModel;
        $categories = $this->categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $this->categoryModel->getEventSubcategories($cat['event_category_id']);
        }
        // Obtener días del evento igual que en buyersRegistration
        $startDate = new DateTime($event->getStartDate());
        $endDate = new DateTime($event->getEndDate());
        $interval = $startDate->diff($endDate);
        $totalDays = $interval->days + 1;
        $eventDays = [];
        $currentDate = clone $startDate;
        for ($i = 0; $i < $totalDays; $i++) {
            $eventDays[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }
        $csrfToken = generateCSRFToken();
        include(VIEW_DIR . '/events/suppliers_registration.php');
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
