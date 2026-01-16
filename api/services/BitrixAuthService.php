<?php
class BitrixAuthService
{
    private $clientId = 'app.67ea84a23d9037.17281291';
    private $clientSecret = 'qLGVBQgHzqK0o4E6QiXbjrgOoL58dNIzQP9LJKIlVgrFTTTZ62';
    private $tokenModel;

    public function __construct()
    {
        $this->tokenModel = new Token();
    }

    public function registerInstallation($portalDomain, $authData)
    {
        // Сохраняем портал
        $portalModel = new Portal();
        $portalModel->create([
            'domain' => $portalDomain,
            'settings' => [
                'last_sync' => null,
                'global_settings' => false,
                'frequency_days' => 7,
                'save_to_chat' => false,
                'save_to_timeline' => true
            ]
        ]);

        // Сохраняем токены
        $tokenData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'access_token' => $authData['access_token'],
            'refresh_token' => $authData['refresh_token'],
            'member_id' => $authData['member_id'],
            'user_id' => $authData['user_id'] ?? 0
        ];

        return $this->tokenModel->save($portalDomain, $tokenData);
    }

    public function getValidAccessToken($portalDomain)
    {
        $token = $this->tokenModel->getByPortal($portalDomain);

        if (!$token) {
            throw new Exception("Токен не найден для портала: {$portalDomain}");
        }


        if (!$this->tokenModel->isExpired($portalDomain)) {
            return $token['access_token'];
        }

        // Если истек - обновляем
        return $this->refreshToken($portalDomain, $token);
    }

    public function refreshToken($portalDomain, $tokenData)
    {
        $url = "https://oauth.bitrix.info/oauth/token/";

        $params = [
            'grant_type' => 'refresh_token',
            'client_id' => $tokenData['client_id'],
            'client_secret' => $tokenData['client_secret'],
            'refresh_token' => $tokenData['refresh_token']
        ];

        $response = $this->makeHttpRequest($url, $params);

        if (!isset($response['access_token'])) {
            throw new Exception("Не удалось обновить токен: " . json_encode($response));
        }

        // Сохраняем новый токен
        $this->tokenModel->updateAccessToken(
            $portalDomain,
            $response['access_token'],
            $response['refresh_token'],
            $response['expires_in'] ?? 3600
        );

        return $response['access_token'];
    }
    // В BitrixAuthService.php добавить:
    public function updateToken($portalDomain, $authData)
    {
        // Проверяем, существует ли портал
        $portalModel = new Portal();
        $portal = $portalModel->getByDomain($portalDomain);

        if (!$portal) {
            // Если портала нет, создаем его с настройками по умолчанию
            $portalModel->create([
                'domain' => $portalDomain,
                'settings' => [
                    'last_sync' => null,
                    'global_settings' => false,
                    'frequency_days' => 7,
                    'save_to_chat' => false,
                    'save_to_timeline' => true
                ]
            ]);
        }

        // Сохраняем токены
        $tokenData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'access_token' => $authData['access_token'],
            'refresh_token' => $authData['refresh_token'] ?? '',
            'member_id' => $authData['member_id'] ?? '',
            'user_id' => $authData['user_id'] ?? 0
        ];

        return $this->tokenModel->save($portalDomain, $tokenData);
    }
    private function makeHttpRequest($url, $params)
    {
        $this->log("=== Bitrix24 API Запрос ===");
        $this->log("URL: {$url}");
        $this->log("Параметры: " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->log("Метод: POST");

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10
        ]);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $totalTime = round(($endTime - $startTime) * 1000, 2); // в мс

        curl_close($ch);


        $this->log("--- Ответ ---");
        $this->log("HTTP код: {$httpCode}");
        $this->log("Время выполнения: {$totalTime} мс");
        $this->log("Размер ответа: " . strlen($response) . " байт");

        // Форматируем вывод ответа для читаемости
        $formattedResponse = $this->formatResponseForLog($response);
        $this->log("Тело ответа:\n" . $formattedResponse);

        return json_decode($response, true);
    }


    private function formatResponseForLog($response)
    {
        // Пытаемся декодировать JSON для красивого форматирования
        $decoded = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Форматируем JSON
            $formatted = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            // Если ответ очень большой, обрезаем его
            if (strlen($formatted) > 2000) {
                $formatted = substr($formatted, 0, 2000) . "\n... [ответ обрезан, размер: " . strlen($formatted) . " байт]";
            }

            return $formatted;
        } else {
            // Если не JSON, просто возвращаем как есть (с обрезкой если нужно)
            if (strlen($response) > 2000) {
                return substr($response, 0, 2000) . "\n... [ответ обрезан, размер: " . strlen($response) . " байт]";
            }

            return $response;
        }
    }

    /**
     * Обновленный метод log для лучшего форматирования
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] {$message}\n";

        // Выводим в консоль с цветами (если поддерживается)
        if (php_sapi_name() === 'cli') {
            // Цвета для разных типов сообщений
            if (strpos($message, 'ОШИБКА') !== false || strpos($message, 'ERROR') !== false) {
                echo "\033[31m" . $formattedMessage . "\033[0m"; // Красный
            } elseif (strpos($message, 'Успешно') !== false || strpos($message, 'завершен успешно') !== false) {
                echo "\033[32m" . $formattedMessage . "\033[0m"; // Зеленый
            } elseif (strpos($message, '---') !== false || strpos($message, '===') !== false) {
                echo "\033[33m" . $formattedMessage . "\033[0m"; // Желтый
            } else {
                echo $formattedMessage;
            }
        } else {
            echo $formattedMessage;
        }
    }
}
