<?php
// Controlador para la vista de agendas de citas por empresa

require_once 'BaseController.php';

class AgendaController extends BaseController {
        private $eventModel;
    private $companyModel;
    private $matchModel;
    private $appointmentModel;

    public function __construct() {
        
        parent::__construct();
        
        // La conexión ya se inicializa en BaseController
        // $this->db ya está disponible
        
        $this->eventModel = new Event($this->db);
        $this->companyModel = new Company($this->db);
        $this->matchModel = new MatchModel($this->db);
        $this->appointmentModel = new Appointment($this->db);
    }

    /**
     * Obtener todas las agendas de citas agrupadas por empresa y rol para un evento
     * Devuelve: [ 'buyers' => [...], 'suppliers' => [...], 'all' => [...] ]
     */
    public function getAgendasByCompany($eventId) {
        // Obtener todas las empresas del evento
        $companies = $this->companyModel->getByEvent($eventId);
        // Obtener todas las citas del evento
        $appointments = $this->appointmentModel->getByEvent($eventId); // Devuelve todas las citas del evento
        // Agrupar citas por company_id
        $appointmentsByCompany = [];
        foreach ($appointments as $appt) {
            // Considera tanto buyer como supplier
            if (isset($appt['buyer_id'])) {
                $appointmentsByCompany[$appt['buyer_id']][] = $appt;
            }
            if (isset($appt['supplier_id'])) {
                $appointmentsByCompany[$appt['supplier_id']][] = $appt;
            }
        }
        $agendas = [ 'buyers' => [], 'suppliers' => [], 'all' => [] ];
        foreach ($companies as $company) {
            $cid = $company['company_id'];
            $agenda = [
                'company' => $company, // Asegura que siempre haya clave 'company'
                'appointments' => $appointmentsByCompany[$cid] ?? []
            ];
            // Log para depuración de estructura
            error_log('AGENDA STRUCTURE: ' . var_export($agenda, true));
            $agendas['all'][] = $agenda;
            if ($company['role'] === 'buyer') {
                $agendas['buyers'][] = $agenda;
            } elseif ($company['role'] === 'supplier') {
                $agendas['suppliers'][] = $agenda;
            }
        }
        error_log('AGENDAS FINAL: ' . var_export($agendas, true));
        return $agendas;
    }

    // Acción para mostrar la vista de agendas
    public function agendas($eventId) {
        if (!$this->eventModel->findById($eventId)) {
            $data = [
                'pageTitle' => 'Agendas - Evento no encontrado',
                'moduleCSS' => 'agendacontroller',
                'moduleJS' => 'agendacontroller'
            ];
            
            $this->render('errors/404', $data, 'admin');
            return;
        }
        
        $event = $this->eventModel;
        $agendas = $this->getAgendasByCompany($eventId);
        
        $data = [
            'pageTitle' => 'Agendas de Citas - ' . $event->getEventName(),
            'moduleCSS' => 'agendas',
            'moduleJS' => 'agendas',
            'eventId' => $eventId,
            'event' => $event,
            'agendas' => $agendas
        ];
        
        $this->render('events/agendas', $data, 'admin');
    }

    // Acción por defecto para /agendas
    public function index($eventId = null) {
        error_log('AgendaController::index called with eventId=' . var_export($eventId, true));
        if ($eventId === null) {
                    $data = [
            'pageTitle' => 'Agendas',
            'moduleCSS' => 'agendacontroller',
            'moduleJS' => 'agendacontroller'
        ];
        
        $this->render('errors/404', $data, 'admin');
            return;
        }
        $this->agendas($eventId);
    }

