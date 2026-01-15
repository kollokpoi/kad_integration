<?php
class TokenController
{
    public function register($data)
    {
        try {
            $portalDomain = $data['portal'] ?? '';
            $authData = $data['auth'] ?? [];

            if (empty($portalDomain) || empty($authData['access_token'])) {
                return $this->jsonResponse([
                    'error' => 'Не переданы обязательные параметры'
                ], 400);
            }

            $authService = new BitrixAuthService();
            $success = $authService->registerInstallation($portalDomain, $authData);

            if ($success) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Портал и токены успешно сохранены',
                    'data' => [
                        'portal' => $portalDomain,
                        'auth_method' => 'OAuth 2.0'
                    ]
                ]);
            }

            return $this->jsonResponse(['error' => 'Ошибка сохранения'], 500);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    public function updateToken($portalDomain, $tokenData)
    {
        try {
            if (empty($portalDomain) || empty($tokenData['access_token'])) {
                return $this->jsonResponse([
                    'error' => 'Не переданы обязательные параметры'
                ], 400);
            }

            $authService = new BitrixAuthService();

            // Подготавливаем данные для сохранения
            $authData = [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? '',
                'member_id' => $tokenData['member_id'] ?? '',
                'user_id' => $tokenData['user_id'] ?? 0
            ];

            // Обновляем токен через существующий метод или создаем новый
            $success = $authService->updateToken($portalDomain, $authData);

            if ($success) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Токен успешно обновлен',
                    'data' => [
                        'portal' => $portalDomain,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ]);
            }

            return $this->jsonResponse(['error' => 'Ошибка обновления токена'], 500);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    public function getToken($portalDomain)
    {
        try {
            if (empty($portalDomain)) {
                return $this->jsonResponse(['error' => 'Не указан портал'], 400);
            }

            $authService = new BitrixAuthService();
            $accessToken = $authService->getValidAccessToken($portalDomain);

            return $this->jsonResponse([
                'success' => true,
                'token' => $accessToken,
                'domain' => $portalDomain,
                'expires_in' => 3600
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function refresh($portalDomain)
    {
        try {
            if (empty($portalDomain)) {
                return $this->jsonResponse(['error' => 'Не указан портал'], 400);
            }

            $tokenModel = new Token();
            $token = $tokenModel->getByPortal($portalDomain);

            if (!$token) {
                return $this->jsonResponse(['error' => 'Токен не найден'], 404);
            }

            $authService = new BitrixAuthService();
            $newToken = $authService->refreshToken($portalDomain, $token);

            return $this->jsonResponse([
                'success' => true,
                'token' => $newToken,
                'domain' => $portalDomain,
                'message' => 'Токен обновлен'
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
