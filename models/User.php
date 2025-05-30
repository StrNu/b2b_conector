<?php
/**
 * Modelo de Usuario
 * 
 * Este modelo maneja todas las operaciones relacionadas con los usuarios.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class User {
    private $db;
    private $id;
    private $username;
    private $email;
    private $password;
    private $name;
    private $role;
    private $is_active;
    private $registration_date;
    private $updated_at;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de la base de datos
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
 * Obtener el ID del usuario
 * 
 * @return int|null ID del usuario
 */
public function getId() {
    return $this->id;
}

/**
 * Obtener el nombre de usuario
 * 
 * @return string Nombre de usuario
 */
public function getUsername() {
    return $this->username;
}

/**
 * Obtener el email del usuario
 * 
 * @return string Email del usuario
 */
public function getEmail() {
    return $this->email;
}

/**
 * Obtener el nombre completo del usuario
 * 
 * @return string Nombre completo del usuario
 */
public function getName() {
    return $this->name;
}

/**
 * Obtener el rol del usuario
 * 
 * @return string Rol del usuario
 */
public function getRole() {
    return $this->role;
}

/**
 * Verificar si el usuario está activo
 * 
 * @return bool True si el usuario está activo, false en caso contrario
 */
public function isActive() {
    return (bool)$this->is_active;
}

/**
 * Obtener fecha de creación del usuario
 * 
 * @return string Fecha de creación
 */
public function getCreatedAt() {
    return $this->registration_date;
}

/**
 * Obtener fecha de actualización del usuario
 * 
 * @return string Fecha de actualización
 */
public function getUpdatedAt() {
    return $this->updated_at;
}
    
    /**
 * Buscar usuario por ID
 * 
 * @param int $id ID del usuario
 * @return bool True si se encontró el usuario, false en caso contrario
 */
public function findById($id) {
    Logger::debug('Buscando usuario por ID: ' . $id);
    $query = "SELECT user_id as id, username, email, password_hash as password, 
                     name, role, is_active, registration_date as created_at, 
                     registration_date as updated_at 
              FROM users 
              WHERE user_id = :id LIMIT 1";
    $result = $this->db->single($query, [':id' => $id]);
    
    if ($result) {
        $this->loadData($result);
        Logger::debug('Usuario encontrado por ID: ' . $id);
        return true;
    }
    
    Logger::debug('Usuario no encontrado por ID: ' . $id);
    return false;
}

/**
 * Buscar usuario por nombre de usuario
 * 
 * @param string $username Nombre de usuario
 * @return bool True si se encontró el usuario, false en caso contrario
 */
public function findByUsername($username) {
    Logger::debug('Buscando usuario por username: ' . $username);
    $query = "SELECT user_id as id, username, email, password_hash as password, 
                     name, role, is_active, registration_date as created_at, 
                     registration_date as updated_at 
              FROM users 
              WHERE username = :username LIMIT 1";
    $result = $this->db->single($query, [':username' => $username]);
    
    if ($result) {
        $this->loadData($result);
        Logger::debug('Usuario encontrado por username: ' . $username);
        return true;
    }
    
    Logger::debug('Usuario no encontrado por username: ' . $username);
    return false;
}

/**
 * Buscar usuario por email
 * 
 * @param string $email Email del usuario
 * @return bool True si se encontró el usuario, false en caso contrario
 */
public function findByEmail($email) {
    Logger::debug('Buscando usuario por email: ' . $email);
    $query = "SELECT user_id as id, username, email, password_hash as password, 
                     name, role, is_active, registration_date as created_at, 
                     registration_date as updated_at 
              FROM users 
              WHERE email = :email LIMIT 1";
    $result = $this->db->single($query, [':email' => $email]);
    
    if ($result) {
        $this->loadData($result);
        Logger::debug('Usuario encontrado por email: ' . $email);
        return true;
    }
    
    Logger::debug('Usuario no encontrado por email: ' . $email);
    return false;
}
    
  /**
 * Buscar usuario por login (email o nombre de usuario)
 * 
 * @param string $login Email o nombre de usuario
 * @return bool True si se encontró el usuario, false en caso contrario
 */
