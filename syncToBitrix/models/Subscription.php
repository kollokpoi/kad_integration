<?php
class Subscription
{
    private $api;

    public function __construct()
    {
        $this->api = Api::getInstance();
    }


    public function getAllActive()
    {
        $response = $this->api->get('/api/subscription/getAll');

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

    public function getValidToken($subscriptionId)
    {
        $response = $this->api->get('/api/subscription/' . $subscriptionId . '/getToken');

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
            return $response['data']['data'];
        }

        return $response['data'];
    }

    public function updateSettings($subscriptionId, array $settings): array
    {
        // Подготавливаем данные для отправки
        $requestData = [
            'updates' => [
                'sync_settings' => $settings
            ]
        ];

        $response = $this->api->post(
            '/api/subscription/' . $subscriptionId . '/update',
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
}
