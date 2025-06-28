<?php
/**
 * Modelo de Appointment (Citas)
 * 
 * Esta clase maneja todas las operaciones relacionadas con las citas/agendas
 * entre compradores y proveedores en los eventos, incluyendo creación,
 * modificación, eliminación y consulta de citas programadas.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class Appointment {
    private $db;
    private $table = 'event_schedules';
    
    // Propiedades que mapean a las columnas de la tabla event_schedules
    private $id;
    private $event_id;
    private $match_id;
    private $table_number;
    private $start_datetime;
    private $end_datetime;
    private $status;
    private $is_manual;
    
    // Estados posibles para una cita
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar cita por ID
     * 
     * @param int $id ID de la cita a buscar
     * @return bool True si la cita existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE schedule_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si ya existe una cita para un match específico
     * 
     * @param int $matchId ID del match
     * @return bool True si ya existe una cita para este match
     */
    public function existsForMatch($matchId) {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE match_id = :match_id";
        $count = $this->db->query($query, ['match_id' => $matchId])->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Crear una nueva cita
     * 
     * @param array $data Datos de la cita a crear
     * @return bool|int ID de la cita creada o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['event_id'], $data['match_id'], $data['start_datetime'], $data['end_datetime'])) {
            return false;
        }
        
        // Verificar si ya existe una cita para este match
        if ($this->existsForMatch($data['match_id'])) {
            return false;
        }
        
        // Formatear fechas si es necesario
        if (isset($data['start_datetime']) && !$this->isValidDateTime($data['start_datetime'])) {
            return false;
        }
        
        if (isset($data['end_datetime']) && !$this->isValidDateTime($data['end_datetime'])) {
            return false;
        }
        
        // Establecer valores por defecto si no están presentes
        if (!isset($data['status'])) {
            $data['status'] = self::STATUS_SCHEDULED;
        }
        
        if (!isset($data['is_manual'])) {
            $data['is_manual'] = 0;
        }
        
        // Verificar disponibilidad del horario y mesa
        if (!$this->isSlotAvailable(
            $data['event_id'],
            $data['start_datetime'],
            $data['end_datetime'],
            $data['table_number'] ?? null
        )) {
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
     * Actualizar datos de una cita
     * 
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function update($data) {
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        // Verificar disponibilidad del nuevo horario y mesa si se están actualizando
        if ((isset($data['start_datetime']) || isset($data['end_datetime']) || isset($data['table_number']))) {
            $startDateTime = $data['start_datetime'] ?? $this->start_datetime;
            $endDateTime = $data['end_datetime'] ?? $this->end_datetime;
            $tableNumber = $data['table_number'] ?? $this->table_number;
            
            if (!$this->isSlotAvailable(
                $this->event_id,
                $startDateTime,
                $endDateTime,
                $tableNumber,
                $this->id
            )) {
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
                  WHERE schedule_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar cita
     * 
     * @param int $id ID de la cita a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $scheduleId = $id ?? $this->id;
        
        if (!$scheduleId) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE schedule_id = :id";
        return $this->db->query($query, ['id' => $scheduleId]) ? true : false;
    }
    
    /**
     * Obtener todas las citas
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de citas
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT es.*, 
                  m.buyer_id, m.supplier_id, 
                  b.company_name as buyer_name, 
                  s.company_name as supplier_name 
                  FROM {$this->table} es
                  JOIN matches m ON es.match_id = m.match_id 
                  JOIN company b ON m.buyer_id = b.company_id 
                  JOIN company s ON m.supplier_id = s.company_id";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                $conditions[] = "es.$key = :$key";
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY es.start_datetime ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de citas
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de citas
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table} es";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                $conditions[] = "es.$key = :$key";
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Obtener citas por evento
     * 
     * @param int $eventId ID del evento
     * @param string $status Estado de la cita (opcional)
     * @param array $pagination Información de paginación
     * @return array Lista de citas para el evento
     */
    public function getByEvent($eventId, $status = null, $pagination = null) {
        $filters = ['event_id' => $eventId];
        
        if ($status) {
            $filters['status'] = $status;
        }
        
        return $this->getAll($filters, $pagination);
    }
    
    /**
     * Obtener citas por compañía (comprador o proveedor)
     * 
     * @param int $companyId ID de la compañía
     * @param int $eventId ID del evento (opcional)
     * @param string $status Estado de la cita (opcional)
     * @param array $pagination Información de paginación
     * @return array Lista de citas para la compañía
     */
    public function getByCompany($companyId, $eventId = null, $status = null, $pagination = null) {
        $query = "SELECT es.*, 
                  m.buyer_id, m.supplier_id, 
                  b.company_name as buyer_name, 
                  s.company_name as supplier_name,
                  CASE 
                    WHEN m.buyer_id = :company_id_ref THEN s.company_name
                    ELSE b.company_name
                  END as partner_company
                  FROM {$this->table} es
                  JOIN matches m ON es.match_id = m.match_id 
                  JOIN company b ON m.buyer_id = b.company_id 
                  JOIN company s ON m.supplier_id = s.company_id
                  WHERE (m.buyer_id = :company_id1 OR m.supplier_id = :company_id2)";
        
        // Usar los nombres correctos de parámetros
        $params = [
            'company_id1' => $companyId, 
            'company_id2' => $companyId,
            'company_id_ref' => $companyId
        ];
        
        if ($eventId) {
            $query .= " AND es.event_id = :event_id";
            $params['event_id'] = $eventId;
        }
        
        if ($status) {
            $query .= " AND es.status = :status";
            $params['status'] = $status;
        }
        
        $query .= " ORDER BY es.start_datetime ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Obtener citas por fecha
     * 
     * @param string $date Fecha en formato Y-m-d
     * @param int $eventId ID del evento (opcional)
     * @param array $pagination Información de paginación
     * @return array Lista de citas para la fecha
     */
    public function getByDate($date, $eventId = null, $pagination = null) {
        $query = "SELECT es.*, 
                  m.buyer_id, m.supplier_id, 
                  b.company_name as buyer_name, 
                  s.company_name as supplier_name 
                  FROM {$this->table} es
                  JOIN matches m ON es.match_id = m.match_id 
                  JOIN company b ON m.buyer_id = b.company_id 
                  JOIN company s ON m.supplier_id = s.company_id
                  WHERE DATE(es.start_datetime) = :date";
        
        $params = ['date' => $date];
        
        if ($eventId) {
            $query .= " AND es.event_id = :event_id";
            $params['event_id'] = $eventId;
        }
        
        $query .= " ORDER BY es.start_datetime ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Actualizar estado de una cita
     * 
     * @param int $appointmentId ID de la cita
     * @param string $status Nuevo estado (scheduled, completed, cancelled)
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function updateStatus($appointmentId, $status) {
        if (!in_array($status, [self::STATUS_SCHEDULED, self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return false;
        }
        
        if ($this->findById($appointmentId)) {
            return $this->update(['status' => $status]);
        }
        
        return false;
    }
    
    /**
     * Verificar si un horario está disponible
     * 
     * @param int $eventId ID del evento
     * @param string $startDateTime Fecha y hora de inicio
     * @param string $endDateTime Fecha y hora de fin
     * @param int|null $tableNumber Número de mesa (opcional)
     * @param int|null $excludeId ID de la cita a excluir de la verificación (para actualizaciones)
     * @return bool True si el horario está disponible
     */
    public function isSlotAvailable($eventId, $startDateTime, $endDateTime, $tableNumber = null, $excludeId = null) {
        // Si no se especifica número de mesa, solo verificamos conflictos de horario por compañía
        if ($tableNumber === null) {
            return true;
        }
        
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE event_id = :event_id 
                  AND table_number = :table_number 
                  AND (
                    (start_datetime < :end_datetime AND end_datetime > :start_datetime)
                  )";
        
        $params = [
            'event_id' => $eventId,
            'table_number' => $tableNumber,
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime
        ];
        
        if ($excludeId) {
            $query .= " AND schedule_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->query($query, $params);
        if ($stmt === false) {
            // Si la consulta falla, asumimos que NO está disponible (mejor que error fatal)
            return false;
        }
        $count = $stmt->fetchColumn();
        return $count == 0;
    }
    
    /**
     * Verificar si una compañía está disponible en un horario específico
     * 
     * @param int $companyId ID de la compañía
     * @param int $eventId ID del evento
     * @param string $startDateTime Fecha y hora de inicio
     * @param string $endDateTime Fecha y hora de fin
     * @param int|null $excludeId ID de la cita a excluir de la verificación (para actualizaciones)
     * @return bool True si la compañía está disponible
     */
    public function isCompanyAvailable($companyId, $eventId, $startDateTime, $endDateTime, $excludeId = null) {
        $query = "SELECT COUNT(*) FROM {$this->table} es
                  JOIN matches m ON es.match_id = m.match_id
                  WHERE es.event_id = :event_id 
                  AND (m.buyer_id = :company_id1 OR m.supplier_id = :company_id2)
                  AND (
                    (es.start_datetime < :end_datetime AND es.end_datetime > :start_datetime)
                  )";
        
        $params = [
            'event_id' => $eventId,
            'company_id1' => $companyId,
            'company_id2' => $companyId,    
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime
        ];
        
        if ($excludeId) {
            $query .= " AND es.schedule_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
            $stmt = $this->db->query($query, $params);
        if ($stmt === false) {
            // Si la consulta falla, asumimos que NO está disponible (mejor que error fatal)
            return false;
        }
        $count = $stmt->fetchColumn();
        return $count == 0;
    }
    
    /**
     * Encontrar una mesa disponible para un horario específico
     * 
     * @param int $eventId ID del evento
     * @param string $startDateTime Fecha y hora de inicio
     * @param string $endDateTime Fecha y hora de fin
     * @return int|false Número de mesa disponible o false si no hay mesas disponibles
     */
    public function findAvailableTable($eventId, $startDateTime, $endDateTime) {
        // Obtener el número de mesas disponibles para el evento
        $eventQuery = "SELECT available_tables FROM events WHERE event_id = :event_id";
        $availableTables = (int) $this->db->query($eventQuery, ['event_id' => $eventId])->fetchColumn();
        
        if ($availableTables <= 0) {
            return false;
        }
        
        // Obtener mesas ocupadas para el horario especificado
        $query = "SELECT table_number FROM {$this->table} 
                  WHERE event_id = :event_id 
                  AND (
                    (start_datetime < :end_datetime AND end_datetime > :start_datetime)
                  )";
        
        $params = [
            'event_id' => $eventId,
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime
        ];
        
        $occupiedTables = $this->db->query($query, $params)->fetchAll(PDO::FETCH_COLUMN);
        
        // Encontrar la primera mesa disponible
        for ($table = 1; $table <= $availableTables; $table++) {
            if (!in_array($table, $occupiedTables)) {
                return $table;
            }
        }
        
        return false;
    }
    
    /**
     * Generar agenda de citas automáticamente para un evento
     * 
     * @param int $eventId ID del evento
     * @param array $matchIds IDs de matches para los que generar citas (opcional)
     * @return array Resultado de la generación de agenda
     */
    public function generateSchedules($eventId, $matchIds = []) {
        // Verificar que el evento existe
        $event = new Event($this->db);
        if (!$event->findById($eventId)) {
            return [
                'success' => false,
                'message' => 'El evento no existe',
                'total' => 0,
                'scheduled' => 0
            ];
        }
        
        // Obtener información del evento
        $eventInfo = [
            'start_date' => $event->getStartDate(),
            'end_date' => $event->getEndDate(),
            'start_time' => $event->getStartTime(),
            'end_time' => $event->getEndTime(),
            'meeting_duration' => $event->getMeetingDuration(),
            'available_tables' => $event->getAvailableTables()
        ];
        
        if (!$eventInfo['available_tables']) {
            return [
                'success' => false,
                'message' => 'El evento no tiene mesas configuradas',
                'total' => 0,
                'scheduled' => 0
            ];
        }
        
        // Iniciar transacción
        $this->db->beginTransaction();
        
        try {
            // Obtener matches para el evento
            $matchQuery = "SELECT m.* FROM matches m WHERE m.event_id = :event_id AND m.status = 'matched'";
            $matchParams = ['event_id' => $eventId];
            
            if (!empty($matchIds)) {
                $matchIds = array_map('intval', $matchIds);
                $matchQuery .= " AND m.match_id IN (" . implode(',', $matchIds) . ")";
            }
            
            $matchQuery .= " ORDER BY m.match_strength DESC";
            $matches = $this->db->resultSet($matchQuery, $matchParams);
            
            if (empty($matches)) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'No hay matches aceptados disponibles para programar',
                    'total' => 0,
                    'scheduled' => 0
                ];
            }
            
            // Generar slots de tiempo
            $timeSlots = $this->generateTimeSlots($eventId, $eventInfo);
            
            if (empty($timeSlots)) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'No se pudieron generar slots de tiempo',
                    'total' => count($matches),
                    'scheduled' => 0
                ];
            }
            
            // Programar citas
            $scheduled = 0;
            $companySchedules = [];  // [company_id][date_time] = true/false
            
            foreach ($matches as $match) {
                $matchId = $match['match_id'];
                $buyerId = $match['buyer_id'];
                $supplierId = $match['supplier_id'];
                
                // Verificar si ya existe una cita para este match
                if ($this->existsForMatch($matchId)) {
                    continue;
                }
                
                // Obtener días de asistencia comunes para ambas empresas
                $attendanceDays = $this->getCommonAttendanceDays($buyerId, $supplierId, $eventId);
                
                if (empty($attendanceDays)) {
                    continue; // No hay días comunes de asistencia
                }
                
                // Intentar programar la cita
                $scheduled = false;
                foreach ($timeSlots as $slot) {
                    $slotDate = date('Y-m-d', strtotime($slot['start_datetime']));
                    
                    // Verificar si ambas empresas asisten este día
                    if (!in_array($slotDate, $attendanceDays)) {
                        continue;
                    }
                    
                    // Verificar disponibilidad de comprador y proveedor en este horario
                    $buyerKey = $buyerId . '_' . $slot['start_datetime'];
                    $supplierKey = $supplierId . '_' . $slot['start_datetime'];
                    
                    if (isset($companySchedules[$buyerKey]) || isset($companySchedules[$supplierKey])) {
                        continue; // Alguna de las empresas ya tiene cita en este horario
                    }
                    
                    // Encontrar una mesa disponible
                    $tableNumber = $this->findAvailableTable($eventId, $slot['start_datetime'], $slot['end_datetime']);
                    
                    if (!$tableNumber) {
                        continue; // No hay mesas disponibles en este horario
                    }
                    
                    // Crear la cita
                    $appointmentData = [
                        'event_id' => $eventId,
                        'match_id' => $matchId,
                        'table_number' => $tableNumber,
                        'start_datetime' => $slot['start_datetime'],
                        'end_datetime' => $slot['end_datetime'],
                        'status' => self::STATUS_SCHEDULED,
                        'is_manual' => 0
                    ];
                    
                    if ($this->create($appointmentData)) {
                        // Marcar este horario como ocupado para ambas empresas
                        $companySchedules[$buyerKey] = true;
                        $companySchedules[$supplierKey] = true;
                        $scheduled++;
                        break; // Cita programada, pasar al siguiente match
                    }
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => "Se programaron $scheduled citas exitosamente",
                'total' => count($matches),
                'scheduled' => $scheduled
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
     * Generar slots de tiempo para un evento
     * 
     * @param int $eventId ID del evento
     * @param array $eventInfo Información del evento
     * @return array Lista de slots de tiempo
     */
    public function generateTimeSlots($eventId, $eventInfo) {
        $slots = [];
        
        // Obtener breaks del evento
        $breaksQuery = "SELECT * FROM breaks WHERE event_id = :event_id ORDER BY start_time";
        $breaks = $this->db->resultSet($breaksQuery, ['event_id' => $eventId]);
        
        // Calcular días del evento
        $startDate = new DateTime($eventInfo['start_date']);
        $endDate = new DateTime($eventInfo['end_date']);
        $interval = $startDate->diff($endDate);
        $days = $interval->days + 1;
        
        $currentDate = clone $startDate;
        
        // Duración de las reuniones en minutos
        $duration = $eventInfo['meeting_duration'];
        
        // Para cada día del evento
        for ($day = 0; $day < $days; $day++) {
            $date = $currentDate->format('Y-m-d');
            
            // Hora de inicio y fin en minutos desde el inicio del día
            $startParts = explode(':', $eventInfo['start_time']);
            $endParts = explode(':', $eventInfo['end_time']);
            
            $startMinutes = (int)$startParts[0] * 60 + (int)$startParts[1];
            $endMinutes = (int)$endParts[0] * 60 + (int)$endParts[1];
            
            // Convertir breaks a minutos desde el inicio del día
            $breakIntervals = [];
            foreach ($breaks as $break) {
                $breakStartParts = explode(':', $break['start_time']);
                $breakEndParts = explode(':', $break['end_time']);
                
                $breakStart = (int)$breakStartParts[0] * 60 + (int)$breakStartParts[1];
                $breakEnd = (int)$breakEndParts[0] * 60 + (int)$breakEndParts[1];
                
                $breakIntervals[] = [
                    'start' => $breakStart,
                    'end' => $breakEnd
                ];
            }
            
            // Generar slots para este día
            $currentTime = $startMinutes;
            
            while ($currentTime + $duration <= $endMinutes) {
                $slotStartMinutes = $currentTime;
                $slotEndMinutes = $currentTime + $duration;
                
                // Verificar si el slot coincide con algún break
                $isBreak = false;
                foreach ($breakIntervals as $break) {
                    // Si el slot se superpone con un break
                    if (!($slotEndMinutes <= $break['start'] || $slotStartMinutes >= $break['end'])) {
                        $isBreak = true;
                        break;
                    }
                }
                
                if (!$isBreak) {
                    // Convertir a formato datetime
                    $startHour = floor($slotStartMinutes / 60);
                    $startMinute = $slotStartMinutes % 60;
                    $endHour = floor($slotEndMinutes / 60);
                    $endMinute = $slotEndMinutes % 60;
                    
                    $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
                    $endTime = sprintf('%02d:%02d:00', $endHour, $endMinute);
                    
                    $slots[] = [
                        'start_datetime' => "$date $startTime",
                        'end_datetime' => "$date $endTime"
                    ];
                }
                
                $currentTime += $duration;
            }
            
            // Avanzar al siguiente día
            $currentDate->modify('+1 day');
        }
        
        return $slots;
    }
    
    /**
     * Obtener días de asistencia comunes para dos empresas
     * 
     * @param int $company1Id ID de la primera empresa
     * @param int $company2Id ID de la segunda empresa
     * @param int $eventId ID del evento
     * @return array Lista de fechas comunes de asistencia
     */
    private function getCommonAttendanceDays($company1Id, $company2Id, $eventId) {
        $query1 = "SELECT attendance_date FROM attendance_days 
                  WHERE event_id = :event_id AND company_id = :company_id 
                  ORDER BY attendance_date";
        
        $query2 = "SELECT attendance_date FROM attendance_days 
                  WHERE event_id = :event_id AND company_id = :company_id 
                  ORDER BY attendance_date";
        
        $days1 = $this->db->resultSet($query1, [
            'event_id' => $eventId, 
            'company_id' => $company1Id
        ]);
        
        $days2 = $this->db->resultSet($query2, [
            'event_id' => $eventId, 
            'company_id' => $company2Id
        ]);
        
        // Convertir a arrays simples
        $dates1 = array_column($days1, 'attendance_date');
        $dates2 = array_column($days2, 'attendance_date');
        
        // Encontrar intersección
        return array_values(array_intersect($dates1, $dates2));
    }
    
   /**
 * Reprogramar una cita
 * 
 * @param int $appointmentId ID de la cita
 * @param string $newStartDateTime Nueva fecha y hora de inicio
 * @param string $newEndDateTime Nueva fecha y hora de fin
 * @param int|null $newTableNumber Nuevo número de mesa (opcional)
 * @return bool True si la reprogramación fue exitosa, false en caso contrario
 */
public function reschedule($appointmentId, $newStartDateTime, $newEndDateTime, $newTableNumber = null) {
    if (!$this->findById($appointmentId)) {
        return false;
    }
    
    // Verificar que las nuevas fechas sean válidas
    if (!$this->isValidDateTime($newStartDateTime) || !$this->isValidDateTime($newEndDateTime)) {
        return false;
    }
    
    // Si no se especifica un nuevo número de mesa, mantener el actual
    if ($newTableNumber === null) {
        $newTableNumber = $this->table_number;
    }
    
    // Verificar disponibilidad del nuevo horario y mesa
    if (!$this->isSlotAvailable(
        $this->event_id,
        $newStartDateTime,
        $newEndDateTime,
        $newTableNumber,
        $this->id
    )) {
        return false;
    }
    
    // Verificar disponibilidad de las empresas para el nuevo horario
    $matchQuery = "SELECT buyer_id, supplier_id FROM matches WHERE match_id = :match_id";
    $matchData = $this->db->single($matchQuery, ['match_id' => $this->match_id]);
    
    if (!$matchData) {
        return false;
    }
    
    $buyerId = $matchData['buyer_id'];
    $supplierId = $matchData['supplier_id'];
    
    // Verificar si las empresas están disponibles en el nuevo horario
    if (!$this->isCompanyAvailable($buyerId, $this->event_id, $newStartDateTime, $newEndDateTime, $this->id) ||
        !$this->isCompanyAvailable($supplierId, $this->event_id, $newStartDateTime, $newEndDateTime, $this->id)) {
        return false;
    }
    
    // Actualizar la cita con los nuevos datos
    $data = [
        'start_datetime' => $newStartDateTime,
        'end_datetime' => $newEndDateTime,
        'table_number' => $newTableNumber,
        'is_manual' => 1 // Marcamos como manual para indicar que fue reprogramada
    ];
    
    return $this->update($data);
}

/**
 * Verificar si un formato de fecha y hora es válido
 * 
 * @param string $dateTime Fecha y hora a verificar (formato Y-m-d H:i:s)
 * @return bool True si es válido, false en caso contrario
 */
private function isValidDateTime($dateTime) {
    $format = 'Y-m-d H:i:s';
    $date = DateTime::createFromFormat($format, $dateTime);
    return $date && $date->format($format) === $dateTime;
}

/**
 * Crear una cita manual
 * 
 * @param int $eventId ID del evento
 * @param int $matchId ID del match
 * @param string $startDateTime Fecha y hora de inicio
 * @param string $endDateTime Fecha y hora de fin
 * @param int|null $tableNumber Número de mesa (si es null, se busca una disponible)
 * @return bool|int ID de la cita creada o false en caso de error
 */
public function createManualAppointment($eventId, $matchId, $startDateTime, $endDateTime, $tableNumber = null) {
    // Verificar que el match existe
    $matchQuery = "SELECT * FROM matches WHERE match_id = :match_id AND event_id = :event_id";
    $matchData = $this->db->single($matchQuery, ['match_id' => $matchId, 'event_id' => $eventId]);
    
    if (!$matchData) {
        return false;
    }
    
    // Verificar que las fechas son válidas
    if (!$this->isValidDateTime($startDateTime) || !$this->isValidDateTime($endDateTime)) {
        return false;
    }
    
    // Si no se especifica una mesa, buscar una disponible
    if ($tableNumber === null) {
        $tableNumber = $this->findAvailableTable($eventId, $startDateTime, $endDateTime);
        if (!$tableNumber) {
            return false; // No hay mesas disponibles
        }
    }
    
    // Verificar si el horario y mesa están disponibles
    if (!$this->isSlotAvailable($eventId, $startDateTime, $endDateTime, $tableNumber)) {
        return false;
    }
    
    // Verificar si las empresas están disponibles
    $buyerId = $matchData['buyer_id'];
    $supplierId = $matchData['supplier_id'];
    
    if (!$this->isCompanyAvailable($buyerId, $eventId, $startDateTime, $endDateTime) ||
        !$this->isCompanyAvailable($supplierId, $eventId, $startDateTime, $endDateTime)) {
        return false;
    }
    
    // Crear la cita
    $appointmentData = [
        'event_id' => $eventId,
        'match_id' => $matchId,
        'table_number' => $tableNumber,
        'start_datetime' => $startDateTime,
        'end_datetime' => $endDateTime,
        'status' => self::STATUS_SCHEDULED,
        'is_manual' => 1
    ];
    
    return $this->create($appointmentData);
}

/**
 * Cancelar una cita
 * 
 * @param int $appointmentId ID de la cita
 * @return bool True si la cancelación fue exitosa, false en caso contrario
 */
public function cancel($appointmentId) {
    return $this->updateStatus($appointmentId, self::STATUS_CANCELLED);
}

/**
 * Marcar una cita como completada
 * 
 * @param int $appointmentId ID de la cita
 * @return bool True si la actualización fue exitosa, false en caso contrario
 */
public function complete($appointmentId) {
    return $this->updateStatus($appointmentId, self::STATUS_COMPLETED);
}

/**
 * Obtener agenda de citas para una fecha específica de un evento
 * 
 * @param int $eventId ID del evento
 * @param string $date Fecha en formato Y-m-d
 * @return array Agenda de citas ordenada por hora y mesa
 */
public function getDailySchedule($eventId, $date) {
    $query = "SELECT es.*, 
              m.buyer_id, m.supplier_id, 
              b.company_name as buyer_name, 
              s.company_name as supplier_name 
              FROM {$this->table} es
              JOIN matches m ON es.match_id = m.match_id 
              JOIN company b ON m.buyer_id = b.company_id 
              JOIN company s ON m.supplier_id = s.company_id
              WHERE es.event_id = :event_id 
              AND DATE(es.start_datetime) = :date
              ORDER BY es.start_datetime ASC, es.table_number ASC";
    
    $params = [
        'event_id' => $eventId,
        'date' => $date
    ];
    
    return $this->db->resultSet($query, $params);
}

/**
 * Verificar si una empresa tiene citas programadas en un evento
 * 
 * @param int $companyId ID de la empresa
 * @param int $eventId ID del evento
 * @return bool True si tiene citas, false en caso contrario
 */
public function hasAppointments($companyId, $eventId) {
    $query = "SELECT COUNT(*) FROM {$this->table} es
              JOIN matches m ON es.match_id = m.match_id
              WHERE es.event_id = :event_id 
              AND (m.buyer_id = :company_id OR m.supplier_id = :company_id)";
    
    $params = [
        'event_id' => $eventId,
        'company_id' => $companyId
    ];
    
    $count = $this->db->query($query, $params)->fetchColumn();
    return $count > 0;
}

/**
 * Establecer propiedades del modelo desde un array de datos
 * 
 * @param array $data Datos a establecer
 * @return void
 */
private function setProperties($data) {
    $this->id = $data['schedule_id'] ?? null;
    $this->event_id = $data['event_id'] ?? null;
    $this->match_id = $data['match_id'] ?? null;
    $this->table_number = $data['table_number'] ?? null;
    $this->start_datetime = $data['start_datetime'] ?? null;
    $this->end_datetime = $data['end_datetime'] ?? null;
    $this->status = $data['status'] ?? null;
    $this->is_manual = $data['is_manual'] ?? null;
}

/**
 * Getters para propiedades privadas
 */
public function getId() {
    return $this->id;
}

public function getEventId() {
    return $this->event_id;
}

public function getMatchId() {
    return $this->match_id;
}

public function getTableNumber() {
    return $this->table_number;
}

public function getStartDateTime() {
    return $this->start_datetime;
}

public function getEndDateTime() {
    return $this->end_datetime;
}

public function getStatus() {
    return $this->status;
}

public function isManual() {
    return (bool) $this->is_manual;
}

/**
 * Formatear fecha y hora de inicio
 * 
 * @param string $format Formato deseado (por defecto d/m/Y H:i)
 * @return string Fecha y hora formateada
 */
public function getFormattedStartDateTime($format = 'd/m/Y H:i') {
    if (!$this->start_datetime) {
        return '';
    }
    
    $dateTime = new DateTime($this->start_datetime);
    return $dateTime->format($format);
}

/**
 * Formatear fecha y hora de fin
 * 
 * @param string $format Formato deseado (por defecto d/m/Y H:i)
 * @return string Fecha y hora formateada
 */
public function getFormattedEndDateTime($format = 'd/m/Y H:i') {
    if (!$this->end_datetime) {
        return '';
    }
    
    $dateTime = new DateTime($this->end_datetime);
    return $dateTime->format($format);
}

/**
 * Obtener la duración de la cita en minutos
 * 
 * @return int Duración en minutos
 */
public function getDuration() {
    if (!$this->start_datetime || !$this->end_datetime) {
        return 0;
    }
    
    $start = new DateTime($this->start_datetime);
    $end = new DateTime($this->end_datetime);
    $diff = $start->diff($end);
    
    return ($diff->h * 60) + $diff->i;
}

/**
 * Calcular tasa de asistencia a citas
 * 
 * @return float Porcentaje de citas completadas vs programadas (0-100)
 */
public function calculateAttendanceRate() {
    $scheduledAppointments = $this->count(['status' => [self::STATUS_SCHEDULED, self::STATUS_COMPLETED]]);
    if ($scheduledAppointments == 0) {
        return 0;
    }
    
    $completedAppointments = $this->count(['status' => self::STATUS_COMPLETED]);
    return round(($completedAppointments / $scheduledAppointments) * 100, 2);
}

/**
 * Calcular tasa de asistencia a citas para un evento específico
 * 
 * @param int $eventId ID del evento
 * @return float Porcentaje de citas completadas vs programadas (0-100)
 */

/**
 * Calcular tasa de asistencia a citas para un evento específico
 * 
 * @param int $eventId ID del evento
 * @return float Porcentaje de citas completadas vs programadas (0-100)
 */
public function calculateEventAttendanceRate($eventId) {
    $scheduledAppointments = $this->count([
        'event_id' => $eventId, 
        'status' => [self::STATUS_SCHEDULED, self::STATUS_COMPLETED]
    ]);
    
    if ($scheduledAppointments == 0) {
        return 0;
    }
    
    $completedAppointments = $this->count([
        'event_id' => $eventId, 
        'status' => self::STATUS_COMPLETED
    ]);
    
    return round(($completedAppointments / $scheduledAppointments) * 100, 2);
}

/**
     * Programar automáticamente una cita para un match (manual, un match a la vez)
     * @param int $eventId
     * @param int $matchId
     * @return int|false ID de la cita creada o false si no se pudo programar
     */
    public function scheduleMatch($eventId, $matchId) {
        // Obtener datos del match
        $matchQuery = "SELECT * FROM matches WHERE match_id = :match_id AND event_id = :event_id";
        $match = $this->db->single($matchQuery, ['match_id' => $matchId, 'event_id' => $eventId]);
        if (!$match) return false;
        $buyerId = $match['buyer_id'];
        $supplierId = $match['supplier_id'];
        // Obtener días de asistencia comunes
        $commonDays = $this->getCommonAttendanceDays($buyerId, $supplierId, $eventId);
        if (empty($commonDays)) return false;
        // Obtener info del evento
        $event = new Event($this->db);
        if (!$event->findById($eventId)) return false;
        $eventInfo = [
            'start_time' => $event->getStartTime(),
            'end_time' => $event->getEndTime(),
            'meeting_duration' => $event->getMeetingDuration(),
            'available_tables' => $event->getAvailableTables()
        ];
        // Para cada día común, buscar el primer slot disponible
        foreach ($commonDays as $day) {
            $slots = $event->generateTimeSlots($eventId, $day);
            foreach ($slots as $slot) {
                // Buscar mesa disponible
                $tableNumber = $this->findAvailableTable($eventId, $slot['start_datetime'], $slot['end_datetime']);
                if (!$tableNumber) continue;
                // Verificar disponibilidad de comprador y proveedor
                if (!$this->isCompanyAvailable($buyerId, $eventId, $slot['start_datetime'], $slot['end_datetime'])) continue;
                if (!$this->isCompanyAvailable($supplierId, $eventId, $slot['start_datetime'], $slot['end_datetime'])) continue;
                // Crear la cita
                $appointmentData = [
                    'event_id' => $eventId,
                    'match_id' => $matchId,
                    'table_number' => $tableNumber,
                    'start_datetime' => $slot['start_datetime'],
                    'end_datetime' => $slot['end_datetime'],
                    'status' => self::STATUS_SCHEDULED,
                    'is_manual' => 1
                ];
                $appointmentId = $this->create($appointmentData);
                if ($appointmentId) return $appointmentId;
            }
        }
        return false;
    }

    /**
     * Obtener todas las citas de una empresa (buyer o supplier) en un evento
     * @param int $companyId
     * @param int $eventId
     * @return array
     */
    public function getByCompanyAndEvent($companyId, $eventId) {
        $query = "SELECT es.*, 
                  m.buyer_id, m.supplier_id, 
                  b.company_name as buyer_name, 
                  s.company_name as supplier_name 
                  FROM {$this->table} es
                  JOIN matches m ON es.match_id = m.match_id 
                  JOIN company b ON m.buyer_id = b.company_id 
                  JOIN company s ON m.supplier_id = s.company_id
                  WHERE es.event_id = :event_id
                  AND (m.buyer_id = :company_id1 OR m.supplier_id = :company_id2)
                  ORDER BY es.start_datetime ASC";
        $params = [
            'event_id' => $eventId,
            'company_id1' => $companyId,
            'company_id2' => $companyId
        ];
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar citas por evento
     * 
     * @param int $eventId ID del evento
     * @return int Número de citas
     */
    public function countByEvent($eventId) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE event_id = :event_id";
        $result = $this->db->single($query, ['event_id' => $eventId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Contar citas por evento y estado
     * 
     * @param int $eventId ID del evento
     * @param string $status Estado de la cita
     * @return int Número de citas
     */
    public function countByEventAndStatus($eventId, $status) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE event_id = :event_id AND status = :status";
        $result = $this->db->single($query, ['event_id' => $eventId, 'status' => $status]);
        return $result ? (int)$result['count'] : 0;
    }
    
    
    /**
     * Obtener próximas citas por evento
     * 
     * @param int $eventId ID del evento
     * @param int $limit Límite de resultados
     * @return array Lista de próximas citas
     */
    public function getUpcomingByEvent($eventId, $limit = 10) {
        $query = "SELECT a.*, 
                         bc.company_name as buyer_name,
                         sc.company_name as supplier_name
                  FROM {$this->table} a
                  INNER JOIN matches m ON a.match_id = m.match_id
                  INNER JOIN company bc ON m.buyer_id = bc.company_id
                  INNER JOIN company sc ON m.supplier_id = sc.company_id
                  WHERE a.event_id = :event_id 
                  AND a.start_datetime > NOW()
                  ORDER BY a.start_datetime ASC
                  LIMIT :limit";
        
        return $this->db->resultSet($query, ['event_id' => $eventId, 'limit' => $limit]);
    }
}