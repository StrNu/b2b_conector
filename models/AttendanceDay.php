<?php
/**
 * Modelo de Día de Asistencia
 * 
 * Esta clase maneja todas las operaciones relacionadas con los días de asistencia
 * de las empresas a los eventos, fundamentales para generar matches y citas.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class AttendanceDay {
    private $db;
    private $table = 'attendance_days';
    
    // Propiedades que mapean a las columnas de la tabla attendance_days
    private $id;
    private $company_id;
    private $event_id;
    private $attendance_date;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar día de asistencia por ID
     * 
     * @param int $id ID del día de asistencia a buscar
     * @return bool True si el día de asistencia existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE attendance_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si existe un día de asistencia para una empresa y evento en una fecha específica
     * 
     * @param int $companyId ID de la empresa
     * @param int $eventId ID del evento
     * @param string $date Fecha de asistencia (formato Y-m-d)
     * @return bool True si el día de asistencia existe, false en caso contrario
     */
    public function exists($companyId, $eventId, $date) {
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE company_id = :company_id 
                  AND event_id = :event_id 
                  AND attendance_date = :date";
        
        $params = [
            'company_id' => $companyId,
            'event_id' => $eventId,
            'date' => $date
        ];
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear un nuevo día de asistencia
     * 
     * @param array $data Datos del día de asistencia a crear
     * @return bool|int ID del día de asistencia creado o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['company_id']) || !isset($data['event_id']) || !isset($data['attendance_date'])) {
            return false;
        }
        
        // Validar que la empresa exista
        $companyModel = new Company($this->db);
        if (!$companyModel->findById($data['company_id'])) {
            return false;
        }
        
        // Validar que el evento exista
        $eventModel = new Event($this->db);
        if (!$eventModel->findById($data['event_id'])) {
            return false;
        }
        
        // Validar que la fecha esté dentro del rango del evento
        if (!$this->isDateWithinEventRange($data['attendance_date'], $data['event_id'])) {
            return false;
        }
        
        // Verificar que no exista ya este día de asistencia
        if ($this->exists($data['company_id'], $data['event_id'], $data['attendance_date'])) {
            return false;
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
     * Actualizar datos de un día de asistencia
     * 
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function update($data) {
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        // Validar que la fecha esté dentro del rango del evento si se está actualizando
        if (isset($data['attendance_date']) && isset($data['event_id'])) {
            if (!$this->isDateWithinEventRange($data['attendance_date'], $data['event_id'])) {
                return false;
            }
        }
        
        // Generar sentencia de actualización
        $updateFields = [];
        foreach ($data as $key => $value) {
            $updateFields[] = "$key = :$key";
        }
        
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $updateFields) . " 
                  WHERE attendance_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar día de asistencia
     * 
     * @param int $id ID del día de asistencia a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $attendanceId = $id ?? $this->id;
        
        if (!$attendanceId) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE attendance_id = :id";
        return $this->db->query($query, ['id' => $attendanceId]) ? true : false;
    }
    
    /**
     * Eliminar todos los días de asistencia de una empresa a un evento
     * 
     * @param int $companyId ID de la empresa
     * @param int $eventId ID del evento
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function deleteByCompanyAndEvent($companyId, $eventId) {
        if (!$companyId || !$eventId) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} 
                  WHERE company_id = :company_id AND event_id = :event_id";
                  
        $params = [
            'company_id' => $companyId,
            'event_id' => $eventId
        ];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Eliminar un día de asistencia específico de una empresa a un evento
     * 
     * @param int $companyId ID de la empresa
     * @param int $eventId ID del evento
     * @param string $date Fecha de asistencia (formato Y-m-d)
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function deleteSpecificDay($companyId, $eventId, $date) {
        if (!$companyId || !$eventId || !$date) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} 
                  WHERE company_id = :company_id 
                  AND event_id = :event_id 
                  AND attendance_date = :date";
                  
        $params = [
            'company_id' => $companyId,
            'event_id' => $eventId,
            'date' => $date
        ];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Obtener todos los días de asistencia
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de días de asistencia
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT ad.*, c.company_name, e.event_name 
                  FROM {$this->table} ad
                  JOIN company c ON ad.company_id = c.company_id
                  JOIN events e ON ad.event_id = e.event_id";
                  
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'date_from' && !empty($value)) {
                    $conditions[] = "ad.attendance_date >= :date_from";
                    $params['date_from'] = $value;
                } else if ($key === 'date_to' && !empty($value)) {
                    $conditions[] = "ad.attendance_date <= :date_to";
                    $params['date_to'] = $value;
                } else {
                    $conditions[] = "ad.$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY ad.attendance_date ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de días de asistencia
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de días de asistencia
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table} ad";
        
        // Incluir joins si se necesitan para filtros
        if (isset($filters['company_name']) || isset($filters['event_name'])) {
            $query .= " JOIN company c ON ad.company_id = c.company_id
                       JOIN events e ON ad.event_id = e.event_id";
        }
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                if ($key === 'date_from' && !empty($value)) {
                    $conditions[] = "ad.attendance_date >= :date_from";
                    $params['date_from'] = $value;
                } else if ($key === 'date_to' && !empty($value)) {
                    $conditions[] = "ad.attendance_date <= :date_to";
                    $params['date_to'] = $value;
                } else if ($key === 'company_name' && !empty($value)) {
                    $conditions[] = "c.company_name LIKE :company_name";
                    $params['company_name'] = '%' . $value . '%';
                } else if ($key === 'event_name' && !empty($value)) {
                    $conditions[] = "e.event_name LIKE :event_name";
                    $params['event_name'] = '%' . $value . '%';
                } else {
                    $conditions[] = "ad.$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Obtener días de asistencia de una empresa a un evento
     * 
     * @param int $companyId ID de la empresa
     * @param int $eventId ID del evento
     * @return array Lista de fechas de asistencia
     */
    public function getByCompanyAndEvent($companyId, $eventId) {
        $query = "SELECT attendance_date FROM {$this->table} 
                  WHERE company_id = :company_id AND event_id = :event_id
                  ORDER BY attendance_date ASC";
                  
        $params = [
            'company_id' => $companyId,
            'event_id' => $eventId
        ];
        
        $result = $this->db->resultSet($query, $params);
        
        // Convertir a un array simple de fechas
        $dates = [];
        foreach ($result as $row) {
            $dates[] = $row['attendance_date'];
        }
        
        return $dates;
    }
    
    /**
     * Obtener días de asistencia comunes entre dos empresas para un evento
     * 
     * @param int $company1Id ID de la primera empresa
     * @param int $company2Id ID de la segunda empresa
     * @param int $eventId ID del evento
     * @return array Lista de fechas comunes de asistencia
     */
    public function getCommonDays($company1Id, $company2Id, $eventId) {
        // Obtener días de asistencia de cada empresa
        $company1Days = $this->getByCompanyAndEvent($company1Id, $eventId);
        $company2Days = $this->getByCompanyAndEvent($company2Id, $eventId);
        
        // Encontrar la intersección (días comunes)
        return array_values(array_intersect($company1Days, $company2Days));
    }
    
    /**
     * Verificar si dos empresas tienen al menos un día común de asistencia
     * 
     * @param int $company1Id ID de la primera empresa
     * @param int $company2Id ID de la segunda empresa
     * @param int $eventId ID del evento
     * @return bool True si tienen al menos un día común, false en caso contrario
     */
    public function haveCommonDays($company1Id, $company2Id, $eventId) {
        return !empty($this->getCommonDays($company1Id, $company2Id, $eventId));
    }
    
    /**
     * Agregar múltiples días de asistencia para una empresa a un evento
     * 
     * @param int $companyId ID de la empresa
     * @param int $eventId ID del evento
     * @param array $dates Lista de fechas de asistencia (formato Y-m-d)
     * @return array Resultado de la operación [success, added, errors]
     */
    public function addMultipleDays($companyId, $eventId, $dates) {
        if (!$companyId || !$eventId || empty($dates)) {
            return [
                'success' => false,
                'added' => 0,
                'errors' => ['Datos incompletos']
            ];
        }
        
        $addedCount = 0;
        $errors = [];
        
        foreach ($dates as $date) {
            // Validar formato de fecha
            if (!$this->isValidDate($date)) {
                $errors[] = "Formato de fecha inválido: $date";
                continue;
            }
            
            // Formatear fecha si es necesario (de d/m/Y a Y-m-d)
            if (strpos($date, '/') !== false) {
                $date = $this->formatDateToDatabase($date);
            }
            
            // Validar que esté dentro del rango del evento
            if (!$this->isDateWithinEventRange($date, $eventId)) {
                $errors[] = "Fecha fuera del rango del evento: $date";
                continue;
            }
            
            // Si ya existe, saltamos esta fecha
            if ($this->exists($companyId, $eventId, $date)) {
                continue;
            }
            
            // Agregar el día de asistencia
            $data = [
                'company_id' => $companyId,
                'event_id' => $eventId,
                'attendance_date' => $date
            ];
            
            if ($this->create($data)) {
                $addedCount++;
            } else {
                $errors[] = "Error al agregar la fecha: $date";
            }
        }
        
        return [
            'success' => $addedCount > 0,
            'added' => $addedCount,
            'errors' => $errors
        ];
    }
    
    /**
     * Verificar si una fecha está dentro del rango de fechas de un evento
     * 
     * @param string $date Fecha a verificar (formato Y-m-d)
     * @param int $eventId ID del evento
     * @return bool True si la fecha está dentro del rango, false en caso contrario
     */
    public function isDateWithinEventRange($date, $eventId) {
        // Formatear fecha si es necesario
        if (strpos($date, '/') !== false) {
            $date = $this->formatDateToDatabase($date);
        }
        
        $query = "SELECT COUNT(*) FROM events 
                  WHERE event_id = :event_id 
                  AND :date BETWEEN start_date AND end_date";
                  
        $params = [
            'event_id' => $eventId,
            'date' => $date
        ];
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Validar formato de fecha
     * 
     * @param string $date Fecha a validar
     * @return bool True si el formato es válido, false en caso contrario
     */
    public function isValidDate($date) {
        // Validar formato Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            return $d && $d->format('Y-m-d') === $date;
        }
        
        // Validar formato d/m/Y
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) {
            $d = DateTime::createFromFormat('d/m/Y', $date);
            return $d && $d->format('d/m/Y') === $date;
        }
        
        return false;
    }
    /**
 * Formatear fecha de d/m/Y a Y-m-d para almacenar en la base de datos
 * 
 * @param string $date Fecha en formato d/m/Y
 * @return string Fecha en formato Y-m-d
 */
