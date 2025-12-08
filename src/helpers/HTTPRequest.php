<?php
namespace ziphp\helpers;

use Exception;

class HTTPRequest {
    private string $baseUrl;

    private array $methods = [
        'POST' => CURLOPT_POST,
        'PUT' => CURLOPT_CUSTOMREQUEST,
        'PATCH' => CURLOPT_CUSTOMREQUEST,
        'DELETE' => CURLOPT_CUSTOMREQUEST,
        'GET' => null
    ];

    private array $fields = [
        'POST' => CURLOPT_POSTFIELDS,
        'PUT' => CURLOPT_POSTFIELDS,
        'PATCH' => CURLOPT_POSTFIELDS,
        'DELETE' => CURLOPT_POSTFIELDS,
        'GET' => null
    ];

    public function __construct(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function send(string $endpoint, array $data = [], string $method = 'GET') {
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

        return json_decode($response, true);
    }
}