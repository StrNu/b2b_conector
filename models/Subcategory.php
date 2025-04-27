<?php
/**
 * Modelo de Subcategoría
 * 
 * Esta clase maneja todas las operaciones relacionadas con las subcategorías
 * que utilizan compradores y proveedores para establecer coincidencias.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class Subcategory {
    private $db;
    private $table = 'subcategories';
    
    // Propiedades que mapean a las columnas de la tabla subcategories
    private $id;
    private $subcategory_name;
    private $category_id;
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
     * Encontrar subcategoría por ID
     * 
     * @param int $id ID de la subcategoría a buscar
     * @return bool True si la subcategoría existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE subcategory_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar subcategoría por nombre
     * 
     * @param string $name Nombre de la subcategoría a buscar
     * @param int $categoryId ID de la categoría (opcional)
     * @return bool True si la subcategoría existe, false en caso contrario
     */
    public function findByName($name, $categoryId = null) {
        $query = "SELECT * FROM {$this->table} WHERE subcategory_name = :name";
        $params = ['name' => $name];
        
        if ($categoryId) {
            $query .= " AND category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        
        $query .= " LIMIT 1";
        $result = $this->db->single($query, $params);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si existe una subcategoría por nombre
     * 
     * @param string $name Nombre de la subcategoría a verificar
     * @param int $categoryId ID de la categoría (opcional)
     * @return bool True si la subcategoría existe, false en caso contrario
     */
    public function exists($name, $categoryId = null) {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE subcategory_name = :name";
        $params = ['name' => $name];
        
        if ($categoryId) {
            $query .= " AND category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear una nueva subcategoría
     * 
     * @param array $data Datos de la subcategoría a crear
     * @return bool|int ID de la subcategoría creada o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['subcategory_name']) || !isset($data['category_id'])) {
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
     * Actualizar datos de una subcategoría
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
                  WHERE subcategory_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar subcategoría
     * 
     * @param int $id ID de la subcategoría a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $subcategoryId = $id ?? $this->id;
        
        if (!$subcategoryId) {
            return false;
        }
        
        // Verificar que no existan requerimientos o ofertas asociadas
        if ($this->hasRequirements($subcategoryId) || $this->hasOffers($subcategoryId)) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE subcategory_id = :id";
        return $this->db->query($query, ['id' => $subcategoryId]) ? true : false;
    }
    
    /**
     * Obtener todas las subcategorías
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de subcategorías
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT s.*, c.category_name 
                  FROM {$this->table} s
                  JOIN categories c ON s.category_id = c.category_id";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'subcategory_name' && strpos($value, '%') !== false) {
                    $conditions[] = "s.$key LIKE :$key";
                } else {
                    $conditions[] = "s.$key = :$key";
                }
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY c.category_name ASC, s.subcategory_name ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de subcategorías
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de subcategorías
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table} s";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'subcategory_name' && strpos($value, '%') !== false) {
                    $conditions[] = "s.$key LIKE :$key";
                } else {
                    $conditions[] = "s.$key = :$key";
                }
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Obtener subcategorías por categoría
     * 
     * @param int $categoryId ID de la categoría
     * @param bool $onlyActive Solo devolver subcategorías activas
     * @param array $pagination Información de paginación
     * @return array Lista de subcategorías
     */
    public function getByCategory($categoryId, $onlyActive = false, $pagination = null) {
        $filters = ['category_id' => $categoryId];
        
        if ($onlyActive) {
            $filters['is_active'] = 1;
        }
        
        return $this->getAll($filters, $pagination);
    }
    
    /**
     * Obtener subcategorías activas
     * 
     * @param array $pagination Información de paginación
     * @return array Lista de subcategorías activas
     */
    public function getActiveSubcategories($pagination = null) {
        return $this->getAll(['is_active' => 1], $pagination);
    }
    
    /**
     * Verificar si una subcategoría tiene requerimientos asociados
     * 
     * @param int $subcategoryId ID de la subcategoría
     * @return bool True si tiene requerimientos, false en caso contrario
     */
    public function hasRequirements($subcategoryId = null) {
        $id = $subcategoryId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        $query = "SELECT COUNT(*) FROM requirements WHERE subcategory_id = :subcategory_id";
        $count = $this->db->query($query, ['subcategory_id' => $id])->fetchColumn();
        
        return $count > 0;
    }
    
    /**
     * Verificar si una subcategoría tiene ofertas asociadas
     * 
     * @param int $subcategoryId ID de la subcategoría
     * @return bool True si tiene ofertas, false en caso contrario
     */
    public function hasOffers($subcategoryId = null) {
        $id = $subcategoryId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        $query = "SELECT COUNT(*) FROM supplier_offers WHERE subcategory_id = :subcategory_id";
        $count = $this->db->query($query, ['subcategory_id' => $id])->fetchColumn();
        
        return $count > 0;
    }
    
    /**
     * Obtener información de la categoría a la que pertenece la subcategoría
     * 
     * @param int $subcategoryId ID de la subcategoría
     * @return array|false Datos de la categoría o false si no existe
     */
    public function getCategory($subcategoryId = null) {
        $id = $subcategoryId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        // Primero obtener el category_id de la subcategoría
        if ($subcategoryId === null && $this->category_id) {
            $categoryId = $this->category_id;
        } else {
            $query = "SELECT category_id FROM {$this->table} WHERE subcategory_id = :id LIMIT 1";
            $categoryId = $this->db->query($query, ['id' => $id])->fetchColumn();
            
            if (!$categoryId) {
                return false;
            }
        }
        
        // Luego obtener los datos de la categoría
        $query = "SELECT * FROM categories WHERE category_id = :category_id LIMIT 1";
        return $this->db->single($query, ['category_id' => $categoryId]);
    }
    
    /**
     * Establecer propiedades del modelo desde un array de datos
     * 
     * @param array $data Datos a establecer
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['subcategory_id'] ?? null;
        $this->subcategory_name = $data['subcategory_name'] ?? null;
        $this->category_id = $data['category_id'] ?? null;
        $this->is_active = $data['is_active'] ?? null;
    }
    
    /**
     * Getters para propiedades privadas
     */
    public function getId() {
        return $this->id;
    }
    
    public function getSubcategoryName() {
        return $this->subcategory_name;
    }
    
    public function getCategoryId() {
        return $this->category_id;
    }
    
    public function isActive() {
        return (bool) $this->is_active;
    }
}