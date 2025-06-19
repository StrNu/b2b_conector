-- Tabla para ignorar sugerencias de matches por otros criterios (no elimina matches ni agendas)
CREATE TABLE IF NOT EXISTS ignored_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    buyer_id INT NOT NULL,
    supplier_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ignored (event_id, buyer_id, supplier_id)
);
-- Para revertir: DROP TABLE ignored_matches;
