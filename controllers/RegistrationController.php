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
        $db = $this->db;
        $db->beginTransaction();
        try {
            $companyData = [
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
            ];
            $companyId = $this->companyModel->create($companyData);
            if (!$companyId) throw new Exception('No se pudo registrar la empresa.');
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            if ($username && $password) {
                $userData = [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'company_id' => $companyId,
                    'event_id' => $eventId,
                    'role' => 'buyer',
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $this->userModel->create($userData);
            }
            if (!empty($_POST['assistants']) && is_array($_POST['assistants'])) {
                foreach ($_POST['assistants'] as $assistant) {
                    if (!empty($assistant['first_name']) && !empty($assistant['last_name']) && !empty($assistant['email'])) {
                        $assistantData = [
                            'first_name' => trim($assistant['first_name']),
                            'last_name' => trim($assistant['last_name']),
                            'email' => trim($assistant['email']),
                        ];
                        $this->companyModel->addAssistant($assistantData, $companyId);
                    }
                }
            }
            if (!empty($_POST['requirements']) && is_array($_POST['requirements'])) {
                $this->companyModel->findById($companyId);
                foreach ($_POST['requirements'] as $subcategoryId) {
                    $reqData = [
                        'subcategory_id' => (int)$subcategoryId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $this->companyModel->addRequirement($reqData, $companyId);
                }
            }
            if (!empty($_POST['attendance_days']) && is_array($_POST['attendance_days'])) {
                foreach ($_POST['attendance_days'] as $date) {
                    $this->companyModel->addAttendanceDay($eventId, trim($date), $companyId);
                }
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

    // Aquí puedes agregar métodos para suppliersRegistration y storeSuppliersRegistration
}