    public function preview() {
        $eventId = $_GET['event_id'] ?? null;
        $companyId = $_GET['company_id'] ?? null;
        error_log('[AGENDA PREVIEW] event_id=' . var_export($eventId, true) . ' company_id=' . var_export($companyId, true));
        if (!$eventId || !$companyId) {
                    $data = [
            'pageTitle' => 'Agendas',
            'moduleCSS' => 'agendacontroller',
            'moduleJS' => 'agendacontroller'
        ];
        
        $this->render('errors/404', $data, 'admin');
            return;
        }
        // Always load event as object
        if (!$this->eventModel->findById($eventId)) {
                    $data = [
            'pageTitle' => 'Agendas',
            'moduleCSS' => 'agendacontroller',
            'moduleJS' => 'agendacontroller'
        ];
        
        $this->render('errors/404', $data, 'admin');
            return;
        }
        $event = $this->eventModel; // Always object
        $company = $this->companyModel->getById($companyId);
        if (!$company) {
                    $data = [
            'pageTitle' => 'Agendas',
            'moduleCSS' => 'agendacontroller',
            'moduleJS' => 'agendacontroller'
        ];
        
        $this->render('errors/404', $data, 'admin');
            return;
        }
        $appointments = $this->appointmentModel->getByCompanyAndEvent($companyId, $eventId);
        error_log('[AGENDA PREVIEW] appointments=' . var_export($appointments, true));
        
        $data = [
            'pageTitle' => 'Agenda - ' . ($company['company_name'] ?? 'Empresa'),
            'moduleCSS' => 'agendas',
            'moduleJS' => 'agendas',
            'eventId' => $eventId,
            'event' => $event,
            'company' => $company,
            'appointments' => $appointments
        ];
        
        $this->render('events/preview_agenda', $data, 'admin');
    }

    // Descargar agenda como PDF
    public function download_pdf() {
        $eventId = $_GET['event_id'] ?? null;
        $companyId = $_GET['company_id'] ?? null;
        if (!$eventId || !$companyId) {
            http_response_code(400);
            echo 'Parámetros requeridos faltantes.';
            exit;
        }
        $event = new Event($this->db);
        if (!$event->findById($eventId)) {
            http_response_code(404);
            echo 'Evento no encontrado.';
            exit;
        }
        $companyModel = new Company($this->db);
        $company = $companyModel->getById($companyId);
        if (!$company) {
            http_response_code(404);
            echo 'Empresa no encontrada.';
            exit;
        }
        $appointmentModel = new Appointment($this->db);
        $appointments = $appointmentModel->getByCompanyAndEvent($companyId, $eventId);
        
        // Render HTML agenda
        ob_start();
        $data = [
            'pageTitle' => 'Agenda PDF - ' . ($company['company_name'] ?? 'Empresa'),
            'moduleCSS' => 'agendas',
            'moduleJS' => 'agendas',
            'eventId' => $eventId,
            'event' => $event,
            'company' => $company,
            'appointments' => $appointments
        ];
        
        // Renderizar directamente sin layout para evitar el menú del sistema
        include VIEW_DIR . '/events/preview_agenda_pdf.php';
        $html = ob_get_clean();
        // Intentar cargar Dompdf desde utils/dompdf/autoload.inc.php en vez de vendor/autoload.php. Así funcionará con tu instalación manual.
        if (!class_exists('Dompdf\\Dompdf')) {
            $autoload = __DIR__ . '/../utils/dompdf/autoload.inc.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }
        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            // Configurar chroot para permitir imágenes locales
            $dompdf->getOptions()->setChroot('/var/www/html/b2b_conector/public');
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            // Create a clean filename with company and event names
            $companyName = $this->sanitizeFilename($company['company_name'] ?? 'Empresa');
            $eventName = $this->sanitizeFilename($event->getEventName() ?? 'Evento');
            
            // Limit length to avoid filesystem issues
            $companyName = $this->truncateString($companyName, 25);
            $eventName = $this->truncateString($eventName, 20);
            
            $filename = $companyName . '_' . $eventName . '_Agenda.pdf';
            // Forzar descarga
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $dompdf->output();
            exit;
        } else {
            http_response_code(500);
            echo 'No se pudo generar el PDF. Dompdf no está instalado correctamente en utils/dompdf.';
            exit;
        }
    }

