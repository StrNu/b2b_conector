<?php
// Controlador para la vista de agendas de citas por empresa

class AgendaController {
    private $db;
    private $eventModel;
    private $companyModel;
    private $matchModel;
    private $appointmentModel;

    public function __construct() {
        $this->db = Database::getInstance();
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
            include(VIEW_DIR . '/errors/404.php');
            return;
        }
        $event = $this->eventModel;
        $agendas = $this->getAgendasByCompany($eventId);
        include(VIEW_DIR . '/events/agendas.php');
    }

    // Acción por defecto para /agendas
    public function index($eventId = null) {
        error_log('AgendaController::index called with eventId=' . var_export($eventId, true));
        if ($eventId === null) {
            include(VIEW_DIR . '/errors/404.php');
            return;
        }
        $this->agendas($eventId);
    }

    public function preview() {
        $eventId = $_GET['event_id'] ?? null;
        $companyId = $_GET['company_id'] ?? null;
        error_log('[AGENDA PREVIEW] event_id=' . var_export($eventId, true) . ' company_id=' . var_export($companyId, true));
        if (!$eventId || !$companyId) {
            include(VIEW_DIR . '/errors/404.php');
            return;
        }
        // Always load event as object
        if (!$this->eventModel->findById($eventId)) {
            include(VIEW_DIR . '/errors/404.php');
            return;
        }
        $event = $this->eventModel; // Always object
        $company = $this->companyModel->getById($companyId);
        if (!$company) {
            include(VIEW_DIR . '/errors/404.php');
            return;
        }
        $appointments = $this->appointmentModel->getByCompanyAndEvent($companyId, $eventId);
        error_log('[AGENDA PREVIEW] appointments=' . var_export($appointments, true));
        include(VIEW_DIR . '/events/preview_agenda.php');
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
        include(VIEW_DIR . '/events/preview_agenda_pdf.php');
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
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $filename = 'agenda_' . $companyId . '_' . $eventId . '.pdf';
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
        include(VIEW_DIR . '/events/agenda_buyers_pdf.php');
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
}
