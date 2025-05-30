<?php
// Wrapper mínimo para leer .xls/.xlsx usando PhpSpreadsheet
// Requiere que PhpSpreadsheet esté instalado en utils/PhpSpreadsheet
if (!class_exists('PhpOffice\PhpSpreadsheet\Reader\Xlsx')) {
    require_once __DIR__ . '/PhpSpreadsheet/vendor/autoload.php';
}
use PhpOffice\PhpSpreadsheet\IOFactory;
class SpreadsheetReader implements Iterator {
    private $spreadsheet;
    private $worksheet;
    private $rows;
    private $position = 0;
    public function __construct($file) {
        $this->spreadsheet = IOFactory::load($file);
        $this->worksheet = $this->spreadsheet->getActiveSheet();
        $this->rows = $this->worksheet->toArray();
    }
    public function current() { return $this->rows[$this->position]; }
    public function key() { return $this->position; }
    public function next() { ++$this->position; }
    public function rewind() { $this->position = 0; }
    public function valid() { return isset($this->rows[$this->position]); }
}
