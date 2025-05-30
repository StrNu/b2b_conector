<?php
/**
 * Modelo de Match
 * 
 * Esta clase maneja todas las operaciones relacionadas con los matches (coincidencias)
 * entre compradores y proveedores, incluyendo la generación automática, consulta,
 * actualización y eliminación de coincidencias para eventos de networking.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class MatchModel {
    private $db;
    private $table = 'matches';
    
    // Propiedades que mapean a las columnas de la tabla matches
    private $id;
    private $buyer_id;
    private $supplier_id;
    private $event_id;
    private $match_strength;
    private $created_at;
    private $status;
    private $matched_categories; // JSON

    // Estados posibles para un match
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar match por ID
     * 
     * @param int $id ID del match a buscar
     * @return bool True si el match existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE match_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }

    /**
     * Verificar si ya existe un match entre comprador y proveedor para un evento
     * 
     * @param int $buyerId ID del comprador
     * @param int $supplierId ID del proveedor
     * @param int $eventId ID del evento
     * @return bool True si el match ya existe
     */
    public function exists($buyerId, $supplierId, $eventId) {
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE buyer_id = :buyer_id 
                  AND supplier_id = :supplier_id 
                  AND event_id = :event_id";
        
        $params = [
            'buyer_id' => $buyerId,
            'supplier_id' => $supplierId,
            'event_id' => $eventId
        ];
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear un nuevo match
     * 
     * @param array $data Datos del match a crear
     * @return bool|int ID del match creado o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['buyer_id'], $data['supplier_id'], $data['event_id'])) {
            return false;
        }
        
        // Verificar si ya existe este match
        if ($this->exists($data['buyer_id'], $data['supplier_id'], $data['event_id'])) {
            return false;
        }
        
        // Establecer valores por defecto si no están presentes
        if (!isset($data['status'])) {
            $data['status'] = self::STATUS_PENDING;
        }
        
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Si no se proporciona match_strength, calcularlo
        if (!isset($data['match_strength']) && isset($data['matched_categories'])) {
            $categories = json_decode($data['matched_categories'], true);
            $data['match_strength'] = count($categories);
        } elseif (!isset($data['match_strength'])) {
            $data['match_strength'] = 1; // Valor por defecto
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
     * Actualizar datos de un match
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
                  WHERE match_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar match
     * 
     * @param int $id ID del match a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $matchId = $id ?? $this->id;
        
        if (!$matchId) {
            return false;
        }
        
        // Eliminar agendas relacionadas con este match
        $query = "DELETE FROM event_schedules WHERE match_id = :match_id";
        $this->db->query($query, ['match_id' => $matchId]);
        
        // Eliminar el match
        $query = "DELETE FROM {$this->table} WHERE match_id = :id";
        return $this->db->query($query, ['id' => $matchId]) ? true : false;
    }
    
    /**
     * Obtener todos los matches
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de matches
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT m.*, 
                  b.company_name as buyer_name, 
                  s.company_name as supplier_name 
                  FROM {$this->table} m
                  JOIN company b ON m.buyer_id = b.company_id 
                  JOIN company s ON m.supplier_id = s.company_id";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                $conditions[] = "m.$key = :$key";
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY m.match_strength DESC, m.created_at DESC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de matches
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de matches
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table} m";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                $conditions[] = "m.$key = :$key";
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Obtener matches por evento
     * 
     * @param int $eventId ID del evento
     * @param string $status Estado del match (opcional)
     * @param array $pagination Información de paginación
     * @return array Lista de matches para el evento
     */
    public function getByEvent($eventId, $status = null, $pagination = null) {
        $filters = ['event_id' => $eventId];
        
        if ($status) {
            $filters['status'] = $status;
        }
        
        return $this->getAll($filters, $pagination);
    }
    
    /**
     * Obtener matches por comprador
     * 
     * @param int $buyerId ID del comprador
     * @param int $eventId ID del evento (opcional)
     * @param string $status Estado del match (opcional)
     * @param array $pagination Información de paginación
     * @return array Lista de matches para el comprador
     */
    public function getByBuyer($buyerId, $eventId = null, $status = null, $pagination = null) {
        $filters = ['buyer_id' => $buyerId];
        
        if ($eventId) {
            $filters['event_id'] = $eventId;
        }
        
        if ($status) {
            $filters['status'] = $status;
        }
        
        return $this->getAll($filters, $pagination);
    }
    
    /**
     * Obtener matches por proveedor
     * 
     * @param int $supplierId ID del proveedor
     * @param int $eventId ID del evento (opcional)
     * @param string $status Estado del match (opcional)
     * @param array $pagination Información de paginación
     * @return array Lista de matches para el proveedor
     */
    public function getBySupplier($supplierId, $eventId = null, $status = null, $pagination = null) {
        $filters = ['supplier_id' => $supplierId];
        
        if ($eventId) {
            $filters['event_id'] = $eventId;
        }
        
        if ($status) {
            $filters['status'] = $status;
        }
        
        return $this->getAll($filters, $pagination);
    }
    
    /**
     * Actualizar estado de un match
     * 
     * @param int $matchId ID del match
     * @param string $status Nuevo estado (pending, accepted, rejected)
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function updateStatus($matchId, $status) {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_REJECTED])) {
            return false;
        }
        
        if ($this->findById($matchId)) {
            return $this->update(['status' => $status]);
        }
        
        return false;
    }
    
    /**
     * Generar matches automáticos para un evento
     * 
     * @param int $eventId ID del evento
     * @param array $options Opciones adicionales (forceRegenerate, buyerId, supplierId)
     * @return array Resultado de la generación (total, nuevos, existentes)
     */
    public function generateMatches($eventId, $options = []) {
        // Verificar si el evento existe
        $event = new Event($this->db);
        if (!$event->findById($eventId)) {
            return [
                'success' => false,
                'message' => 'El evento no existe',
                'total' => 0,
                'new' => 0,
                'existing' => 0
            ];
        }
        
        // Iniciar transacción para garantizar integridad SOLO si no hay una activa
        $startedTransaction = false;
        if (method_exists($this->db, 'inTransaction') && !$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $startedTransaction = true;
        }
        
        try {
            // Opciones por defecto
            $forceRegenerate = $options['forceRegenerate'] ?? false;
            $specificBuyerId = $options['buyerId'] ?? null;
            $specificSupplierId = $options['supplierId'] ?? null;
            
            // Si se solicita regenerar, eliminar matches existentes
            if ($forceRegenerate) {
                $deleteQuery = "DELETE FROM {$this->table} WHERE event_id = :event_id";
                $params = ['event_id' => $eventId];
                
                if ($specificBuyerId) {
                    $deleteQuery .= " AND buyer_id = :buyer_id";
                    $params['buyer_id'] = $specificBuyerId;
                }
                
                if ($specificSupplierId) {
                    $deleteQuery .= " AND supplier_id = :supplier_id";
                    $params['supplier_id'] = $specificSupplierId;
                }
                
                $this->db->query($deleteQuery, $params);
            }
            
            // Obtener compradores del evento
            $company = new Company($this->db);
            $buyersQuery = "SELECT * FROM company WHERE event_id = :event_id AND role = 'buyer'";
            $buyersParams = ['event_id' => $eventId];
            
            if ($specificBuyerId) {
                $buyersQuery .= " AND company_id = :buyer_id";
                $buyersParams['buyer_id'] = $specificBuyerId;
            }
            
            $buyers = $this->db->resultSet($buyersQuery, $buyersParams);
            
            // Obtener proveedores del evento
            $suppliersQuery = "SELECT * FROM company WHERE event_id = :event_id AND role = 'supplier'";
            $suppliersParams = ['event_id' => $eventId];
            
            if ($specificSupplierId) {
                $suppliersQuery .= " AND company_id = :supplier_id";
                $suppliersParams['supplier_id'] = $specificSupplierId;
            }
            
            $suppliers = $this->db->resultSet($suppliersQuery, $suppliersParams);
            
            // Contador de matches
            $newMatches = 0;
            $existingMatches = 0;
            
            // Procesar cada comprador
            foreach ($buyers as $buyer) {
                $buyerId = $buyer['company_id'];
                // Obtener requerimientos del comprador
                $buyerRequirementsQuery = "SELECT r.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                                          FROM requirements r
                                          JOIN event_subcategories s ON r.event_subcategory_id = s.event_subcategory_id
                                          JOIN event_categories c ON s.event_category_id = c.event_category_id
                                          WHERE r.buyer_id = :buyer_id";
                $buyerRequirements = $this->db->resultSet($buyerRequirementsQuery, ['buyer_id' => $buyerId]);
                // LOG: requerimientos del comprador
                Logger::debug('[MATCHGEN] Buyer ' . $buyerId . ' requirements', ['buyer' => $buyer, 'requirements' => $buyerRequirements]);
                if (empty($buyerRequirements)) {
                    continue;
                }
                foreach ($suppliers as $supplier) {
                    $supplierId = $supplier['company_id'];
                    if (!$forceRegenerate && $this->exists($buyerId, $supplierId, $eventId)) {
                        $existingMatches++;
                        continue;
                    }
                    // Obtener ofertas del proveedor
                    $supplierOffersQuery = "SELECT so.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                                           FROM supplier_offers so
                                           JOIN event_subcategories s ON so.event_subcategory_id = s.event_subcategory_id
                                           JOIN event_categories c ON s.event_category_id = c.event_category_id
                                           WHERE so.supplier_id = :supplier_id";
                    $supplierOffers = $this->db->resultSet($supplierOffersQuery, ['supplier_id' => $supplierId]);
                    // LOG: ofertas del proveedor
                    Logger::debug('[MATCHGEN] Supplier ' . $supplierId . ' offers', ['supplier' => $supplier, 'offers' => $supplierOffers]);
                    if (empty($supplierOffers)) {
                        continue;
                    }
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
                    // LOG: coincidencias encontradas
                    Logger::debug('[MATCHGEN] Buyer ' . $buyerId . ' - Supplier ' . $supplierId . ' matched categories', ['matched' => $matchedCategories]);
                    if (!empty($matchedCategories)) {
                        $matchData = [
                            'buyer_id' => $buyerId,
                            'supplier_id' => $supplierId,
                            'event_id' => $eventId,
                            'match_strength' => count($matchedCategories),
                            'status' => self::STATUS_PENDING,
                            'created_at' => date('Y-m-d H:i:s'),
                            'matched_categories' => json_encode($matchedCategories)
                        ];
                        if ($this->create($matchData)) {
                            $newMatches++;
                        }
                    }
                }
            }
            
            // Confirmar transacción
            if ($startedTransaction) {
                $this->db->commit();
            }
            return [
                'success' => true,
                'message' => 'Matches generados exitosamente',
                'total' => $newMatches + $existingMatches,
                'new' => $newMatches,
                'existing' => $existingMatches
            ];
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error SOLO si la iniciamos aquí
            if ($startedTransaction) {
                $this->db->rollback();
            }
            return [
                'success' => false,
                'message' => 'Error al generar matches: ' . $e->getMessage(),
                'total' => 0,
                'new' => 0,
                'existing' => 0
            ];
        }
    }
    
    /**
     * Generar un match manual entre un comprador y un proveedor
     * 
     * @param int $buyerId ID del comprador
     * @param int $supplierId ID del proveedor
     * @param int $eventId ID del evento
     * @param array $categories Categorías coincidentes (opcional)
     * @return bool|int ID del match creado o false en caso de error
     */
    public function createManualMatch($buyerId, $supplierId, $eventId, $categories = []) {
        // Verificar que el comprador y proveedor existan y pertenezcan al evento
        $buyerQuery = "SELECT * FROM company WHERE company_id = :id AND role = 'buyer' AND event_id = :event_id";
        $supplierQuery = "SELECT * FROM company WHERE company_id = :id AND role = 'supplier' AND event_id = :event_id";
        
        $buyerExists = $this->db->single($buyerQuery, ['id' => $buyerId, 'event_id' => $eventId]);
        $supplierExists = $this->db->single($supplierQuery, ['id' => $supplierId, 'event_id' => $eventId]);
        
        if (!$buyerExists || !$supplierExists) {
            return false;
        }
        
        // Si no se proporcionan categorías, intentar encontrar coincidencias automáticamente
        if (empty($categories)) {
            $buyerRequirementsQuery = "SELECT r.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                                      FROM requirements r
                                      JOIN event_subcategories s ON r.event_subcategory_id = s.event_subcategory_id
                                      JOIN event_categories c ON s.event_category_id = c.event_category_id
                                      WHERE r.buyer_id = :buyer_id";
            
            $supplierOffersQuery = "SELECT so.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                                   FROM supplier_offers so
                                   JOIN event_subcategories s ON so.event_subcategory_id = s.event_subcategory_id
                                   JOIN event_categories c ON s.event_category_id = c.event_category_id
                                   WHERE so.supplier_id = :supplier_id";
            
            $buyerRequirements = $this->db->resultSet($buyerRequirementsQuery, ['buyer_id' => $buyerId]);
            $supplierOffers = $this->db->resultSet($supplierOffersQuery, ['supplier_id' => $supplierId]);
            
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
            
            $categories = $matchedCategories;
        }
        
        // Crear el match
        $matchData = [
            'buyer_id' => $buyerId,
            'supplier_id' => $supplierId,
            'event_id' => $eventId,
            'match_strength' => count($categories),
            'status' => self::STATUS_ACCEPTED, // Los matches manuales se crean como aceptados
            'created_at' => date('Y-m-d H:i:s'),
            'matched_categories' => json_encode($categories)
        ];
        
        return $this->create($matchData);
    }
    
    /**
     * Calcular la fuerza de coincidencia entre un comprador y un proveedor
     * 
     * @param int $buyerId ID del comprador
     * @param int $supplierId ID del proveedor
     * @return array Información de coincidencia (strength, categories)
     */
    public function calculateMatchStrength($buyerId, $supplierId) {
        // Obtener requerimientos del comprador
        $buyerRequirementsQuery = "SELECT r.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                                  FROM requirements r
                                  JOIN event_subcategories s ON r.event_subcategory_id = s.event_subcategory_id
                                  JOIN event_categories c ON s.event_category_id = c.event_category_id
                                  WHERE r.buyer_id = :buyer_id";
        
        // Obtener ofertas del proveedor
        $supplierOffersQuery = "SELECT so.*, s.name as subcategory_name, c.name as category_name, c.event_category_id as category_id 
                               FROM supplier_offers so
                               JOIN event_subcategories s ON so.event_subcategory_id = s.event_subcategory_id
                               JOIN event_categories c ON s.event_category_id = c.event_category_id
                               WHERE so.supplier_id = :supplier_id";
        
        $buyerRequirements = $this->db->resultSet($buyerRequirementsQuery, ['buyer_id' => $buyerId]);
        $supplierOffers = $this->db->resultSet($supplierOffersQuery, ['supplier_id' => $supplierId]);
        
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
        
        return [
            'strength' => count($matchedCategories),
            'categories' => $matchedCategories
        ];
    }
    
    /**
     * Establecer propiedades del modelo desde un array de datos
     * 
     * @param array $data Datos a establecer
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['match_id'] ?? null;
        $this->buyer_id = $data['buyer_id'] ?? null;
        $this->supplier_id = $data['supplier_id'] ?? null;
        $this->event_id = $data['event_id'] ?? null;
        $this->match_strength = $data['match_strength'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->matched_categories = $data['matched_categories'] ?? null;
    }

    /**
 * Calcular tasa de éxito de matches
 * 
 * @return float Porcentaje de matches aceptados (0-100)
 */
