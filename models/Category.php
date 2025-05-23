<?php
/**
 * Modelo de Categoría
 * 
 * Esta clase maneja todas las operaciones relacionadas con las categorías
 * que utilizan compradores y proveedores para establecer coincidencias.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class Category {
    private $db;
    private $table = 'categories';
    
    // Propiedades que mapean a las columnas de la tabla categories
    private $id;
    private $category_name;
    private $is_active;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar categoría por ID
     * 
     * @param int $id ID de la categoría a buscar
     * @return bool True si la categoría existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE category_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar categoría por nombre
     * 
     * @param string $name Nombre de la categoría a buscar
     * @return bool True si la categoría existe, false en caso contrario
     */
    public function findByName($name) {
        $query = "SELECT * FROM {$this->table} WHERE category_name = :name LIMIT 1";
        $result = $this->db->single($query, ['name' => $name]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si existe una categoría por nombre
     * 
     * @param string $name Nombre de la categoría a verificar
     * @return bool True si la categoría existe, false en caso contrario
     */
    public function exists($name) {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE category_name = :name";
        $count = $this->db->query($query, ['name' => $name])->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear una nueva categoría
     * 
     * @param array $data Datos de la categoría a crear
     * @return bool|int ID de la categoría creada o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['category_name'])) {
            return false;
        }
        
        // Establecer valores por defecto si no están presentes
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
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
     * Actualizar datos de una categoría
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
                  WHERE category_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar categoría
     * 
     * @param int $id ID de la categoría a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $categoryId = $id ?? $this->id;
        
        if (!$categoryId) {
            return false;
        }
        
        // Verificar que no existan subcategorías asociadas
        if ($this->hasSubcategories($categoryId)) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE category_id = :id";
        return $this->db->query($query, ['id' => $categoryId]) ? true : false;
    }
    
    /**
     * Obtener todas las categorías
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de categorías
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'category_name' && strpos($value, '%') !== false) {
                    $conditions[] = "$key LIKE :$key";
                } else {
                    $conditions[] = "$key = :$key";
                }
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY category_name ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de categorías
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de categorías
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'category_name' && strpos($value, '%') !== false) {
                    $conditions[] = "$key LIKE :$key";
                } else {
                    $conditions[] = "$key = :$key";
                }
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Obtener categorías activas
     * 
     * @param array $pagination Información de paginación
     * @return array Lista de categorías activas
     */
    public function getActiveCategories($pagination = null) {
        return $this->getAll(['is_active' => 1], $pagination);
    }
    
    /**
     * Verificar si una categoría tiene subcategorías asociadas
     * 
     * @param int $categoryId ID de la categoría
     * @return bool True si tiene subcategorías, false en caso contrario
     */
    public function hasSubcategories($categoryId = null) {
        $id = $categoryId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        $query = "SELECT COUNT(*) FROM subcategories WHERE category_id = :category_id";
        $count = $this->db->query($query, ['category_id' => $id])->fetchColumn();
        
        return $count > 0;
    }
    
    /**
     * Obtener subcategorías de una categoría
     * 
     * @param int $categoryId ID de la categoría
     * @param bool $onlyActive Solo devolver subcategorías activas
     * @return array Lista de subcategorías
     */
    public function getSubcategories($categoryId = null, $onlyActive = false) {
        $id = $categoryId ?? $this->id;
        
        if (!$id) {
            return [];
        }
        
        $query = "SELECT * FROM subcategories WHERE category_id = :category_id";
        $params = ['category_id' => $id];
        
        if ($onlyActive) {
            $query .= " AND is_active = 1";
        }
        
        $query .= " ORDER BY subcategory_name ASC";
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Establecer propiedades del modelo desde un array de datos
     * 
     * @param array $data Datos a establecer
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['category_id'] ?? null;
        $this->category_name = $data['category_name'] ?? null;
        $this->is_active = $data['is_active'] ?? null;
    }
    
    /**
     * Getters para propiedades privadas
     */
    public function getId() {
        return $this->id;
    }
    
    public function getCategoryName() {
        return $this->category_name;
    }
    
    public function isActive() {
        return (bool) $this->is_active;
    }

    /**
 * Obtener estadísticas de categorías para un evento específico
 * 
 * @param int $eventId ID del evento
 * @return array Estadísticas de categorías
 */
public function getEventStats($eventId) {
    $stats = [];
    
    // Obtener todas las categorías activas
    $categories = $this->getAll(['is_active' => 1]);
    
    foreach ($categories as $category) {
        $categoryId = $category['category_id'];
        $categoryName = $category['category_name'];
        
        // Obtener subcategorías para esta categoría
        $subcategories = $this->getSubcategories($categoryId);
        
        $buyerRequirements = 0;
        $supplierOffers = 0;
        
        foreach ($subcategories as $subcategory) {
            $subcategoryId = $subcategory['subcategory_id'];
            
            // Contar requerimientos de compradores
            $query = "SELECT COUNT(*) as count FROM requirements r 
                      JOIN company c ON r.buyer_id = c.company_id 
                      WHERE r.subcategory_id = :subcategory_id 
                      AND c.event_id = :event_id";
            
            $params = [
                ':subcategory_id' => $subcategoryId,
                ':event_id' => $eventId
            ];
            
            $result = $this->db->single($query, $params);
            $buyerRequirements += $result ? (int)$result['count'] : 0;
            
            // Contar ofertas de proveedores
            $query = "SELECT COUNT(*) as count FROM supplier_offers so 
                      JOIN company c ON so.supplier_id = c.company_id 
                      WHERE so.subcategory_id = :subcategory_id 
                      AND c.event_id = :event_id";
            
            $params = [
                ':subcategory_id' => $subcategoryId,
                ':event_id' => $eventId
            ];
            
            $result = $this->db->single($query, $params);
            $supplierOffers += $result ? (int)$result['count'] : 0;
        }
        
        $stats[] = [
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'buyer_requirements' => $buyerRequirements,
            'supplier_offers' => $supplierOffers,
            'match_potential' => min($buyerRequirements, $supplierOffers)
        ];
    }
    
    return $stats;
}

}