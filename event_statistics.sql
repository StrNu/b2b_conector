-- Tabla para almacenar estadísticas agregadas por evento
CREATE TABLE event_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    keywords JSON NULL,
    categories JSON NULL,
    subcategories JSON NULL,
    descriptions JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY (event_id)
);
-- Puedes actualizar los campos JSON con los datos agregados cada vez que cambien las empresas del evento.
-- Si tu versión de MySQL/MariaDB no soporta JSON, usa TEXT y almacena el JSON serializado.