public function formatDateToDatabase($date) {
    if (empty($date)) return null;
    
    $dateObj = DateTime::createFromFormat('d/m/Y', $date);
    return $dateObj ? $dateObj->format('Y-m-d') : null;
}

/**
 * Formatear fecha de Y-m-d a d/m/Y para mostrar en la interfaz
 * 
 * @param string $date Fecha en formato Y-m-d
 * @return string Fecha en formato d/m/Y
 */
public function formatDateFromDatabase($date) {
    if (empty($date)) return '';
    
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    return $dateObj ? $dateObj->format('d/m/Y') : '';
}

/**
 * Obtener compañías que asisten en una fecha específica a un evento
 * 
 * @param int $eventId ID del evento
 * @param string $date Fecha específica (formato Y-m-d)
 * @return array Lista de compañías asistentes en esa fecha
 */
public function getCompaniesByDate($eventId, $date) {
    // Formatear fecha si es necesario
    if (strpos($date, '/') !== false) {
        $date = $this->formatDateToDatabase($date);
    }
    
    $query = "SELECT c.* FROM company c
              JOIN {$this->table} ad ON c.company_id = ad.company_id
              WHERE ad.event_id = :event_id AND ad.attendance_date = :date
              ORDER BY c.company_name ASC";
              
    $params = [
        'event_id' => $eventId,
        'date' => $date
    ];
    
    return $this->db->resultSet($query, $params);
}

