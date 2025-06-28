<?php
// filepath: /var/www/html/b2b_conector/controllers/PublicRegistrationController.php

require_once(MODEL_DIR . '/Category.php');

class PublicRegistrationController {
    private $db;
    private $companyModel;
    private $eventModel;
    private $requirementModel;
    private $attendanceDayModel;
    private $assistantModel;
    private $userModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->companyModel = new Company($this->db);
        $this->eventModel = new Event($this->db);
        $this->requirementModel = new Requirement($this->db);
        $this->attendanceDayModel = new AttendanceDay($this->db);
        $this->assistantModel = new Assistant($this->db);
        $this->userModel = new User($this->db);
    }

    // Mostrar formulario de registro público
    public function buyersRegistration($eventId) {
        // Obtener datos del evento
        $eventFound = $this->eventModel->findById($eventId);

        if (!$eventFound) {
            include(VIEW_DIR . '/errors/404.php');
            return;
        }
        $event = $this->eventModel;
        // Cambiar: obtener categorías del evento usando Category
        $categoryModel = new Category($this->db);
        $categories = $categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $categoryModel->getEventSubcategories($cat['event_category_id']);
        }
        $csrfToken = generateCSRFToken();

        include(VIEW_DIR . '/events/buyers_registration.php'); // Descomentar esta línea
    }

    // Procesar registro público
    public function storeBuyersRegistration($eventId) {
        // Eliminamos la verificación de CSRF para registro público
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Solicitud inválida', 'danger');
            header('Location: ' . BASE_URL . "/buyers_registration/$eventId");
            exit;
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
                'keywords' => trim($_POST['keywords'] ?? ''), // <-- Agregado para guardar keywords
                'contact_first_name' => trim($_POST['contact_first_name'] ?? ''),
                'contact_last_name' => trim($_POST['contact_last_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'role' => 'buyer',
                'event_id' => $eventId,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            // Procesar keywords como JSON válido
            $keywords = trim($_POST['keywords'] ?? '');
            if ($keywords !== '') {
                $keywordsArray = array_map('trim', explode(',', $keywords));
                $companyData['keywords'] = json_encode($keywordsArray, JSON_UNESCAPED_UNICODE);
            } else {
                $companyData['keywords'] = json_encode([]);
            }

            // Procesar certificaciones como JSON válido
            $certifications = $_POST['certifications'] ?? [];
            $otros = trim($_POST['certifications_otros'] ?? '');
            if ($otros !== '') {
                $certifications[] = $otros;
            }
            $companyData['certifications'] = json_encode($certifications, JSON_UNESCAPED_UNICODE);

            // Validar y normalizar teléfono (permite lada entre paréntesis o con +, default México)
            $phone = trim($_POST['phone'] ?? '');
            if ($phone === '') {
                $phone = '+52'; // Default México
            } elseif (!preg_match('/^(\+\d{1,3}|\(\d{2,4}\))? ?[\d\s-]{6,}$/', $phone)) {
                // Permite +lada, (lada), espacios y guiones
                setFlashMessage('El teléfono no es válido. Ejemplo: +52 222 123 4567 o (52) 222 123 4567', 'danger');
                header('Location: ' . BASE_URL . "/buyers_registration/$eventId");
                exit;
            }
            $companyData['phone'] = $phone;

            // Manejo de subida de logo
            $uploadedLogoName = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                // Validaciones básicas (puedes usar funciones de helpers.php si existen)
                $fileName = $_FILES['logo']['name'];
                $fileSize = $_FILES['logo']['size'];
                $fileTmpName = $_FILES['logo']['tmp_name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
                    Logger::warning('Intento de subida de logo con extensión no permitida.', ['filename' => $fileName, 'extension' => $fileExtension]);
                    setFlashMessage('Error al subir el logo: Extensión de archivo no permitida.', 'danger');
                } elseif ($fileSize > MAX_UPLOAD_SIZE) {
                    Logger::warning('Intento de subida de logo que excede el tamaño máximo.', ['filename' => $fileName, 'size' => $fileSize]);
                    setFlashMessage('Error al subir el logo: El archivo es demasiado grande (máx. ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB).', 'danger');
                } else {
                    // Crear directorio si no existe (ya está en el modelo, pero una verificación aquí no hace daño)
                    if (!is_dir(LOGO_DIR)) {
                        mkdir(LOGO_DIR, 0755, true);
                    }
                    
                    $uniqueLogoName = uniqid('logo_', true) . '.' . $fileExtension;
                    $destinationPath = LOGO_DIR . '/' . $uniqueLogoName;

                    if (move_uploaded_file($fileTmpName, $destinationPath)) {
                        $uploadedLogoName = $uniqueLogoName;
                        Logger::info('Logo subido exitosamente.', ['filename' => $uploadedLogoName, 'path' => $destinationPath]);
                    } else {
                        Logger::error('Error al mover el archivo de logo subido.', ['tmp_name' => $fileTmpName, 'destination' => $destinationPath]);
                        setFlashMessage('Error interno al guardar el logo. Intente de nuevo.', 'danger');
                    }
                }
            }

            if ($uploadedLogoName) {
                $companyData['company_logo'] = $uploadedLogoName;
            }

            $companyId = $this->companyModel->createForEvent($companyData);
            if (!$companyId) throw new Exception('No se pudo registrar la empresa.');

            // 2. Crear usuario en event_users
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

            // 3. Guardar asistentes en assistants
            if (!empty($_POST['assistants']) && is_array($_POST['assistants'])) {
                foreach ($_POST['assistants'] as $assistant) {
                    if (!empty($assistant['first_name']) && !empty($assistant['last_name']) && !empty($assistant['email'])) {
                        $assistantPhone = trim($assistant['phone'] ?? '');
                        if ($assistantPhone === '') {
                            $assistantPhone = '+52';
                        } elseif (!preg_match('/^(\+\d{1,3}|\(\d{2,4}\))? ?[\d\s-]{6,}$/', $assistantPhone)) {
                            setFlashMessage('El teléfono del asistente no es válido. Ejemplo: +52 222 123 4567 o (52) 222 123 4567', 'danger');
                            header('Location: ' . BASE_URL . "/buyers_registration/$eventId");
                            exit;
                        }
                        $assistantData = [
                            'first_name' => trim($assistant['first_name']),
                            'last_name' => trim($assistant['last_name']),
                            'email' => trim($assistant['email']),
                            'mobile_phone' => $assistantPhone, // CORRECTO: usar mobile_phone
                        ];
                        $this->companyModel->addAssistant($assistantData, $companyId);
                    }
                }
            }

            // 4. Guardar productos/servicios en requirements
            if (!empty($_POST['requirements']) && is_array($_POST['requirements'])) {
                $this->companyModel->findById($companyId); // Para setear el rol
                foreach ($_POST['requirements'] as $eventSubcategoryId => $req) {
                    if (isset($req['selected']) && $req['selected']) {
                        $reqData = [
                            'subcategory_id' => (int)$eventSubcategoryId, // event_subcategory_id
                            'budget_usd' => isset($req['budget']) && $req['budget'] !== '' ? (float)$req['budget'] : null,
                            'quantity' => isset($req['quantity']) && $req['quantity'] !== '' ? (int)$req['quantity'] : null,
                            'unit_of_measurement' => isset($req['unit']) ? trim($req['unit']) : null,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        $this->companyModel->addRequirement($reqData, $companyId);
                    }
                }
            }

            // 5. Guardar días de asistencia en attendance_days
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
}
