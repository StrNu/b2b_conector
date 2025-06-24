<?php
/**
 * Modelo de Asistente
 * 
 * Esta clase maneja todas las operaciones relacionadas con los asistentes
 * que representan a las personas que acuden a los eventos en nombre de las empresas.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class Assistant {
    private $db;
    private $table = 'assistants';
    
    // Propiedades que mapean a las columnas de la tabla assistants
    private $id;
    private $company_id;
    private $first_name;
    private $last_name;
    private $mobile_phone;
    private $email;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar asistente por ID
     * 
     * @param int $id ID del asistente a buscar
     * @return bool True si el asistente existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE assistant_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar asistentes por empresa
     * 
     * @param int $companyId ID de la empresa
     * @param array $pagination Información de paginación
     * @return array Lista de asistentes de la empresa
     */
    public function findByCompany($companyId, $pagination = null) {
        return $this->getAll(['company_id' => $companyId], $pagination);
    }
    
    /**
     * Encontrar asistente por email
     * 
     * @param string $email Email del asistente a buscar
     * @return bool True si el asistente existe, false en caso contrario
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
     * Verificar si existe un asistente por email en una empresa específica
     * 
     * @param string $email Email del asistente
     * @param int $companyId ID de la empresa
     * @return bool True si el asistente existe, false en caso contrario
     */
    public function exists($email, $companyId) {
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE email = :email AND company_id = :company_id";
        
        $params = [
            'email' => $email,
            'company_id' => $companyId
        ];
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear un nuevo asistente
     * 
     * @param array $data Datos del asistente a crear
     * @return bool|int ID del asistente creado o false en caso de error
     */
    public function create($data) {
        // DEBUG: Log de datos recibidos
        Logger::debug('[DEBUG Assistant::create] Datos recibidos: ' . print_r($data, true));
        // Validar datos mínimos requeridos
        if (!isset($data['company_id']) || !isset($data['first_name']) || 
            !isset($data['last_name']) || !isset($data['email'])) {
            Logger::debug('[DEBUG Assistant::create] Faltan campos obligatorios');
            return false;
        }
        // Validar que la empresa exista
        $companyModel = new Company($this->db);
        if (!$companyModel->findById($data['company_id'])) {
            Logger::debug('[DEBUG Assistant::create] Empresa no existe: ' . $data['company_id']);
            return false;
        }
        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Logger::debug('[DEBUG Assistant::create] Email inválido: ' . $data['email']);
            return false;
        }
        // Verificar que el email no esté duplicado en la misma empresa
        if ($this->exists($data['email'], $data['company_id'])) {
            Logger::debug('[DEBUG Assistant::create] Email duplicado: ' . $data['email']);
            return false;
        }
        // Generar consulta SQL
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        $query = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";
        $result = $this->db->query($query, $data);
        Logger::debug('[DEBUG Assistant::create] Resultado query: ' . print_r($result, true));
        if ($result) {
            $id = $this->db->lastInsertId();
            Logger::debug('[DEBUG Assistant::create] ID insertado: ' . $id);
            return $id;
        }
        Logger::debug('[DEBUG Assistant::create] Falló el insert');
        return false;
    }
    
    /**
     * Actualizar datos de un asistente
     * 
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function update($data) {
        // Si se recibe un id como primer parámetro (por compatibilidad), ajústalo
        if (is_numeric($data)) {
            $args = func_get_args();
            $id = $args[0];
            $data = $args[1] ?? [];
            $this->findById($id);
        }
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        // Validar email si se proporciona
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        // Verificar que el email no esté duplicado en la misma empresa si se está actualizando
        if (isset($data['email']) && isset($data['company_id']) && 
            $data['email'] !== $this->email &&
            $this->exists($data['email'], $data['company_id'])) {
            return false;
        }
        // Generar sentencia de actualización
        $updateFields = [];
        foreach ($data as $key => $value) {
            $updateFields[] = "$key = :$key";
        }
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $updateFields) . " 
                  WHERE assistant_id = :id";
        $data['id'] = $this->id;
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar asistente
     * 
     * @param int $id ID del asistente a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $assistantId = $id ?? $this->id;
        
        if (!$assistantId) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE assistant_id = :id";
        return $this->db->query($query, ['id' => $assistantId]) ? true : false;
    }
    
    /**
     * Obtener todos los asistentes
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de asistentes
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT a.*, c.company_name 
                  FROM {$this->table} a
                  JOIN company c ON a.company_id = c.company_id";
                  
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'search' && !empty($value)) {
                    // Búsqueda por nombre, apellido o email
                    $conditions[] = "(a.first_name LIKE :search OR a.last_name LIKE :search OR a.email LIKE :search)";
                    $params['search'] = '%' . $value . '%';
                } else {
                    $conditions[] = "a.$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY a.first_name ASC, a.last_name ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    
    /**
     * Contar asistentes por evento
     * 
     * @param int $eventId ID del evento
     * @return int Número de asistentes
     */
    public function countByEvent($eventId) {
        $query = "SELECT COUNT(*) as count 
                  FROM {$this->table} a
                  INNER JOIN company c ON a.company_id = c.company_id
                  WHERE c.event_id = :event_id";
        $result = $this->db->single($query, ['event_id' => $eventId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Contar total de asistentes
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de asistentes
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table} a";
        
        // Incluir join si se necesita para el filtro de búsqueda
        if (isset($filters['search'])) {
            $query .= " LEFT JOIN company c ON a.company_id = c.company_id";
        }
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'search' && !empty($value)) {
                    $conditions[] = "(a.first_name LIKE :search OR a.last_name LIKE :search OR a.email LIKE :search OR c.company_name LIKE :search)";
                    $params['search'] = '%' . $value . '%';
                } else {
                    $conditions[] = "a.$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Obtener asistentes por evento
     * 
     * @param int $eventId ID del evento
     * @return array Lista de asistentes del evento
     */
    public function getByEvent($eventId) {
        $query = "SELECT a.*, c.company_name, c.event_id
                  FROM {$this->table} a
                  JOIN company c ON a.company_id = c.company_id
                  WHERE c.event_id = :event_id
                  ORDER BY a.first_name ASC, a.last_name ASC";
        return $this->db->resultSet($query, ['event_id' => $eventId]);
    }
    
    /**
     * Obtener información de la empresa asociada
     * 
     * @return array|false Datos de la empresa o false si no existe
     */
    public function getCompany() {
        if (!$this->company_id) {
            return false;
        }
        
        $companyModel = new Company($this->db);
        if ($companyModel->findById($this->company_id)) {
            return [
                'company_id' => $companyModel->getId(),
                'company_name' => $companyModel->getCompanyName(),
                'role' => $companyModel->getRole(),
                'event_id' => $companyModel->getEventId()
            ];
        }
        
        return false;
    }
    
    /**
     * Obtener nombre completo del asistente
     * 
     * @return string Nombre completo
     */
    public function getFullName() {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    /**
     * Establecer propiedades del modelo desde un array de datos
     * 
     * @param array $data Datos a establecer
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['assistant_id'] ?? null;
        $this->company_id = $data['company_id'] ?? null;
        $this->first_name = $data['first_name'] ?? null;
        $this->last_name = $data['last_name'] ?? null;
        $this->mobile_phone = $data['mobile_phone'] ?? null;
        $this->email = $data['email'] ?? null;
    }
    
    /**
     * Getters para propiedades privadas
     */
    public function getId() {
        return $this->id;
    }
    
    public function getCompanyId() {
        return $this->company_id;
    }
    
    public function getFirstName() {
        return $this->first_name;
    }
    
    public function getLastName() {
        return $this->last_name;
    }
    
    public function getMobilePhone() {
        return $this->mobile_phone;
    }
    
    public function getEmail() {
        return $this->email;
    }
}