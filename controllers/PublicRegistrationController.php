<?php
// filepath: /var/www/html/b2b_conector/controllers/PublicRegistrationController.php

require_once(MODEL_DIR . '/Category.php');

require_once 'PublicBaseController.php';

class PublicRegistrationController extends PublicBaseController {
        private $companyModel;
    private $eventModel;
    private $requirementModel;
    private $attendanceDayModel;
    private $assistantModel;
    private $userModel;

    public function __construct() {
        
        parent::__construct();
        
        // Cargar configuración de Material Design para páginas públicas
        if (file_exists(CONFIG_DIR . '/material-config.php')) {
            require_once CONFIG_DIR . '/material-config.php';
        }
        
        // La conexión ya se inicializa en PublicBaseController
        // $this->db ya está disponible
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
            $data = [
                'pageTitle' => 'Evento no encontrado',
                'moduleCSS' => 'publicregistrationcontroller',
                'moduleJS' => 'publicregistrationcontroller',
                'eventFound' => $eventFound
            ];
            
            $this->render('errors/404', $data, 'admin');
            return;
        }
        
        // El evento ya está cargado en $this->eventModel después del findById
        $event = $this->eventModel;
        
        // Obtener categorías del evento usando Category
        $categoryModel = new Category($this->db);
        $categories = $categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $categoryModel->getEventSubcategories($cat['event_category_id']);
        }
        
        // Generar token CSRF
        $csrfToken = generateCSRFToken();

        $data = [
            'pageTitle' => 'Registro de Compradores',
            'moduleCSS' => 'publicregistrationcontroller',
            'moduleJS' => 'publicregistrationcontroller',
            'event' => $event,
            'eventId' => $eventId,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'csrfToken' => $csrfToken
        ];
        
        $this->render('events/buyers_registration', $data, 'public');
    }

    // Mostrar formulario de registro público para proveedores
    public function suppliersRegistration($eventId) {
        // Obtener datos del evento
        $eventFound = $this->eventModel->findById($eventId);

        if (!$eventFound) {
            $data = [
                'pageTitle' => 'Evento no encontrado',
                'moduleCSS' => 'publicregistrationcontroller',
                'moduleJS' => 'publicregistrationcontroller',
                'eventFound' => $eventFound
            ];
            
            $this->render('errors/404', $data, 'event');
            return;
        }
        
        // El evento ya está cargado en $this->eventModel después del findById
        $event = $this->eventModel;
        
        // Obtener categorías del evento usando Category
        $categoryModel = new Category($this->db);
        $categories = $categoryModel->getEventCategories($eventId);
        $subcategories = [];
        foreach ($categories as $cat) {
            $subcategories[$cat['event_category_id']] = $categoryModel->getEventSubcategories($cat['event_category_id']);
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
        
        // Generar token CSRF
        $csrfToken = generateCSRFToken();

        $data = [
            'pageTitle' => 'Registro de Proveedores',
            'moduleCSS' => 'public_registration',
            'moduleJS' => 'publicregistrationcontroller',
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

            // 6. Generar matches automáticamente después del registro de buyer
            $this->generateMatchesForNewBuyer($companyId, $eventId);

            $db->commit();
            setFlashMessage('Registro enviado correctamente. Pronto nos pondremos en contacto.', 'success');
        } catch (Exception $e) {
            $db->rollback();
            setFlashMessage('Error al registrar: ' . $e->getMessage(), 'danger');
        }
        header('Location: ' . BASE_URL . "/buyers_registration/$eventId");
        exit;
    }

    // Procesar registro público de proveedores
    public function storeSuppliersRegistration($eventId) {
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('Solicitud inválida', 'danger');
            header('Location: ' . BASE_URL . "/suppliers_registration/$eventId");
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
                'role' => 'supplier',
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

            // 3. Guardar asistentes
            if (!empty($_POST['assistant_first_name']) && is_array($_POST['assistant_first_name'])) {
                foreach ($_POST['assistant_first_name'] as $index => $firstName) {
                    if (!empty($firstName) && !empty($_POST['assistant_last_name'][$index]) && !empty($_POST['assistant_email'][$index])) {
                        $assistantData = [
                            'first_name' => trim($firstName),
                            'last_name' => trim($_POST['assistant_last_name'][$index]),
                            'email' => trim($_POST['assistant_email'][$index]),
                            'mobile_phone' => trim($_POST['assistant_phone'][$index] ?? ''),
                        ];
                        $this->companyModel->addAssistant($assistantData, $companyId);
                    }
                }
            }

            // 4. Guardar ofertas de proveedor
            if (!empty($_POST['supplier_offers']) && is_array($_POST['supplier_offers'])) {
                foreach ($_POST['supplier_offers'] as $subcategoryId) {
                    $offerData = [
                        'subcategory_id' => (int)$subcategoryId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $this->companyModel->addOffer($offerData, $companyId);
                }
            }

            // 5. Guardar días de asistencia
            if (!empty($_POST['attendance_days']) && is_array($_POST['attendance_days'])) {
                foreach ($_POST['attendance_days'] as $date) {
                    $this->companyModel->addAttendanceDay($eventId, trim($date), $companyId);
                }
            }

            // 6. Generar matches automáticamente después del registro de supplier
            $this->generateMatchesForNewSupplier($companyId, $eventId);

            $db->commit();
            setFlashMessage('Registro enviado correctamente. Pronto nos pondremos en contacto.', 'success');
        } catch (Exception $e) {
            $db->rollback();
            setFlashMessage('Error al registrar: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: ' . BASE_URL . "/suppliers_registration/$eventId");
        exit;
    }

    /**
     * Generar matches automáticamente para un nuevo buyer registrado
     * 
     * @param int $buyerId ID del buyer recién registrado
     * @param int $eventId ID del evento
     * @return void
     */
    private function generateMatchesForNewBuyer($buyerId, $eventId) {
        try {
            Logger::info("Iniciando generación de matches para nuevo buyer", [
                'buyer_id' => $buyerId,
                'event_id' => $eventId
            ]);

            // Obtener requirements del buyer
            $buyerRequirements = $this->db->resultSet(
                'SELECT r.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                 FROM requirements r
                 JOIN event_subcategories s ON r.event_subcategory_id = s.event_subcategory_id
                 JOIN event_categories c ON s.event_category_id = c.event_category_id
                 WHERE r.buyer_id = :buyer_id',
                ['buyer_id' => $buyerId]
            );

            if (empty($buyerRequirements)) {
                Logger::info("No se encontraron requirements para el buyer", ['buyer_id' => $buyerId]);
                return;
            }

            // Obtener fechas de asistencia del buyer
            $buyerDates = $this->db->resultSet(
                'SELECT DISTINCT attendance_date FROM attendance_days 
                 WHERE company_id = :company_id AND event_id = :event_id',
                ['company_id' => $buyerId, 'event_id' => $eventId]
            );

            $buyerDatesArray = array_column($buyerDates, 'attendance_date');

            if (empty($buyerDatesArray)) {
                Logger::info("No se encontraron fechas de asistencia para el buyer", ['buyer_id' => $buyerId]);
                return;
            }

            // Obtener todos los suppliers del evento
            $suppliers = $this->db->resultSet(
                'SELECT * FROM company WHERE event_id = :event_id AND role = "supplier"',
                ['event_id' => $eventId]
            );

            foreach ($suppliers as $supplier) {
                $this->createMatchIfValid($buyerId, $supplier['company_id'], $eventId, $buyerRequirements, $buyerDatesArray, 'buyer');
            }

        } catch (Exception $e) {
            Logger::error("Error generando matches para nuevo buyer", [
                'buyer_id' => $buyerId,
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generar matches automáticamente para un nuevo supplier registrado
     * 
     * @param int $supplierId ID del supplier recién registrado
     * @param int $eventId ID del evento
     * @return void
     */
    private function generateMatchesForNewSupplier($supplierId, $eventId) {
        try {
            Logger::info("Iniciando generación de matches para nuevo supplier", [
                'supplier_id' => $supplierId,
                'event_id' => $eventId
            ]);

            // Obtener ofertas del supplier
            $supplierOffers = $this->db->resultSet(
                'SELECT so.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                 FROM supplier_offers so
                 JOIN event_subcategories s ON so.event_subcategory_id = s.event_subcategory_id
                 JOIN event_categories c ON s.event_category_id = c.event_category_id
                 WHERE so.supplier_id = :supplier_id',
                ['supplier_id' => $supplierId]
            );

            if (empty($supplierOffers)) {
                Logger::info("No se encontraron ofertas para el supplier", ['supplier_id' => $supplierId]);
                return;
            }

            // Obtener fechas de asistencia del supplier
            $supplierDates = $this->db->resultSet(
                'SELECT DISTINCT attendance_date FROM attendance_days 
                 WHERE company_id = :company_id AND event_id = :event_id',
                ['company_id' => $supplierId, 'event_id' => $eventId]
            );

            $supplierDatesArray = array_column($supplierDates, 'attendance_date');

            if (empty($supplierDatesArray)) {
                Logger::info("No se encontraron fechas de asistencia para el supplier", ['supplier_id' => $supplierId]);
                return;
            }

            // Obtener todos los buyers del evento
            $buyers = $this->db->resultSet(
                'SELECT * FROM company WHERE event_id = :event_id AND role = "buyer"',
                ['event_id' => $eventId]
            );

            foreach ($buyers as $buyer) {
                // Obtener requirements del buyer para este match
                $buyerRequirements = $this->db->resultSet(
                    'SELECT r.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                     FROM requirements r
                     JOIN event_subcategories s ON r.event_subcategory_id = s.event_subcategory_id
                     JOIN event_categories c ON s.event_category_id = c.event_category_id
                     WHERE r.buyer_id = :buyer_id',
                    ['buyer_id' => $buyer['company_id']]
                );

                // Obtener fechas del buyer
                $buyerDates = $this->db->resultSet(
                    'SELECT DISTINCT attendance_date FROM attendance_days 
                     WHERE company_id = :company_id AND event_id = :event_id',
                    ['company_id' => $buyer['company_id'], 'event_id' => $eventId]
                );

                $buyerDatesArray = array_column($buyerDates, 'attendance_date');

                if (!empty($buyerRequirements) && !empty($buyerDatesArray)) {
                    $this->createMatchIfValid($buyer['company_id'], $supplierId, $eventId, $buyerRequirements, $buyerDatesArray, 'supplier', $supplierOffers);
                }
            }

        } catch (Exception $e) {
            Logger::error("Error generando matches para nuevo supplier", [
                'supplier_id' => $supplierId,
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crear match si es válido (con subcategorías y fechas coincidentes)
     * 
     * @param int $buyerId ID del buyer
     * @param int $supplierId ID del supplier
     * @param int $eventId ID del evento
     * @param array $buyerRequirements Requirements del buyer
     * @param array $buyerDatesArray Fechas de asistencia del buyer
     * @param string $triggerRole Rol que disparó la creación ('buyer' o 'supplier')
     * @param array $supplierOffers Ofertas del supplier (opcional, si no se proporciona se obtienen)
     * @return void
     */
    private function createMatchIfValid($buyerId, $supplierId, $eventId, $buyerRequirements, $buyerDatesArray, $triggerRole, $supplierOffers = null) {
        try {
            // Verificar si ya existe el match
            $existingMatch = $this->db->single(
                'SELECT COUNT(*) as count FROM matches 
                 WHERE buyer_id = :buyer_id AND supplier_id = :supplier_id AND event_id = :event_id',
                ['buyer_id' => $buyerId, 'supplier_id' => $supplierId, 'event_id' => $eventId]
            );

            if ($existingMatch['count'] > 0) {
                return; // Match ya existe
            }

            // Obtener ofertas del supplier si no se proporcionaron
            if ($supplierOffers === null) {
                $supplierOffers = $this->db->resultSet(
                    'SELECT so.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                     FROM supplier_offers so
                     JOIN event_subcategories s ON so.event_subcategory_id = s.event_subcategory_id
                     JOIN event_categories c ON s.event_category_id = c.event_category_id
                     WHERE so.supplier_id = :supplier_id',
                    ['supplier_id' => $supplierId]
                );
            }

            // Obtener fechas de asistencia del supplier
            $supplierDates = $this->db->resultSet(
                'SELECT DISTINCT attendance_date FROM attendance_days 
                 WHERE company_id = :company_id AND event_id = :event_id',
                ['company_id' => $supplierId, 'event_id' => $eventId]
            );

            $supplierDatesArray = array_column($supplierDates, 'attendance_date');

            // 1. Verificar coincidencia en subcategorías
            $matchedCategories = [];
            foreach ($buyerRequirements as $requirement) {
                foreach ($supplierOffers as $offer) {
                    if ($requirement['event_subcategory_id'] == $offer['event_subcategory_id']) {
                        $matchedCategories[] = [
                            'category_id' => $requirement['category_id'],
                            'category_name' => $requirement['category_name'],
                            'subcategory_id' => $requirement['event_subcategory_id'],
                            'subcategory_name' => $requirement['subcategory_name']
                        ];
                        break;
                    }
                }
            }

            // 2. Verificar coincidencia en fechas de asistencia
            $coincidenceDates = array_intersect($buyerDatesArray, $supplierDatesArray);

            // 3. NUEVA CONDICIÓN: Debe haber coincidencia tanto en subcategorías como en fechas
            if (empty($matchedCategories) || empty($coincidenceDates)) {
                Logger::debug("Match no creado por falta de coincidencias", [
                    'buyer_id' => $buyerId,
                    'supplier_id' => $supplierId,
                    'matched_categories_count' => count($matchedCategories),
                    'coincidence_dates_count' => count($coincidenceDates)
                ]);
                return;
            }

            // 4. Calcular match strength
            $totalBuyerSubcats = count($buyerRequirements);
            $matchStrength = $totalBuyerSubcats > 0 ? round((count($matchedCategories) / $totalBuyerSubcats) * 100) : 0;

            // 5. Obtener datos de empresas para keywords y descriptions
            $buyer = $this->db->single('SELECT * FROM company WHERE company_id = :id', ['id' => $buyerId]);
            $supplier = $this->db->single('SELECT * FROM company WHERE company_id = :id', ['id' => $supplierId]);

            // 6. Preparar datos del match con campos requeridos
            $coincidenceDatesString = implode(',', $coincidenceDates);
            $dateDatesCount = count($coincidenceDates); // Campo date_match

            $matchData = [
                'buyer_id' => $buyerId,
                'supplier_id' => $supplierId,
                'event_id' => $eventId,
                'match_strength' => $matchStrength,
                'status' => 'matched',
                'created_at' => date('Y-m-d H:i:s'),
                'matched_categories' => json_encode($matchedCategories),
                'buyer_subcategories' => json_encode(array_column($buyerRequirements, 'event_subcategory_id')),
                'supplier_subcategories' => json_encode(array_column($supplierOffers, 'event_subcategory_id')),
                'buyer_dates' => implode(',', $buyerDatesArray),
                'supplier_dates' => implode(',', $supplierDatesArray),
                'buyer_keywords' => $buyer['keywords'] ?? null,
                'supplier_keywords' => $supplier['keywords'] ?? null,
                'buyer_description' => $buyer['description'] ?? null,
                'supplier_description' => $supplier['description'] ?? null,
                'reason' => 'subcategoria_y_fecha_' . $triggerRole . '_registration',
                'keywords_match' => null, // Se puede calcular después si es necesario
                'coincidence_of_dates' => $coincidenceDatesString, // NUNCA NULL
                'date_match' => $dateDatesCount, // Campo date_match con total de días coincidentes
                'programed' => 0 // Inicialmente no programado
            ];

            // 7. Crear el match
            $fields = array_keys($matchData);
            $placeholders = array_map(function($field) { return ":$field"; }, $fields);
            
            $query = "INSERT INTO matches (" . implode(', ', $fields) . ") 
                      VALUES (" . implode(', ', $placeholders) . ")";

            if ($this->db->query($query, $matchData)) {
                $matchId = $this->db->lastInsertId();
                Logger::info("Match creado exitosamente en registro público", [
                    'match_id' => $matchId,
                    'buyer_id' => $buyerId,
                    'supplier_id' => $supplierId,
                    'event_id' => $eventId,
                    'match_strength' => $matchStrength,
                    'coincidence_dates' => $coincidenceDatesString,
                    'date_match_count' => $dateDatesCount,
                    'trigger_role' => $triggerRole
                ]);
            } else {
                Logger::error("Error creando match en registro público", [
                    'buyer_id' => $buyerId,
                    'supplier_id' => $supplierId,
                    'event_id' => $eventId,
                    'query' => $query,
                    'data' => $matchData
                ]);
            }

        } catch (Exception $e) {
            Logger::error("Error en createMatchIfValid", [
                'buyer_id' => $buyerId,
                'supplier_id' => $supplierId,
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
