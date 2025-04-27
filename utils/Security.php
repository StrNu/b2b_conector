<?php
// utils/Security.php
class Security {
    /**
     * Generar hash de contraseña
     * @param string $password Contraseña en texto plano
     * @return string Hash bcrypt de la contraseña
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verificar si la contraseña coincide con el hash
     * @param string $password Contraseña en texto plano
     * @param string $hash Hash almacenado
     * @return bool True si coincide, false en caso contrario
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generar un token aleatorio
     * @param int $length Longitud del token
     * @return string Token generado
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Sanitizar una entrada para prevenir XSS
     * @param mixed $input Entrada a sanitizar (string, array)
     * @return mixed Entrada sanitizada
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitize($value);
            }
            return $input;
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitizar datos para SQL (prevenir SQL Injection)
     * @param PDO $pdo Conexión PDO
     * @param string $string Cadena a sanitizar
     * @return string Cadena sanitizada
     */
    public static function sanitizeSQL($pdo, $string) {
        return $pdo->quote($string);
    }
    
    /**
     * Verificar la fortaleza de una contraseña
     * @param string $password Contraseña a verificar
     * @return bool True si es fuerte, false en caso contrario
     */
    public static function isStrongPassword($password) {
        // Mínimo 8 caracteres, al menos una letra mayúscula, una minúscula y un número
        return (strlen($password) >= PASSWORD_MIN_LENGTH) &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }
    
    /**
     * Generar un ID de sesión seguro
     * @return void
     */
    /*public static function regenerateSession() {
        // Regenerar ID de sesión para prevenir fixation attacks
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }*/

    public static function regenerateSession() {
        Logger::info('Iniciando regeneración de sesión');
        
        // Regenerar ID de sesión para prevenir fixation attacks
        if (session_status() === PHP_SESSION_ACTIVE) {
            Logger::debug('Sesión activa, guardando datos actuales');
            
            // Guardar datos de sesión actuales
            $old_session_data = $_SESSION;
            
            // Regenerar el ID de sesión
            Logger::debug('Regenerando ID de sesión');
            session_regenerate_id(true);
            
            // Restaurar los datos de sesión
            $_SESSION = $old_session_data;
            
            Logger::debug('Sesión regenerada, datos restaurados: ' . json_encode(array_keys($_SESSION)));
        } else {
            Logger::error('Error: Intentando regenerar una sesión inactiva');
        }
    }
    
    /**
     * Destruir sesión actual de forma segura
     * @return void
     */
    public static function destroySession() {
        $_SESSION = [];
        
        // Si hay una cookie de sesión, destruirla
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }
    
    /**
     * Proteger contra CSRF generando un token
     * @return string Token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verificar token CSRF
     * @param string $token Token a verificar
     * @return bool True si es válido, false en caso contrario
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Filtrar array eliminando campos no permitidos
     * @param array $data Datos a filtrar
     * @param array $allowedFields Campos permitidos
     * @return array Datos filtrados
     */
    public static function filterData($data, $allowedFields) {
        return array_intersect_key($data, array_flip($allowedFields));
    }
}