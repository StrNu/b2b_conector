<?php
/**
 * Controlador de Autenticación
 * 
 * Este controlador maneja todas las operaciones relacionadas con la autenticación
 * incluyendo inicio de sesión, registro, cierre de sesión y recuperación de contraseña.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class AuthController {
    private $db;
    private $userModel;
    private $validator;
    
    /**
     * Constructor
     * 
     * Inicializa los modelos necesarios y otras dependencias
     */
    public function __construct() {
        // Inicializar conexión a la base de datos
        $this->db = Database::getInstance();
        
        // Inicializar modelo de usuario
        $this->userModel = new User($this->db);
        
        // Inicializar validador
        $this->validator = new Validator();
        Logger::debug('AuthController inicializado');
    }
    
    /**
     * Mostrar formulario de inicio de sesión
     * 
     * @return void
     */
    public function login() {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect(BASE_URL . '/dashboard');
            exit;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        Logger::debug('Token CSRF generado para formulario de login: ' . substr($csrfToken, 0, 8) . '...');
        
        // Cargar vista del formulario de login
        include(VIEW_DIR . '/auth/login.php');
    }
    
    /**
     * Procesar el inicio de sesión
     * 
     * @return void
     */
    public function authenticate() {
        Logger::info('Iniciando proceso de autenticación');
        Logger::debug('Datos recibidos en POST: ' . json_encode($_POST));
        
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            Logger::debug('Usuario ya autenticado, redirigiendo a dashboard');
            redirect(BASE_URL . '/dashboard');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::warning('Método de solicitud no permitido para autenticación: ' . $_SERVER['REQUEST_METHOD']);
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            Logger::error('Token CSRF inválido en el inicio de sesión');
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('login', 'El usuario o email es obligatorio')
                       ->required('password', 'La contraseña es obligatoria');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            Logger::warning('Errores de validación en login: ' . json_encode($this->validator->getErrors()));
            $_SESSION['form_data'] = ['login' => $_POST['login'] ?? '']; // No guardar la contraseña
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Obtener y sanitizar datos
        $login = sanitize($_POST['login']);
        $password = $_POST['password']; // No sanitizar la contraseña para no alterar su valor
        
        // Registrar el intento de autenticación
        Logger::debug('Intentando autenticar usuario: ' . $login);
        
        // Intentar autenticar al usuario
        $authenticated = $this->userModel->authenticate($login, $password);
        
        // Registrar el resultado de la autenticación
        Logger::info('Resultado de autenticación: ' . ($authenticated ? 'éxito' : 'fallido'));

        
        if ($authenticated) {
            Logger::debug('Autenticación exitosa, guardando datos de sesión');
            // Regenerar ID de sesión por seguridad
             // Verificar estado de sesión antes de regenerar
        Logger::debug('Estado de sesión antes de regenerar: ' . (session_status() === PHP_SESSION_ACTIVE ? 'activa' : 'inactiva'));
        Logger::debug('ID de sesión antes: ' . session_id());
        
            Security::regenerateSession();
        
            Logger::debug('ID de sesión después: ' . session_id());
            // Guardar información del usuario en la sesión
            $_SESSION['user_id'] = $this->userModel->getId();
            $_SESSION['username'] = $this->userModel->getUsername();
            $_SESSION['name'] = $this->userModel->getName();
            $_SESSION['role'] = $this->userModel->getRole();
            
            // Registrar datos de sesión establecidos
          logger::debug('Datos de sesión guardados: ' . json_encode([
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'name' => $_SESSION['name'],
                'role' => $_SESSION['role']
            ]));
            
            // Redirigir al dashboard o página apropiada según el rol
            Logger::info('Redirigiendo a: ' . BASE_URL . '/dashboard');
            setFlashMessage('Inicio de sesión exitoso. Bienvenido(a) ' . $this->userModel->getName(), 'success');
            redirect(BASE_URL . '/dashboard');
        } else {
            // Autenticación fallida
            Logger::warning('Autenticación fallida para usuario: ' . $login);
            setFlashMessage('Credenciales inválidas. Por favor, intente nuevamente', 'danger');
            $_SESSION['form_data'] = ['login' => $login]; // No guardar la contraseña
            redirect(BASE_URL . '/auth/login');
        }
    }
    
    /**
     * Mostrar formulario de registro
     * 
     * @return void
     */
    public function register() {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect(BASE_URL . '/dashboard');
            exit;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario de registro
        include(VIEW_DIR . '/auth/register.php');
    }
    
    /**
     * Procesar el registro de un nuevo usuario
     * 
     * @return void
     */
    public function store() {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect(BASE_URL . '/dashboard');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/register');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/register');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('username', 'El nombre de usuario es obligatorio')
                       ->required('email', 'El email es obligatorio')
                       ->required('password', 'La contraseña es obligatoria')
                       ->required('password_confirm', 'La confirmación de contraseña es obligatoria')
                       ->required('name', 'El nombre completo es obligatorio')
                       ->email('email', 'El formato de email no es válido')
                       ->minLength('password', PASSWORD_MIN_LENGTH, 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres')
                       ->matches('password', 'password_confirm', 'Las contraseñas no coinciden');
        
        // Verificar fortaleza de la contraseña
        if (!Security::isStrongPassword($_POST['password'])) {
            $this->validator->addError('password', 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número');
        }
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? ''
            ]; // No guardar las contraseñas
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/auth/register');
            exit;
        }
        
        // Verificar si el usuario o email ya existen
        if ($this->userModel->exists($_POST['email'], $_POST['username'])) {
            setFlashMessage('El nombre de usuario o email ya están registrados', 'danger');
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? ''
            ];
            redirect(BASE_URL . '/auth/register');
            exit;
        }
        
        // Preparar datos para el modelo
        $userData = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'password' => $_POST['password'], // Se hashea en el modelo
            'name' => sanitize($_POST['name']),
            'role' => 'user', // Rol por defecto
            'is_active' => 1
        ];
        
        // Crear el usuario
        $userId = $this->userModel->create($userData);
        
        if ($userId) {
            setFlashMessage('Registro exitoso. Ahora puede iniciar sesión', 'success');
            redirect(BASE_URL . '/auth/login');
        } else {
            setFlashMessage('Error al registrar el usuario. Por favor, intente nuevamente', 'danger');
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? ''
            ];
            redirect(BASE_URL . '/auth/register');
        }
    }
    
    /**
     * Mostrar formulario de login para usuarios de eventos
     * 
     * @param int $eventId ID del evento (opcional)
     * @return void
     */
    public function eventLogin($eventId = null) {
        Logger::debug("AuthController::eventLogin iniciado", ['eventId' => $eventId]);
        
        // Si el usuario ya está autenticado como event user, redirigir
        Logger::debug("Verificando autenticación de evento...");
        if (isEventUserAuthenticated()) {
            Logger::debug("Usuario evento ya autenticado, redirigiendo a event-dashboard");
            redirect(BASE_URL . '/event-dashboard');
            exit;
        }
        Logger::debug("Usuario evento no autenticado, continuando...");
        
        // Token CSRF para el formulario
        Logger::debug("Generando token CSRF...");
        $csrfToken = generateCSRFToken();
        Logger::debug("Token CSRF generado exitosamente");
        
        // Si se proporciona un ID de evento, obtener información del evento
        $eventName = null;
        Logger::debug("Verificando ID de evento...", ['eventId' => $eventId]);
        if ($eventId) {
            Logger::debug("ID de evento proporcionado, cargando información...");
            $eventModel = new Event($this->db);
            if ($eventModel->findById($eventId)) {
                $eventName = $eventModel->getEventName();
                Logger::debug("Información del evento cargada", ['eventName' => $eventName]);
            } else {
                Logger::debug("Evento no encontrado con ID: " . $eventId);
            }
        } else {
            Logger::debug("No se proporcionó ID de evento");
        }
        
        // Cargar vista del formulario de login de eventos
        Logger::debug("Cargando vista de event_login");
        include(VIEW_DIR . '/auth/event_login.php');
    }
    
    /**
     * Procesar el inicio de sesión para usuarios de eventos
     * 
     * @return void
     */
    public function eventAuthenticate() {
        Logger::info('Iniciando proceso de autenticación de evento');
        
        // Si el usuario ya está autenticado como event user, redirigir
        if (isEventUserAuthenticated()) {
            redirect(BASE_URL . '/event-dashboard');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('email', 'El email es obligatorio')
                       ->required('password', 'La contraseña es obligatoria')
                       ->required('user_type', 'El tipo de usuario es obligatorio')
                       ->email('email', 'El formato del email no es válido');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = ['email' => $_POST['email'] ?? '', 'user_type' => $_POST['user_type'] ?? ''];
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            redirect(BASE_URL . '/auth/event-login');
            exit;
        }
        
        // Obtener y sanitizar datos
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $userType = sanitize($_POST['user_type']);
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
        
        Logger::debug("Intentando autenticar usuario de evento: $email, tipo: $userType");
        
        // Intentar autenticar según el tipo de usuario
        $authenticated = false;
        $userData = null;
        
        if ($userType === 'event_admin') {
            // Autenticar administrador de evento
            $userData = $this->authenticateEventAdmin($email, $password, $eventId);
        } elseif ($userType === 'assistant') {
            // Autenticar asistente de evento
            $userData = $this->authenticateEventAssistant($email, $password, $eventId);
        }
        
        if ($userData) {
            Logger::info("Autenticación de evento exitosa para: $email");
            
            // Regenerar ID de sesión por seguridad
            Security::regenerateSession();
            
            // Guardar información del usuario en la sesión
            $_SESSION['event_user_id'] = $userData['id'];
            $_SESSION['event_user_email'] = $userData['email'];
            $_SESSION['event_user_type'] = $userData['type'];
            $_SESSION['event_id'] = $userData['event_id'];
            $_SESSION['company_id'] = $userData['company_id'] ?? null;
            $_SESSION['event_name'] = $userData['event_name'] ?? '';
            
            Logger::debug('Datos de sesión de evento guardados: ' . json_encode([
                'event_user_id' => $_SESSION['event_user_id'],
                'event_user_email' => $_SESSION['event_user_email'],
                'event_user_type' => $_SESSION['event_user_type'],
                'event_id' => $_SESSION['event_id']
            ]));
            
            // Redirigir según el tipo de usuario
            setFlashMessage('Acceso exitoso. Bienvenido(a) al evento', 'success');
            
            if ($userData['type'] === 'event_admin') {
                // Event admin va a la vista de su evento
                $eventId = $_SESSION['event_id'];
                redirect(BASE_URL . '/events/view/' . $eventId);
            } else {
                // Asistentes van al dashboard de eventos
                redirect(BASE_URL . '/event-dashboard');
            }
        } else {
            // Autenticación fallida
            Logger::warning("Autenticación de evento fallida para: $email");
            setFlashMessage('Credenciales inválidas o no tiene acceso a este evento', 'danger');
            $_SESSION['form_data'] = ['email' => $email, 'user_type' => $userType];
            redirect(BASE_URL . '/auth/event-login');
        }
    }
    
    /**
     * Autenticar administrador de evento
     * 
     * @param string $email Email del administrador
     * @param string $password Contraseña
     * @param int|null $eventId ID del evento (opcional)
     * @return array|false Datos del usuario o false si falla
     */
    private function authenticateEventAdmin($email, $password, $eventId = null) {
        $query = "SELECT eu.*, e.event_name 
                  FROM event_users eu 
                  INNER JOIN events e ON eu.event_id = e.event_id 
                  WHERE eu.email = :email 
                  AND eu.role = 'event_admin' 
                  AND eu.is_active = 1";
        
        $params = [':email' => $email];
        
        if ($eventId) {
            $query .= " AND eu.event_id = :event_id";
            $params[':event_id'] = $eventId;
        }
        
        $user = $this->db->single($query, $params);
        
        if ($user && password_verify($password, $user['password'])) {
            return [
                'id' => $user['id'],
                'email' => $user['email'],
                'type' => 'event_admin',
                'event_id' => $user['event_id'],
                'company_id' => $user['company_id'],
                'event_name' => $user['event_name']
            ];
        }
        
        return false;
    }
    
    /**
     * Autenticar asistente de evento
     * 
     * @param string $email Email del asistente
     * @param string $password Contraseña
     * @param int|null $eventId ID del evento (opcional)
     * @return array|false Datos del usuario o false si falla
     */
    private function authenticateEventAssistant($email, $password, $eventId = null) {
        $query = "SELECT eu.*, e.event_name, c.company_name 
                  FROM event_users eu 
                  INNER JOIN events e ON eu.event_id = e.event_id 
                  LEFT JOIN company c ON eu.company_id = c.company_id
                  WHERE eu.email = :email 
                  AND eu.role IN ('buyer', 'supplier') 
                  AND eu.is_active = 1";
        
        $params = [':email' => $email];
        
        if ($eventId) {
            $query .= " AND eu.event_id = :event_id";
            $params[':event_id'] = $eventId;
        }
        
        $user = $this->db->single($query, $params);
        
        if ($user && password_verify($password, $user['password'])) {
            return [
                'id' => $user['id'],
                'email' => $user['email'],
                'type' => 'assistant',
                'role' => $user['role'], // buyer o supplier
                'event_id' => $user['event_id'],
                'company_id' => $user['company_id'],
                'event_name' => $user['event_name'],
                'company_name' => $user['company_name']
            ];
        }
        
        return false;
    }
    
    /**
     * Cerrar sesión
     * 
     * @return void
     */
    public function logout() {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Destruir la sesión
        Security::destroySession();
        
        // Redirigir al login
        setFlashMessage('Ha cerrado sesión exitosamente', 'success');
        redirect(BASE_URL . '/auth/login');
    }
    
    /**
     * Mostrar formulario de recuperación de contraseña
     * 
     * @return void
     */
    public function forgot() {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect(BASE_URL . '/dashboard');
            exit;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario de recuperación
        include(VIEW_DIR . '/auth/forgot.php');
    }
    
    /**
     * Procesar solicitud de recuperación de contraseña
     * 
     * @return void
     */
    public function recover() {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (isAuthenticated()) {
            redirect(BASE_URL . '/dashboard');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/forgot');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/forgot');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('email', 'El email es obligatorio')
                       ->email('email', 'El formato de email no es válido');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = ['email' => $_POST['email'] ?? ''];
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/auth/forgot');
            exit;
        }
        
        $email = sanitize($_POST['email']);
        
        // Verificar si el email existe en la base de datos
        $exists = $this->userModel->findByEmail($email);
        
        // Por seguridad, siempre mostrar el mismo mensaje aunque el email no exista
        // Esto evita que un atacante determine qué emails están registrados
        setFlashMessage('Si el email existe en nuestra base de datos, recibirá instrucciones para restablecer su contraseña', 'info');
        
        if ($exists) {
            // Aquí implementaríamos la lógica de envío de email con token de recuperación
            // En una aplicación real, generaríamos un token único, lo guardaríamos en la BD
            // y enviaríamos un email con un enlace para restablecer la contraseña
            
            // Por simplicidad, mostramos un mensaje informativo
            // En una implementación real, no deberíamos mostrar este mensaje
            setFlashMessage('Funcionalidad de envío de email pendiente de implementar', 'warning');
        }
        
        redirect(BASE_URL . '/auth/login');
    }
    
    /**
     * Mostrar formulario para cambiar la contraseña (usuario autenticado)
     * 
     * @return void
     */
    public function changePassword() {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario de cambio de contraseña
        include(VIEW_DIR . '/auth/change_password.php');
    }
    
    /**
     * Procesar cambio de contraseña (usuario autenticado)
     * 
     * @return void
     */
    public function updatePassword() {
        // Verificar si el usuario está autenticado
        if (!isAuthenticated()) {
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/change-password');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/change-password');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('current_password', 'La contraseña actual es obligatoria')
                       ->required('new_password', 'La nueva contraseña es obligatoria')
                       ->required('confirm_password', 'La confirmación de contraseña es obligatoria')
                       ->minLength('new_password', PASSWORD_MIN_LENGTH, 'La nueva contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres')
                       ->matches('new_password', 'confirm_password', 'Las contraseñas no coinciden');
        
        // Verificar fortaleza de la nueva contraseña
        if (!Security::isStrongPassword($_POST['new_password'])) {
            $this->validator->addError('new_password', 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número');
        }
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            redirect(BASE_URL . '/auth/change-password');
            exit;
        }
        
        // Cargar datos del usuario actual
        $userId = $_SESSION['user_id'];
        if (!$this->userModel->findById($userId)) {
            setFlashMessage('Error al cargar los datos del usuario', 'danger');
            redirect(BASE_URL . '/auth/change-password');
            exit;
        }
        
        // Verificar contraseña actual
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        
        $passwordChanged = $this->userModel->changePassword($currentPassword, $newPassword);
        
        if ($passwordChanged) {
            setFlashMessage('Contraseña actualizada exitosamente', 'success');
            redirect(BASE_URL . '/dashboard');
        } else {
            setFlashMessage('La contraseña actual es incorrecta', 'danger');
            redirect(BASE_URL . '/auth/change-password');
        }
    }
    
    /**
     * Mostrar panel de administración de usuarios (solo para administradores)
     * 
     * @return void
     */
    public function adminUsers() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Obtener parámetros de paginación y filtros
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        // Configurar filtros
        $filters = [];
        
        // Filtrar por rol si se especifica
        if (isset($_GET['role']) && !empty($_GET['role'])) {
            $filters['role'] = sanitize($_GET['role']);
        }
        
        // Filtrar por estado si se especifica
        if (isset($_GET['status']) && in_array($_GET['status'], ['1', '0'])) {
            $filters['is_active'] = (int)$_GET['status'];
        }
        
        // Filtrar por búsqueda si se especifica
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = '%' . sanitize($_GET['search']) . '%';
        }
        
        // Obtener total de usuarios según filtros
        $totalUsers = $this->userModel->count($filters);
        
        // Configurar paginación
        $pagination = paginate($totalUsers, $page, $perPage);
        
        // Obtener usuarios para la página actual con filtros aplicados
        $users = $this->userModel->getAll($filters, $pagination);
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista
        include(VIEW_DIR . '/auth/admin_users.php');
    }
    
    /**
     * Mostrar formulario para crear un nuevo usuario (solo para administradores)
     * 
     * @return void
     */
    public function createUser() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista
        include(VIEW_DIR . '/auth/create_user.php');
    }
    
    /**
     * Procesar la creación de un nuevo usuario (solo para administradores)
     * 
     * @return void
     */
    public function storeUser() {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/admin/create-user');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/admin/create-user');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('username', 'El nombre de usuario es obligatorio')
                       ->required('email', 'El email es obligatorio')
                       ->required('password', 'La contraseña es obligatoria')
                       ->required('name', 'El nombre completo es obligatorio')
                       ->required('role', 'El rol es obligatorio')
                       ->email('email', 'El formato de email no es válido')
                       ->minLength('password', PASSWORD_MIN_LENGTH, 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres')
                       ->in('role', [ROLE_ADMIN, ROLE_ORGANIZER, ROLE_BUYER, ROLE_SUPPLIER, 'user'], 'Rol inválido');
        
        // Verificar fortaleza de la contraseña
        if (!Security::isStrongPassword($_POST['password'])) {
            $this->validator->addError('password', 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número');
        }
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? '',
                'role' => $_POST['role'] ?? ''
            ]; // No guardar las contraseñas
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/auth/admin/create-user');
            exit;
        }
        
        // Verificar si el usuario o email ya existen
        if ($this->userModel->exists($_POST['email'], $_POST['username'])) {
            setFlashMessage('El nombre de usuario o email ya están registrados', 'danger');
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? '',
                'role' => $_POST['role'] ?? ''
            ];
            redirect(BASE_URL . '/auth/admin/create-user');
            exit;
        }
        
        // Preparar datos para el modelo
        $userData = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'password' => $_POST['password'], // Se hashea en el modelo
            'name' => sanitize($_POST['name']),
            'role' => sanitize($_POST['role']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Crear el usuario
        $userId = $this->userModel->create($userData);
        
        if ($userId) {
            setFlashMessage('Usuario creado exitosamente', 'success');
            redirect(BASE_URL . '/auth/admin/users');
        } else {
            setFlashMessage('Error al crear el usuario. Por favor, intente nuevamente', 'danger');
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? '',
                'role' => $_POST['role'] ?? ''
            ];
            redirect(BASE_URL . '/auth/admin/create-user');
        }
    }
    
    /**
     * Mostrar formulario para editar un usuario (solo para administradores)
     * 
     * @param int $id ID del usuario a editar
     * @return void
     */
    public function editUser($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista
        include(VIEW_DIR . '/auth/edit_user.php');
    }
    
    /**
     * Procesar la actualización de un usuario (solo para administradores)
     * 
     * @param int $id ID del usuario a actualizar
     * @return void
     */
    public function updateUser($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/admin/edit-user/' . $id);
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/admin/edit-user/' . $id);
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('username', 'El nombre de usuario es obligatorio')
                       ->required('email', 'El email es obligatorio')
                       ->required('name', 'El nombre completo es obligatorio')
                       ->required('role', 'El rol es obligatorio')
                       ->email('email', 'El formato de email no es válido')
                       ->in('role', [ROLE_ADMIN, ROLE_ORGANIZER, ROLE_BUYER, ROLE_SUPPLIER, 'user'], 'Rol inválido');
        
        // Validar contraseña solo si se proporciona
        if (!empty($_POST['password'])) {
            $this->validator->minLength('password', PASSWORD_MIN_LENGTH, 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
            
            // Verificar fortaleza de la contraseña
            if (!Security::isStrongPassword($_POST['password'])) {
                $this->validator->addError('password', 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número');
            }
        }
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? '',
                'role' => $_POST['role'] ?? ''
            ]; // No guardar las contraseñas
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/auth/admin/edit-user/' . $id);
            exit;
        }
        
        // Preparar datos para el modelo
        $userData = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'name' => sanitize($_POST['name']),
            'role' => sanitize($_POST['role']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Agregar contraseña si se proporciona
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password']; // Se hashea en el modelo
        }
        
        // Actualizar el usuario
        $updated = $this->userModel->update($userData);
        
        if ($updated) {
            setFlashMessage('Usuario actualizado exitosamente', 'success');
            redirect(BASE_URL . '/auth/admin/users');
        } else {
            setFlashMessage('Error al actualizar el usuario. Por favor, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/admin/edit-user/' . $id);
        }
    }
    
    /**
     * Eliminar un usuario (solo para administradores)
     * 
     * @param int $id ID del usuario a eliminar
     * @return void
     */
    public function deleteUser($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }
        
        // No permitir eliminar al propio usuario
        if ($id == $_SESSION['user_id']) {
            setFlashMessage('No puede eliminar su propio usuario', 'danger');
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }
        
        // Eliminar usuario
        $deleted = $this->userModel->delete($id);
        
        if ($deleted) {
            setFlashMessage('Usuario eliminado exitosamente', 'success');
        } else {
            setFlashMessage('Error al eliminar el usuario', 'danger');
        }
        
        redirect(BASE_URL . '/auth/admin/users');
    }
    
    /**
     * Cambiar estado (activar/desactivar) de un usuario (solo para administradores)
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function toggleUserStatus($id) {
        // Verificar permisos
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }
        
        // No permitir desactivar al propio usuario
        if ($id == $_SESSION['user_id']) {
            setFlashMessage('No puede desactivar su propio usuario', 'danger');
            redirect(BASE_URL . '/auth/admin/users');
            exit;
        }

        // Buscar usuario por ID
if (!$this->userModel->findById($id)) {
    setFlashMessage('Usuario no encontrado', 'danger');
    redirect(BASE_URL . '/auth/admin/users');
    exit;
}

// Obtener estado actual y cambiarlo
$currentState = $this->userModel->isActive();
$newState = $currentState ? 0 : 1;

// Actualizar estado
try {
    $updated = $this->userModel->update(['is_active' => $newState]);
    
    if ($updated) {
        $message = $newState ? 'Usuario activado exitosamente' : 'Usuario desactivado exitosamente';
        setFlashMessage($message, 'success');
    } else {
        throw new Exception('Error al cambiar el estado del usuario');
    }
} catch (Exception $e) {
    setFlashMessage('Error al cambiar el estado del usuario: ' . $e->getMessage(), 'danger');
}

redirect(BASE_URL . '/auth/admin/users');
    }
    
    /**
     * Mostrar formulario para cambiar la contraseña de usuario de evento (comprador)
     *
     * @return void
     */
    public function changePasswordEventForm() {
        // No requiere autenticación de sesión global
        // Mostrar formulario de cambio de contraseña para compradores
        include(VIEW_DIR . '/auth/change_password.php');
    }

    /**
     * Procesar cambio de contraseña para usuario de evento (comprador)
     *
     * @return void
     */
    public function changePasswordEvent() {
        // Validar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/auth/change_password_event');
            exit;
        }

        // Validar email y contraseñas
        $this->validator->setData($_POST);
        $this->validator->required('email', 'El email es obligatorio')
                       ->required('new_password', 'La nueva contraseña es obligatoria')
                       ->required('confirm_password', 'La confirmación de contraseña es obligatoria')
                       ->minLength('new_password', PASSWORD_MIN_LENGTH, 'La nueva contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres')
                       ->matches('new_password', 'confirm_password', 'Las contraseñas no coinciden');
        if (!Security::isStrongPassword($_POST['new_password'])) {
            $this->validator->addError('new_password', 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número');
        }
        if ($this->validator->hasErrors()) {
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            $redirectUrl = !empty($_POST['redirect']) ? $_POST['redirect'] : (BASE_URL . '/auth/change_password_event');
            redirect($redirectUrl);
            exit;
        }
        $email = sanitize($_POST['email']);
        $newPassword = $_POST['new_password'];
        $result = $this->userModel->updateEventUserPassword($email, $newPassword);
        if ($result) {
            setFlashMessage('Contraseña actualizada exitosamente', 'success');
        } else {
            setFlashMessage('No se pudo actualizar la contraseña. Verifique el email.', 'danger');
        }
        $redirectUrl = !empty($_POST['redirect']) ? $_POST['redirect'] : (BASE_URL . '/auth/change_password_event');
        redirect($redirectUrl);
    }
    
    /**
     * Alias for eventLogin method to support snake_case URL routing
     * 
     * @param int $eventId ID del evento (opcional)
     * @return void
     */
    public function event_login($eventId = null) {
        Logger::debug("AuthController::event_login alias llamado", ['eventId' => $eventId]);
        return $this->eventLogin($eventId);
    }
    
    /**
     * Alias for eventAuthenticate method to support snake_case URL routing
     * 
     * @return void
     */
    public function event_authenticate() {
        return $this->eventAuthenticate();
    }
}