/**
 * Obtener fechas con mayor asistencia para un evento
 * 
 * @param int $eventId ID del evento
 * @param int $limit Número máximo de fechas a retornar
 * @return array Lista de fechas con la cantidad de empresas asistentes
 */
public function getTopAttendanceDates($eventId, $limit = 5) {
    $query = "SELECT attendance_date, COUNT(company_id) as company_count 
              FROM {$this->table} 
              WHERE event_id = :event_id
              GROUP BY attendance_date
              ORDER BY company_count DESC, attendance_date ASC
              LIMIT :limit";
              
    $params = [
        'event_id' => $eventId,
        'limit' => $limit
    ];
    
    return $this->db->resultSet($query, $params);
}

/**
 * Establecer propiedades del modelo desde un array de datos
 * 
 * @param array $data Datos a establecer
 * @return void
 */
private function setProperties($data) {
    $this->id = $data['attendance_id'] ?? null;
    $this->company_id = $data['company_id'] ?? null;
    $this->event_id = $data['event_id'] ?? null;
    $this->attendance_date = $data['attendance_date'] ?? null;
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

public function getEventId() {
    return $this->event_id;
}

public function getAttendanceDate() {
    return $this->attendance_date;
}

/**
 * Obtener fecha formateada para mostrar
 * 
 * @return string Fecha en formato d/m/Y
 */
public function getFormattedDate() {
    return $this->formatDateFromDatabase($this->attendance_date);
}
}