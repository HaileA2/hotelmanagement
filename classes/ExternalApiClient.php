<?php
class ExternalApiClient {
    protected $baseUrl;
    protected $apiKey;
    protected $timeout = 30;
    
    public function __construct($baseUrl, $apiKey = null) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }
    
    protected function sendRequest($method, $endpoint, $data = null, $headers = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        // Set default headers
        $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        
        // Add API key if provided - send both Authorization and X-API-KEY to support different providers
        if ($this->apiKey) {
            $defaultHeaders['Authorization'] = 'Bearer ' . $this->apiKey;
            // Some providers prefer an X-API-KEY header (we include both for compatibility)
            $defaultHeaders['X-API-KEY'] = $this->apiKey;
        }
        
        // Merge default and custom headers
        $headers = array_merge($defaultHeaders, $headers);
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->prepareHeaders($headers));
        
        // Set request method and data
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
                }
                break;
                
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
                }
                break;
                
            case 'GET':
            default:
                if ($data && is_array($data)) {
                    $query = http_build_query($data);
                    curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                }
                break;
        }
        
        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Handle errors
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        // Parse JSON response
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }
        
        // Check for API errors
        if ($httpCode >= 400) {
            $errorMessage = $result['message'] ?? 'Unknown error';
            throw new Exception("API Error ({$httpCode}): {$errorMessage}");
        }
        
        return $result;
    }
    
    protected function prepareHeaders($headers) {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[] = "{$key}: {$value}";
        }
        return $result;
    }
    
    protected function getConfig($key, $default = null) {
        global $config;
        return $config['external_apis'][$key] ?? $default;
    }
}
?>
