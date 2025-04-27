<?php
/**
 * Modelo de Usuario
 * 
 * Esta clase maneja todas las operaciones relacionadas con los usuarios
 * incluyendo autenticación, registro y gestión de datos de usuario.
 * 
 * @package B2B Conector
 * @author Tu Nombre
 * @version 1.0
 */

class User {
    private $db;
    private $table = 'users';
    
    // Propiedades que mapean a las columnas de la tabla users
    private $id;
    private $username;
    private $email;
    private $password;
    private $role;
    private $is_active;
    private $name;
    private $registration_date;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar usuario por ID
     * 
     * @param int $id ID del usuario a buscar
     * @return bool True si el usuario existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE user_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar usuario por nombre de usuario
     * 
     * @param string $username Nombre de usuario a buscar
     * @return bool True si el usuario existe, false en caso contrario
     */
    public function findByUsername($username) {
        $query = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        $result = $this->db->single($query, ['username' => $username]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar usuario por email
     * 
     * @param string $email Email a buscar
     * @return bool True si el usuario existe, false en caso contrario
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $result = $this->db->single($query, ['email' => $email]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si existe un usuario por email o username
     * 
     * @param string $email Email a buscar
     * @param string $username Username a buscar
     * @return bool True si el usuario existe, false en caso contrario
     */
    public function exists($email, $username = null) {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];
        
        if ($username) {
            $query .= " OR username = :username";
            $params['username'] = $username;
        }
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear un nuevo usuario
     * 
     * @param array $data Datos del usuario a crear
     * @return bool|int ID del usuario creado o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['username'], $data['email'], $data['password'])) {
            return false;
        }
        
        // Verificar si el usuario ya existe
        if ($this->exists($data['email'], $data['username'])) {
            return false;
        }
        
        // Preparar datos para inserción
        $data['password_hash'] = Security::hashPassword($data['password']);
        unset($data['password']); // No almacenar la contraseña en texto plano
        
        // Establecer valores por defecto si no están presentes
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }
        
        if (!isset($data['role'])) {
            $data['role'] = 'user'; // Rol por defecto
        }
        
        // Generar consulta SQL
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        
        $query = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";
        
        if ($this->db->query($query, $data)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar datos de usuario
     * 
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function update($data) {
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        // Preparar datos para actualización
        if (isset($data['password'])) {
            $data['password_hash'] = Security::hashPassword($data['password']);
            unset($data['password']); // No almacenar la contraseña en texto plano
        }
        
        // Generar sentencia de actualización
        $updateFields = [];
        foreach ($data as $key => $value) {
            $updateFields[] = "$key = :$key";
        }
        
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $updateFields) . " 
                  WHERE user_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar usuario
     * 
     * @param int $id ID del usuario a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $userId = $id ?? $this->id;
        
        if (!$userId) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE user_id = :id";
        return $this->db->query($query, ['id' => $userId]) ? true : false;
    }
    
    /**
     * Autenticar usuario
     * 
     * @param string $login Email o nombre de usuario
     * @param string $password Contraseña
     * @return bool True si la autenticación fue exitosa, false en caso contrario
     */
    public function authenticate($login, $password) {
        // Determinar si el login es un email o un username
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        
        if ($isEmail) {
            $found = $this->findByEmail($login);
            error_log("Intento de autenticación por email: $login, encontrado: " . ($found ? "sí" : "no"));
        } else {
            $found = $this->findByUsername($login);
            error_log("Intento de autenticación por nombre de usuario: $login, encontrado: " . ($found ? "sí" : "no"));
        }
        
        if (!$found) {
            error_log("Usuario no encontrado: $login");
            return false;
        }
        
        // Verificar si el usuario está activo
        if (!$this->is_active) {
            error_log("Usuario inactivo: $login");
            return false;
        }
        
        // Registrar información sobre el hash almacenado
        error_log("Hash de contraseña almacenado: " . ($this->password ? substr($this->password, 0, 10) . "..." : "NULL"));
        
        // Verificar la contraseña
        $passwordMatch = Security::verifyPassword($password, $this->password);
        error_log("Resultado de verificación de contraseña: " . ($passwordMatch ? "coincide" : "no coincide"));
        
        return $passwordMatch;
    }
    
    /**
     * Cambiar contraseña de usuario
     * 
     * @param string $currentPassword Contraseña actual
     * @param string $newPassword Nueva contraseña
     * @return bool True si el cambio fue exitoso, false en caso contrario
     */
    public function changePassword($currentPassword, $newPassword) {
        if (!Security::verifyPassword($currentPassword, $this->password)) {
            return false;
        }
        
        return $this->update([
            'password' => $newPassword
        ]);
    }
    
    /**
     * Obtener todos los usuarios
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de usuarios
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY registration_date DESC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de usuarios
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de usuarios
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Establecer propiedades del modelo desde un array de datos
     * 
     * @param array $data Datos a establecer
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['user_id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password_hash'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->is_active = $data['is_active'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->registration_date = $data['registration_date'] ?? null;
    }
    
    /**
     * Getters para propiedades privadas
     */
    public function getId() {
        return $this->id;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getRole() {
        return $this->role;
    }
    
    public function isActive() {
        return (bool) $this->is_active;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getRegistrationDate() {
        return $this->registration_date;
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     * 
     * @param string|array $roles Rol o roles a verificar
     * @return bool True si el usuario tiene el rol, false en caso contrario
     */
    public function hasRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($this->role, $roles);
    }
}