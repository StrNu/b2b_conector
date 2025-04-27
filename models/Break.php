<?php
/**
 * Modelo de Break (Descansos)
 * 
 * Esta clase maneja todas las operaciones relacionadas con los descansos
 * programados durante los eventos de networking, incluyendo creación,
 * modificación, eliminación y consulta de periodos de descanso.
 * 
 * @package B2B Conector
 * @version 1.0
 */

class BreakModel {
    private $db;
    private $table = 'breaks';
    
    // Propiedades que mapean a las columnas de la tabla breaks
    private $id;
    private $event_id;
    private $start_time;
    private $end_time;
    
    /**
     * Constructor
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Encontrar descanso por ID
     * 
     * @param int $id ID del descanso a buscar
     * @return bool True si el descanso existe, false en caso contrario
     */
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE break_id = :id LIMIT 1";
        $result = $this->db->single($query, ['id' => $id]);
        
        if ($result) {
            $this->setProperties($result);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si existe un descanso que se superponga con los horarios dados
     * 
     * @param int $eventId ID del evento
     * @param string $startTime Hora de inicio del descanso (formato H:i:s)
     * @param string $endTime Hora de fin del descanso (formato H:i:s)
     * @param int $excludeId ID del descanso a excluir de la verificación (para actualizaciones)
     * @return bool True si hay superposición, false en caso contrario
     */
    public function hasOverlap($eventId, $startTime, $endTime, $excludeId = null) {
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE event_id = :event_id 
                  AND (
                      (start_time <= :end_time AND end_time >= :start_time)
                  )";
        
        $params = [
            'event_id' => $eventId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        if ($excludeId !== null) {
            $query .= " AND break_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $count = $this->db->query($query, $params)->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Verificar si los horarios del descanso están dentro del rango del evento
     * 
     * @param int $eventId ID del evento
     * @param string $startTime Hora de inicio del descanso (formato H:i:s)
     * @param string $endTime Hora de fin del descanso (formato H:i:s)
     * @return bool True si los horarios están dentro del rango, false en caso contrario
     */
    public function isWithinEventHours($eventId, $startTime, $endTime) {
        $query = "SELECT start_time, end_time FROM events 
                  WHERE event_id = :event_id LIMIT 1";
        
        $eventHours = $this->db->single($query, ['event_id' => $eventId]);
        
        if (!$eventHours) {
            return false;
        }
        
        $eventStartTime = $eventHours['start_time'];
        $eventEndTime = $eventHours['end_time'];
        
        // Verificar que el descanso esté dentro del horario del evento
        return ($startTime >= $eventStartTime && $endTime <= $eventEndTime);
    }
    
    /**
     * Crear un nuevo descanso
     * 
     * @param array $data Datos del descanso a crear
     * @return bool|int ID del descanso creado o false en caso de error
     */
    public function create($data) {
        // Validar datos mínimos requeridos
        if (!isset($data['event_id']) || !isset($data['start_time']) || !isset($data['end_time'])) {
            return false;
        }
        
        // Validar que el evento exista
        $eventModel = new Event($this->db);
        if (!$eventModel->findById($data['event_id'])) {
            return false;
        }
        
        // Validar que el horario de inicio sea anterior al de fin
        if ($data['start_time'] >= $data['end_time']) {
            return false;
        }
        
        // Validar que el horario esté dentro del rango del evento
        if (!$this->isWithinEventHours($data['event_id'], $data['start_time'], $data['end_time'])) {
            return false;
        }
        
        // Validar que no se superponga con otros descansos
        if ($this->hasOverlap($data['event_id'], $data['start_time'], $data['end_time'])) {
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
     * Actualizar datos de un descanso
     * 
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function update($data) {
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        // Validar que el horario de inicio sea anterior al de fin si se están actualizando
        if (isset($data['start_time']) && isset($data['end_time'])) {
            if ($data['start_time'] >= $data['end_time']) {
                return false;
            }
        } else if (isset($data['start_time']) && !isset($data['end_time'])) {
            if ($data['start_time'] >= $this->end_time) {
                return false;
            }
        } else if (!isset($data['start_time']) && isset($data['end_time'])) {
            if ($this->start_time >= $data['end_time']) {
                return false;
            }
        }
        
        // Validar que el horario esté dentro del rango del evento
        $eventId = $data['event_id'] ?? $this->event_id;
        $startTime = $data['start_time'] ?? $this->start_time;
        $endTime = $data['end_time'] ?? $this->end_time;
        
        if (!$this->isWithinEventHours($eventId, $startTime, $endTime)) {
            return false;
        }
        
        // Validar que no se superponga con otros descansos
        if ($this->hasOverlap($eventId, $startTime, $endTime, $this->id)) {
            return false;
        }
        
        // Generar sentencia de actualización
        $updateFields = [];
        foreach ($data as $key => $value) {
            $updateFields[] = "$key = :$key";
        }
        
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $updateFields) . " 
                  WHERE break_id = :id";
        
        $data['id'] = $this->id;
        
        return $this->db->query($query, $data) ? true : false;
    }
    
    /**
     * Eliminar descanso
     * 
     * @param int $id ID del descanso a eliminar
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function delete($id = null) {
        $breakId = $id ?? $this->id;
        
        if (!$breakId) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE break_id = :id";
        return $this->db->query($query, ['id' => $breakId]) ? true : false;
    }
    
    /**
     * Obtener todos los descansos
     * 
     * @param array $filters Filtros a aplicar
     * @param array $pagination Información de paginación
     * @return array Lista de descansos
     */
    public function getAll($filters = [], $pagination = null) {
        $query = "SELECT b.*, e.event_name
                  FROM {$this->table} b
                  JOIN events e ON b.event_id = e.event_id";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                $conditions[] = "b.$key = :$key";
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aplicar ordenamiento
        $query .= " ORDER BY b.start_time ASC";
        
        // Aplicar paginación
        if ($pagination) {
            $query .= " LIMIT :offset, :limit";
            $params['offset'] = $pagination['offset'];
            $params['limit'] = $pagination['per_page'];
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    /**
     * Contar total de descansos
     * 
     * @param array $filters Filtros a aplicar
     * @return int Total de descansos
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM {$this->table} b";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($filters as $key => $value) {
                $conditions[] = "b.$key = :$key";
                $params[$key] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        return (int) $this->db->query($query, $params)->fetchColumn();
    }
    
    /**
     * Obtener descansos por evento
     * 
     * @param int $eventId ID del evento
     * @param array $pagination Información de paginación
     * @return array Lista de descansos del evento
     */
    public function getByEvent($eventId, $pagination = null) {
        return $this->getAll(['event_id' => $eventId], $pagination);
    }
    
    /**
     * Eliminar todos los descansos de un evento
     * 
     * @param int $eventId ID del evento
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function deleteByEvent($eventId) {
        if (!$eventId) {
            return false;
        }
        
        $query = "DELETE FROM {$this->table} WHERE event_id = :event_id";
        return $this->db->query($query, ['event_id' => $eventId]) ? true : false;
    }
    
    /**
     * Verificar si un evento tiene descansos programados
     * 
     * @param int $eventId ID del evento
     * @return bool True si el evento tiene descansos, false en caso contrario
     */
    public function eventHasBreaks($eventId) {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE event_id = :event_id";
        $count = $this->db->query($query, ['event_id' => $eventId])->fetchColumn();
        return $count > 0;
    }
    
    /**
     * Establecer propiedades del modelo desde un array de datos
     * 
     * @param array $data Datos a establecer
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['break_id'] ?? null;
        $this->event_id = $data['event_id'] ?? null;
        $this->start_time = $data['start_time'] ?? null;
        $this->end_time = $data['end_time'] ?? null;
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
    
    public function getStartTime() {
        return $this->start_time;
    }
    
    public function getEndTime() {
        return $this->end_time;
    }
    
    /**
     * Obtener duración del descanso en minutos
     * 
     * @return int Duración en minutos
     */
    public function getDuration() {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        
        return floor(($end - $start) / 60);
    }
    
    /**
     * Validar formato de hora (H:i:s)
     * 
     * @param string $time Hora a validar
     * @return bool True si el formato es válido, false en caso contrario
     */
    public static function isValidTimeFormat($time) {
        return preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $time);
    }
    
    /**
     * Formatear hora para mostrar (H:i)
     * 
     * @param string $time Hora en formato H:i:s
     * @return string Hora en formato H:i
     */
    public static function formatTimeForDisplay($time) {
        $timeObj = DateTime::createFromFormat('H:i:s', $time);
        return $timeObj ? $timeObj->format('H:i') : $time;
    }
}