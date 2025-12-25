<?php
namespace indura\validator;

/**
 * Scheme
 * 
 * A validation class that validates data against a defined set of rules.
 * Provides built-in validation rules for common data types and constraints,
 * and allows custom rule registration.
 */
class Scheme {
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

    /**
     * Constructor
     * 
     * @param array $scheme Associative array where keys are field names and values are arrays of validation rules
     */
    public function __construct($scheme) {
        $this->scheme = $scheme;
    }

    /**
     * Validates data against the defined scheme
     * 
     * @param array $data The data to validate
     * @return array Array of validation errors, empty if validation passes
     */
    public function validate(array $data): array {
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

    /**
     * Gets all validation errors
     * 
     * @return array Array of validation errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Checks if there are any validation errors
     * 
     * @return bool True if errors exist, false otherwise
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Validates that a field is present and not empty
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters (unused)
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateRequired($value, string $fieldName, array $params, array $data): null | string {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return "The {$fieldName} field is required";
        }
        return null;
    }

    /**
     * Validates that a field is not present or empty
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters (unused)
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateExcluded($value, string $fieldName, array $params, array $data): null | string {
        if ($value !== null && $value !== '') {
            return "The {$fieldName} field must not be present";
        }
        return null;
    }

    /**
     * Validates that a field is a valid email address
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters (unused)
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateEmail($value, string $fieldName, array $params, array $data): null | string {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$fieldName} field must be a valid email format";
        }
        return null;
    }

    /**
     * Validates that a field is a string
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters (unused)
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateString($value, string $fieldName, array $params, array $data): null | string {
        if ($value !== null && !is_string($value)) {
            return "The {$fieldName} field must be a string";
        }
        return null;
    }

    /**
     * Validates that a field is an integer
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters (unused)
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateInteger($value, string $fieldName, array $params, array $data): null | string {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
            return "The {$fieldName} field must be an integer";
        }
        return null;
    }

    /**
     * Validates that a field is a float/decimal number
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters (unused)
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateFloat($value, string $fieldName, array $params, array $data): null | string {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            return "The {$fieldName} field must be a decimal number";
        }
        return null;
    }

    /**
     * Validates that a field is a boolean value
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters (unused)
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateBoolean($value, string $fieldName, array $params, array $data): null | string {
        if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            return "The {$fieldName} field must be a boolean";
        }
        return null;
    }

    /**
     * Validates that a field value is within a list of allowed values
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Array of allowed values
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateIn($value, string $fieldName, array $params, array $data): null | string {
        if (!empty($value) && !in_array($value, $params, true)) {
            return "The {$fieldName} field must be one of: " . implode(', ', $params);
        }
        return null;
    }

    /**
     * Validates that a field value is not within a list of excluded values
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Array of excluded values
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateExcludedIn($value, string $fieldName, array $params, array $data): null | string {
        if (!empty($value) && in_array($value, $params, true)) {
            return "The {$fieldName} field must not be one of: " . implode(', ', $params);
        }
        return null;
    }

    /**
     * Validates that a field value is unique and does not exist in another field's array
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Array containing the name of the field to check against
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateUniqueIn($value, string $fieldName, array $params, array $data): null | string {
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

    /**
     * Validates that a field is required when another field has a specific value
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Array containing [dependent field name, dependent field value]
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateRequiredIn($value, string $fieldName, array $params, array $data): null | string {
        $dependentField = $params[0] ?? null;
        $dependentValue = $params[1] ?? null;

        if ($dependentField && isset($data[$dependentField]) && $data[$dependentField] == $dependentValue) {
            return $this->validateRequired($value, $fieldName, [], $data);
        }
        return null;
    }

    /**
     * Validates that a field is an associative array (dictionary)
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters (unused)
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateDict($value, string $fieldName, array $params, array $data): null | string {
        if ($value !== null && (!is_array($value) || array_keys($value) === range(0, count($value) - 1))) {
            return "The {$fieldName} field must be an associative array (dictionary)";
        }
        return null;
    }

    /**
     * Validates minimum value, length, or array size
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Array containing the minimum value
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateMin($value, $fieldName, $params, $data): null | string {
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

    /**
     * Validates maximum value, length, or array size
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Array containing the maximum value
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    private function validateMax($value, string $fieldName, array $params, array $data): null | string {
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

    /**
     * Registers a custom validation rule
     * 
     * @param string $ruleName The name of the custom rule
     * @param callable $callback The validation callback function
     * @return void
     */
    public function addRule(string $ruleName, $callback) {
        $this->ruleMethods[$ruleName] = $callback;
    }

    /**
     * Executes a custom validation rule
     * 
     * @param string $ruleName The name of the rule to execute
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field
     * @param array $params Additional parameters for the rule
     * @param array $data The full data array
     * @return string|null Error message or null if valid
     */
    public function customValidation(string $ruleName, $value, string $fieldName, array $params, array $data) {
        if (is_callable($this->ruleMethods[$ruleName])) {
            return call_user_func($this->ruleMethods[$ruleName], $value, $fieldName, $params, $data);
        }
        return null;
    }
}