    // Descargar agenda como archivo .ics (iCalendar)
    public function download_ics() {
        $eventId = $_GET['event_id'] ?? null;
        $companyId = $_GET['company_id'] ?? null;
        if (!$eventId || !$companyId) {
            http_response_code(400);
            echo 'Parámetros requeridos faltantes.';
            exit;
        }
        $event = new Event($this->db);
        if (!$event->findById($eventId)) {
            http_response_code(404);
            echo 'Evento no encontrado.';
            exit;
        }
        $companyModel = new Company($this->db);
        $company = $companyModel->getById($companyId);
        if (!$company) {
            http_response_code(404);
            echo 'Empresa no encontrada.';
            exit;
        }
        $appointmentModel = new Appointment($this->db);
        $appointments = $appointmentModel->getByCompanyAndEvent($companyId, $eventId);
        // Generar contenido ICS
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//B2B Conector//EN\r\n";
        foreach ($appointments as $appt) {
            $start = date('Ymd\THis', strtotime($appt['start_datetime']));
            $end = date('Ymd\THis', strtotime($appt['end_datetime']));
            $summary = 'Cita: ' . $appt['buyer_name'] . ' - ' . $appt['supplier_name'];
            $desc = 'Mesa: ' . $appt['table_number'] . ' | Estado: ' . $appt['status'];
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:agenda-{$appt['schedule_id']}@b2bconector\r\n";
            $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $ics .= "DTSTART:{$start}\r\n";
            $ics .= "DTEND:{$end}\r\n";
            $ics .= "SUMMARY:" . addslashes($summary) . "\r\n";
            $ics .= "DESCRIPTION:" . addslashes($desc) . "\r\n";
            $ics .= "END:VEVENT\r\n";
        }
        $ics .= "END:VCALENDAR\r\n";
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="agenda_' . $companyId . '_' . $eventId . '.ics"');
        echo $ics;
        exit;
    }

    // Descargar agenda de comprador como PDF (vista igual a la pestaña)
    public function download_buyers_pdf() {
        $eventId = $_GET['event_id'] ?? null;
        $companyId = $_GET['company_id'] ?? null;
        error_log('[AGENDA PDF] event_id=' . var_export($eventId, true) . ' company_id=' . var_export($companyId, true));
        if (!$eventId || !$companyId) {
            http_response_code(400);
            echo 'Parámetros requeridos faltantes.';
            exit;
        }
        $event = new Event($this->db);
        if (!$event->findById($eventId)) {
            http_response_code(404);
            echo 'Evento no encontrado.';
            exit;
        }
        $companyModel = new Company($this->db);
        $company = $companyModel->getById($companyId);
        if (!$company) {
            http_response_code(404);
            echo 'Empresa no encontrada.';
            exit;
        }
        $appointmentModel = new Appointment($this->db);
        $appointments = $appointmentModel->getByCompanyAndEvent($companyId, $eventId);
        error_log('[AGENDA PDF] appointments=' . var_export($appointments, true));
        // Render HTML agenda (buyers tab style)
        ob_start();
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'agendacontroller',
            'moduleJS' => 'agendacontroller'
        ];
        
