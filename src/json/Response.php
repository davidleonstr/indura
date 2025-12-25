<?php
namespace indura\json;

/**
 * Response
 * 
 * Utility class for generating standardized HTTP JSON responses.
 * Provides static methods for common response types including success,
 * error, not found, and validation responses. All responses include
 * a timestamp and automatically set appropriate HTTP status codes.
 */
class Response {
    /**
     * Sends a success JSON response
     * 
     * Outputs a JSON response with success status, optional data payload,
     * and a custom message. Sets the HTTP status code and terminates execution.
     * 
     * @param mixed $data Optional data to include in the response
     * @param string $message Success message to display
     * @param int $code HTTP status code (default: 200)
     * @return void Outputs JSON and exits
     */
    public static function success($data = null, string $message = 'Successful operation', int $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Sends an error JSON response
     * 
     * Outputs a JSON response with error status, custom message,
     * and optional error details. Sets the HTTP status code and terminates execution.
     * 
     * @param string $message Error message to display
     * @param int $code HTTP status code (default: 400)
     * @param mixed $details Optional additional error details
     * @return void Outputs JSON and exits
     */
    public static function error(string $message = 'Operation error', int $code = 400, $details = null) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Sends a 'not found' JSON response
     * 
     * Convenience method that sends a 404 error response.
     * 
     * @param string $message Not found message to display
     * @return void Outputs JSON and exits
     */
    public static function notFound(string $message = 'Resource not found') {
        self::error($message, 404);
    }

    /**
     * Sends a validation error JSON response
     * 
     * Convenience method that sends a 422 (Unprocessable Entity) error response
     * with validation error details, typically used for form validation failures.
     * 
     * @param mixed $errors Validation errors to include in the response details
     * @param string $message Validation error message to display
     * @return void Outputs JSON and exits
     */
    public static function validation($errors, string $message = 'Validation errors') {
        self::error($message, 422, $errors);
    }
}