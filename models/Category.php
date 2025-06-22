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

    // === EVENT CATEGORIES & SUBCATEGORIES ===
    public function getEventCategories($eventId) {
        $query = "SELECT * FROM event_categories WHERE event_id = :event_id ORDER BY name";
        return $this->db->resultSet($query, [':event_id' => $eventId]);
    }
    public function getEventCategoryByName($eventId, $name) {
        $query = "SELECT * FROM event_categories WHERE event_id = :event_id AND name = :name LIMIT 1";
        return $this->db->single($query, [':event_id' => $eventId, ':name' => $name]);
    }
    public function addEventCategory($eventId, $name,  $isActive = 1) {
        $query = "INSERT INTO event_categories (event_id, name, is_active) VALUES (:event_id, :name, :is_active)";
        $this->db->query($query, [
            ':event_id' => $eventId,
            ':name' => $name,
            ':is_active' => $isActive
        ]);
        return $this->db->lastInsertId();
    }
    public function addEventSubcategory($categoryId, $name, $isActive = 1) {
        $query = "INSERT INTO event_subcategories (event_category_id, name, is_active) VALUES (:category_id, :name, :is_active)";
        $this->db->query($query, [
            ':category_id' => $categoryId,
            ':name' => $name,
            ':is_active' => $isActive
        ]);
        return $this->db->lastInsertId();
    }
    public function getEventSubcategories($categoryId) {
        $query = "SELECT * FROM event_subcategories WHERE event_category_id = :category_id ORDER BY name";
        return $this->db->resultSet($query, [':category_id' => $categoryId]);
    }

    // Obtener una subcategoría de evento por su ID
    public function getEventSubcategory($subcategoryId) {
        $query = "SELECT * FROM event_subcategories WHERE event_subcategory_id = :id LIMIT 1";
        return $this->db->single($query, [':id' => $subcategoryId]);
    }

    // Obtener una categoría de evento por su ID
    public function getEventCategory($categoryId) {
        Logger::debug('[getEventCategory] Llamado con categoryId: ' . var_export($categoryId, true));
        $query = "SELECT * FROM event_categories WHERE event_category_id = :id LIMIT 1";
        $result = $this->db->single($query, [':id' => $categoryId]);
        Logger::debug('[getEventCategory] Resultado de single: ' . var_export($result, true));
        if (!$result || !is_array($result) || empty($result)) {
            Logger::debug('[getEventCategory] No se encontró la categoría.');
            return null;
        }
        return $result;
    }

    // Eliminar una subcategoría de evento por su ID
    public function deleteEventSubcategory($subcategoryId) {
        $query = "DELETE FROM event_subcategories WHERE event_subcategory_id = :id";
        return $this->db->query($query, [':id' => $subcategoryId]);
    }

    // Eliminar una categoría de evento por su ID
    public function deleteEventCategory($categoryId) {
        // Primero eliminar subcategorías asociadas
        $this->db->query("DELETE FROM event_subcategories WHERE event_category_id = :category_id", [':category_id' => $categoryId]);
        // Luego eliminar la categoría
        $query = "DELETE FROM event_categories WHERE event_category_id = :id";
        return $this->db->query($query, [':id' => $categoryId]);
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

/**
 * Editar una categoría de evento por su ID
 * @param int $categoryId
 * @param array $data
 * @return bool
 */
public function editEventCategory($categoryId, $data) {
    if (empty($categoryId) || empty($data) || !is_array($data)) {
        return false;
    }
    $fields = [];
    $params = [':id' => $categoryId];
    foreach ($data as $key => $value) {
        $fields[] = "$key = :$key";
        $params[":$key"] = $value;
    }
    $query = "UPDATE event_categories SET " . implode(', ', $fields) . " WHERE event_category_id = :id";
    return $this->db->query($query, $params);
}

/**
 * Editar una subcategoría de evento por su ID
 * @param int $subcategoryId
 * @param array $data
 * @return bool
 */
public function editEventSubcategory($subcategoryId, $data) {
    if (empty($subcategoryId) || empty($data) || !is_array($data)) {
        return false;
    }
    $fields = [];
    $params = [':id' => $subcategoryId];
    foreach ($data as $key => $value) {
        $fields[] = "$key = :$key";
        $params[":$key"] = $value;
    }
    $query = "UPDATE event_subcategories SET " . implode(', ', $fields) . " WHERE event_subcategory_id = :id";
    return $this->db->query($query, $params);
}

/**
     * Actualiza el nombre de una subcategoría de evento por su ID
     * @param int $subcategoryId
     * @param string $name
     * @return bool
     */
    public function updateEventSubcategory($subcategoryId, $name) {
        if (empty($subcategoryId) || $name === '' || $name === null) {
            return false;
        }
        $query = "UPDATE event_subcategories SET name = :name WHERE event_subcategory_id = :id";
        return $this->db->query($query, [':name' => $name, ':id' => $subcategoryId]);
    }

    /**
     * Actualiza el nombre de una categoría de evento por su ID
     * @param int $categoryId
     * @param string $name
     * @return bool
     */
    public function updateEventCategory($categoryId, $name) {
        if (empty($categoryId) || $name === '' || $name === null) {
            return false;
        }
        $query = "UPDATE event_categories SET name = :name WHERE event_category_id = :id";
        return $this->db->query($query, [':name' => $name, ':id' => $categoryId]);
    }

    // Buscar o crear una categoría de evento para un evento dado
    public function findOrCreateEventCategory($eventId, $categoryId, $name) {
        $query = "SELECT event_category_id FROM event_categories WHERE event_id = :event_id AND category_id = :category_id LIMIT 1";
        $params = [':event_id' => $eventId, ':category_id' => $categoryId];
        $result = $this->db->single($query, $params);
        if ($result && isset($result['event_category_id'])) {
            return $result['event_category_id'];
        }
        // Insertar si no existe
        $insert = "INSERT INTO event_categories (event_id, category_id, name, is_active) VALUES (:event_id, :category_id, :name, 1)";
        $this->db->query($insert, [':event_id' => $eventId, ':category_id' => $categoryId, ':name' => $name]);
        return $this->db->lastInsertId();
    }

    // Buscar o crear una subcategoría de evento
    public function findOrCreateEventSubcategory($eventCategoryId, $subcategoryId, $name) {
        $query = "SELECT event_subcategory_id FROM event_subcategories WHERE event_category_id = :event_category_id AND subcategory_id = :subcategory_id LIMIT 1";
        $params = [':event_category_id' => $eventCategoryId, ':subcategory_id' => $subcategoryId];
        $result = $this->db->single($query, $params);
        if ($result && isset($result['event_subcategory_id'])) {
            return $result['event_subcategory_id'];
        }
        // Insertar si no existe
        $insert = "INSERT INTO event_subcategories (event_category_id, subcategory_id, name, is_active) VALUES (:event_category_id, :subcategory_id, :name, 1)";
        $this->db->query($insert, [':event_category_id' => $eventCategoryId, ':subcategory_id' => $subcategoryId, ':name' => $name]);
        return $this->db->lastInsertId();
    }

    /**
     * Obtener categorías de un evento con sus subcategorías (centralizado)
     * @param int $eventId
     * @return array
     */
    public function getEventCategoriesWithSubcategories($eventId) {
        $categories = $this->getEventCategories($eventId);
        $categoriesWithSubcategories = [];
        foreach ($categories as $category) {
            $subcategories = $this->getEventSubcategories($category['event_category_id']);
            $categoriesWithSubcategories[] = [
                'category' => $category,
                'subcategories' => $subcategories
            ];
        }
        return $categoriesWithSubcategories;
    }

}