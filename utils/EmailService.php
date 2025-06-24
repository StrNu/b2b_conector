<?php
// utils/EmailService.php

// Incluir PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    /**
     * Enviar correo electrónico usando PHPMailer
     * 
     * @param string $to Dirección de destino
     * @param string $subject Asunto del correo
     * @param string $message Mensaje del correo
     * @param array $options Opciones adicionales (attachments, etc.)
     * @return bool True si se envió correctamente, false en caso contrario
     */
    public static function sendMail($to, $subject, $message, $options = []) {
        try {
            // Crear instancia de PHPMailer
            $mail = new PHPMailer(true);
            
            // Configurar servidor SMTP si está configurado
            if (defined('SMTP_HOST') && !empty(SMTP_HOST)) {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                
                // Debug (solo en desarrollo)
                if (defined('SMTP_DEBUG')) {
                    $mail->SMTPDebug = SMTP_DEBUG;
                    $mail->Debugoutput = function($str, $level) {
                        Logger::debug("SMTP Debug: $str");
                    };
                }
            } else {
                // Usar mail() nativo de PHP como fallback
                $mail->isMail();
                Logger::info("Usando mail() nativo como fallback (SMTP no configurado)");
            }
            
            // Configurar remitente
            $fromEmail = defined('APP_EMAIL') ? APP_EMAIL : 'noreply@' . $_SERVER['HTTP_HOST'];
            $fromName = defined('APP_EMAIL_NAME') ? APP_EMAIL_NAME : 'B2B Conector';
            $mail->setFrom($fromEmail, $fromName);
            
            // Configurar destinatario
            $mail->addAddress($to);
            
            // Configurar reply-to
            $mail->addReplyTo($fromEmail, $fromName);
            
            // Configurar contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->CharSet = 'UTF-8';
            
            // Agregar archivos adjuntos si se proporcionan
            if (isset($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $name = $attachment['name'] ?? basename($attachment['path']);
                        $mail->addAttachment($attachment['path'], $name);
                    }
                }
            }
            
            // Log de debug
            Logger::debug("Intentando enviar email a: $to, Subject: $subject");
            
            // Enviar correo
            $result = $mail->send();
            
            if ($result) {
                Logger::info("Email enviado exitosamente a: $to");
                return true;
            } else {
                Logger::error("Error al enviar email a: $to");
                return false;
            }
            
        } catch (Exception $e) {
            Logger::error("Excepción PHPMailer al enviar email a $to: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Logger::error("Excepción general al enviar email a $to: " . $e->getMessage());
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
        
        // Intentar enviar el correo
        $emailSent = self::sendMail($email, $subject, $message);
        
        // Si no se pudo enviar, registrar las credenciales en logs de forma segura
        if (!$emailSent) {
            Logger::warning("CREDENCIALES NO ENVIADAS POR EMAIL - Event: $eventName");
            Logger::warning("Email del administrador: $email");
            Logger::warning("Contraseña del administrador: " . str_repeat('*', strlen($password) - 2) . substr($password, -2));
            Logger::info("Las credenciales completas del administrador deben ser proporcionadas manualmente");
        }
        
        return $emailSent;
    }
    
    /**
     * Obtener credenciales en formato texto plano para mostrar al usuario
     * cuando el email falla
     * 
     * @param string $email Email del administrador
     * @param string $password Contraseña
     * @param string $eventName Nombre del evento
     * @return string Credenciales en texto plano
     */
    public static function getCredentialsPlainText($email, $password, $eventName) {
        return "CREDENCIALES DEL ADMINISTRADOR DEL EVENTO\n" .
               "==========================================\n" .
               "Evento: $eventName\n" .
               "Email/Usuario: $email\n" .
               "Contraseña: $password\n" .
               "==========================================\n" .
               "IMPORTANTE: Guarde estas credenciales de forma segura.\n" .
               "El administrador debe cambiar la contraseña en el primer acceso.";
    }
    
    /**
     * Enviar agenda de citas por email con archivo adjunto
     * 
     * @param string $email Email del destinatario
     * @param string $eventName Nombre del evento
     * @param string $filePath Ruta del archivo de agenda (CSV, PDF, etc.)
     * @param string $fileName Nombre del archivo para el adjunto
     * @return bool True si se envió correctamente
     */
    public static function sendEventSchedule($email, $eventName, $filePath, $fileName = null) {
        $subject = "Agenda de Citas - Evento: $eventName";
        $fileName = $fileName ?: basename($filePath);
        
        $message = self::getScheduleEmailTemplate($eventName, $fileName);
        
        $options = [
            'attachments' => [
                [
                    'path' => $filePath,
                    'name' => $fileName
                ]
            ]
        ];
        
        return self::sendMail($email, $subject, $message, $options);
    }
    
    /**
     * Plantilla de correo para envío de agenda
     * 
     * @param string $eventName Nombre del evento
     * @param string $fileName Nombre del archivo adjunto
     * @return string HTML del correo
     */
    private static function getScheduleEmailTemplate($eventName, $fileName) {
        $baseUrl = BASE_URL;
        $appName = defined('APP_NAME') ? APP_NAME : 'B2B Conector';
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Agenda de Citas - $appName</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f8f9fa; }
                .attachment-info { background-color: #e9ecef; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>$appName</h1>
                    <h2>Agenda de Citas del Evento</h2>
                </div>
                
                <div class='content'>
                    <p>Estimado/a participante,</p>
                    
                    <p>Adjunto encontrará la agenda de citas del evento <strong>\"$eventName\"</strong>.</p>
                    
                    <div class='attachment-info'>
                        <h3>📎 Archivo Adjunto</h3>
                        <p><strong>Archivo:</strong> $fileName</p>
                        <p><strong>Contenido:</strong> Agenda completa con horarios, mesas y contactos</p>
                    </div>
                    
                    <p>Esta agenda incluye:</p>
                    <ul>
                        <li>Horarios de todas las citas programadas</li>
                        <li>Información de contacto de las empresas</li>
                        <li>Números de mesa asignados</li>
                        <li>Detalles de productos/servicios de interés</li>
                    </ul>
                    
                    <p><strong>Recomendaciones:</strong></p>
                    <ul>
                        <li>Revise su agenda con anticipación</li>
                        <li>Confirme su asistencia a las citas programadas</li>
                        <li>Prepare material informativo sobre su empresa</li>
                        <li>Llegue puntualmente a cada cita</li>
                    </ul>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$baseUrl' class='btn'>Acceder al Sistema</a>
                    </p>
                </div>
                
                <div class='footer'>
                    <p>Este correo fue generado automáticamente por $appName.</p>
                    <p>Para consultas, contacte al organizador del evento.</p>
                    <p>&copy; " . date('Y') . " $appName. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>";
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