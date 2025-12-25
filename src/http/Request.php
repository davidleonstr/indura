<?php
namespace indura\http;

use Exception;
use JsonException;

/**
 * Request
 * 
 * HTTP client class for sending requests to REST API endpoints.
 * Supports GET, POST, PUT, PATCH, and DELETE methods with automatic
 * JSON encoding/decoding of request and response data.
 */
class Request {
    /**
     * Base URL for all API endpoints
     * 
     * @var string
     */
    private string $baseUrl;

    /**
     * Maps HTTP methods to their corresponding cURL options
     * 
     * @var array
     */
    private array $methods = [
        'POST' => CURLOPT_POST,
        'PUT' => CURLOPT_CUSTOMREQUEST,
        'PATCH' => CURLOPT_CUSTOMREQUEST,
        'DELETE' => CURLOPT_CUSTOMREQUEST,
        'GET' => null
    ];

    /**
     * Maps HTTP methods to their corresponding cURL field options
     * 
     * @var array
     */
    private array $fields = [
        'POST' => CURLOPT_POSTFIELDS,
        'PUT' => CURLOPT_POSTFIELDS,
        'PATCH' => CURLOPT_POSTFIELDS,
        'DELETE' => CURLOPT_POSTFIELDS,
        'GET' => null
    ];

    /**
     * Constructor
     * 
     * Initializes the HTTP client with a base URL.
     * Trailing slashes are automatically removed from the base URL.
     * 
     * @param string $baseUrl The base URL for all API requests
     */
    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Sends an HTTP request to the specified endpoint
     * 
     * Constructs and executes an HTTP request using cURL.
     * For GET requests, data is appended as query parameters.
     * For other methods, data is sent as JSON in the request body.
     * Automatically decodes JSON responses or returns raw response if not JSON.
     * 
     * @param string $endpoint The API endpoint path (relative to base URL)
     * @param array $data Associative array of data to send with the request
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE). Defaults to GET
     * @return array|string Decoded JSON response as array, or raw response string if not JSON
     * @throws Exception If HTTP method is unsupported or cURL error occurs
     */
    public function send(string $endpoint, array $data = [], string $method = 'GET'): array | string {
        $method = strtoupper($method);
        
        if (!array_key_exists($method, $this->methods)) {
            throw new Exception("Unsupported HTTP method: $method");
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if ($method === 'GET') {
            // For GET, we add parameters to the URL
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
            }
        } else {
            $methodOption = $this->methods[$method];
            if ($methodOption === CURLOPT_POST) {
                curl_setopt($ch, CURLOPT_POST, true);
            } else {
                curl_setopt($ch, $methodOption, $method);
            }

            $fieldOption = $this->fields[$method];
            if ($fieldOption !== null) {
                curl_setopt($ch, $fieldOption, json_encode($data));
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            throw new Exception($error);
        }

        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $response;
        }
    }
}