<?php
// Controlador básico para buyers_registration
require_once 'BaseController.php';

class Buyers_registrationController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }

public function index() {
        // Puedes cargar una vista o redirigir según la lógica de tu app
        echo "Buyers Registration Controller funcionando correctamente.";
    }

    public function store() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $data = $_POST;
        $validation_errors = [];
        // Validar campos obligatorios
        if (empty($data['company_name'])) {
            $validation_errors['company_name'] = 'El nombre de la empresa es obligatorio.';
        }
        if (empty($data['description'])) {
            $validation_errors['description'] = 'La descripción es obligatoria.';
        }
        if (empty($data['contact_first_name'])) {
            $validation_errors['contact_first_name'] = 'El nombre de contacto es obligatorio.';
        }
        if (empty($data['contact_last_name'])) {
            $validation_errors['contact_last_name'] = 'El apellido de contacto es obligatorio.';
        }
        if (empty($data['email'])) {
            $validation_errors['email'] = 'El correo electrónico es obligatorio.';
        }
        // Validar teléfono (solo números, espacios, +, -, paréntesis, min 7 dígitos)
        if (!empty($data['phone']) && !preg_match('/^[\d\s\-\+\(\)]{7,}$/', $data['phone'])) {
            $validation_errors['phone'] = 'Ingrese un teléfono válido (mínimo 7 dígitos, solo números y símbolos válidos).';
        }
        // Validar keywords como texto plano
        $keywords = isset($data['keywords']) ? trim($data['keywords']) : '';
        error_log('[buyers_registration] Valor recibido en POST["keywords"]: ' . var_export($data['keywords'], true));
        // No convertir a null, siempre pasar el valor (aunque sea vacío) para repoblar el input
        // Si quieres que sea obligatorio, agrega validación aquí
        // Si hay errores, guardar datos y errores en sesión y recargar vista
        if (!empty($validation_errors)) {
            $data['keywords'] = $keywords; // asegurar que keywords siempre esté presente
            $_SESSION['form_data'] = $data;
            $_SESSION['validation_errors'] = $validation_errors;
            header('Location: ' . BASE_URL . '/buyers_registration/' . (int)($_GET['event_id'] ?? $data['event_id'] ?? 0));
            exit;
        }
        $companyData = [
            'event_id' => $_GET['event_id'] ?? $data['event_id'] ?? null,
            'company_name' => $data['company_name'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'country' => $data['country'] ?? '',
            'website' => $data['website'] ?? '',
            'company_logo' => '', // Procesar logo si aplica
            'contact_first_name' => $data['contact_first_name'] ?? '',
            'contact_last_name' => $data['contact_last_name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'is_active' => 1,
            'role' => 'buyer',
            'description' => $data['description'] ?? '',
            'keywords' => $keywords
        ];
        error_log('[buyers_registration] companyData a guardar: ' . var_export($companyData, true));
        require_once(MODEL_DIR . '/Company.php');
        $companyModel = new Company();
        $companyId = $companyModel->createForEvent($companyData);
        error_log('[buyers_registration] Resultado createForEvent: ' . var_export($companyId, true));
        if ($companyId) {
            unset($_SESSION['form_data']);
            unset($_SESSION['validation_errors']);
            header('Location: ' . BASE_URL . '/events/companies');
            exit;
        } else {
            $_SESSION['form_data'] = $data;
            $_SESSION['validation_errors']['general'] = 'Error al registrar la empresa.';
            error_log('[buyers_registration] Error al registrar la empresa.');
            header('Location: ' . BASE_URL . '/buyers_registration/' . (int)($_GET['event_id'] ?? $data['event_id'] ?? 0));
            exit;
        }
    }
}