        $this->render('events/agenda_buyers_pdf', $data, 'admin');
        $html = ob_get_clean();
        // PDF: use dompdf if available, else fallback to HTML
        if (class_exists('Dompdf\\Dompdf') || class_exists('Dompdf\Dompdf')) {
            if (!class_exists('Dompdf\\Dompdf') && !class_exists('Dompdf\Dompdf')) {
                if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                    require_once __DIR__ . '/../../vendor/autoload.php';
                }
            }
            if (class_exists('Dompdf\\Dompdf')) {
                $dompdf = new Dompdf\Dompdf();
            } else {
                $dompdf = new Dompdf\Dompdf();
            }
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $filename = 'agenda_comprador_' . $companyId . '_' . $eventId . '.pdf';
            $dompdf->stream($filename, ['Attachment' => false]); // Preview in browser
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
        }
        exit;
    }
    
    /**
     * Programar una cita automáticamente para un match
     * Busca el primer time_slot disponible en la primera fecha de asistencia coincidente
     * 
     * @return void (JSON response)
     */
    public function scheduleAppointment() {
        header('Content-Type: application/json');
        
        // Validar método y CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
            exit;
        }
        
        // Obtener y validar parámetros
        $matchId = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;
        $buyerId = isset($_POST['buyer_id']) ? (int)$_POST['buyer_id'] : 0;
        $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        
        if (!$matchId || !$buyerId || !$supplierId || !$eventId) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']);
            exit;
        }
        
        try {
            // Verificar que el evento existe
            if (!$this->eventModel->findById($eventId)) {
                echo json_encode(['success' => false, 'message' => 'Evento no encontrado']);
                exit;
            }
            
            // Obtener información del match para las fechas de coincidencia
            $match = $this->matchModel->findById($matchId);
            Logger::debug("DEBUG match data", [
                'match_id' => $matchId,
                'match_found' => !empty($match),
                'match_data' => $match
            ]);
            
            if (!$match) {
                echo json_encode(['success' => false, 'message' => 'Match no encontrado']);
                exit;
            }
            
            // Verificar si ya existe una cita para este match
            $existingAppointment = $this->db->single(
                'SELECT * FROM event_schedules WHERE match_id = :match_id',
                ['match_id' => $matchId]
            );
            
            if ($existingAppointment) {
                echo json_encode(['success' => false, 'message' => 'Ya existe una cita programada para este match']);
                exit;
            }
            
            // Obtener fechas de coincidencia
            $coincidenceDates = !empty($match['coincidence_of_dates']) ? 
                explode(',', $match['coincidence_of_dates']) : [];
            
            // DEBUG: Log para verificar qué está pasando
            Logger::debug("DEBUG scheduleAppointment", [
                'match_id' => $matchId,
                'coincidence_of_dates_raw' => $match['coincidence_of_dates'],
                'coincidence_dates_array' => $coincidenceDates,
                'is_empty_check' => empty($coincidenceDates)
            ]);
            
            // Si no hay fechas de coincidencia, intentar calcularlas dinámicamente
            if (empty($coincidenceDates)) {
                Logger::debug("Attempting to calculate coincidence dates dynamically", [
                    'buyer_id' => $buyerId,
                    'supplier_id' => $supplierId,
                    'event_id' => $eventId
                ]);
                
                // Obtener fechas de asistencia del buyer y supplier
                $buyerDates = $this->db->resultSet(
                    'SELECT DISTINCT attendance_date FROM attendance_days WHERE company_id = :company_id AND event_id = :event_id',
                    ['company_id' => $buyerId, 'event_id' => $eventId]
                );
                
                $supplierDates = $this->db->resultSet(
                    'SELECT DISTINCT attendance_date FROM attendance_days WHERE company_id = :company_id AND event_id = :event_id',
                    ['company_id' => $supplierId, 'event_id' => $eventId]
                );
                
                // Encontrar fechas coincidentes
                $buyerDatesArray = array_column($buyerDates, 'attendance_date');
                $supplierDatesArray = array_column($supplierDates, 'attendance_date');
                $coincidenceDates = array_intersect($buyerDatesArray, $supplierDatesArray);
                
                Logger::debug("Dynamic coincidence calculation", [
                    'buyer_dates' => $buyerDatesArray,
                    'supplier_dates' => $supplierDatesArray,
                    'coincidence_dates' => $coincidenceDates
                ]);
                
                if (empty($coincidenceDates)) {
                    echo json_encode(['success' => false, 'message' => 'No hay fechas de asistencia coincidentes entre el buyer y supplier']);
                    exit;
                }
                
                // Actualizar el match con las fechas calculadas
                $coincidenceDatesString = implode(',', $coincidenceDates);
                $this->db->query(
                    'UPDATE matches SET coincidence_of_dates = :dates WHERE match_id = :match_id',
                    ['dates' => $coincidenceDatesString, 'match_id' => $matchId]
                );
                
                Logger::info("Updated match with calculated coincidence dates", [
                    'match_id' => $matchId,
                    'calculated_dates' => $coincidenceDatesString
                ]);
            }
            
            // Buscar el primer slot disponible en la primera fecha de coincidencia
            $firstDate = trim($coincidenceDates[0]);
            
            // DEBUG: Log para verificar la consulta de slots
            Logger::debug("DEBUG slot search", [
                'event_id' => $eventId,
                'first_date' => $firstDate,
                'query_params' => ['event_id' => $eventId, 'date' => $firstDate]
            ]);
            
            $availableSlot = $this->db->single(
                'SELECT * FROM event_schedules 
                 WHERE event_id = :event_id 
                 AND DATE(start_datetime) = :date 
                 AND match_id IS NULL 
                 AND status = "available"
                 ORDER BY start_datetime ASC, table_number ASC 
                 LIMIT 1',
                [
                    'event_id' => $eventId,
                    'date' => $firstDate
                ]
            );
            
            if (!$availableSlot) {
                // DEBUG: Verificar qué slots existen para esta fecha
                $allSlots = $this->db->resultSet(
                    'SELECT schedule_id, start_datetime, status, match_id FROM event_schedules 
                     WHERE event_id = :event_id AND DATE(start_datetime) = :date',
                    ['event_id' => $eventId, 'date' => $firstDate]
                );
                
                Logger::debug("DEBUG no available slots", [
                    'event_id' => $eventId,
                    'date' => $firstDate,
                    'all_slots_for_date' => $allSlots
                ]);
                
                echo json_encode(['success' => false, 'message' => "No hay slots disponibles para la fecha $firstDate"]);
                exit;
            }
            
            // Asignar el match al slot
            $updateResult = $this->db->query(
                'UPDATE event_schedules 
                 SET match_id = :match_id, status = "occupied" 
                 WHERE schedule_id = :schedule_id',
                [
                    'match_id' => $matchId,
                    'schedule_id' => $availableSlot['schedule_id']
                ]
            );
            
            if (!$updateResult) {
                echo json_encode(['success' => false, 'message' => 'Error al asignar el slot']);
                exit;
            }
            
            // Actualizar el campo programed del match a 1
            $updateMatchResult = $this->db->query(
                'UPDATE matches SET programed = 1 WHERE match_id = :match_id',
                ['match_id' => $matchId]
            );
            
            if (!$updateMatchResult) {
                Logger::warning("No se pudo actualizar el campo programed del match", [
                    'match_id' => $matchId
                ]);
                // No salimos aquí porque la cita ya se programó exitosamente
            } else {
                Logger::info("Campo programed actualizado exitosamente", [
                    'match_id' => $matchId,
                    'programed' => 1
                ]);
            }
            
            // Obtener información de las empresas para la respuesta
            $buyer = $this->companyModel->getById($buyerId);
            $supplier = $this->companyModel->getById($supplierId);
            
            // Preparar respuesta con información de la cita
            $appointmentInfo = [
                'match_id' => $matchId,
                'buyer_name' => $buyer['company_name'] ?? 'Comprador',
                'supplier_name' => $supplier['company_name'] ?? 'Proveedor',
                'date' => date('d/m/Y', strtotime($availableSlot['start_datetime'])),
                'time' => date('H:i', strtotime($availableSlot['start_datetime'])) . ' - ' . date('H:i', strtotime($availableSlot['end_datetime'])),
                'table' => $availableSlot['table_number'],
                'schedule_id' => $availableSlot['schedule_id']
            ];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cita programada exitosamente',
                'appointment' => $appointmentInfo
            ]);
            
        } catch (Exception $e) {
            error_log('Error in scheduleAppointment: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        
        exit;
    }
    
    /**
     * Sanitize a string for use in filenames
     * @param string $string
     * @return string
     */
    private function sanitizeFilename($string) {
        // Remove or replace special characters
        $string = str_replace(['ñ', 'Ñ'], ['n', 'N'], $string);
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        
        // Remove special characters and keep only letters, numbers, spaces, and hyphens
        $string = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $string);
        
        // Replace spaces with underscores
        $string = str_replace(' ', '_', $string);
        
        // Remove multiple underscores
        $string = preg_replace('/_+/', '_', $string);
        
        // Trim underscores from start and end
        $string = trim($string, '_');
        
        return $string ?: 'Sin_Nombre';
    }
    
    /**
     * Truncate string to specified length
     * @param string $string
     * @param int $length
     * @return string
     */
    private function truncateString($string, $length) {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length);
    }
}
