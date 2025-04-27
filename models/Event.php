<?php
/**
 * Modelo de Evento
 * 
 * Esta clase maneja todas las operaciones relacionadas con los eventos
 * incluyendo creación, modificación, eliminación y consulta de eventos de networking.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class Event {
    private $db;
    private $table = 'events';
    
    // Propiedades que mapean a las columnas de la tabla events
    private $id;
    private $event_name;
    private $venue;
    private $start_date;
    private $end_date;
    private $available_tables;
    private $meeting_duration;
    private $is_active;
    private $start_time;
    private $end_time;
    private $has_break;
    private $company_name;
    private $contact_name;
    private $contact_phone;
    private $contact_email;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar evento por ID
     * 
     * @param int $id ID del evento a buscar
     * @return bool True si el evento existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE event_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Encontrar evento por nombre
     * 
     * @param string $name Nombre del evento a buscar
     * @return bool True si el evento existe, false en caso contrario
     */
    public function findByName($name) {
        $query = "SELECT * FROM {$this->table} WHERE event_name = :name LIMIT 1";
        $result = $this->db->single($query, ['name' => $name]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si existe un evento por nombre
     * 
     * @param string $name Nombre del evento a verificar
     * @return bool True si el evento existe, false en caso contrario
     */
    public function exists($name) {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE event_name = :name";
        $count = $this->db->query($query, ['name' => $name])->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear un nuevo evento
     * 
     * @param array $data Datos del evento a crear
     * @return bool|int ID del evento creado o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['event_name'], $data['start_date'], $data['end_date'])) {
            return false;
        }
        
        // Formatear fechas si es necesario
        if (isset($data['start_date']) && strpos($data['start_date'], '/') !== false) {
            $data['start_date'] = dateToDatabase($data['start_date']);
        }
        
        if (isset($data['end_date']) && strpos($data['end_date'], '/') !== false) {
            $data['end_date'] = dateToDatabase($data['end_date']);
        }
        
        // Establecer valores por defecto si no están presentes
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }
        
        if (!isset($data['meeting_duration'])) {
            $data['meeting_duration'] = DEFAULT_MEETING_DURATION;
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
     * Actualizar datos de evento
     * 
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function update($data) {
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        // Formatear fechas si es necesario
        if (isset($data['start_date']) && strpos($data['start_date'], '/') !== false) {
            $data['start_date'] = dateToDatabase($data['start_date']);
        }
        
        if (isset($data['end_date']) && strpos($data['end_date'], '/') !== false) {
            $data['end_date'] = dateToDatabase($data['end_date']);
        }
        
        // Generar sentencia de actualización
        $updateFields = [];
        foreach ($data as $key => $value) {
            $updateFields[] = "$key = :$key";
        }
        
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $updateFields) . " 
                  WHERE event_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar evento
     * 
     * @param int $id ID del evento a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $eventId = $id ?? $this->id;
        
        if (!$eventId) {
            return false;
        }
        
        // Iniciar transacción para eliminar registros relacionados
        $this->db->beginTransaction();
        
        try {
            // Eliminar registros relacionados primero
            // Eliminar breaks
            $this->deleteBreaks($eventId);
            
            // Eliminar schedules
            $query = "DELETE FROM event_schedules WHERE event_id = :id";
            $this->db->query($query, ['id' => $eventId]);
            
            // Eliminar matches
            $query = "DELETE FROM matches WHERE event_id = :id";
            $this->db->query($query, ['id' => $eventId]);
            
            // Eliminar attendance_days
            $query = "DELETE FROM attendance_days WHERE event_id = :id";
            $this->db->query($query, ['id' => $eventId]);
            
            // Eliminar event_users
            $query = "DELETE FROM event_users WHERE event_id = :id";
            $this->db->query($query, ['id' => $eventId]);
            
            // Finalmente, eliminar el evento
            $query = "DELETE FROM {$this->table} WHERE event_id = :id";
            $result = $this->db->query($query, ['id' => $eventId]);
            
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
     * Obtener todos los eventos
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de eventos
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
        $query .= " ORDER BY start_date DESC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de eventos
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de eventos
     */
    /*public function count($filters = []) {
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
    }*/

    /**
 * Contar el número total de eventos con filtros opcionales
 * 
 * @param array $filters Filtros a aplicar (is_active, fecha, etc.)
 * @return int Número de eventos que coinciden con los filtros
 */
public function count($filters = []) {
    // Consulta base
    $query = "SELECT COUNT(*) as count FROM events WHERE 1=1";
    $params = [];
    
    // Aplicar filtros
    if (isset($filters['is_active'])) {
        $query .= " AND is_active = :is_active";
        $params[':is_active'] = $filters['is_active'];
    }
    
    // Filtrar por nombre de evento
    if (!empty($filters['event_name'])) {
        $query .= " AND event_name LIKE :event_name";
        $params[':event_name'] = '%' . $filters['event_name'] . '%';
    }
    
    // Filtrar por lugar (venue)
    if (!empty($filters['venue'])) {
        $query .= " AND venue LIKE :venue";
        $params[':venue'] = '%' . $filters['venue'] . '%';
    }
    
    // Filtrar eventos actuales (que están en progreso)
    if (isset($filters['current']) && $filters['current']) {
        $today = date('Y-m-d');
        $query .= " AND start_date <= :today AND end_date >= :today";
        $params[':today'] = $today;
    }
    
    // Filtrar eventos futuros
    if (isset($filters['upcoming']) && $filters['upcoming']) {
        $today = date('Y-m-d');
        $query .= " AND start_date > :today";
        $params[':today'] = $today;
    }
    
    // Filtrar eventos pasados
    if (isset($filters['past']) && $filters['past']) {
        $today = date('Y-m-d');
        $query .= " AND end_date < :today";
        $params[':today'] = $today;
    }
    
    // Filtrar por rango de fechas de inicio
    if (!empty($filters['start_date_from'])) {
        $query .= " AND start_date >= :start_date_from";
        $params[':start_date_from'] = $filters['start_date_from'];
    }
    
    if (!empty($filters['start_date_to'])) {
        $query .= " AND start_date <= :start_date_to";
        $params[':start_date_to'] = $filters['start_date_to'];
    }
    
    // Filtrar por rango de fechas de finalización
    if (!empty($filters['end_date_from'])) {
        $query .= " AND end_date >= :end_date_from";
        $params[':end_date_from'] = $filters['end_date_from'];
    }
    
    if (!empty($filters['end_date_to'])) {
        $query .= " AND end_date <= :end_date_to";
        $params[':end_date_to'] = $filters['end_date_to'];
    }
    
    // Ejecutar la consulta
    try {
        $result = $this->db->single($query, $params);
        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        Logger::error('Error al contar eventos', [
            'filters' => $filters,
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}
    
    /**
     * Obtener eventos activos
     * 
     * @param array $pagination Información de paginación
     * @return array Lista de eventos activos
     */
    public function getActiveEvents($pagination = null) {
        return $this->getAll(['is_active' => 1], $pagination);
    }
    
    /**
     * Obtener eventos actuales (en curso o futuros)
     * 
     * @param array $pagination Información de paginación
     * @return array Lista de eventos actuales
     */
    public function getCurrentEvents($pagination = null) {
        $today = date('Y-m-d');
        $query = "SELECT * FROM {$this->table} WHERE end_date >= :today AND is_active = 1 ORDER BY start_date ASC";
        $params = ['today' => $today];
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    
    /**
     * Obtener compradores participantes en un evento
     * 
     * @param int $eventId ID del evento
     * @return array Lista de compradores
     */
    public function getBuyers($eventId = null) {
        return $this->getParticipants($eventId, 'buyer');
    }
    
    /**
     * Obtener proveedores participantes en un evento
     * 
     * @param int $eventId ID del evento
     * @return array Lista de proveedores
     */
    public function getSuppliers($eventId = null) {
        return $this->getParticipants($eventId, 'supplier');
    }
    

    
    /**
     * Agregar día de asistencia para una empresa
     * 
     * @param int $companyId ID de la empresa
     * @param string $date Fecha de asistencia (formato Y-m-d)
     * @param int $eventId ID del evento
     * @return bool True si se agregó correctamente, false en caso contrario
     */
    public function addAttendanceDay($companyId, $date, $eventId = null) {
        $id = $eventId ?? $this->id;
        
        if (!$id || !$companyId || !$date) {
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
            'event_id' => $id,
            'company_id' => $companyId,
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
     * @param int $companyId ID de la empresa
     * @param string $date Fecha de asistencia (formato Y-m-d)
     * @param int $eventId ID del evento
     * @return bool True si se eliminó correctamente, false en caso contrario
     */
    public function removeAttendanceDay($companyId, $date, $eventId = null) {
        $id = $eventId ?? $this->id;
        
        if (!$id || !$companyId || !$date) {
            return false;
        }
        
        // Formatear fecha si es necesario
        if (strpos($date, '/') !== false) {
            $date = dateToDatabase($date);
        }
        
        $query = "DELETE FROM attendance_days 
                  WHERE event_id = :event_id AND company_id = :company_id AND attendance_date = :date";
        
        $params = [
            'event_id' => $id,
            'company_id' => $companyId,
            'date' => $date
        ];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Obtener breaks (descansos) de un evento
     * 
     * @param int $eventId ID del evento
     * @return array Lista de breaks
     */
    public function getBreaks($eventId = null) {
        $id = $eventId ?? $this->id;
        
        if (!$id) {
            return [];
        }
        
        $query = "SELECT * FROM breaks WHERE event_id = :event_id ORDER BY start_time";
        $params = ['event_id' => $id];
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Agregar break (descanso) a un evento
     * 
     * @param string $startTime Hora de inicio (formato H:i:s)
     * @param string $endTime Hora de fin (formato H:i:s)
     * @param int $eventId ID del evento
     * @return bool|int ID del break creado o false en caso de error
     */
    public function addBreak($startTime, $endTime, $eventId = null) {
        $id = $eventId ?? $this->id;
        
        if (!$id || !$startTime || !$endTime) {
            return false;
        }
        
        $query = "INSERT INTO breaks (event_id, start_time, end_time) 
                  VALUES (:event_id, :start_time, :end_time)";
        
        $params = [
            'event_id' => $id,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        if ($this->db->query($query, $params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar un break (descanso)
     * 
     * @param int $breakId ID del break
     * @param string $startTime Hora de inicio (formato H:i:s)
     * @param string $endTime Hora de fin (formato H:i:s)
     * @return bool True si se actualizó correctamente, false en caso contrario
     */
    public function updateBreak($breakId, $startTime, $endTime) {
        if (!$breakId || !$startTime || !$endTime) {
            return false;
        }
        
        $query = "UPDATE breaks SET start_time = :start_time, end_time = :end_time 
                  WHERE break_id = :break_id";
        
        $params = [
            'break_id' => $breakId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Eliminar un break (descanso)
     * 
     * @param int $breakId ID del break
     * @return bool True si se eliminó correctamente, false en caso contrario
     */
    public function deleteBreak($breakId) {
        if (!$breakId) {
            return false;
        }
        
        $query = "DELETE FROM breaks WHERE break_id = :break_id";
        $params = ['break_id' => $breakId];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Eliminar todos los breaks de un evento
     * 
     * @param int $eventId ID del evento
     * @return bool True si se eliminaron correctamente, false en caso contrario
     */
    public function deleteBreaks($eventId = null) {
        $id = $eventId ?? $this->id;
        
        if (!$id) {
            return false;
        }
        
        $query = "DELETE FROM breaks WHERE event_id = :event_id";
        $params = ['event_id' => $id];
        
        return $this->db->query($query, $params) ? true : false;
    }
    
    /**
     * Obtener citas programadas para un evento
     * 
     * @param int $eventId ID del evento
     * @param array $filters Filtros adicionales
     * @return array Lista de citas
     */
    public function getSchedules($eventId = null, $filters = []) {
        $id = $eventId ?? $this->id;
        
        if (!$id) {
            return [];
        }
        
        $query = "SELECT es.*, m.buyer_id, m.supplier_id, 
                  b.company_name as buyer_name, s.company_name as supplier_name 
                  FROM event_schedules es 
                  JOIN matches m ON es.match_id = m.match_id 
                  JOIN company b ON m.buyer_id = b.company_id 
                  JOIN company s ON m.supplier_id = s.company_id 
                  WHERE es.event_id = :event_id";
        
        $params = ['event_id' => $id];
        
        // Aplicar filtros adicionales
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $query .= " AND $key = :$key";
                $params[$key] = $value;
            }
        }
        
        $query .= " ORDER BY es.start_datetime";
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Generar slots de tiempo disponibles para citas
     * 
     * @param int $eventId ID del evento
     * @param string $date Fecha específica (formato Y-m-d)
     * @return array Lista de slots de tiempo
     */
    public function generateTimeSlots($eventId = null, $date = null) {
        $id = $eventId ?? $this->id;
        
        if (!$id) {
            return [];
        }
        
        // Si no se especifica fecha, usamos la fecha de inicio del evento
        if (!$date) {
            $date = $this->start_date;
        }
        
        // Formatear fecha si es necesario
        if (strpos($date, '/') !== false) {
            $date = dateToDatabase($date);
        }
        
        // Obtener información del evento
        if (!$this->findById($id)) {
            return [];
        }
        
        // Obtener breaks del evento
        $breaks = $this->getBreaks($id);
        
        // Duración de las reuniones en minutos
        $duration = $this->meeting_duration ?? DEFAULT_MEETING_DURATION;
        
        // Convertir horas de inicio y fin a minutos desde el inicio del día
        $startTimeParts = explode(':', $this->start_time);
        $endTimeParts = explode(':', $this->end_time);
        
        $startMinutes = (int)$startTimeParts[0] * 60 + (int)$startTimeParts[1];
        $endMinutes = (int)$endTimeParts[0] * 60 + (int)$endTimeParts[1];
        
        // Convertir breaks a minutos desde el inicio del día
        $breakIntervals = [];
        foreach ($breaks as $break) {
            $startParts = explode(':', $break['start_time']);
            $endParts = explode(':', $break['end_time']);
            
            $breakStart = (int)$startParts[0] * 60 + (int)$startParts[1];
            $breakEnd = (int)$endParts[0] * 60 + (int)$endParts[1];
            
            $breakIntervals[] = [
                'start' => $breakStart,
                'end' => $breakEnd
            ];
        }
        
        // Generar slots
        $slots = [];
        $currentTime = $startMinutes;
        
        while ($currentTime + $duration <= $endMinutes) {
            $slotStart = $currentTime;
            $slotEnd = $currentTime + $duration;
            
            // Verificar si el slot coincide con algún break
            $isBreak = false;
            foreach ($breakIntervals as $break) {
                // Si el slot se superpone con un break
                if (!($slotEnd <= $break['start'] || $slotStart >= $break['end'])) {
                    $isBreak = true;
                    break;
                }
            }
            
            if (!$isBreak) {
                // Formatear las horas
                $startHour = floor($slotStart / 60);
                $startMinute = $slotStart % 60;
                $endHour = floor($slotEnd / 60);
                $endMinute = $slotEnd % 60;
                
                $startFormatted = sprintf('%02d:%02d:00', $startHour, $startMinute);
                $endFormatted = sprintf('%02d:%02d:00', $endHour, $endMinute);
                
                $slots[] = [
                    'start' => $startFormatted,
                    'end' => $endFormatted,
                    'start_datetime' => $date . ' ' . $startFormatted,
                    'end_datetime' => $date . ' ' . $endFormatted
                ];
            }
            
            $currentTime += $duration;
        }
        
        return $slots;
    }
    
    /**
     * Generar citas automáticas basadas en matches
     * 
     * @param int $eventId ID del evento
     * @param array $matchIds IDs de matches (opcional, si no se especifica se usan todos los matches)
     * @return array Resultado de la generación de citas
     */
    public function generateSchedules($eventId = null, $matchIds = []) {
        $id = $eventId ?? $this->id;
        
        if (!$id) {
            return [
                'success' => false,
                'message' => 'ID de evento no especificado',
                'total' => 0,
                'scheduled' => 0
            ];
        }
        
        // Obtener información del evento
        if (!$this->findById($id)) {
            return [
                'success' => false,
                'message' => 'Evento no encontrado',
                'total' => 0,
                'scheduled' => 0
            ];
        }
        
        // Iniciar transacción
        $this->db->beginTransaction();
        
        try {
            // Obtener matches para el evento
            $matchQuery = "SELECT m.* FROM matches m WHERE m.event_id = :event_id";
            $matchParams = ['event_id' => $id];
            
            if (!empty($matchIds)) {
                $matchQuery .= " AND m.match_id IN (" . implode(',', array_map('intval', $matchIds)) . ")";
            }
            
            $matchQuery .= " ORDER BY m.match_strength DESC";
            $matches = $this->db->resultSet($matchQuery, $matchParams);
            
            if (empty($matches)) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'No hay matches disponibles para programar',
                    'total' => 0,
                    'scheduled' => 0
                ];
            }
            
            // Obtener días del evento
            $startDate = new DateTime($this->start_date);
            $endDate = new DateTime($this->end_date);
            $interval = $startDate->diff($endDate);
            $totalDays = $interval->days + 1;
            
            $eventDays = [];
            $currentDate = clone $startDate;
            
            for ($i = 0; $i < $totalDays; $i++) {
                $eventDays[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }
            
            // Generar slots de tiempo para cada día
            $allSlots = [];
            foreach ($eventDays as $day) {
                $slots = $this->generateTimeSlots($id, $day);
                foreach ($slots as $slot) {
                    $allSlots[] = $slot;
                }
            }
            
            if (empty($allSlots)) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'No hay slots de tiempo disponibles',
                    'total' => count($matches),
                    'scheduled' => 0
                ];
            }
            
            // Obtener disponibilidad de empresas por día
            $companyAvailability = [];
            foreach ($matches as $match) {
                $buyerId = $match['buyer_id'];
                $supplierId = $match['supplier_id'];
                
                // Obtener días de asistencia para ambas empresas
                $buyerDays = $this->getAttendanceDays($buyerId, $id);
                $supplierDays = $this->getAttendanceDays($supplierId, $id);
                
                // Encontrar días comunes
                $commonDays = array_intersect($buyerDays, $supplierDays);
                
                $companyAvailability[$match['match_id']] = [
                    'buyer_id' => $buyerId,
                    'supplier_id' => $supplierId,
                    'days' => $commonDays
                ];
            }
            
            // Inicializar variables para seguimiento
            $scheduledCount = 0;
            $tables = $this->available_tables;
            $usedSlots = []; // [date_time][table] = true/false
            $companySchedules = []; // [company_id][date_time] = true/false
            
            // Programar citas
            foreach ($matches as $match) {
                $matchId = $match['match_id'];
                $buyerId = $match['buyer_id'];
                $supplierId = $match['supplier_id'];
                
                // Verificar si hay días comunes
                $availability = $companyAvailability[$matchId] ?? null;
                if (!$availability || empty($availability['days'])) {
                    continue;
                }
                
                $scheduled = false;
                
                // Intentar programar en cada día disponible
                foreach ($availability['days'] as $day) {
                    if ($scheduled) break;
                    
                    // Filtrar slots para este día
                    $daySlots = array_filter($allSlots, function($slot) use ($day) {
                        return strpos($slot['start_datetime'], $day) === 0;
                    });
                    
                    if (empty($daySlots)) continue;
                    
                    // Iterar por cada slot de tiempo en este día
                    foreach ($daySlots as $slot) {
                        if ($scheduled) break;
                        
                        $startDateTime = $slot['start_datetime'];
                        $endDateTime = $slot['end_datetime'];
                        
                        // Verificar si el comprador ya tiene cita en este horario
                        if (isset($companySchedules[$buyerId . '_' . $startDateTime])) {
                            continue;
                        }
                        
                        // Verificar si el proveedor ya tiene cita en este horario
                        if (isset($companySchedules[$supplierId . '_' . $startDateTime])) {
                            continue;
                        }
                        
                        // Buscar mesa disponible
                        $tableAssigned = false;
                        for ($tableNum = 1; $tableNum <= $tables; $tableNum++) {
                            $slotTableKey = $startDateTime . '_' . $tableNum;
                            
                            // Si la mesa está libre en este horario
                            if (!isset($usedSlots[$slotTableKey])) {
                                // Marcar la mesa como ocupada para este horario
                                $usedSlots[$slotTableKey] = true;
                                
                                // Crear la cita
                                $scheduleData = [
                                    'event_id' => $id,
                                    'match_id' => $matchId,
                                    'table_number' => $tableNum,
                                    'start_datetime' => $startDateTime,
                                    'end_datetime' => $endDateTime,
                                    'status' => 'scheduled',
                                    'is_manual' => 0
                                ];
                                
                                $query = "INSERT INTO event_schedules (event_id, match_id, table_number, start_datetime, end_datetime, status, is_manual) 
                                        VALUES (:event_id, :match_id, :table_number, :start_datetime, :end_datetime, :status, :is_manual)";
                                
                                if ($this->db->query($query, $scheduleData)) {
                                    // Marcar horario como ocupado para ambas empresas
                                    $companySchedules[$buyerId . '_' . $startDateTime] = true;
                                    $companySchedules[$supplierId . '_' . $startDateTime] = true;
                                    
                                    $tableAssigned = true;
                                    $scheduled = true;
                                    $scheduledCount++;
                                    break;
                                }
                            }
                        }
                        
                        if ($tableAssigned) break;
                    }
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => "Se programaron $scheduledCount citas exitosamente",
                'total' => count($matches),
                'scheduled' => $scheduledCount
            ];
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => 'Error al generar agenda: ' . $e->getMessage(),
                'total' => count($matches ?? []),
                'scheduled' => 0
            ];
        }
    }

    /**
 * Contar eventos próximos
 * 
 * @param array|null $eventIds Lista específica de IDs de eventos a considerar
 * @return int Número de eventos próximos
 */
public function countUpcomingEvents($eventIds = null) {
    $today = date('Y-m-d');
    
    if ($eventIds !== null && is_array($eventIds)) {
        // Si se proporciona una lista específica de IDs
        if (empty($eventIds)) {
            return 0;
        }
        
        $placeholders = [];
        $params = [':today' => $today];
        
        foreach ($eventIds as $index => $id) {
            $placeholder = ":id_$index";
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }
        
        $query = "SELECT COUNT(*) as count FROM events 
                  WHERE end_date >= :today 
                  AND is_active = 1 
                  AND event_id IN (" . implode(", ", $placeholders) . ")";
    } else {
        // Contar todos los eventos próximos
        $query = "SELECT COUNT(*) as count FROM events 
                  WHERE end_date >= :today 
                  AND is_active = 1";
        $params = [':today' => $today];
    }
    
    $result = $this->db->single($query, $params);
    return $result ? (int)$result['count'] : 0;
}

/**
 * Formatear fecha para mostrar
 * 
 * @param string $dateStr Fecha en formato Y-m-d
 * @return string Fecha formateada
 */
private function formatDate($dateStr) {
    // Puedes implementar tu propia lógica de formato o usar una función existente
    // Por ejemplo, podrías tener una función dateFromDatabase() en algún lugar
    if (function_exists('dateFromDatabase')) {
        return dateFromDatabase($dateStr);
    }
    
    // Implementación básica de formato
    $date = new DateTime($dateStr);
    return $date->format('d/m/Y');
}
/**
 * Obtener los eventos más activos según número de citas o matches
 * 
 * @param int $limit Número máximo de eventos a retornar
 * @return array Lista de eventos con sus estadísticas
 */
public function getTopEvents($limit = 5) {
    $events = $this->getAll(['is_active' => 1]);
    $topEvents = [];
    
    foreach ($events as $event) {
        $eventId = $event['event_id'];
        
        // Obtener conteos relacionados
        $matchModel = new MatchModel($this->db);
        $appointmentModel = new Appointment($this->db);
        
        $matchesCount = $matchModel->count(['event_id' => $eventId]);
        $appointmentsCount = $appointmentModel->count(['event_id' => $eventId]);
        $participantsCount = count($this->getParticipants($eventId));
        
        $topEvents[] = [
            'id' => $eventId,
            'name' => $event['event_name'],
            'start_date' => $event['start_date'],
            'end_date' => $event['end_date'],
            'matches' => $matchesCount,
            'appointments' => $appointmentsCount,
            'participants' => $participantsCount,
            'activity_score' => $matchesCount + ($appointmentsCount * 2) + $participantsCount
        ];
    }
    
    // Ordenar por puntuación de actividad en orden descendente
    usort($topEvents, function($a, $b) {
        return $b['activity_score'] - $a['activity_score'];
    });
    
    // Limitar número de resultados
    return array_slice($topEvents, 0, $limit);
}

/**
 * Obtener el nombre del evento actual
 * 
 * @return string Nombre del evento
 */
public function getEventName() {
    // Si el nombre ya está cargado en la propiedad, devolverlo
    if (isset($this->event_name)) {
        return $this->event_name;
    }
    
    // Si tenemos un ID pero no el nombre, buscarlo en la base de datos
    if (isset($this->event_id) && $this->event_id) {
        $query = "SELECT event_name FROM events WHERE event_id = :event_id LIMIT 1";
        $result = $this->db->single($query, [':event_id' => $this->event_id]);
        
        if ($result) {
            $this->event_name = $result['event_name'];
            return $this->event_name;
        }
    }
    
    // Si no se encuentra o no hay ID, devolver cadena vacía
    return '';
}

/**
 * Obtener la fecha de inicio del evento
 * 
 * @param string $format Formato de fecha (opcional)
 * @return string Fecha de inicio en el formato especificado
 */
public function getStartDate($format = 'Y-m-d') {
    // Si la fecha ya está cargada en la propiedad, devolverla formateada
    if (isset($this->start_date)) {
        if ($format === null) {
            return $this->start_date;
        }
        
        try {
            $date = new DateTime($this->start_date);
            return $date->format($format);
        } catch (Exception $e) {
            Logger::error('Error al formatear fecha de inicio: ' . $e->getMessage());
            return $this->start_date;
        }
    }
    
    // Si tenemos un ID pero no la fecha, buscarla en la base de datos
    if (isset($this->event_id) && $this->event_id) {
        $query = "SELECT start_date FROM events WHERE event_id = :event_id LIMIT 1";
        $result = $this->db->single($query, [':event_id' => $this->event_id]);
        
        if ($result) {
            $this->start_date = $result['start_date'];
            
            if ($format === null) {
                return $this->start_date;
            }
            
            try {
                $date = new DateTime($this->start_date);
                return $date->format($format);
            } catch (Exception $e) {
                Logger::error('Error al formatear fecha de inicio: ' . $e->getMessage());
                return $this->start_date;
            }
        }
    }
    
    // Si no se encuentra o no hay ID, devolver cadena vacía
    return '';
}

/**
 * Obtener la fecha de finalización del evento
 * 
 * @param string $format Formato de fecha (opcional)
 * @return string Fecha de finalización en el formato especificado
 */
public function getEndDate($format = 'Y-m-d') {
    // Si la fecha ya está cargada en la propiedad, devolverla formateada
    if (isset($this->end_date)) {
        if ($format === null) {
            return $this->end_date;
        }
        
        try {
            $date = new DateTime($this->end_date);
            return $date->format($format);
        } catch (Exception $e) {
            Logger::error('Error al formatear fecha de finalización: ' . $e->getMessage());
            return $this->end_date;
        }
    }
    
    // Si tenemos un ID pero no la fecha, buscarla en la base de datos
    if (isset($this->event_id) && $this->event_id) {
        $query = "SELECT end_date FROM events WHERE event_id = :event_id LIMIT 1";
        $result = $this->db->single($query, [':event_id' => $this->event_id]);
        
        if ($result) {
            $this->end_date = $result['end_date'];
            
            if ($format === null) {
                return $this->end_date;
            }
            
            try {
                $date = new DateTime($this->end_date);
                return $date->format($format);
            } catch (Exception $e) {
                Logger::error('Error al formatear fecha de finalización: ' . $e->getMessage());
                return $this->end_date;
            }
        }
    }
    
    // Si no se encuentra o no hay ID, devolver cadena vacía
    return '';
}

/**
 * Verificar si el evento está activo
 * 
 * @return bool True si el evento está activo, false en caso contrario
 */
public function isActive() {
    // Si ya tenemos el estado cargado en la propiedad, devolverlo
    if (isset($this->is_active)) {
        return (bool)$this->is_active;
    }
    
    // Si tenemos un ID pero no el estado, buscarlo en la base de datos
    if (isset($this->event_id) && $this->event_id) {
        $query = "SELECT is_active FROM events WHERE event_id = :event_id LIMIT 1";
        $result = $this->db->single($query, [':event_id' => $this->event_id]);
        
        if ($result) {
            $this->is_active = $result['is_active'];
            return (bool)$this->is_active;
        }
    }
    
    // Por defecto, asumir que no está activo
    return false;
}

/**
 * Obtener el lugar donde se realizará el evento
 * 
 * @return string Lugar del evento
 */
public function getVenue() {
    // Si ya tenemos el lugar cargado en la propiedad, devolverlo
    if (isset($this->venue)) {
        return $this->venue;
    }
    
    // Si tenemos un ID pero no el lugar, buscarlo en la base de datos
    if (isset($this->event_id) && $this->event_id) {
        $query = "SELECT venue FROM events WHERE event_id = :event_id LIMIT 1";
        $result = $this->db->single($query, [':event_id' => $this->event_id]);
        
        if ($result) {
            $this->venue = $result['venue'];
            return $this->venue;
        }
    }
    
    // Si no se encuentra o no hay ID, devolver cadena vacía
    return '';
}

/**
 * Obtener el número de mesas disponibles para el evento
 * 
 * @return int Número de mesas disponibles
 */
public function getAvailableTables() {
    // Si ya tenemos el número de mesas cargado en la propiedad, devolverlo
    if (isset($this->available_tables)) {
        return (int)$this->available_tables;
    }
    
    // Si tenemos un ID pero no el número de mesas, buscarlo en la base de datos
    if (isset($this->event_id) && $this->event_id) {
        $query = "SELECT available_tables FROM events WHERE event_id = :event_id LIMIT 1";
        $result = $this->db->single($query, [':event_id' => $this->event_id]);
        
        if ($result) {
            $this->available_tables = $result['available_tables'];
            return (int)$this->available_tables;
        }
    }
    
    // Por defecto, devolver 0
    return 0;
}

/**
 * Obtener la duración de las reuniones en el evento (en minutos)
 * 
 * @return int Duración de las reuniones en minutos
 */
public function getMeetingDuration() {
    // Si ya tenemos la duración cargada en la propiedad, devolverla
    if (isset($this->meeting_duration)) {
        return (int)$this->meeting_duration;
    }
    
    // Si tenemos un ID pero no la duración, buscarla en la base de datos
    if (isset($this->event_id) && $this->event_id) {
        $query = "SELECT meeting_duration FROM events WHERE event_id = :event_id LIMIT 1";
        $result = $this->db->single($query, [':event_id' => $this->event_id]);
        
        if ($result) {
            $this->meeting_duration = $result['meeting_duration'];
            return (int)$this->meeting_duration;
        }
    }
    
    // Si no se encuentra o no hay ID, devolver el valor por defecto
    return defined('DEFAULT_MEETING_DURATION') ? DEFAULT_MEETING_DURATION : 30;
}

/**
 * Contar eventos sin participantes
 * 
 * @return int Número de eventos sin participantes
 */
public function countWithoutParticipants() {
    Logger::debug('Contando eventos sin participantes');
    
    // Primero intentamos consultar todos los eventos activos
    $query = "SELECT e.event_id, e.event_name 
                FROM events e
                WHERE e.is_active = 1";
    
    $events = $this->db->resultSet($query);
    
    if (!$events) {
        Logger::warning('No se encontraron eventos activos');
        return 0;
    }
    
    $count = 0;
    
    // Para cada evento, verificamos si tiene participantes
    foreach ($events as $event) {
        $participantsQuery = "SELECT COUNT(*) as total 
                             FROM event_participants 
                             WHERE event_id = :event_id";
        
        $result = $this->db->single($participantsQuery, [':event_id' => $event['event_id']]);
        
        if ($result && $result['total'] == 0) {
            $count++;
            Logger::debug("Evento sin participantes encontrado: {$event['event_name']} (ID: {$event['event_id']})");
        }
    }
    
    Logger::info("Total de eventos sin participantes: $count");
    return $count;
}

/**
 * Contar eventos sin categorías asignadas
 * 
 * @return int Número de eventos sin categorías
 */
public function countWithoutCategories() {
    Logger::debug('Contando eventos sin categorías asignadas');
    
    // Consulta para encontrar eventos que no tienen categorías asignadas
    $query = "SELECT COUNT(e.id) AS total
              FROM events e
              LEFT JOIN event_categories ec ON e.id = ec.event_id
              WHERE e.is_active = 1 AND ec.event_id IS NULL";
    
    $result = $this->db->single($query);
    
    if (!$result) {
        Logger::warning('Error al contar eventos sin categorías');
        return 0;
    }
    
    $count = $result['total'];
    Logger::info("Total de eventos sin categorías: $count");
    
    return $count;
}

/**
 * Obtener estadísticas de días de asistencia para un evento específico
 * 
 * @param int $eventId ID del evento
 * @return array Estadísticas de días de asistencia
 */
public function getAttendanceDaysStats($eventId) {
    Logger::debug("Obteniendo estadísticas de asistencia para el evento ID: $eventId");
    
    // Verificar si tenemos el ID del evento
    if (!$eventId) {
        // Si no tenemos ID pero tenemos una instancia cargada, usamos su ID
        if ($this->id) {
            $eventId = $this->id;
        } else {
            Logger::warning('No se proporcionó ID de evento para obtener estadísticas de asistencia');
            return [];
        }
    }
    
    // Obtenemos el rango de fechas del evento
    $eventQuery = "SELECT start_date, end_date FROM events WHERE id = :event_id LIMIT 1";
    $eventData = $this->db->single($eventQuery, [':event_id' => $eventId]);
    
    if (!$eventData) {
        Logger::warning("No se encontró información del evento ID: $eventId");
        return [];
    }
    
    // Consultamos los días de asistencia de empresas participantes
    $query = "SELECT a.attendance_date, COUNT(a.company_id) as company_count
              FROM event_attendance a
              WHERE a.event_id = :event_id
              GROUP BY a.attendance_date
              ORDER BY a.attendance_date ASC";
    
    $stats = $this->db->resultSet($query, [':event_id' => $eventId]);
    
    if (!$stats) {
        Logger::info("No se encontraron datos de asistencia para el evento ID: $eventId");
        return [];
    }
    
    // Formateamos los resultados para la visualización
    $formattedStats = [];
    foreach ($stats as $day) {
        $formattedStats[] = [
            'date' => dateFromDatabase($day['attendance_date']), // Usando función helper para formato de fecha
            'count' => $day['company_count'],
        ];
    }
    
    // Calculamos también algunas métricas adicionales
    $totalDays = (strtotime($eventData['end_date']) - strtotime($eventData['start_date'])) / (60 * 60 * 24) + 1;
    $totalCompanies = $this->getTotalParticipantsCount($eventId);
    
    $summary = [
        'event_duration' => $totalDays,
        'total_participants' => $totalCompanies,
        'daily_stats' => $formattedStats,
        'peak_day' => $this->getPeakAttendanceDay($formattedStats),
    ];
    
    Logger::info("Estadísticas de asistencia generadas para evento ID: $eventId");
    return $summary;
}

/**
 * Método auxiliar para obtener el total de participantes de un evento
 * 
 * @param int $eventId ID del evento
 * @return int Número total de participantes
 */
private function getTotalParticipantsCount($eventId) {
    $query = "SELECT COUNT(DISTINCT company_id) as total 
              FROM event_participants 
              WHERE event_id = :event_id";
    
    $result = $this->db->single($query, [':event_id' => $eventId]);
    
    return $result ? $result['total'] : 0;
}

/**
 * Método auxiliar para encontrar el día con mayor asistencia
 * 
 * @param array $stats Estadísticas diarias de asistencia
 * @return array|null Información del día con mayor asistencia
 */
private function getPeakAttendanceDay($stats) {
    if (empty($stats)) {
        return null;
    }
    
    $peak = null;
    foreach ($stats as $day) {
        if ($peak === null || $day['count'] > $peak['count']) {
            $peak = $day;
        }
    }
    
    return $peak;
}

/**
 * Obtener todos los participantes (empresas) de un evento
 * 
 * @param int|null $eventId ID del evento (opcional, usa el evento actual si no se proporciona)
 * @return array Lista de participantes
 */
public function getParticipants($eventId = null) {
    $id = $eventId ?? $this->event_id;
    if (!$id) {
        return [];
    }
    $query = "SELECT a.*, c.company_name
              FROM assistants a
              LEFT JOIN company c ON a.company_id = c.company_id
              WHERE c.event_id = :event_id";
    $params = [':event_id' => $id];
    $result = $this->db->resultSet($query, $params);
    return $result ?: [];
}

/**
 * Establecer propiedades del objeto a partir de un array de datos
 * 
 * @param array $data Datos para establecer propiedades
 * @return void
 */
public function setProperties($data) {
    // Asignar valores a las propiedades de la instancia
    $this->event_id = $data['event_id'] ?? null;
    $this->event_name = $data['event_name'] ?? '';
    $this->venue = $data['venue'] ?? '';
    $this->start_date = $data['start_date'] ?? null;
    $this->end_date = $data['end_date'] ?? null;
    $this->start_time = $data['start_time'] ?? null;
    $this->end_time = $data['end_time'] ?? null;
    $this->available_tables = $data['available_tables'] ?? 0;
    $this->meeting_duration = $data['meeting_duration'] ?? DEFAULT_MEETING_DURATION;
    $this->has_break = $data['has_break'] ?? 0;
    $this->company_name = $data['company_name'] ?? '';
    $this->contact_name = $data['contact_name'] ?? '';
    $this->contact_phone = $data['contact_phone'] ?? '';
    $this->contact_email = $data['contact_email'] ?? '';
    $this->is_active = $data['is_active'] ?? 0;
    $this->event_logo = $data['event_logo'] ?? null;
    $this->company_logo = $data['company_logo'] ?? null;
    $this->created_at = $data['created_at'] ?? null;
    $this->updated_at = $data['updated_at'] ?? null;
}

/**
 * Obtener el ID del evento
 * @return int|null
 */
public function getId() {
    return $this->event_id ?? null;
}

/**
 * Obtener la hora de inicio del evento
 * @return string|null
 */
public function getStartTime() {
    return $this->start_time ?? null;
}

/**
 * Obtener la hora de fin del evento
 * @return string|null
 */
public function getEndTime() {
    return $this->end_time ?? null;
}

/**
 * Obtener el nombre de la empresa organizadora
 * @return string|null
 */
public function getCompanyName() {
    return $this->company_name ?? null;
}

/**
 * Obtener el nombre del contacto del evento
 * @return string|null
 */
public function getContactName() {
    return $this->contact_name ?? null;
}

/**
 * Obtener el email de contacto del evento
 * @return string|null
 */
public function getContactEmail() {
    return $this->contact_email ?? null;
}
}