public function findByLogin($login) {
    if (empty($login)) {
        Logger::warning('Intento de búsqueda con login vacío');
        return false;
    }
    
    Logger::debug('Buscando usuario por login: ' . $login);
    
    // Usar los nombres de columna correctos según la estructura de la tabla
    $query = "SELECT user_id as id, username, email, password_hash as password, 
                     name, role, is_active, registration_date as created_at, 
                     registration_date as updated_at 
              FROM users 
              WHERE email = :login_email OR username = :login_username 
              LIMIT 1";
              
    $result = $this->db->single($query, [
        ':login_email' => $login, 
        ':login_username' => $login
    ]);
    
    if ($result && is_array($result)) {
        $this->loadData($result);
        Logger::debug('Usuario encontrado por login: ' . $login);
        return true;
    }
    
    Logger::debug('Usuario no encontrado por login: ' . $login);
    return false;
}

/**
 * Cargar datos del usuario desde un arreglo
 * 
 * @param array $data Datos del usuario
 * @return void
 */
private function loadData($data) {
    // Verificar que $data sea un array válido
    if (!is_array($data)) {
        Logger::warning('Intento de cargar datos de usuario con un valor no válido');
        return;
    }
    
    // Usar el operador de fusión null para proporcionar valores predeterminados seguros
    $this->id = isset($data['id']) ? (int)$data['id'] : null;
    $this->username = $data['username'] ?? '';
    $this->email = $data['email'] ?? '';
    $this->password = $data['password'] ?? ''; // Ahora contiene el valor de password_hash
    $this->name = $data['name'] ?? '';
    $this->role = $data['role'] ?? 'user';
    $this->is_active = isset($data['is_active']) ? (int)$data['is_active'] : 0;
    $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
    $this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
    
    // Registrar información sobre los datos cargados para depuración
    Logger::debug('Datos de usuario cargados', [
        'id' => $this->id,
        'username' => $this->username,
        'email' => $this->email,
        'has_password' => !empty($this->password) ? 'sí' : 'no',
        'role' => $this->role,
        'is_active' => $this->is_active
    ]);
}
   /**
 * Autenticar usuario
 * 
 * @param string $login Email o nombre de usuario
 * @param string $password Contraseña
 * @return bool True si la autenticación fue exitosa, false en caso contrario
 */
