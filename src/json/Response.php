<?php
namespace indura\json;

class Response {
    public static function success($data = null, $message = 'Successful operation', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    public static function error($message = 'Operation error', $code = 400, $details = null) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    public static function validation($errors, $message = 'Validation errors') {
        self::error($message, 422, $errors);
    }
}