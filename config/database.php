<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'b2b_conector';
    private $username = 'root';
    private $password = 'N1kt3.';
    private $conn;
    private static $instance = null;

    // Constructor privado para patrón Singleton
    private function __construct() {
        $this->connect();
    }

    // Método para obtener instancia única (Singleton)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Método para conectar a la base de datos
    private function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            Logger::error("Error de conexión a la base de datos: " . $e->getMessage(), [
                'host' => $this->host,
                'db_name' => $this->db_name,
                'user' => $this->username
            ]);
            die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
        }
    }

    // Método para obtener la conexión
    public function getConnection() {
        return $this->conn;
    }

    // Método para ejecutar consultas preparadas
    public function query($sql, $params = []) {
        try {
            $start = microtime(true);
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $executionTime = microtime(true) - $start;
            
            // Registrar la consulta en modo DEBUG
            if (defined('LOG_LEVEL') && (LOG_LEVEL == 'DEBUG')) {
                Logger::debug("Consulta SQL ejecutada", [
                    'sql' => $sql,
                    'params' => $params,
                    'execution_time' => round($executionTime * 1000, 2) . ' ms'
                ]);
            }
            
            return $stmt;
        } catch(PDOException $e) {
            Logger::error("Error en consulta SQL: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            return false;
        }
    }

    // Método para obtener un solo registro
    public function single($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }

    // Método para obtener todos los registros
    public function resultSet($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }

    // Método para obtener el último ID insertado
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    // Método para iniciar una transacción
    public function beginTransaction() {
        Logger::info("Iniciando transacción de base de datos");
        return $this->conn->beginTransaction();
    }

    // Método para confirmar una transacción
    public function commit() {
        Logger::info("Confirmando transacción de base de datos");
        return $this->conn->commit();
    }

    // Método para revertir una transacción
    public function rollback() {
        Logger::warning("Revirtiendo transacción de base de datos");
        return $this->conn->rollback();
    }
}