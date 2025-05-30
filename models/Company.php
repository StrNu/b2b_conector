<?php
/**
 * Modelo de Empresa
 * 
 * Esta clase maneja todas las operaciones relacionadas con las empresas
 * incluyendo creación, modificación, eliminación y consulta de empresas participantes
 * en los eventos de networking.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class Company {
    private $db;
    private $table = 'company';
    
    // Propiedades que mapean a las columnas de la tabla company
    private $id;
    private $company_name;
    private $address;
    private $city;
    private $country;
    private $website;
    private $company_logo;
    private $contact_first_name;
    private $contact_last_name;
    private $phone;
    private $email;
    private $created_at;
    private $is_active;
    private $role;
    private $event_id;
    private $description;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar empresa por ID
     * 
     * @param int $id ID de la empresa a buscar
     * @return bool True si la empresa existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE company_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar empresa por nombre
     * 
     * @param string $name Nombre de la empresa a buscar
     * @return bool True si la empresa existe, false en caso contrario
     */
    public function findByName($name) {
        $query = "SELECT * FROM {$this->table} WHERE company_name = :name LIMIT 1";
        $result = $this->db->single($query, ['name' => $name]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar empresa por email
     * 
     * @param string $email Email de la empresa a buscar
     * @return bool True si la empresa existe, false en caso contrario
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
     * Verificar si existe una empresa por nombre o email
     * 
     * @param string $name Nombre de la empresa
     * @param string $email Email de la empresa
     * @return bool True si la empresa existe, false en caso contrario
     */
    public function exists($name = null, $email = null) {
        if (!$name && !$email) {
            return false;
        }
        
        $conditions = [];
        $params = [];
        
        if ($name) {
            $conditions[] = "company_name = :name";
            $params['name'] = $name;
        }
        
        if ($email) {
            $conditions[] = "email = :email";
            $params['email'] = $email;
        }
        
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' OR ', $conditions);
        $count = $this->db->query($query, $params)->fetchColumn();
        
        return $count > 0;
    }
    
    /**
     * Actualizar datos de una empresa
     * 
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function update($data) {
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        // Verificar si el rol es válido (si se está actualizando)
        if (isset($data['role']) && !in_array($data['role'], ['buyer', 'supplier'])) {
            return false;
        }
        
        // Log de los datos recibidos para depuración
        Logger::debug('Datos recibidos para update de empresa', $data);
        
        // Generar sentencia de actualización
        $updateFields = [];
        foreach ($data as $key => $value) {
            $updateFields[] = "$key = :$key";
        }
        
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $updateFields) . " 
                  WHERE company_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar empresa
     * 
     * @param int $id ID de la empresa a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $companyId = $id ?? $this->id;
        
        if (!$companyId) {
            return false;
        }
        
        // Iniciar transacción para eliminar registros relacionados
        $this->db->beginTransaction();
        
        try {
            // Eliminar registros relacionados primero
            
            // Eliminar asistentes
            $query = "DELETE FROM assistants WHERE company_id = :id";
            $this->db->query($query, ['id' => $companyId]);
            
            // Eliminar días de asistencia
            $query = "DELETE FROM attendance_days WHERE company_id = :id";
            $this->db->query($query, ['id' => $companyId]);
            
            // Eliminar usuarios de evento
            $query = "DELETE FROM event_users WHERE company_id = :id";
            $this->db->query($query, ['id' => $companyId]);
            
            // Eliminar matches donde la empresa es compradora o proveedora
            $query = "DELETE FROM matches WHERE buyer_id = :buyer_id OR supplier_id = :supplier_id";
            $this->db->query($query, ['buyer_id' => $companyId, 'supplier_id' => $companyId]);
            
            // Eliminar requerimientos si es comprador
            $query = "DELETE FROM requirements WHERE buyer_id = :id";
            $this->db->query($query, ['id' => $companyId]);
            
            // Eliminar ofertas si es proveedor
            $query = "DELETE FROM supplier_offers WHERE supplier_id = :id";
            $this->db->query($query, ['id' => $companyId]);
            
            // Finalmente, eliminar la empresa
            $query = "DELETE FROM {$this->table} WHERE company_id = :id";
            $result = $this->db->query($query, ['id' => $companyId]);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
    
    /**
     * Crear empresa asociada a un evento
     * @param array $data
     * @return int|false ID de la empresa creada o false
     */
    public function createForEvent($data) {
        $query = "INSERT INTO company (
            event_id, company_name, address, city, country, website, company_logo, contact_first_name, contact_last_name, phone, email, is_active, role, description
        ) VALUES (
            :event_id, :company_name, :address, :city, :country, :website, :company_logo, :contact_first_name, :contact_last_name, :phone, :email, :is_active, :role, :description
        )";
        $params = [
            'event_id' => $data['event_id'],
            'company_name' => $data['company_name'],
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'country' => $data['country'] ?? '',
            'website' => $data['website'] ?? '',
            'company_logo' => $data['company_logo'] ?? '',
            'contact_first_name' => $data['contact_first_name'] ?? '',
            'contact_last_name' => $data['contact_last_name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'],
            'is_active' => $data['is_active'],
            'role' => $data['role'],
            'description' => $data['description'] ?? ''
        ];
        $result = $this->db->query($query, $params);
        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Obtener todas las empresas
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de empresas
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
        $query .= " ORDER BY company_id ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de empresas
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de empresas
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
     * Obtener empresas por rol
     * 
     * @param string $role Rol a filtrar (buyer/supplier)
     * @param array $pagination Información de paginación
     * @return array Lista de empresas filtradas por rol
     */
    public function getByRole($role, $pagination = null) {
        return $this->getAll(['role' => $role], $pagination);
    }
    
    /**
     * Obtener empresas por evento
     * 
     * @param int $eventId ID del evento
     * @param string $role Rol opcional para filtrar (buyer/supplier)
     * @param array $pagination Información de paginación
     * @return array Lista de empresas filtradas por evento y opcionalmente por rol
     */
    public function getByEvent($eventId, $role = null, $pagination = null, $filters = [], $order = 'asc') {
        $baseFilters = ['event_id' => $eventId];
        if ($role) {
            $baseFilters['role'] = $role;
        }
        // Merge search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query = "SELECT * FROM {$this->table} WHERE event_id = :event_id ";
            $params = ['event_id' => $eventId];
            if ($role) {
                $query .= " AND role = :role ";
                $params['role'] = $role;
            }
            $query .= " AND (company_name LIKE :search1 OR contact_first_name LIKE :search2 OR contact_last_name LIKE :search3 OR email LIKE :search4) ";
            $params['search1'] = $search;
            $params['search2'] = $search;
            $params['search3'] = $search;
            $params['search4'] = $search;
            $query .= " ORDER BY company_id " . (strtolower($order) === 'desc' ? 'DESC' : 'ASC');
            return $this->db->resultSet($query, $params);
        }
        // Sin búsqueda, solo filtros y orden
        $query = "SELECT * FROM {$this->table} WHERE event_id = :event_id";
        $params = ['event_id' => $eventId];
        if ($role) {
            $query .= " AND role = :role";
            $params['role'] = $role;
        }
        $query .= " ORDER BY company_id " . (strtolower($order) === 'desc' ? 'DESC' : 'ASC');
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Obtener compradores de un evento
     * 
     * @param int $eventId ID del evento
     * @param array $pagination Información de paginación
     * @return array Lista de compradores del evento
     */
    public function getBuyersByEvent($eventId, $pagination = null) {
        return $this->getByEvent($eventId, 'buyer', $pagination);
    }
    
    /**
     * Obtener proveedores de un evento
     * 
     * @param int $eventId ID del evento
     * @param array $pagination Información de paginación
     * @return array Lista de proveedores del evento
     */
    public function getSuppliersByEvent($eventId, $pagination = null) {
        return $this->getByEvent($eventId, 'supplier', $pagination);
    }
    
    /**
     * Cargar logo de empresa
     * 
     * @param array $file Datos del archivo subido ($_FILES['logo'])
     * @param int $companyId ID de la empresa
     * @return bool|string Ruta del logo guardado o false en caso de error
     */
    public function uploadLogo($file, $companyId) { // Se hace $companyId obligatorio para este contexto
        if (!$companyId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            Logger::warning('UploadLogo: No ID, no file, or upload error.', ['id' => $companyId, 'file_error' => $file['error'] ?? 'N/A']);
            return false;
        }
        
        // Validar tipo de archivo
        if (!isAllowedExtension($file['name'], ALLOWED_EXTENSIONS)) {
            Logger::warning('UploadLogo: Invalid extension.', ['filename' => $file['name']]);
            return false;
        }
        
        // Validar tamaño
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            Logger::warning('UploadLogo: File too large.', ['filesize' => $file['size']]);
            return false;
        }
        
        // Generar nombre único para el archivo
        $logoName = generateUniqueFileName($file['name']);
        $logoPath = LOGO_DIR . '/' . $logoName;
        
        // Crear directorio si no existe
        if (!is_dir(LOGO_DIR)) {
            if (!mkdir(LOGO_DIR, 0755, true)) {
                Logger::error('UploadLogo: Failed to create logo directory.', ['path' => LOGO_DIR]);
                return false;
            }
        }
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $logoPath)) {
            // Actualizar ruta en la base de datos directamente
            $query = "UPDATE {$this->table} SET company_logo = :logo WHERE company_id = :id";
            $params = ['logo' => $logoName, 'id' => $companyId];
            
            if ($this->db->query($query, $params)) {
                // Si la instancia actual del modelo corresponde al companyId, actualizamos su propiedad
                if ($this->id == $companyId) {
                    $this->company_logo = $logoName;
                }
                Logger::info("Logo '{$logoName}' updated in DB for company ID {$companyId}.");
                return $logoName;
            } else {
                Logger::error("UploadLogo: Failed to update logo path in DB for company ID {$companyId}.");
                // Si falla la actualización en BD, eliminamos el archivo subido para no dejar huérfanos
                if (file_exists($logoPath)) {
                    unlink($logoPath);
                }
                return false;
            }
        } else {
            Logger::error('UploadLogo: Failed to move uploaded file.', ['tmp_name' => $file['tmp_name'], 'destination' => $logoPath]);
            return false;
        }
    }
    
    /**
     * Actualizar logo de empresa
     * 
     * @param array $file Datos del archivo subido ($_FILES['logo'])
     * @param int $companyId ID de la empresa
     * @return bool|string Ruta del logo actualizado o false en caso de error
     */
    public function updateLogo($file, $companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        // Obtener logo actual
        $currentLogo = $this->company_logo;
        
        // Primero cargar el nuevo logo
        $newLogo = $this->uploadLogo($file, $id);
        
        if ($newLogo) {
            // Si se cargó correctamente, eliminar el logo anterior si existe
            if ($currentLogo && file_exists(LOGO_DIR . '/' . $currentLogo)) {
                unlink(LOGO_DIR . '/' . $currentLogo);
            }
            
            return $newLogo;
        }
        
        return false;
    }
    
    /**
     * Obtener asistentes de una empresa
     * 
     * @param int $companyId ID de la empresa
     * @return array Lista de asistentes
     */
    public function getAssistants($companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id) {
            return [];
        }
        
        $query = "SELECT * FROM assistants WHERE company_id = :company_id ORDER BY first_name, last_name";
        $params = ['company_id' => $id];
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Agregar asistente a una empresa
     * 
     * @param array $data Datos del asistente
     * @param int $companyId ID de la empresa
     * @return bool|int ID del asistente creado o false en caso de error
     */
    public function addAssistant($data, $companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        // Validar datos mínimos
        if (!isset($data['first_name'], $data['last_name'], $data['email'])) {
            return false;
        }
        
        // Agregar ID de la empresa
        $data['company_id'] = $id;
        
        // Generar consulta SQL
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        
        $query = "INSERT INTO assistants (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";
        
        if ($this->db->query($query, $data)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Eliminar asistente
     * 
     * @param int $assistantId ID del asistente a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function removeAssistant($assistantId) {
        if (!$assistantId) {
            return false;
        }
        
        $query = "DELETE FROM assistants WHERE assistant_id = :id";
        $params = ['id' => $assistantId];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Obtener requerimientos de un comprador
     * 
     * @param int $companyId ID de la empresa (debe ser comprador)
     * @param int $eventId ID del evento
     * @return array Lista de requerimientos
     */
    public function getRequirements($companyId = null, $eventId = null) {
        $id = $companyId ?? $this->id;
        $eventId = $eventId ?? $this->event_id ?? null;

        if (!$id || !$eventId) {
            return [];
        }

        // Consultar el rol directamente de la base de datos para evitar depender de la propiedad privada
        $queryRole = "SELECT role FROM {$this->table} WHERE company_id = :id LIMIT 1";
        $roleResult = $this->db->single($queryRole, ['id' => $id]);
        $role = $roleResult['role'] ?? null;
        if ($role !== 'buyer') {
            return [];
        }

        $query = "SELECT r.*, es.name AS subcategory_name, ec.name AS category_name, ec.event_category_id AS category_id
                  FROM requirements r
                  JOIN event_subcategories es ON r.event_subcategory_id = es.event_subcategory_id
                  JOIN event_categories ec ON es.event_category_id = ec.event_category_id
                  WHERE r.buyer_id = :buyer_id AND ec.event_id = :event_id
                  ORDER BY ec.name, es.name";

        $params = ['buyer_id' => $id, 'event_id' => $eventId];

        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Agregar requerimiento para un comprador
     * 
     * @param array $data Datos del requerimiento
     * @param int $companyId ID de la empresa (debe ser comprador)
     * @return bool|int ID del requerimiento creado o false en caso de error
     */
    public function addRequirement($data, $companyId = null) {
        $id = $companyId ?? $this->id;
        Logger::debug('addRequirement: companyId', ['companyId' => $id, 'role' => $this->role, 'data' => $data]);
        if (!$id) {
            Logger::error('addRequirement: No companyId');
            return false;
        }
        // Forzar el rol a 'buyer' si se llama desde registro público
        if (empty($this->role)) {
            $this->role = 'buyer';
            Logger::debug('addRequirement: role vacío, forzando a buyer');
        }
        if ($this->role !== 'buyer') {
            Logger::error('addRequirement: El rol no es buyer', ['role' => $this->role]);
            return false;
        }
        // Validar datos mínimos
        if (!isset($data['subcategory_id']) && !isset($data['event_subcategory_id'])) {
            Logger::error('addRequirement: Falta event_subcategory_id', ['data' => $data]);
            return false;
        }
        // Usar event_subcategory_id para la tabla requirements
        $data['event_subcategory_id'] = isset($data['event_subcategory_id'])
            ? (int)$data['event_subcategory_id']
            : (isset($data['subcategory_id']) ? (int)$data['subcategory_id'] : null);
        unset($data['subcategory_id']);
        $data['buyer_id'] = $id;
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        $query = "INSERT INTO requirements (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";
        Logger::debug('addRequirement: Ejecutando query', ['query' => $query, 'params' => $data]);
        if ($this->db->query($query, $data)) {
            $lastId = $this->db->lastInsertId();
            Logger::info('addRequirement: Insert exitoso', ['lastInsertId' => $lastId]);
            return $lastId;
        } else {
            Logger::error('addRequirement: Falló el insert', ['query' => $query, 'params' => $data]);
        }
        return false;
    }
    
    /**
     * Eliminar requerimiento
     * 
     * @param int $requirementId ID del requerimiento a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function removeRequirement($requirementId) {
        if (!$requirementId) {
            return false;
        }
        
        // Verificar que la empresa sea un comprador
        if ($this->role !== 'buyer') {
            return false;
        }
        
        $query = "DELETE FROM requirements WHERE requirement_id = :id AND buyer_id = :buyer_id";
        $params = [
            'id' => $requirementId,
            'buyer_id' => $this->id
        ];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Obtener ofertas de un proveedor
     * 
     * @param int $companyId ID de la empresa (debe ser proveedor)
     * @return array Lista de ofertas
     */
    public function getOffers($companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id) {
            return [];
        }
        
        $query = "SELECT so.*, s.name AS subcategory_name, c.name AS category_name, c.event_category_id AS category_id
                  FROM supplier_offers so
                  JOIN event_subcategories s ON so.event_subcategory_id = s.event_subcategory_id
                  JOIN event_categories c ON s.event_category_id = c.event_category_id
                  WHERE so.supplier_id = :supplier_id
                  ORDER BY c.name, s.name";
        
        $params = ['supplier_id' => $id];
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Agregar oferta para un proveedor
     * 
     * @param array $data Datos de la oferta
     * @param int $companyId ID de la empresa (debe ser proveedor)
     * @return bool|int ID de la oferta creada o false en caso de error
     */
    public function addOffer($data, $companyId = null) {
        $id = $companyId ?? $this->id;
        if (class_exists('Logger')) {
            Logger::debug('addOffer: Iniciando', ['companyId' => $id, 'role' => $this->role, 'data' => $data]);
        }
        if (!$id) {
            if (class_exists('Logger')) {
                Logger::error('addOffer: No companyId');
            }
            return false;
        }
        // Forzar el rol a 'supplier' si se llama desde registro público
        if (empty($this->role)) {
            $this->role = 'supplier';
            if (class_exists('Logger')) {
                Logger::debug('addOffer: role vacío, forzando a supplier');
            }
        }
        if ($this->role !== 'supplier') {
            if (class_exists('Logger')) {
                Logger::error('addOffer: El rol no es supplier', ['role' => $this->role]);
            }
            return false;
        }
        // Validar datos mínimos
        if (!isset($data['event_subcategory_id'])) {
            if (class_exists('Logger')) {
                Logger::error('addOffer: Falta event_subcategory_id', ['data' => $data]);
            }
            return false;
        }
        $data['supplier_id'] = $id;
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        $query = "INSERT INTO supplier_offers (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";
        if (class_exists('Logger')) {
            Logger::debug('addOffer: Ejecutando query', ['query' => $query, 'params' => $data]);
        }
        if ($this->db->query($query, $data)) {
            $lastId = $this->db->lastInsertId();
            if (class_exists('Logger')) {
                Logger::info('addOffer: Insert exitoso', ['lastInsertId' => $lastId]);
            }
            return $lastId;
        } else {
            if (class_exists('Logger')) {
                Logger::error('addOffer: Falló el insert', ['query' => $query, 'params' => $data]);
            }
        }
        return false;
    }
    
    /**
     * Eliminar oferta
     * 
     * @param int $offerId ID de la oferta a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function removeOffer($offerId) {
        if (!$offerId) {
            return false;
        }
        
        // Verificar que la empresa sea un proveedor
        if ($this->role !== 'supplier') {
            return false;
        }
        
        $query = "DELETE FROM supplier_offers WHERE offer_id = :id AND supplier_id = :supplier_id";
        $params = [
            'id' => $offerId,
            'supplier_id' => $this->id
        ];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Obtener días de asistencia de una empresa a un evento
     * 
     * @param int $eventId ID del evento
     * @param int $companyId ID de la empresa
     * @return array Lista de fechas de asistencia
     */
    public function getAttendanceDays($eventId, $companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id || !$eventId) {
            return [];
        }
        
        $query = "SELECT attendance_date FROM attendance_days 
                  WHERE event_id = :event_id AND company_id = :company_id 
                  ORDER BY attendance_date";
        
        $params = [
            'event_id' => $eventId,
            'company_id' => $id
        ];
        
        $result = $this->db->resultSet($query, $params);
        
        // Formatear las fechas a un array simple
        $dates = [];
        foreach ($result as $row) {
            $dates[] = $row['attendance_date'];
        }
        
        return $dates;
    }
    
    /**
     * Agregar día de asistencia para una empresa
     * 
     * @param int $eventId ID del evento
     * @param string $date Fecha de asistencia (formato Y-m-d)
     * @param int $companyId ID de la empresa
     * @return bool True si se agregó correctamente, false en caso contrario
     */
    public function addAttendanceDay($eventId, $date, $companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id || !$eventId || !$date) {
            return false;
        }
        
        // Formatear fecha si es necesario
        if (strpos($date, '/') !== false) {
            $date = dateToDatabase($date);
        }
        
        // Verificar si ya existe la asistencia
        $query = "SELECT COUNT(*) FROM attendance_days 
                  WHERE event_id = :event_id AND company_id = :company_id AND attendance_date = :date";
        
        $params = [
            'event_id' => $eventId,
            'company_id' => $id,
            'date' => $date
        ];
        
        $count = $this->db->query($query, $params)->fetchColumn();
        
        if ($count > 0) {
            return true; // Ya existe, consideramos que fue exitoso
        }
        
        // Insertar nueva asistencia
        $query = "INSERT INTO attendance_days (event_id, company_id, attendance_date) 
                  VALUES (:event_id, :company_id, :date)";
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Eliminar día de asistencia para una empresa
     * 
     * @param int $eventId ID del evento
     * @param string $date Fecha de asistencia (formato Y-m-d)
     * @param int $companyId ID de la empresa
     * @return bool True si se eliminó correctamente, false en caso contrario
     */
    public function removeAttendanceDay($eventId, $date, $companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id || !$eventId || !$date) {
            return false;
        }
        
        // Formatear fecha si es necesario
        if (strpos($date, '/') !== false) {
            $date = dateToDatabase($date);
        }
        
        $query = "DELETE FROM attendance_days 
                  WHERE event_id = :event_id AND company_id = :company_id AND attendance_date = :date";
        
        $params = [
            'event_id' => $eventId,
            'company_id' => $id,
            'date' => $date
        ];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Obtener citas programadas para una empresa
     * 
     * @param int $eventId ID del evento
     * @param int $companyId ID de la empresa
     * @return array Lista de citas
     */
    public function getSchedules($eventId, $companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id || !$eventId) {
            return [];
        }
        
        $query = "SELECT es.*, 
                  m.buyer_id, m.supplier_id, 
                  b.company_name as buyer_name, 
                  s.company_name as supplier_name 
                  FROM event_schedules es 
                  JOIN matches m ON es.match_id = m.match_id 
                  JOIN company b ON m.buyer_id = b.company_id 
                  JOIN company s ON m.supplier_id = s.company_id 
                  WHERE es.event_id = :event_id 
                  AND (m.buyer_id = :company_id1 OR m.supplier_id = :company_id2) 
                  ORDER BY es.start_datetime";
        
        $params = [
            'event_id' => $eventId,
            'company_id1' => $id,
            'company_id2' => $id
        ];
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Obtener matches de una empresa
     * 
     * @param int $eventId ID del evento
     * @param int $companyId ID de la empresa
     * @return array Lista de matches
     */
    public function getMatches($eventId, $companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id || !$eventId) {
            return [];
        }
        
        $query = "SELECT m.*, 
                  b.company_name as buyer_name, 
                  s.company_name as supplier_name 
                  FROM matches m 
                  JOIN company b ON m.buyer_id = b.company_id 
                  JOIN company s ON m.supplier_id = s.company_id 
                  WHERE m.event_id = :event_id 
                  AND (m.buyer_id = :company_id1 OR m.supplier_id = :company_id2) 
                              ORDER BY m.match_strength DESC";

        $params = [
            'event_id' => $eventId,
            'company_id1' => $id,
            'company_id2' => $id
        ];

        return $this->db->resultSet($query, $params);
    }

/**
 * Obtener eventos en los que participa una empresa
 * 
 * @param int $companyId ID de la empresa
 * @return array Lista de eventos en los que participa la empresa
 */
public function getEvents($companyId = null) {
    // Si no se proporciona el ID pero tenemos una instancia cargada, usamos su ID
    if (!$companyId && $this->company_id) {
        $companyId = $this->company_id;
    }
    
    // Si aún no tenemos un ID, no podemos continuar
    if (!$companyId) {
        Logger::warning('No se proporcionó ID de empresa para obtener eventos');
        return [];
    }
    
    Logger::debug("Obteniendo eventos para la empresa ID: $companyId");
    
    // Consultamos los eventos donde participa la empresa
    // La tabla company tiene una relación directa con event_id
    $query = "SELECT e.* 
              FROM events e
              INNER JOIN company c ON e.id = c.event_id
              WHERE c.company_id = :company_id";
    
    $events = $this->db->resultSet($query, [':company_id' => $companyId]);
    
    if (!$events) {
        Logger::info("No se encontraron eventos para la empresa ID: $companyId");
        return [];
    }
    
    // Formato estandarizado para los eventos
    $formattedEvents = [];
    foreach ($events as $event) {
        $formattedEvents[] = [
            'event_id' => $event['id'],
            'event_name' => $event['name'],
            'start_date' => $event['start_date'],
            'end_date' => $event['end_date'],
            'venue' => $event['venue'],
            'description' => $event['description'],
            'is_active' => $event['is_active']
        ];
    }
    
    Logger::info("Se encontraron " . count($formattedEvents) . " eventos para la empresa ID: $companyId");
    return $formattedEvents;
}

/**
 * Obtener próximos eventos para una empresa
 * 
 * @param int $companyId ID de la empresa
 * @param int $limit Límite de eventos a retornar
 * @return array Lista de próximos eventos para la empresa
 */
public function getUpcomingEvents($companyId = null, $limit = 3) {
    // Si no se proporciona el ID pero tenemos una instancia cargada, usamos su ID
    if (!$companyId && $this->company_id) {
        $companyId = $this->company_id;
    }
    
    // Si aún no tenemos un ID, no podemos continuar
    if (!$companyId) {
        Logger::warning('No se proporcionó ID de empresa para obtener próximos eventos');
        return [];
    }
    
    Logger::debug("Obteniendo próximos eventos para la empresa ID: $companyId");
    
    // Fecha actual para filtrar solo eventos futuros o en curso
    $currentDate = date('Y-m-d');
    
    // Consultamos los eventos futuros donde participa la empresa
    $query = "SELECT e.* 
              FROM events e
              INNER JOIN company c ON e.id = c.event_id
              WHERE c.company_id = :company_id
              AND e.end_date >= :current_date
              ORDER BY e.start_date ASC
              LIMIT :limit";
    
    // Para usar LIMIT con parámetros en PDO, necesitamos bindear el parámetro como entero
    $params = [
        ':company_id' => $companyId,
        ':current_date' => $currentDate
    ];
    
    // La sintaxis exacta puede variar según cómo esté implementado el método query en tu Database.php
    // Aquí asumimos que query() maneja el bindeo de parámetros correctamente
    $query .= " LIMIT " . (int)$limit;
    
    $events = $this->db->resultSet($query, $params);
    
    if (!$events) {
        Logger::info("No se encontraron próximos eventos para la empresa ID: $companyId");
        return [];
    }
    
    // Formato estandarizado para los eventos
    $upcomingEvents = [];
    foreach ($events as $event) {
        // Calculamos días restantes hasta el evento
        $daysUntilStart = max(0, floor((strtotime($event['start_date']) - time()) / (60 * 60 * 24)));
        $isOngoing = $currentDate >= $event['start_date'] && $currentDate <= $event['end_date'];
        
        $upcomingEvents[] = [
            'event_id' => $event['id'],
            'event_name' => $event['name'],
            'start_date' => $event['start_date'],
            'end_date' => $event['end_date'],
            'venue' => $event['venue'],
            'description' => $event['description'],
            'days_until' => $isOngoing ? 0 : $daysUntilStart,
            'status' => $isOngoing ? 'ongoing' : 'upcoming',
            'is_active' => $event['is_active']
        ];
    }
    
    Logger::info("Se encontraron " . count($upcomingEvents) . " próximos eventos para la empresa ID: $companyId");
    return $upcomingEvents;
}

    /**
     * Obtener el ID del evento asociado a la empresa
     * 
     * @return int|null ID del evento o null si no está definido
     */
    public function getEventId() {
        return $this->event_id ?? null;
    }

    /**
     * Obtener el rol de la empresa actual
     * @return string|null
     */
    public function getRole() {
        return $this->role ?? null;
    }

    /**
     * Obtener el nombre de la empresa actual
     * @return string|null
     */
    public function getCompanyName() {
        return $this->company_name;
    }

    /**
     * Obtener el nombre del contacto de la empresa
     * @return string|null
     */
    public function getContactFirstName() {
        return $this->contact_first_name;
    }

    /**
     * Obtener el apellido del contacto de la empresa
     * @return string|null
     */
    public function getContactLastName() {
        return $this->contact_last_name;
    }

    /**
     * Obtener el email de la empresa
     * @return string|null
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Obtener el teléfono de la empresa
     * @return string|null
     */
    public function getPhone() {
        return $this->phone;
    }

    /**
     * Obtener la dirección de la empresa
     * @return string|null
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Obtener la ciudad de la empresa
     * @return string|null
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * Obtener el país de la empresa
     * @return string|null
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Obtener el sitio web de la empresa
     * @return string|null
     */
    public function getWebsite() {
        return $this->website;
    }

    /**
     * Obtener la descripción de la empresa
     * @return string|null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Obtener el logo de la empresa
     * @return string|null
     */
    public function getCompanyLogo() {
        return $this->company_logo;
    }

    /**
     * Verificar si la empresa está activa
     * @return bool
     */
    public function isActive() {
        return $this->is_active;
    }

    /**
     * Obtener el ID de la empresa actual
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Asignar propiedades del modelo desde un array de datos
     * @param array $data
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['company_id'] ?? null;
        $this->company_name = $data['company_name'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->city = $data['city'] ?? null;
        $this->country = $data['country'] ?? null;
        $this->website = $data['website'] ?? null;
        $this->company_logo = $data['company_logo'] ?? null;
        $this->contact_first_name = $data['contact_first_name'] ?? null;
        $this->contact_last_name = $data['contact_last_name'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->is_active = $data['is_active'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->event_id = $data['event_id'] ?? null;
        $this->description = $data['description'] ?? null;
    }

    /**
     * Elimina todos los requerimientos de una empresa para un evento
     */
    public function deleteAllRequirements($companyId, $eventId) {
        $query = "DELETE FROM requirements WHERE buyer_id = :company_id AND event_subcategory_id IN (
            SELECT es.event_subcategory_id FROM event_subcategories es
            JOIN event_categories ec ON es.event_category_id = ec.event_category_id
            WHERE ec.event_id = :event_id
        )";
        $this->db->query($query, [
            'company_id' => $companyId,
            'event_id' => $eventId
        ]);
    }

    /**
     * Obtener empresa por ID (como array)
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE company_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        return $result ?: null;
    }
}
