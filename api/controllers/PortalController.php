<?php
class PortalController {
    public function getSettings($portalDomain) {
        try {
            $portalModel = new Portal();
            $portal = $portalModel->getByDomain($portalDomain);
            
            if (!$portal) {
                return $this->jsonResponse(['error' => 'Портал не найден'], 404);
            }
            
            $settings = json_decode($portal['settings'], true);
            
            return $this->jsonResponse([
                'success' => true,
                'domain' => $portal['portal_domain'],
                'settings' => $settings,
                'created_at' => $portal['created_at']
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    public function updateSettings($portalDomain, $settings) {
        try {
            if (empty($settings) || !is_array($settings)) {
                return $this->jsonResponse(['error' => 'Некорректные настройки'], 400);
            }
            
            $portalModel = new Portal();
            $success = $portalModel->updateSettings($portalDomain, $settings);
            
            if ($success) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Настройки обновлены'
                ]);
            }
            
            return $this->jsonResponse(['error' => 'Не удалось обновить настройки'], 500);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>