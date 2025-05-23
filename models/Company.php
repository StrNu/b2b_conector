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
     * Crear una nueva empresa
     * 
     * @param array $data Datos de la empresa a crear
     * @return bool|int ID de la empresa creada o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['company_name'], $data['role'])) {
            return false;
        }
        
        // Verificar si el rol es válido
        if (!in_array($data['role'], ['buyer', 'supplier'])) {
            return false;
        }
        
        // Establecer valores por defecto si no están presentes
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }
        
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
            $query = "DELETE FROM matches WHERE buyer_id = :id OR supplier_id = :id";
            $this->db->query($query, ['id' => $companyId]);
            
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
        $query .= " ORDER BY company_name ASC";
        
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
    public function getByEvent($eventId, $role = null, $pagination = null) {
        $filters = ['event_id' => $eventId];
        
        if ($role) {
            $filters['role'] = $role;
        }
        
        return $this->getAll($filters, $pagination);
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
    public function uploadLogo($file, $companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Validar tipo de archivo
        if (!isAllowedExtension($file['name'], ALLOWED_EXTENSIONS)) {
            return false;
        }
        
        // Validar tamaño
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return false;
        }
        
        // Generar nombre único para el archivo
        $logoName = generateUniqueFileName($file['name']);
        $logoPath = LOGO_DIR . '/' . $logoName;
        
        // Crear directorio si no existe
        if (!is_dir(LOGO_DIR)) {
            mkdir(LOGO_DIR, 0755, true);
        }
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $logoPath)) {
            // Actualizar ruta en la base de datos
            $data = ['company_logo' => $logoName];
            
            if ($this->update($data)) {
                return $logoName;
            }
        }
        
        return false;
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
     * @return array Lista de requerimientos
     */
    public function getRequirements($companyId = null) {
        $id = $companyId ?? $this->id;
        
        if (!$id) {
            return [];
        }
        
        // Verificar que la empresa sea un comprador
        if ($this->role !== 'buyer') {
            return [];
        }
        
        $query = "SELECT r.*, s.subcategory_name, c.category_name, c.category_id
                  FROM requirements r
                  JOIN subcategories s ON r.subcategory_id = s.subcategory_id
                  JOIN categories c ON s.category_id = c.category_id
                  WHERE r.buyer_id = :buyer_id
                  ORDER BY c.category_name, s.subcategory_name";
        
        $params = ['buyer_id' => $id];
        
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
        
        if (!$id) {
            return false;
        }
        
        // Verificar que la empresa sea un comprador
        if ($this->role !== 'buyer') {
            return false;
        }
        
        // Validar datos mínimos
        if (!isset($data['subcategory_id'])) {
            return false;
        }
        
        // Agregar ID del comprador
        $data['buyer_id'] = $id;
        
        // Establecer fecha de creación si no está presente
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Generar consulta SQL
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        
        $query = "INSERT INTO requirements (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";
        
        if ($this->db->query($query, $data)) {
            return $this->db->lastInsertId();
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
        
        // Verificar que la empresa sea un proveedor
        if ($this->role !== 'supplier') {
            return [];
        }
        
        $query = "SELECT so.*, s.subcategory_name, c.category_name, c.category_id
                  FROM supplier_offers so
                  JOIN subcategories s ON so.subcategory_id = s.subcategory_id
                  JOIN categories c ON s.category_id = c.category_id
                  WHERE so.supplier_id = :supplier_id
                  ORDER BY c.category_name, s.subcategory_name";
        
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
        
        if (!$id) {
            return false;
        }
        
        // Verificar que la empresa sea un proveedor
        if ($this->role !== 'supplier') {
            return false;
        }
        
        // Validar datos mínimos
        if (!isset($data['subcategory_id'])) {
            return false;
        }
        
        // Agregar ID del proveedor
        $data['supplier_id'] = $id;
        
        // Generar consulta SQL
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        
        $query = "INSERT INTO supplier_offers (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";
        
        if ($this->db->query($query, $data)) {
            return $this->db->lastInsertId();
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
                  AND (m.buyer_id = :company_id OR m.supplier_id = :company_id) 
                  ORDER BY es.start_datetime";
        
        $params = [
            'event_id' => $eventId,
            'company_id' => $id
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
                  AND (m.buyer_id = :company_id OR m.supplier_id = :company_id) 
                              ORDER BY m.match_strength DESC";

        $params = [
            'event_id' => $eventId,
            'company_id' => $id
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
}
