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
    const STATUS_MATCHED = 'matched';
    
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
            'supplier_id' => $SupplierId,
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
        
        // Si no se proporciona match_strength, calcularlo como porcentaje
        if (!isset($data['match_strength']) && isset($data['matched_categories']) && isset($data['buyer_id'])) {
            $categories = json_decode($data['matched_categories'], true);
            $buyerRequirementsQuery = "SELECT COUNT(*) as total FROM requirements WHERE buyer_id = :buyer_id";
            $result = $this->db->single($buyerRequirementsQuery, ['buyer_id' => $data['buyer_id']]);
            $totalBuyerSubcats = $result && isset($result['total']) ? (int)$result['total'] : 0;
            $matchStrength = $totalBuyerSubcats > 0 ? round((count($categories) / $totalBuyerSubcats) * 100) : 0;
            $data['match_strength'] = $matchStrength;
        } elseif (!isset($data['match_strength'])) {
            $data['match_strength'] = 0; // Valor por defecto
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
     * Elimina todos los matches existentes de un evento
     * @param int $eventId
     * @return bool
     */
    public function deleteAllByEvent($eventId) {
        if (!$eventId) return false;
        $query = "DELETE FROM {$this->table} WHERE event_id = :event_id";
        return $this->db->query($query, ['event_id' => $eventId]);
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
            $status = isset($options['status']) ? $options['status'] : self::STATUS_MATCHED;
            
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
                Logger::debug('[MATCHGEN] Buyer ' . $buyerId . ' requirements', ['buyer' => $buyer, 'requirements' => $buyerRequirements]);
                if (empty($buyerRequirements)) {
                    continue;
                }
                $totalBuyerSubcats = count($buyerRequirements);
                // Subcategorías del comprador
                $buyerSubcategories = array_map(function($r) { return $r['event_subcategory_id']; }, $buyerRequirements);
                // Fechas del comprador
                $buyerDatesArr = $this->db->resultSet("SELECT attendance_date FROM attendance_days WHERE event_id = :event_id AND company_id = :company_id", [
                    'event_id' => $eventId,
                    'company_id' => $buyerId
                ]);
                $buyerDates = array_map(function($r) { return $r['attendance_date']; }, $buyerDatesArr);
                // Keywords y descripción del comprador
                $buyer_keywords = $buyer['keywords'] ?? null;
                $buyer_description = $buyer['description'] ?? null;

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
                    Logger::debug('[MATCHGEN] Buyer ' . $buyerId . ' - Supplier ' . $supplierId . ' matched categories', ['matched' => $matchedCategories]);
                    if (!empty($matchedCategories)) {
                        $matchStrength = $totalBuyerSubcats > 0 ? round((count($matchedCategories) / $totalBuyerSubcats) * 100) : 0;
                        // Subcategorías del proveedor
                        $supplierSubcategories = array_map(function($o) { return $o['event_subcategory_id']; }, $supplierOffers);
                        // Fechas del proveedor
                        $supplierDatesArr = $this->db->resultSet("SELECT attendance_date FROM attendance_days WHERE event_id = :event_id AND company_id = :company_id", [
                            'event_id' => $eventId,
                            'company_id' => $supplierId
                        ]);
                        $supplierDates = array_map(function($r) { return $r['attendance_date']; }, $supplierDatesArr);
                        // Keywords y descripción del proveedor
                        $supplier_keywords = $supplier['keywords'] ?? null;
                        $supplier_description = $supplier['description'] ?? null;
                        // Fechas coincidentes
                        $commonDates = array_intersect($buyerDates, $supplierDates);
                        $coincidence_of_dates = !empty($commonDates) ? implode(',', $commonDates) : null;
                        // Reason (puedes ajustar la lógica según tus reglas)
                        $reason = !empty($commonDates) ? 'subcategoria_y_fecha' : 'subcategoria_sin_dias_comunes';
                        // Keywords match (intersección de keywords si ambas existen y son JSON)
                        $keywords_match = null;
                        if ($buyer_keywords && $supplier_keywords) {
                            $bk = json_decode($buyer_keywords, true);
                            $sk = json_decode($supplier_keywords, true);
                            if (is_array($bk) && is_array($sk)) {
                                $keywords_match = array_values(array_intersect($bk, $sk));
                            }
                        }
                        $matchData = [
                            'buyer_id' => $buyerId,
                            'supplier_id' => $supplierId,
                            'event_id' => $eventId,
                            'match_strength' => $matchStrength,
                            'status' => $status,
                            'created_at' => date('Y-m-d H:i:s'),
                            'matched_categories' => json_encode($matchedCategories),
                            'buyer_subcategories' => json_encode($buyerSubcategories),
                            'supplier_subcategories' => json_encode($supplierSubcategories),
                            'buyer_dates' => implode(',', $buyerDates),
                            'supplier_dates' => implode(',', $supplierDates),
                            'buyer_keywords' => $buyer_keywords,
                            'supplier_keywords' => $supplier_keywords,
                            'buyer_description' => $buyer_description,
                            'supplier_description' => $supplier_description,
                            'reason' => $reason,
                            'keywords_match' => $keywords_match ? json_encode($keywords_match) : null,
                            'coincidence_of_dates' => $coincidence_of_dates
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
        // Calcular porcentaje de match_strength
        $totalBuyerSubcats = isset($buyerRequirements) ? count($buyerRequirements) : 0;
        $matchStrength = $totalBuyerSubcats > 0 ? round((count($categories) / $totalBuyerSubcats) * 100) : 0;
        // Crear el match
        $matchData = [
            'buyer_id' => $buyerId,
            'supplier_id' => $supplierId,
            'event_id' => $eventId,
            'match_strength' => $matchStrength,
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

    /**
     * Guardar o actualizar estadísticas de un evento en la tabla event_statistics
     * @param int $eventId
     * @param array $stats Debe tener claves: keywords, categories, subcategories, descriptions (todas arrays)
     * @return bool
     */
    public function saveEventStatistics($eventId, $stats) {
        // Convertir arrays a JSON
        $keywords = json_encode($stats['keywords'] ?? []);
        $categories = json_encode($stats['categories'] ?? []);
        $subcategories = json_encode($stats['subcategories'] ?? []);
        $descriptions = json_encode($stats['descriptions'] ?? []);
        // Verificar si ya existe
        $exists = $this->db->single("SELECT id FROM event_statistics WHERE event_id = :event_id", ['event_id' => $eventId]);
        if ($exists) {
            // Update
            $sql = "UPDATE event_statistics SET keywords = :keywords, categories = :categories, subcategories = :subcategories, descriptions = :descriptions, updated_at = NOW() WHERE event_id = :event_id";
        } else {
            // Insert
            $sql = "INSERT INTO event_statistics (event_id, keywords, categories, subcategories, descriptions, created_at) VALUES (:event_id, :keywords, :categories, :subcategories, :descriptions, NOW())";
        }
        $params = [
            'event_id' => $eventId,
            'keywords' => $keywords,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'descriptions' => $descriptions
        ];
        return $this->db->query($sql, $params) !== false;
    }

    /**
     * Obtener estadísticas de un evento desde la tabla event_statistics
     * @param int $eventId
     * @return array|null
     */
    public function getEventStatistics($eventId) {
        $row = $this->db->single("SELECT * FROM event_statistics WHERE event_id = :event_id", ['event_id' => $eventId]);
        if (!$row) return null;
        return [
            'keywords' => json_decode($row['keywords'] ?? '[]', true),
            'categories' => json_decode($row['categories'] ?? '[]', true),
            'subcategories' => json_decode($row['subcategories'] ?? '[]', true),
            'descriptions' => json_decode($row['descriptions'] ?? '[]', true),
        ];
    }


    /**
     * Obtener todos los matches de un evento, agregando todas las subcategorías y fechas coincidentes por match (para Matches encontrados)
     * Solo devuelve matches con al menos una subcategoría coincidente y al menos una fecha coincidente.
     * @param int $eventId
     * @return array
     */
    public function getAllMatchesWithSubcategoriesByEvent($eventId) {
        $sql = "
        SELECT
            m.match_id,
            m.buyer_id,
            b.company_name AS buyer_name,
            m.supplier_id,
            s.company_name AS supplier_name,
            m.match_strength,
            m.status,
            m.created_at,
            m.matched_categories,
            m.buyer_subcategories,
            m.supplier_subcategories,
            m.buyer_dates,
            m.supplier_dates,
            m.reason,
            m.keywords_match,
            m.coincidence_of_dates
        FROM matches m
        JOIN company b ON m.buyer_id = b.company_id
        JOIN company s ON m.supplier_id = s.company_id
        WHERE m.event_id = :event_id
        ORDER BY m.match_strength DESC, m.created_at DESC
        ";
        $params = [':event_id' => $eventId];
        $stmt = $this->db->query($sql, $params);
        if (!$stmt) {
            error_log('[MATCHES] Error SQL en getAllMatchesWithSubcategoriesByEvent: ' . $sql);
            return [];
        }
        $rows = $stmt->fetchAll();
        $unique = [];
        $filtered = [];
        foreach ($rows as &$row) {
            // Decodificar subcategorías
            $subcats = [];
            if (!empty($row['matched_categories'])) {
                $json = json_decode($row['matched_categories'], true);
                if (is_array($json)) {
                    foreach ($json as $cat) {
                        if (isset($cat['category_name'], $cat['subcategory_name'])) {
                            $subcats[] = $cat['category_name'] . ' > ' . $cat['subcategory_name'];
                        }
                    }
                }
            }
            // Si no hay matched_categories, usar buyer_subcategories y supplier_subcategories
            if (empty($subcats)) {
                $buyerSubcats = !empty($row['buyer_subcategories']) ? json_decode($row['buyer_subcategories'], true) : [];
                $supplierSubcats = !empty($row['supplier_subcategories']) ? json_decode($row['supplier_subcategories'], true) : [];
                $subcats = array_unique(array_merge($buyerSubcats, $supplierSubcats));
                $subcats = array_map('strval', $subcats);
            }
            $row['subcategories'] = !empty($subcats) ? implode(', ', $subcats) : '-';
            // Fechas coincidentes (usar coincidence_of_dates si existe, si no intersectar buyer_dates y supplier_dates)
            $commonDates = [];
            if (!empty($row['coincidence_of_dates'])) {
                $commonDates = explode(',', $row['coincidence_of_dates']);
            } else {
                $buyerDatesArr = !empty($row['buyer_dates']) ? explode(',', $row['buyer_dates']) : [];
                $supplierDatesArr = !empty($row['supplier_dates']) ? explode(',', $row['supplier_dates']) : [];
                $commonDates = array_intersect($buyerDatesArr, $supplierDatesArr);
            }
            $row['match_dates'] = !empty($commonDates) ? implode(', ', $commonDates) : '-';
            // FILTRO: solo matches con al menos una subcategoría y al menos una fecha coincidente
            if (!empty($subcats) && !empty($commonDates)) {
                $key = $row['buyer_id'] . '-' . $row['supplier_id'] . '-' . implode(',', $commonDates);
                if (!isset($unique[$key])) {
                    $filtered[] = $row;
                    $unique[$key] = true;
                }
            }
        }
        unset($row);
        return $filtered;
    }

    /**
     * Obtener matches potenciales avanzados para un evento usando pesos y umbral
     * @param int $eventId
     * @param array $weights [W_REQ, W_DATE, W_KW, W_DESC, MIN_STRENGTH]
     * @return array
     */
    public function getAdvancedPotentialMatches($eventId, $weights) {
        $W_REQ = (int)($weights['W_REQ'] ?? 50);
        $W_KW = (int)($weights['W_KW'] ?? 30);
        $W_DESC = (int)($weights['W_DESC'] ?? 10);
        $MIN_STRENGTH = (int)($weights['MIN_STRENGTH'] ?? 0);
        // Use unique parameter names for each event_id occurrence
        $sql = "
       WITH
  buyer_reqs AS (
    SELECT r.buyer_id, COUNT(*) AS total_reqs
    FROM requirements r
    JOIN company b ON r.buyer_id = b.company_id
    WHERE b.event_id = :event_id1
    GROUP BY r.buyer_id
  ),

  req_offer_matches AS (
    SELECT r.buyer_id, o.supplier_id, COUNT(*) AS matched_offers
    FROM requirements r
    JOIN supplier_offers o ON o.event_subcategory_id = r.event_subcategory_id
    JOIN company b ON r.buyer_id = b.company_id
    JOIN company s ON o.supplier_id = s.company_id
    WHERE b.event_id = :event_id2 AND s.event_id = :event_id3
    GROUP BY r.buyer_id, o.supplier_id
  ),

  date_matches AS (
    SELECT a1.company_id AS buyer_id, a2.company_id AS supplier_id, 1 AS date_match
    FROM attendance_days a1
    JOIN attendance_days a2
      ON a1.attendance_date = a2.attendance_date
     AND a1.company_id <> a2.company_id
    WHERE a1.event_id = :event_id4 AND a2.event_id = :event_id5
    GROUP BY a1.company_id, a2.company_id
  ),

   -- 4. Keywords score por evento (normalizado)
keyword_score AS (
  SELECT
    b.company_id AS buyer_id,
    s.company_id AS supplier_id,
    -- total keywords del buyer
    COUNT(bk.kw) AS total_bkw,
    -- coincidencias de keywords
    COUNT(sk.kw) AS matched_bkw
  FROM company b
  JOIN JSON_TABLE(b.keywords, '$[*]' COLUMNS(kw VARCHAR(255) PATH '$')) bk
    ON b.role='buyer' AND b.event_id= :event_id6
  JOIN company s
    ON s.role='supplier' AND s.event_id = b.event_id
  -- left join para contar coincidencias
  LEFT JOIN JSON_TABLE(s.keywords, '$[*]' COLUMNS(kw VARCHAR(255) PATH '$')) sk
    ON bk.kw = sk.kw
  GROUP BY b.company_id, s.company_id
),

  desc_score AS (
    SELECT b.company_id AS buyer_id, s.company_id AS supplier_id,
           SUM(CASE WHEN LOWER(s.description) LIKE CONCAT('%', LOWER(bk.kw), '%') THEN 1 ELSE 0 END) AS desc_matches
    FROM company b
    JOIN JSON_TABLE(b.keywords, '$[*]' COLUMNS(kw VARCHAR(255) PATH '$')) bk ON b.role='buyer'
    JOIN company s ON s.role='supplier' AND s.event_id = b.event_id
    WHERE b.event_id = :event_id7
    GROUP BY b.company_id, s.company_id
  )

-- 3) Query final
SELECT
  b.company_id    AS buyer_id,
  s.company_id    AS supplier_id,
  b.company_name  AS buyer_name,
  s.company_name  AS supplier_name,

   -- Componentes de score
  COALESCE(rom.matched_offers/br.total_reqs,0)*100 AS pct_req_match,
  COALESCE(dm.date_match,0)                   AS date_match,
  COALESCE(ks.matched_bkw,0)                   AS keyword_matches,
  COALESCE(ds.desc_matches,0)                 AS description_matches,

  -- Score final normalizado utilizando porcentajes de coincidencias
  (
    -- Porcentaje de requerimientos cubiertos
    COALESCE(rom.matched_offers/br.total_reqs, 0)*100 * $W_REQ
    -- Porcentaje de keywords que coinciden
    + COALESCE((ks.matched_bkw/ks.total_bkw)*100, 0) * $W_KW
    -- Porcentaje de coincidencias en descripción (normalizado con total_bkw)
    + COALESCE((ds.desc_matches/ks.total_bkw)*100, 0) * $W_DESC
  ) / ($W_REQ + $W_KW + $W_DESC) AS match_strength

FROM company b
JOIN company s ON s.role = 'supplier' AND s.event_id = b.event_id
LEFT JOIN buyer_reqs        br ON br.buyer_id      = b.company_id
LEFT JOIN req_offer_matches rom ON rom.buyer_id    = b.company_id AND rom.supplier_id = s.company_id
LEFT JOIN date_matches      dm ON dm.buyer_id      = b.company_id AND dm.supplier_id   = s.company_id
LEFT JOIN keyword_score     ks ON ks.buyer_id      = b.company_id AND ks.supplier_id    = s.company_id
LEFT JOIN desc_score        ds ON ds.buyer_id      = b.company_id AND ds.supplier_id    = s.company_id
LEFT JOIN matches m ON m.event_id = :event_id8 AND m.buyer_id = b.company_id AND m.supplier_id = s.company_id

WHERE b.role='buyer' AND b.event_id=:event_id9
  AND (
    COALESCE(rom.matched_offers,0) > 0
    OR COALESCE(ks.matched_bkw,0) > 0
    OR COALESCE(ds.desc_matches,0) > 0
  )
  AND m.match_id IS NULL
ORDER BY match_strength DESC
;";
        $params = [
            ':event_id1' => $eventId,
            ':event_id2' => $eventId,
            ':event_id3' => $eventId,
            ':event_id4' => $eventId,
            ':event_id5' => $eventId,
            ':event_id6' => $eventId,
            ':event_id7' => $eventId,
            ':event_id8' => $eventId,
            ':event_id9' => $eventId
        ];
        error_log('[DEBUG SQL] SQL: ' . $sql);
        error_log('[DEBUG SQL] PARAMS: ' . print_r($params, true));
        $stmt = $this->db->query($sql, $params);
        if (!$stmt) {
            error_log('[MATCHES] Error SQL en getAdvancedPotentialMatches: ' . $sql);
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener empresas del evento que no tienen ningún match (ni como buyer ni como supplier)
     * @param int $eventId
     * @return array
     */
    public function getUnmatchedCompaniesByEvent($eventId) {
        $sql = "
            SELECT c.*
            FROM company c
            WHERE c.event_id = :event_id
            AND NOT EXISTS (
                SELECT 1 FROM matches m
                WHERE m.event_id = c.event_id
                AND (m.buyer_id = c.company_id OR m.supplier_id = c.company_id)
            )
            ORDER BY c.company_name
        ";
        $params = [':event_id' => $eventId];
        $stmt = $this->db->query($sql, $params);
        if (!$stmt) return [];
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}