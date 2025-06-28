<?php
// Controlador para importar categorías y subcategorías a un evento desde CSV o Excel
require_once(__DIR__ . '/../models/Category.php');
require_once(__DIR__ . '/../models/Event.php');

require_once 'BaseController.php';

class CategoryImportController extends BaseController {
        private $categoryModel;
    private $eventModel;

    public function __construct() {
        
        parent::__construct();
        
        // La conexión ya se inicializa en BaseController
        // $this->db ya está disponible        $this->categoryModel = new Category($this->db);
        $this->eventModel = new Event($this->db);
    }

    // Vista de carga
    public function upload($eventId) {
        if (!$this->eventModel->findById($eventId)) {
                    $data = [
            'pageTitle' => 'Upload',
            'moduleCSS' => 'categoryimportcontroller',
            'moduleJS' => 'categoryimportcontroller'
        ];
        
        $this->render('errors/404', $data, 'admin');
            return;
        }
        $event = $this->eventModel;
        $csrfToken = generateCSRFToken();
                $data = [
            'pageTitle' => 'Página',
            'moduleCSS' => 'categoryimportcontroller',
            'moduleJS' => 'categoryimportcontroller'
        ];
        
        $this->render('events/import_categories', $data, 'admin');
    }

    // Procesar importación
    public function import($eventId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Solicitud inválida', 'danger');
            header('Location: ' . BASE_URL . "/events/view/$eventId");
            exit;
        }
        if (!isset($_FILES['categories_file']) || $_FILES['categories_file']['error'] !== UPLOAD_ERR_OK) {
            setFlashMessage('Archivo no válido.', 'danger');
            header('Location: ' . BASE_URL . "/events/view/$eventId");
            exit;
        }
        $file = $_FILES['categories_file']['tmp_name'];
        $filename = $_FILES['categories_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $rows = [];
        if ($ext === 'csv') {
            if (($handle = fopen($file, 'r')) !== false) {
                $header = fgetcsv($handle);
                while (($data = fgetcsv($handle)) !== false) {
                    $rows[] = array_combine($header, $data);
                }
                fclose($handle);
            }
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            require_once(__DIR__ . '/../utils/SpreadsheetReader.php');
            $reader = new SpreadsheetReader($file);
            $header = null;
            foreach ($reader as $row) {
                if (!$header) {
                    $header = $row;
                    continue;
                }
                $rows[] = array_combine($header, $row);
            }
        } else {
            setFlashMessage('Formato de archivo no soportado.', 'danger');
            header('Location: ' . BASE_URL . "/events/view/$eventId");
            exit;
        }
        $this->db->beginTransaction();
        try {
            $catMap = [];
            foreach ($rows as $row) {
                $catName = trim($row['category_name']);
                $subcatName = trim($row['subcategory_name']);
                if ($catName === '') continue;
                // Buscar o crear event_category por nombre
                if (!isset($catMap[$catName])) {
                    // Buscar si ya existe la categoría de evento
                    $existing = $this->categoryModel->getEventCategoryByName($eventId, $catName);
                    if ($existing && isset($existing['event_category_id'])) {
                        $eventCategoryId = $existing['event_category_id'];
                    } else {
                        $eventCategoryId = $this->categoryModel->addEventCategory($eventId, $catName, 1);
                    }
                    $catMap[$catName] = $eventCategoryId;
                }
                // Crear subcategoría solo si hay nombre
                if ($subcatName !== '') {
                    // Buscar si ya existe la subcategoría bajo esa categoría
                    $existingSubcats = $this->categoryModel->getEventSubcategories($catMap[$catName]);
                    $exists = false;
                    foreach ($existingSubcats as $subcat) {
                        if (strcasecmp($subcat['name'], $subcatName) === 0) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $this->categoryModel->addEventSubcategory($catMap[$catName], $subcatName, 1);
                    }
                }
            }
            $this->db->commit();
            setFlashMessage('Categorías y subcategorías importadas correctamente.', 'success');
        } catch (Exception $e) {
            $this->db->rollback();
            setFlashMessage('Error al importar: ' . $e->getMessage(), 'danger');
        }
        header('Location: ' . BASE_URL . "/events/view/$eventId");
        exit;
    }
}
