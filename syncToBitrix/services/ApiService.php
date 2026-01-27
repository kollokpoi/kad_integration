<?php
class Api {
    private static $instance = null;
    private $config;
    private $baseUrl;
    private $headers = [];
    private $accessToken = null;
    

    private function __construct() {
        $this->config = [
            'baseUrl' => 'appsapi.bgdev.site',
            'appId' => 'u2400560_kad_integration_base',
            'auth' => [
                'domain' => 'bg59.online',
                'appId' => '13c5790f-d443-431a-a7a0-c20b49e78780'
            ]
        ];
        
        $this->baseUrl = 'https://' . $this->config['baseUrl'];
        
        $this->authenticate();
        
        $this->headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->accessToken,
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Авторизация и получение токена
     */
    private function authenticate() {
        $authData = [
            'domain' => $this->config['auth']['domain'],
            'applicationId' => $this->config['auth']['appId']
        ];
        
        // Временные заголовки для авторизации
        $tempHeaders = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $url = $this->baseUrl . '/api/auth/login';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($authData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $tempHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('Ошибка при выполнении запроса авторизации');
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200 || !isset($result['success']) || !$result['success']) {
            throw new Exception('Ошибка авторизации: ' . ($result['message'] ?? 'Неизвестная ошибка'));
        }
        
        // Сохраняем только accessToken
        $this->accessToken = $result['data']['tokens']['accessToken'];
    }
    
    /**
     * GET запрос
     */
    public function get(string $endpoint, array $params = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    /**
     * POST запрос
     */
    public function post(string $endpoint, array $data = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    /**
     * Проверка валидности токена
     */
    public function isAuthenticated() {
        return !empty($this->accessToken);
    }
}
?>