public function calculateSuccessRate() {
    $totalMatches = $this->count(['status' => ['pending', 'accepted', 'rejected']]);
    if ($totalMatches == 0) {
        return 0;
    }
    
    $acceptedMatches = $this->count(['status' => 'accepted']);
    return round(($acceptedMatches / $totalMatches) * 100, 2);
}

/**
 * Calcular tasa de éxito de matches para un evento específico
 * 
 * @param int $eventId ID del evento
 * @return float Porcentaje de matches aceptados (0-100)
 */
public function calculateEventSuccessRate($eventId) {
    $totalMatches = $this->count(['event_id' => $eventId, 'status' => ['pending', 'accepted', 'rejected']]);
    if ($totalMatches == 0) {
        return 0;
    }
    
    $acceptedMatches = $this->count(['event_id' => $eventId, 'status' => 'accepted']);
    return round(($acceptedMatches / $totalMatches) * 100, 2);
}

/**
 * Contar matches que no tienen cita programada
 * 
 * @return int Número de matches sin cita
 */
public function countMatchesWithoutSchedule() {
    // Usar el método robusto ya implementado
    return $this->countWithoutSchedule();
}

/**
 * Contar matches que no tienen cita programada (implementación robusta)
 * 
 * @return int Número de matches sin cita
 */