public function authenticate($login, $password) {
    if (empty($login) || empty($password)) {
        Logger::warning('Intento de autenticación con credenciales vacías');
        return false;
    }
    
    Logger::debug('Iniciando proceso de autenticación para: ' . $login);
    
    // Buscar usuario por login (email o nombre de usuario)
    if (!$this->findByLogin($login)) {
        Logger::warning('Usuario no encontrado durante autenticación: ' . $login);
        return false;
    }
    
    // Verificar si el usuario tiene un ID válido
    if (empty($this->id)) {
        Logger::error('Usuario encontrado pero sin ID válido: ' . $login);
        return false;
    }
    
    // Verificar si el usuario está activo
    if (!$this->is_active) {
        Logger::warning('Intento de autenticación de usuario inactivo: ' . $login . ' (ID: ' . $this->id . ')');
        return false;
    }
    
    // Verificar que la contraseña almacenada sea válida
    if (empty($this->password)) {
        Logger::error('Usuario sin contraseña almacenada: ' . $login . ' (ID: ' . $this->id . ')');
        return false;
    }
    
    // Verificar contraseña
    Logger::debug('Verificando contraseña para usuario: ' . $login . ' (ID: ' . $this->id . ')');
    
    try {
        $passwordMatch = Security::verifyPassword($password, $this->password);
        
        if ($passwordMatch) {
            Logger::info('Autenticación exitosa para usuario: ' . $login . ' (ID: ' . $this->id . ')');
            return true;
        } else {
            Logger::warning('Contraseña incorrecta para usuario: ' . $login . ' (ID: ' . $this->id . ')');
            return false;
        }
    } catch (Exception $e) {
        Logger::error('Error al verificar contraseña: ' . $e->getMessage(), [
            'login' => $login,
            'user_id' => $this->id
        ]);
        return false;
    }
}
    
    /**
     * Verificar si ya existe un usuario con el email o nombre de usuario
     * 
     * @param string $email Email
     * @param string $username Nombre de usuario
     * @return bool True si existe, false en caso contrario
     */
    public function exists($email, $username) {
        $query = "SELECT COUNT(*) as count FROM users WHERE email = :email OR username = :username";
        $result = $this->db->single($query, [':email' => $email, ':username' => $username]);
        
        return ($result && $result['count'] > 0);
    }
    
    /**
     * Crear un nuevo usuario
     * 
     * @param array $data Datos del usuario
     * @return int|bool ID del usuario creado o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return false;
        }
        
        // Hashear contraseña
        $passwordHash = Security::hashPassword($data['password']);
        
        // Preparar datos
        $params = [
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password_hash' => $passwordHash,
            ':name' => $data['name'] ?? '',
            ':role' => $data['role'] ?? 'user',
            ':is_active' => isset($data['is_active']) ? $data['is_active'] : 1,
            ':registration_date' => date('Y-m-d H:i:s')
        ];
        
        $query = "INSERT INTO users (username, email, password, name, role, is_active, created_at, updated_at) 
                 VALUES (:username, :email, :password, :name, :role, :is_active, :created_at, :updated_at)";
        
        // Ejecutar consulta
        $result = $this->db->query($query, $params);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar usuario
     * 
     * @param array $data Datos del usuario
     * @return bool True si se actualizó correctamente, false en caso contrario
     */
    public function update($data) {
        // Validar que el usuario está cargado
        if (!$this->id) {
            return false;
        }
        
        // Preparar consulta base
        $setStatements = [];
        $params = [':id' => $this->id];
        
        // Agregar campos a actualizar
        if (isset($data['username'])) {
            $setStatements[] = "username = :username";
            $params[':username'] = $data['username'];
            $this->username = $data['username'];
        }
        
        if (isset($data['email'])) {
            $setStatements[] = "email = :email";
            $params[':email'] = $data['email'];
            $this->email = $data['email'];
        }
        
        if (isset($data['name'])) {
            $setStatements[] = "name = :name";
            $params[':name'] = $data['name'];
            $this->name = $data['name'];
        }
        
        if (isset($data['role'])) {
            $setStatements[] = "role = :role";
            $params[':role'] = $data['role'];
            $this->role = $data['role'];
        }
        
        if (isset($data['is_active'])) {
            $setStatements[] = "is_active = :is_active";
            $params[':is_active'] = $data['is_active'];
            $this->is_active = $data['is_active'];
        }
        
        // Actualizar contraseña sólo si se proporciona
        if (isset($data['password']) && !empty($data['password'])) {
            $setStatements[] = "password_hash = :password_hash";
            $params[':password_hash'] = Security::hashPassword($data['password']);
            $this->password = $params[':password_hash'];
        }
        
        
        // Si no hay nada que actualizar, retornar éxito
        if (empty($setStatements)) {
            return true;
        }
        
        // Construir consulta final
        $query = "UPDATE users SET " . implode(", ", $setStatements) . " WHERE user_id = :id";
        
        // Ejecutar consulta
        $result = $this->db->query($query, $params);
        
        return $result !== false;
    }

    /**
 * Contar usuarios según criterios
 * 
 * @param array $conditions Condiciones para filtrar (opcional)
 * @return int Número de usuarios
 */
