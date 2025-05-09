<?php
// models/PotentialMatch.php

class PotentialMatch
{
    private $db;
    private $table = 'potential_matches';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Crear un nuevo match potencial
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
            (event_id, buyer_id, supplier_id, match_strength, matched_categories, matched_days, status, created_at)
            VALUES (:event_id, :buyer_id, :supplier_id, :match_strength, :matched_categories, :matched_days, :status, :created_at)";
        return $this->db->insert($sql, [
            ':event_id' => $data['event_id'],
            ':buyer_id' => $data['buyer_id'],
            ':supplier_id' => $data['supplier_id'],
            ':match_strength' => $data['match_strength'],
            ':matched_categories' => $data['matched_categories'],
            ':matched_days' => $data['matched_days'],
            ':status' => $data['status'],
            ':created_at' => $data['created_at']
        ]);
    }

    // Obtener matches potenciales por evento
    public function getByEvent($eventId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE event_id = :event_id ORDER BY match_strength DESC, created_at DESC";
        return $this->db->resultSet($sql, [':event_id' => $eventId]);
    }
}