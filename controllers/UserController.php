<?php
/**
 * Controlador de Usuarios
 * 
 * Este controlador maneja todas las operaciones relacionadas con la administración de usuarios
 * incluyendo listado, creación, edición, eliminación y gestión de perfiles.
 * 
 * @package B2B Conector
 * @version 1.0
 */

require_once 'BaseController.php';

class UserController extends BaseController {
        private $userModel;
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
        $this->userModel = new User($this->db);
        
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
     * Listar todos los usuarios
     * 
     * @return void
     */
    public function index() {
        // Verificar permisos (solo administradores pueden ver todos los usuarios)
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
        
        // Cargar vista con los datos
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'usercontroller',
            'moduleJS' => 'usercontroller'
        ];
        
        $this->render('users/index', $data, 'admin');
    }
    
    /**
     * Mostrar detalles de un usuario específico
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function view($id) {
        // Verificar permisos (solo administradores o el propio usuario)
        if (!hasRole([ROLE_ADMIN]) && $_SESSION['user_id'] != $id) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista con los datos
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'usercontroller',
            'moduleJS' => 'usercontroller'
        ];
        
        $this->render('users/view', $data, 'admin');
    }
    
    /**
     * Mostrar formulario para crear un nuevo usuario
     * 
     * @return void
     */
    public function create() {
        // Verificar permisos (solo administradores)
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del formulario
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'usercontroller',
            'moduleJS' => 'usercontroller'
        ];
        
        $this->render('users/create', $data, 'admin');
    }
    
    /**
     * Procesar la creación de un nuevo usuario
     * 
     * @return void
     */
    public function store() {
        // Verificar permisos (solo administradores)
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/users/create');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/users/create');
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
            $this->validator->errors['password'] = 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número';
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
            
            redirect(BASE_URL . '/users/create');
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
            redirect(BASE_URL . '/users/create');
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
            redirect(BASE_URL . '/users');
        } else {
            setFlashMessage('Error al crear el usuario. Por favor, intente nuevamente', 'danger');
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? '',
                'role' => $_POST['role'] ?? ''
            ];
            redirect(BASE_URL . '/users/create');
        }
    }
    
    /**
     * Mostrar formulario para editar un usuario existente
     * 
     * @param int $id ID del usuario a editar
     * @return void
     */
    public function edit($id) {
        // Verificar permisos (solo administradores o el propio usuario)
        if (!hasRole([ROLE_ADMIN]) && $_SESSION['user_id'] != $id) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Token CSRF para el formulario
        $csrfToken = generateCSRFToken();
        
        // Verificar si es un administrador o el propio usuario
        $isAdmin = hasRole([ROLE_ADMIN]);
        $isSelf = ($_SESSION['user_id'] == $id);
        
        // Cargar vista del formulario
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'usercontroller',
            'moduleJS' => 'usercontroller'
        ];
        
        $this->render('users/edit', $data, 'admin');
    }
    
    /**
     * Procesar la actualización de un usuario
     * 
     * @param int $id ID del usuario a actualizar
     * @return void
     */
    public function update($id) {
        // Verificar permisos (solo administradores o el propio usuario)
        if (!hasRole([ROLE_ADMIN]) && $_SESSION['user_id'] != $id) {
            setFlashMessage('No tiene permisos para acceder a esta sección', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/users/edit/' . $id);
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/users/edit/' . $id);
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('username', 'El nombre de usuario es obligatorio')
                       ->required('email', 'El email es obligatorio')
                       ->required('name', 'El nombre completo es obligatorio')
                       ->email('email', 'El formato de email no es válido');
        
        // Los administradores pueden cambiar el rol y estado
        if (hasRole([ROLE_ADMIN])) {
            $this->validator->required('role', 'El rol es obligatorio')
                           ->in('role', [ROLE_ADMIN, ROLE_ORGANIZER, ROLE_BUYER, ROLE_SUPPLIER, 'user'], 'Rol inválido');
        }
        
        // Validar contraseña solo si se proporciona
        if (!empty($_POST['password'])) {
            $this->validator->minLength('password', PASSWORD_MIN_LENGTH, 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
            
            // Verificar fortaleza de la contraseña
            if (!Security::isStrongPassword($_POST['password'])) {
                $this->validator->errors['password'] = 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número';
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
            
            redirect(BASE_URL . '/users/edit/' . $id);
            exit;
        }
        
        // Preparar datos para el modelo
        $userData = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'name' => sanitize($_POST['name'])
        ];
        
        // Agregar contraseña si se proporciona
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password']; // Se hashea en el modelo
        }
        
        // Los administradores pueden cambiar el rol y estado
        if (hasRole([ROLE_ADMIN])) {
            $userData['role'] = sanitize($_POST['role']);
            $userData['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        }
        
        // Actualizar el usuario
        $updated = $this->userModel->update($userData);
        
        if ($updated) {
            // Si se actualizó el usuario actualmente autenticado, actualizar los datos de sesión
            if ($_SESSION['user_id'] == $id) {
                $_SESSION['username'] = $userData['username'];
                $_SESSION['name'] = $userData['name'];
                if (hasRole([ROLE_ADMIN]) && isset($userData['role'])) {
                    $_SESSION['role'] = $userData['role'];
                }
            }
            
            setFlashMessage('Usuario actualizado exitosamente', 'success');
            
            // Redireccionar según el rol y si es el propio usuario
            if (hasRole([ROLE_ADMIN]) && $_SESSION['user_id'] != $id) {
                redirect(BASE_URL . '/users');
            } else {
                redirect(BASE_URL . '/users/profile');
            }
        } else {
            setFlashMessage('Error al actualizar el usuario. Por favor, intente nuevamente', 'danger');
            redirect(BASE_URL . '/users/edit/' . $id);
        }
    }
    
    /**
     * Eliminar un usuario
     * 
     * @param int $id ID del usuario a eliminar
     * @return void
     */
    public function delete($id) {
        // Verificar permisos (solo administradores)
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para eliminar usuarios', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // No permitir eliminar al propio usuario
        if ($id == $_SESSION['user_id']) {
            setFlashMessage('No puede eliminar su propio usuario', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Eliminar usuario
        $deleted = $this->userModel->delete($id);
        
        if ($deleted) {
            setFlashMessage('Usuario eliminado exitosamente', 'success');
        } else {
            setFlashMessage('Error al eliminar el usuario', 'danger');
        }
        
        redirect(BASE_URL . '/users');
    }
    
    /**
     * Cambiar el estado de un usuario (activar/desactivar)
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function toggleActive($id) {
        // Verificar permisos (solo administradores)
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para modificar usuarios', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // No permitir desactivar al propio usuario
        if ($id == $_SESSION['user_id']) {
            setFlashMessage('No puede desactivar su propio usuario', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Obtener estado actual y cambiarlo
        $currentState = $this->userModel->isActive();
        $newState = $currentState ? 0 : 1;
        
        // Actualizar estado
        $updated = $this->userModel->update(['is_active' => $newState]);
        
        if ($updated) {
            $message = $newState ? 'Usuario activado exitosamente' : 'Usuario desactivado exitosamente';
            setFlashMessage($message, 'success');
        } else {
            setFlashMessage('Error al cambiar el estado del usuario', 'danger');
        }
        
        redirect(BASE_URL . '/users');
    }
    
    /**
     * Cambiar el rol de un usuario
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function changeRole($id) {
        // Verificar permisos (solo administradores)
        if (!hasRole([ROLE_ADMIN])) {
            setFlashMessage('No tiene permisos para modificar roles de usuarios', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Validar que se recibió un rol válido
        if (!isset($_POST['role']) || !in_array($_POST['role'], [ROLE_ADMIN, ROLE_ORGANIZER, ROLE_BUYER, ROLE_SUPPLIER, 'user'])) {
            setFlashMessage('Rol inválido', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // No permitir cambiar el rol del propio usuario
        if ($id == $_SESSION['user_id']) {
            setFlashMessage('No puede cambiar su propio rol', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($id)) {
            setFlashMessage('Usuario no encontrado', 'danger');
            redirect(BASE_URL . '/users');
            exit;
        }
        
        // Actualizar rol
        $newRole = sanitize($_POST['role']);
        $updated = $this->userModel->update(['role' => $newRole]);
        
        if ($updated) {
            setFlashMessage('Rol del usuario actualizado exitosamente', 'success');
        } else {
            setFlashMessage('Error al cambiar el rol del usuario', 'danger');
        }
        
        redirect(BASE_URL . '/users');
    }
    
    /**
     * Mostrar perfil del usuario actual
     * 
     * @return void
     */
    public function profile() {
        // Obtener ID del usuario autenticado
        $userId = $_SESSION['user_id'];
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($userId)) {
            setFlashMessage('Error al cargar el perfil de usuario', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Token CSRF para los formularios
        $csrfToken = generateCSRFToken();
        
        // Cargar vista del perfil
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'usercontroller',
            'moduleJS' => 'usercontroller'
        ];
        
        $this->render('users/profile', $data, 'admin');
    }
    
    /**
     * Actualizar perfil del usuario actual
     * 
     * @return void
     */
    public function updateProfile() {
        // Obtener ID del usuario autenticado
        $userId = $_SESSION['user_id'];
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/users/profile');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/users/profile');
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($userId)) {
            setFlashMessage('Error al cargar el perfil de usuario', 'danger');
            redirect(BASE_URL);
            exit;
        }
        
        // Validar datos del formulario
        $this->validator->setData($_POST);
        $this->validator->required('username', 'El nombre de usuario es obligatorio')
                       ->required('email', 'El email es obligatorio')
                       ->required('name', 'El nombre completo es obligatorio')
                       ->email('email', 'El formato de email no es válido');
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['form_data'] = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? ''
            ];
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            
            redirect(BASE_URL . '/users/profile');
            exit;
        }
        
        // Preparar datos para el modelo
        $userData = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'name' => sanitize($_POST['name'])
        ];
        
        // Actualizar el usuario
        $updated = $this->userModel->update($userData);
        
        if ($updated) {
            // Actualizar los datos de sesión
            $_SESSION['username'] = $userData['username'];
            $_SESSION['name'] = $userData['name'];
            
            setFlashMessage('Perfil actualizado exitosamente', 'success');
        } else {
            setFlashMessage('Error al actualizar el perfil. Por favor, intente nuevamente', 'danger');
        }
        
        redirect(BASE_URL . '/users/profile');
    }
    
    /**
     * Actualizar contraseña del usuario actual
     * 
     * @return void
     */
    public function updatePassword() {
        // Obtener ID del usuario autenticado
        $userId = $_SESSION['user_id'];
        
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/users/profile');
            exit;
        }
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Token de seguridad inválido, intente nuevamente', 'danger');
            redirect(BASE_URL . '/users/profile');
            exit;
        }
        
        // Buscar usuario por ID
        if (!$this->userModel->findById($userId)) {
            setFlashMessage('Error al cargar el perfil de usuario', 'danger');
            redirect(BASE_URL);
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
            $this->validator->errors['new_password'] = 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número';
        }
        
        // Si hay errores de validación, volver al formulario
        if ($this->validator->hasErrors()) {
            $_SESSION['validation_errors'] = $this->validator->getErrors();
            redirect(BASE_URL . '/users/profile');
            exit;
        }
        
        // Verificar contraseña actual
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        
        $passwordChanged = $this->userModel->changePassword($currentPassword, $newPassword);
        
        if ($passwordChanged) {
            setFlashMessage('Contraseña actualizada exitosamente', 'success');
        } else {
            setFlashMessage('La contraseña actual es incorrecta', 'danger');
        }
        
        redirect(BASE_URL . '/users/profile');
    }
}