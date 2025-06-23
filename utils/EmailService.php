<?php
// utils/EmailService.php

class EmailService {
    
    /**
     * Enviar correo electrónico usando mail() de PHP
     * 
     * @param string $to Dirección de destino
     * @param string $subject Asunto del correo
     * @param string $message Mensaje del correo
     * @param array $headers Cabeceras adicionales
     * @return bool True si se envió correctamente, false en caso contrario
     */
    public static function sendMail($to, $subject, $message, $headers = []) {
        try {
            // Cabeceras por defecto
            $defaultHeaders = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . (defined('APP_EMAIL') ? APP_EMAIL : 'noreply@' . $_SERVER['HTTP_HOST']),
                'Reply-To: ' . (defined('APP_EMAIL') ? APP_EMAIL : 'noreply@' . $_SERVER['HTTP_HOST']),
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Combinar cabeceras
            $allHeaders = array_merge($defaultHeaders, $headers);
            $headerString = implode("\r\n", $allHeaders);
            
            // Enviar correo
            $result = mail($to, $subject, $message, $headerString);
            
            if ($result) {
                Logger::getInstance()->info("Email enviado exitosamente a: $to");
                return true;
            } else {
                Logger::getInstance()->error("Error al enviar email a: $to");
                return false;
            }
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Excepción al enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar notificación de credenciales de administrador de evento
     * 
     * @param string $email Email del administrador
     * @param string $password Contraseña (sin hashear)
     * @param string $eventName Nombre del evento
     * @param string $organizerName Nombre del organizador
     * @return bool True si se envió correctamente
     */
    public static function sendEventAdminCredentials($email, $password, $eventName, $organizerName = '') {
        $subject = "Credenciales de acceso - Administrador de Evento: $eventName";
        
        $message = self::getEventAdminEmailTemplate($email, $password, $eventName, $organizerName);
        
        return self::sendMail($email, $subject, $message);
    }
    
    /**
     * Plantilla de correo para credenciales de administrador de evento
     * 
     * @param string $email Email del administrador
     * @param string $password Contraseña
     * @param string $eventName Nombre del evento
     * @param string $organizerName Nombre del organizador
     * @return string HTML del correo
     */
    private static function getEventAdminEmailTemplate($email, $password, $eventName, $organizerName) {
        $baseUrl = BASE_URL;
        $appName = defined('APP_NAME') ? APP_NAME : 'B2B Conector';
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Credenciales de Acceso - $appName</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f8f9fa; }
                .credentials { background-color: #e9ecef; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>$appName</h1>
                    <h2>Credenciales de Administrador de Evento</h2>
                </div>
                
                <div class='content'>
                    <p>Estimado/a administrador/a,</p>
                    
                    <p>Se ha creado exitosamente el evento <strong>\"$eventName\"</strong>" . 
                    ($organizerName ? " por $organizerName" : "") . ".</p>
                    
                    <p>Sus credenciales de acceso como administrador del evento son las siguientes:</p>
                    
                    <div class='credentials'>
                        <h3>Datos de Acceso</h3>
                        <p><strong>Usuario (Email):</strong> $email</p>
                        <p><strong>Contraseña:</strong> $password</p>
                        <p><strong>Evento:</strong> $eventName</p>
                    </div>
                    
                    <div class='warning'>
                        <h4>⚠️ Importante - Seguridad</h4>
                        <ul>
                            <li>Cambie su contraseña después del primer acceso</li>
                            <li>No comparta estas credenciales con terceros</li>
                            <li>Mantenga la confidencialidad de la información del evento</li>
                            <li>Contacte al administrador del sistema ante cualquier duda</li>
                        </ul>
                    </div>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$baseUrl/auth/login' class='btn'>Acceder al Sistema</a>
                    </p>
                    
                    <p>Como administrador del evento, podrá:</p>
                    <ul>
                        <li>Gestionar las empresas participantes</li>
                        <li>Supervisar el proceso de registro</li>
                        <li>Coordinar las citas entre empresas</li>
                        <li>Generar reportes del evento</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>Este correo fue generado automáticamente por $appName.</p>
                    <p>Si tiene problemas para acceder, contacte al administrador del sistema.</p>
                    <p>&copy; " . date('Y') . " $appName. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}