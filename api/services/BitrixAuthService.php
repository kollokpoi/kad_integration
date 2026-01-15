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

        // Если токен не истек, возвращаем его
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
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
