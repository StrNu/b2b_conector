<?php
// utils/Validator.php
class Validator {
    private $errors = [];
    private $data = [];
    
    /**
     * Constructor
     * @param array $data Datos a validar
     */
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Obtener errores
     * @return array Errores de validación
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Verificar si hay errores
     * @return bool True si hay errores, false en caso contrario
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Establecer los datos a validar
     * @param array $data Datos
     * @return self
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }
    
    /**
     * Obtener los datos validados
     * @return array Datos
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Validar que un campo no esté vacío
     * @param string $field Campo a validar
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = $message ?? "El campo $field es obligatorio";
        }
        return $this;
    }
    
    /**
     * Validar que un campo tenga una longitud mínima
     * @param string $field Campo a validar
     * @param int $length Longitud mínima
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen(trim($this->data[$field])) < $length) {
            $this->errors[$field] = $message ?? "El campo $field debe tener al menos $length caracteres";
        }
        return $this;
    }
    
    /**
     * Validar que un campo tenga una longitud máxima
     * @param string $field Campo a validar
     * @param int $length Longitud máxima
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen(trim($this->data[$field])) > $length) {
            $this->errors[$field] = $message ?? "El campo $field no debe exceder los $length caracteres";
        }
        return $this;
    }
    
    /**
     * Validar que un campo sea un email válido
     * @param string $field Campo a validar
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "El campo $field debe ser un email válido";
        }
        return $this;
    }
    
    /**
     * Validar que un campo coincida con otro
     * @param string $field1 Primer campo
     * @param string $field2 Segundo campo
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function matches($field1, $field2, $message = null) {
        if (isset($this->data[$field1], $this->data[$field2]) && $this->data[$field1] !== $this->data[$field2]) {
            $this->errors[$field2] = $message ?? "Los campos $field1 y $field2 deben coincidir";
        }
        return $this;
    }
    
    /**
     * Validar que un campo sea un número
     * @param string $field Campo a validar
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "El campo $field debe ser un número";
        }
        return $this;
    }
    
    /**
     * Validar que un campo sea una fecha válida
     * @param string $field Campo a validar
     * @param string $format Formato de fecha
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "El campo $field debe ser una fecha válida en formato $format";
            }
        }
        return $this;
    }
    
  /**
     * Validar que un campo esté en un rango numérico
     * @param string $field Campo a validar
     * @param float $min Valor mínimo
     * @param float $max Valor máximo
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function range($field, $min, $max, $message = null) {
        if (isset($this->data[$field]) && (!is_numeric($this->data[$field]) || $this->data[$field] < $min || $this->data[$field] > $max)) {
            $this->errors[$field] = $message ?? "El campo $field debe estar entre $min y $max";
        }
        return $this;
    }

    /**
     * Validar que un campo sea un valor en una lista específica
     * @param string $field Campo a validar
     * @param array $values Lista de valores permitidos
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values, true)) {
            $this->errors[$field] = $message ?? "El valor del campo $field no es válido";
        }
        return $this;
    }

    /**
     * Validar que un campo sea una URL válida
     * @param string $field Campo a validar
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function url($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message ?? "El campo $field debe ser una URL válida";
        }
        return $this;
    }

    /**
     * Validar que un campo sea un número entero
     * @param string $field Campo a validar
     * @param string $message Mensaje de error personalizado
     * @return self
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?? "El campo $field debe ser un número entero";
        }
        return $this;
    }

    /**
     * Limpiar los errores actuales
     * @return self
     */
    public function clearErrors() {
        $this->errors = [];
        return $this;
    }
}