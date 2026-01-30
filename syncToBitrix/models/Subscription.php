<?php
class Subscription
{
    private $api;
    private $token;

    private $subscriptionData;

    public function __construct($data)
    {
        $this->api = Api::getInstance();
        $this->subscriptionData = $data;
        $this->getValidToken();
    }


    public static function getAllActive()
    {
        $response = Api::getInstance()->get('/api/subscription/getAll');

        if ($response['status'] !== 200) {
            throw new Exception('HTTP ошибка: ' . $response['status']);
        }

        if (!isset($response['data']) || !is_array($response['data'])) {
            throw new Exception('Некорректный формат ответа от API');
        }

        if (isset($response['data']['success']) && !$response['data']['success']) {
            $errorMessage = $response['data']['message'] ?? 'Неизвестная ошибка API';
            throw new Exception('Ошибка API: ' . $errorMessage);
        }

        if (isset($response['data']['data']) && is_array($response['data']['data'])) {
            return $response['data']['data'];
        }
        return $response['data'];
    }

    private function getValidToken()
    {
        $response = $this->api->get('/api/subscription/' . $this->getId() . '/getToken');

        if ($response['status'] !== 200) {
            throw new Exception('HTTP ошибка: ' . $response['status']);
        }

        if (!isset($response['data'])) {
            throw new Exception('Некорректный формат ответа от API');
        }

        if (isset($response['data']['success']) && !$response['data']['success']) {
            $errorMessage = $response['data']['message'] ?? 'Неизвестная ошибка API';
            throw new Exception('Ошибка API: ' . $errorMessage);
        }

        if (isset($response['data']['data']) && is_array($response['data']['data'])) {
            $tokens = $response['data']['data'];
            if (empty($tokens) || empty($tokens['access_token'])) {
                return;
            }
            $this->token = $tokens['access_token'];
        }
        $tokens = $response['data'];
        if (empty($tokens) || empty($tokens['access_token'])) {
            return;
        }
        $this->token = $tokens['access_token'];
    }

    public function updateSettings(array $settings): array
    {
        // Подготавливаем данные для отправки
        $requestData = [
            'updates' => [
                'sync_settings' => $settings
            ]
        ];


        $response = $this->api->post(
            '/api/subscription/' . $this->getId() . '/update',
            $requestData
        );

        if ($response['status'] !== 200) {
            throw new Exception('HTTP ошибка: ' . $response['status']);
        }

        $data = $response['data'];

        if (!$data['success']) {
            throw new Exception('API error: ' . ($data['message'] ?? 'Unknown error'));
        }

        return $data['data'] ?? [];
    }

    public function getId()
    {
        return $this->subscriptionData['id'];
    }
    public function getDomain()
    {
        return $this->subscriptionData['portal']['b24Domain'];
    }
    public function getMetadata()
    {
        $metadata = [];
        if (isset($this->subscriptionData['metadata']) && !empty($this->subscriptionData['metadata'])) {
            if (is_string($this->subscriptionData['metadata'])) {
                $metadata = json_decode($this->subscriptionData['metadata'], true);
            } elseif (is_array($this->subscriptionData['metadata'])) {
                $metadata = $this->subscriptionData['metadata'];
            }
        }
        return $metadata;
    }
    public function getToken()
    {
        return $this->token;
    }
    
    public function getMaxToSync()
    {
        $tariff = $this->subscriptionData['tariff'];
        $limits = $tariff['limits'];
        $maxToSync = null;

        if (!empty($limits) && !empty($limits['maxToSync'])) {
            $maxToSync = intval($limits['maxToSync']);
        }
        return $maxToSync;
    }
}