public function countWithoutSchedule() {
    try {
        if (!$this->db) {
            Logger::error("Error en countWithoutSchedule: La conexión a la base de datos es null");
            return 0;
        }
        $matchesWithoutSchedule = 0;
        $query = "SELECT * FROM matches WHERE status = 'accepted'";
        $acceptedMatches = $this->db->resultSet($query);
        if (!$acceptedMatches) {
            return 0;
        }
        $appointmentModel = new Appointment($this->db);
        foreach ($acceptedMatches as $match) {
            $matchId = $match['match_id'];
            $query = "SELECT COUNT(*) as count FROM appointments WHERE match_id = :match_id";
            $result = $this->db->single($query, [':match_id' => $matchId]);
            if ($result && $result['count'] == 0) {
                $matchesWithoutSchedule++;
            }
        }
        return $matchesWithoutSchedule;
    } catch (Exception $e) {
        Logger::exception($e, ['context' => 'MatchModel::countWithoutSchedule']);
        return 0;
    }
}

/**
 * Contar matches que no tienen participantes asignados
 * 
 * @return int Número de matches sin participantes
 */
public function countWithoutParticipants() {
    $count = 0;
    $allMatches = $this->getAll();
    
    foreach ($allMatches as $match) {
        // Un match sin participantes sería uno donde falta el comprador o el proveedor
        if (empty($match['buyer_id']) || empty($match['supplier_id'])) {
            $count++;
        }
    }
    
    return $count;
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
    
    public function getSupplierId() {
        return $this->supplier_id;
    }
    
    public function getEventId() {
        return $this->event_id;
    }
    
    public function getMatchStrength() {
        return $this->match_strength;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getMatchedCategories() {
        return $this->matched_categories;
    }
    
    /**
     * Obtener array de categorías coincidentes decodificado
     * 
     * @return array Categorías coincidentes
     */
    public function getMatchedCategoriesArray() {
        return json_decode($this->matched_categories, true) ?? [];
    }
}