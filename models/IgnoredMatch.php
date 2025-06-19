<?php
/**
 * Modelo para ignored_matches
 */
class IgnoredMatchModel {
    private $db;
    private $table = 'ignored_matches';

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    public function ignore($eventId, $buyerId, $supplierId) {
        $query = "INSERT IGNORE INTO {$this->table} (event_id, buyer_id, supplier_id) VALUES (:event_id, :buyer_id, :supplier_id)";
        return $this->db->query($query, [
            'event_id' => $eventId,
            'buyer_id' => $buyerId,
            'supplier_id' => $supplierId
        ]);
    }

    public function ignoreBulk($eventId, $pairs) {
        $values = [];
        $params = [];
        $i = 0;
        foreach ($pairs as $pair) {
            $values[] = "(:event_id, :buyer_id{$i}, :supplier_id{$i})";
            $params["buyer_id{$i}"] = $pair['buyer_id'];
            $params["supplier_id{$i}"] = $pair['supplier_id'];
            $i++;
        }
        $params['event_id'] = $eventId;
        if (!$values) return false;
        $query = "INSERT IGNORE INTO {$this->table} (event_id, buyer_id, supplier_id) VALUES " . implode(',', $values);
        return $this->db->query($query, $params);
    }

    public function isIgnored($eventId, $buyerId, $supplierId) {
        $query = "SELECT 1 FROM {$this->table} WHERE event_id = :event_id AND buyer_id = :buyer_id AND supplier_id = :supplier_id LIMIT 1";
        $result = $this->db->single($query, [
            'event_id' => $eventId,
            'buyer_id' => $buyerId,
            'supplier_id' => $supplierId
        ]);
        return (bool)$result;
    }
}
