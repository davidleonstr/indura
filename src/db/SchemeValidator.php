<?php
namespace ziphp\db;

class SchemeValidator {
    private $errors = [];
    private $scheme;
    
    private $ruleMethods = [
        'required' => 'validateRequired',
        'excluded' => 'validateExcluded',
        'email' => 'validateEmail',
        'string' => 'validateString',
        'integer' => 'validateInteger',
        'float' => 'validateFloat',
        'boolean' => 'validateBoolean',
        'in' => 'validateIn',
        'excluded_in' => 'validateExcludedIn',
        'unique-in' => 'validateUniqueIn',
        'required-in' => 'validateRequiredIn',
        'dict' => 'validateDict',
        'min' => 'validateMin',
        'max' => 'validateMax',
    ];

    public function __construct($scheme) {
        $this->scheme = $scheme;
    }

    public function validate($data) {
        $this->errors = [];

        foreach ($this->scheme as $field => $rules) {
            $value = $data[$field] ?? null;

            foreach ($rules as $rule) {
                $parts = explode(':', $rule);
                $ruleName = $parts[0];
                $params = array_slice($parts, 1);

                if (isset($this->ruleMethods[$ruleName])) {
                    $method = $this->ruleMethods[$ruleName];
                    
                    $validation = $this->$method($value, $field, $params, $data);

                    if ($validation !== null) {
                        $this->errors[$field][] = $validation;
                    }
                }
            }
        }

        return $this->errors;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    private function validateRequired($value, $fieldName, $params, $data) {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return "The {$fieldName} field is required";
        }
        return null;
    }

    private function validateExcluded($value, $fieldName, $params, $data) {
        if ($value !== null && $value !== '') {
            return "The {$fieldName} field must not be present";
        }
        return null;
    }

    private function validateEmail($value, $fieldName, $params, $data) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$fieldName} field must be a valid email format";
        }
        return null;
    }

    private function validateString($value, $fieldName, $params, $data) {
        if ($value !== null && !is_string($value)) {
            return "The {$fieldName} field must be a string";
        }
        return null;
    }

    private function validateInteger($value, $fieldName, $params, $data) {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
            return "The {$fieldName} field must be an integer";
        }
        return null;
    }

    private function validateFloat($value, $fieldName, $params, $data) {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            return "The {$fieldName} field must be a decimal number";
        }
        return null;
    }

    private function validateBoolean($value, $fieldName, $params, $data) {
        if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            return "The {$fieldName} field must be a boolean";
        }
        return null;
    }

    private function validateIn($value, $fieldName, $params, $data) {
        if (!empty($value) && !in_array($value, $params, true)) {
            return "The {$fieldName} field must be one of: " . implode(', ', $params);
        }
        return null;
    }

    private function validateExcludedIn($value, $fieldName, $params, $data) {
        if (!empty($value) && in_array($value, $params, true)) {
            return "The {$fieldName} field must not be one of: " . implode(', ', $params);
        }
        return null;
    }

    private function validateUniqueIn($value, $fieldName, $params, $data) {
        $listField = $params[0] ?? null;
        if (!$listField || !isset($data[$listField])) {
            return null;
        }

        $list = is_array($data[$listField]) ? $data[$listField] : [];
        if (!empty($value) && in_array($value, $list, true)) {
            return "The {$fieldName} field must be unique and not exist in {$listField}";
        }
        return null;
    }

    private function validateRequiredIn($value, $fieldName, $params, $data) {
        $dependentField = $params[0] ?? null;
        $dependentValue = $params[1] ?? null;

        if ($dependentField && isset($data[$dependentField]) && $data[$dependentField] == $dependentValue) {
            return $this->validateRequired($value, $fieldName, [], $data);
        }
        return null;
    }

    private function validateDict($value, $fieldName, $params, $data) {
        if ($value !== null && (!is_array($value) || array_keys($value) === range(0, count($value) - 1))) {
            return "The {$fieldName} field must be an associative array (dictionary)";
        }
        return null;
    }

    private function validateMin($value, $fieldName, $params, $data) {
        $min = $params[0] ?? 0;
        
        if (is_numeric($value) && $value < $min) {
            return "The {$fieldName} field must be at least {$min}";
        }
        if (is_string($value) && strlen($value) < $min) {
            return "The {$fieldName} field must be at least {$min} characters";
        }
        if (is_array($value) && count($value) < $min) {
            return "The {$fieldName} field must have at least {$min} items";
        }
        return null;
    }

    private function validateMax($value, $fieldName, $params, $data) {
        $max = $params[0] ?? PHP_INT_MAX;
        
        if (is_numeric($value) && $value > $max) {
            return "The {$fieldName} field must not exceed {$max}";
        }
        if (is_string($value) && strlen($value) > $max) {
            return "The {$fieldName} field must not exceed {$max} characters";
        }
        if (is_array($value) && count($value) > $max) {
            return "The {$fieldName} field must not have more than {$max} items";
        }
        return null;
    }

    public function addRule($ruleName, $callback) {
        $this->ruleMethods[$ruleName] = $callback;
    }

    public function customValidation($ruleName, $value, $fieldName, $params, $data) {
        if (is_callable($this->ruleMethods[$ruleName])) {
            return call_user_func($this->ruleMethods[$ruleName], $value, $fieldName, $params, $data);
        }
        return null;
    }
}