public function count($conditions = []) {
    $query = "SELECT COUNT(*) as count FROM users";
    $params = [];
    
    // Si hay condiciones, añadirlas a la consulta
    if (!empty($conditions)) {
        $query .= " WHERE ";
        $clauses = [];
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // Para condiciones IN (campo IN ('valor1', 'valor2', ...))
                $placeholders = [];
                foreach ($value as $index => $val) {
                    $placeholder = ":{$field}_{$index}";
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $val;
                }
                $clauses[] = "$field IN (" . implode(", ", $placeholders) . ")";
            } else {
                // Para condiciones simples (campo = valor)
                $clauses[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }
        
        $query .= implode(" AND ", $clauses);
    }
    
    $result = $this->db->single($query, $params);
    
    return $result ? (int)$result['count'] : 0;
}

/**
 * Obtener lista de usuarios con filtros opcionales
 * 
 * @param array $filters Filtros a aplicar
 * @param array $pagination Parámetros de paginación (offset, limit)
 * @return array Lista de usuarios
 */
public function getAll($filters = [], $pagination = []) {
    // Construir la consulta base
    $query = "SELECT * FROM users WHERE 1=1";
    $params = [];
    
    // Aplicar filtros
    if (!empty($filters['role'])) {
        $query .= " AND role = :role";
        $params[':role'] = $filters['role'];
    }
    
    if (isset($filters['is_active'])) {
        $query .= " AND is_active = :is_active";
        $params[':is_active'] = $filters['is_active'];
    }
    
    if (!empty($filters['search'])) {
        $query .= " AND (username LIKE :search OR email LIKE :search OR name LIKE :search)";
        $params[':search'] = $filters['search'];
    }
    
    // Ordenar
    $query .= " ORDER BY registration_date DESC";
    
    // Aplicar paginación
    if (!empty($pagination['limit'])) {
        $query .= " LIMIT :limit";
        $params[':limit'] = (int)$pagination['limit'];
        
        if (!empty($pagination['offset'])) {
            $query .= " OFFSET :offset";
            $params[':offset'] = (int)$pagination['offset'];
        }
    }
    
    // Ejecutar consulta
    return $this->db->resultSet($query, $params) ?: [];
}

/**
 * Obtener la fecha de registro del usuario
 * 
 * @param string $format Formato de fecha deseado (opcional)
 * @return string|null Fecha de registro con el formato especificado o null si no existe
 */
public function getRegistrationDate($format = 'Y-m-d H:i:s') {
    // Verificar que el usuario esté cargado y tenga fecha de registro
    if (!$this->id || empty($this->registration_date)) {
        return null;
    }
    
    // Si se solicita el formato original de la base de datos
    if ($format === 'original') {
        return $this->registration_date;
    }
    
    // Convertir a DateTime para formatear
    try {
        $date = new DateTime($this->registration_date);
        return $date->format($format);
    } catch (Exception $e) {
        Logger::error('Error al formatear fecha de registro', [
            'user_id' => $this->id,
            'registration_date' => $this->registration_date,
            'error' => $e->getMessage()
        ]);
        return $this->registration_date; // Devolver el valor original si hay error
    }
}

/**
     * Crear usuario para event_users con email y password
     * @param array $data
     * @return int|false
     */
    public function createEventUser($data) {
        $params = [
            ':company_id' => $data['company_id'],
            ':event_id' => $data['event_id'],
            ':role' => $data['role'] ?? 'buyer',
            ':is_active' => isset($data['is_active']) ? $data['is_active'] : 1,
            ':created_at' => isset($data['created_at']) ? $data['created_at'] : date('Y-m-d H:i:s'),
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ];
        $query = "INSERT INTO event_users (company_id, event_id, role, is_active, created_at, email, password)
                  VALUES (:company_id, :event_id, :role, :is_active, :created_at, :email, :password)";
        $result = $this->db->query($query, $params);
        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Actualizar contraseña en event_users por email
     * @param string $email
     * @param string $newPassword
     * @return bool
     */
    public function updateEventUserPassword($email, $newPassword) {
        $query = "UPDATE event_users SET password = :password WHERE email = :email";
        $params = [
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':email' => $email
        ];
        $result = $this->db->query($query, $params);
        return $result ? true : false;
    }
}