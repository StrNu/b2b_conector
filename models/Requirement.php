<?php
/**
 * Modelo de Requerimiento
 * 
 * Esta clase maneja todas las operaciones relacionadas con los requerimientos
 * de los compradores, que se utilizan para generar matches con proveedores.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class Requirement {
    private $db;
    private $table = 'requirements';
    
    // Propiedades que mapean a las columnas de la tabla requirements
    private $id;
    private $buyer_id;
    private $subcategory_id;
    private $budget_usd;
    private $quantity;
    private $unit_of_measurement;
    private $created_at;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar requerimiento por ID
     * 
     * @param int $id ID del requerimiento a buscar
     * @return bool True si el requerimiento existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE requirement_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar requerimientos por comprador
     * 
     * @param int $buyerId ID del comprador
     * @param array $pagination Información de paginación
     * @return array Lista de requerimientos del comprador
     */
    public function findByBuyer($buyerId, $pagination = null) {
        return $this->getAll(['buyer_id' => $buyerId], $pagination);
    }
    
    /**
     * Encontrar requerimientos por subcategoría
     * 
     * @param int $subcategoryId ID de la subcategoría
     * @param array $pagination Información de paginación
     * @return array Lista de requerimientos de la subcategoría
     */
    public function findBySubcategory($subcategoryId, $pagination = null) {
        return $this->getAll(['subcategory_id' => $subcategoryId], $pagination);
    }
    
    /**
     * Verificar si existe un requerimiento para un comprador y subcategoría específicos
     * 
     * @param int $buyerId ID del comprador
     * @param int $subcategoryId ID de la subcategoría
     * @return bool True si el requerimiento existe, false en caso contrario
     */
    public function exists($buyerId, $subcategoryId) {
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE buyer_id = :buyer_id AND subcategory_id = :subcategory_id";
        
        $params = [
            'buyer_id' => $buyerId,
            'subcategory_id' => $subcategoryId
        ];
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear un nuevo requerimiento
     * 
     * @param array $data Datos del requerimiento a crear
     * @return bool|int ID del requerimiento creado o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['buyer_id']) || !isset($data['subcategory_id'])) {
            return false;
        }
        
        // Verificar que el comprador exista y sea de tipo buyer
        $companyModel = new Company($this->db);
        if (!$companyModel->findById($data['buyer_id']) || $companyModel->getRole() !== 'buyer') {
            return false;
        }
        
        // Verificar que la subcategoría exista
        $subcategoryModel = new Subcategory($this->db);
        if (!$subcategoryModel->findById($data['subcategory_id'])) {
            return false;
        }
        
        // Establecer fecha de creación si no está presente
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
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
     * Actualizar datos de un requerimiento
     * 
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function update($data) {
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        // Generar sentencia de actualización
        $updateFields = [];
        foreach ($data as $key => $value) {
            $updateFields[] = "$key = :$key";
        }
        
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $updateFields) . " 
                  WHERE requirement_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar requerimiento
     * 
     * @param int $id ID del requerimiento a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $requirementId = $id ?? $this->id;
        
        if (!$requirementId) {
            return false;
        }
        
        // Verificar si existen matches asociados
        if ($this->hasMatches($requirementId)) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE requirement_id = :id";
        return $this->db->query($query, ['id' => $requirementId]) ? true : false;
    }
    
    /**
     * Obtener todos los requerimientos
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de requerimientos
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT r.*, 
                  c.company_name as buyer_name,
                  s.subcategory_name,
                  cat.category_name,
                  cat.category_id
                  FROM {$this->table} r
                  JOIN company c ON r.buyer_id = c.company_id
                  JOIN subcategories s ON r.subcategory_id = s.subcategory_id
                  JOIN categories cat ON s.category_id = cat.category_id";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'search' && !empty($value)) {
                    // Búsqueda por nombre de compañía o subcategoría
                    $conditions[] = "(c.company_name LIKE :search OR s.subcategory_name LIKE :search OR cat.category_name LIKE :search)";
                    $params['search'] = '%' . $value . '%';
                } else if ($key === 'budget_min' && !empty($value)) {
                    // Filtro por presupuesto mínimo
                    $conditions[] = "r.budget_usd >= :budget_min";
                    $params['budget_min'] = $value;
                } else if ($key === 'budget_max' && !empty($value)) {
                    // Filtro por presupuesto máximo
                    $conditions[] = "r.budget_usd <= :budget_max";
                    $params['budget_max'] = $value;
                } else {
                    // Filtros directos por columna
                    $conditions[] = "r.$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY r.created_at DESC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de requerimientos
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de requerimientos
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table} r";
        
        // Incluir joins si se necesitan para filtros
        if (isset($filters['search']) || isset($filters['category_id'])) {
            $query .= " JOIN company c ON r.buyer_id = c.company_id
                       JOIN subcategories s ON r.subcategory_id = s.subcategory_id
                       JOIN categories cat ON s.category_id = cat.category_id";
        }
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'search' && !empty($value)) {
                    $conditions[] = "(c.company_name LIKE :search OR s.subcategory_name LIKE :search OR cat.category_name LIKE :search)";
                    $params['search'] = '%' . $value . '%';
                } else if ($key === 'budget_min' && !empty($value)) {
                    $conditions[] = "r.budget_usd >= :budget_min";
                    $params['budget_min'] = $value;
                } else if ($key === 'budget_max' && !empty($value)) {
                    $conditions[] = "r.budget_usd <= :budget_max";
                    $params['budget_max'] = $value;
                } else if ($key === 'category_id' && !empty($value)) {
                    $conditions[] = "cat.category_id = :category_id";
                    $params['category_id'] = $value;
                } else {
                    $conditions[] = "r.$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Verificar si un requerimiento tiene matches asociados
     * 
     * @param int $requirementId ID del requerimiento
     * @return bool True si tiene matches, false en caso contrario
     */
    public function hasMatches($requirementId = null) {
        $id = $requirementId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        // Obtener el buyer_id y subcategory_id del requerimiento
        if (!$this->findById($id)) {
            return false;
        }
        
        $buyerId = $this->buyer_id;
        $subcategoryId = $this->subcategory_id;
        
        // Verificar si hay matches que incluyan este requerimiento
        $query = "SELECT COUNT(*) FROM matches m
                  WHERE m.buyer_id = :buyer_id 
                  AND m.matched_categories LIKE :subcategory_pattern";
                  
        $params = [
            'buyer_id' => $buyerId,
            'subcategory_pattern' => '%"subcategory_id":' . $subcategoryId . '%'
        ];
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Obtener información del comprador
     * 
     * @param int $requirementId ID del requerimiento
     * @return array|false Datos del comprador o false si no existe
     */
    public function getBuyer($requirementId = null) {
        $id = $requirementId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        // Si ya tenemos el buyer_id cargado, usarlo directamente
        if ($requirementId === null && $this->buyer_id) {
            $buyerId = $this->buyer_id;
        } else {
            // Obtener el buyer_id del requerimiento
            $query = "SELECT buyer_id FROM {$this->table} WHERE requirement_id = :id LIMIT 1";
            $buyerId = $this->db->query($query, ['id' => $id])->fetchColumn();
            
            if (!$buyerId) {
                return false;
            }
        }
        
        // Obtener datos del comprador
        $query = "SELECT * FROM company WHERE company_id = :buyer_id LIMIT 1";
        return $this->db->single($query, ['buyer_id' => $buyerId]);
    }
    
    /**
     * Obtener información de la subcategoría
     * 
     * @param int $requirementId ID del requerimiento
     * @return array|false Datos de la subcategoría o false si no existe
     */
    public function getSubcategory($requirementId = null) {
        $id = $requirementId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        // Si ya tenemos el subcategory_id cargado, usarlo directamente
        if ($requirementId === null && $this->subcategory_id) {
            $subcategoryId = $this->subcategory_id;
        } else {
            // Obtener el subcategory_id del requerimiento
            $query = "SELECT subcategory_id FROM {$this->table} WHERE requirement_id = :id LIMIT 1";
            $subcategoryId = $this->db->query($query, ['id' => $id])->fetchColumn();
            
            if (!$subcategoryId) {
                return false;
            }
        }
        
        // Obtener datos de la subcategoría incluyendo su categoría
        $query = "SELECT s.*, c.category_name, c.category_id
                  FROM subcategories s
                  JOIN categories c ON s.category_id = c.category_id
                  WHERE s.subcategory_id = :subcategory_id 
                  LIMIT 1";
                  
        return $this->db->single($query, ['subcategory_id' => $subcategoryId]);
    }
    
    /**
     * Obtener requerimientos por evento
     * 
     * @param int $eventId ID del evento
     * @param array $pagination Información de paginación
     * @return array Lista de requerimientos del evento
     */
    public function getByEvent($eventId, $pagination = null) {
        $query = "SELECT r.*, 
                  c.company_name as buyer_name,
                  s.subcategory_name,
                  cat.category_name,
                  cat.category_id
                  FROM {$this->table} r
                  JOIN company c ON r.buyer_id = c.company_id
                  JOIN subcategories s ON r.subcategory_id = s.subcategory_id
                  JOIN categories cat ON s.category_id = cat.category_id
                  WHERE c.event_id = :event_id
                  ORDER BY r.created_at DESC";
                  
        $params = ['event_id' => $eventId];
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Establecer propiedades del modelo desde un array de datos
     * 
     * @param array $data Datos a establecer
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['requirement_id'] ?? null;
        $this->buyer_id = $data['buyer_id'] ?? null;
        $this->subcategory_id = $data['subcategory_id'] ?? null;
        $this->budget_usd = $data['budget_usd'] ?? null;
        $this->quantity = $data['quantity'] ?? null;
        $this->unit_of_measurement = $data['unit_of_measurement'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }
    
    /**
     * Getters para propiedades privadas
     */
    public function getId() {
        return $this->id;
    }
    
    public function getBuyerId() {
        return $this->buyer_id;
    }
    
    public function getSubcategoryId() {
        return $this->subcategory_id;
    }
    
    public function getBudgetUsd() {
        return $this->budget_usd;
    }
    
    public function getQuantity() {
        return $this->quantity;
    }
    
    public function getUnitOfMeasurement() {
        return $this->unit_of_measurement;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
}