<?php
class Portal {
    private $pdo;
    
    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO portals (portal_domain, settings)
            VALUES (:domain, :settings)
            ON DUPLICATE KEY UPDATE 
                settings = :settings,
                updated_at = NOW()
        ");
        
        return $stmt->execute([
            ':domain' => $data['domain'],
            ':settings' => json_encode($data['settings'] ?? [])
        ]);
    }
    
    public function getByDomain($domain) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM portals 
            WHERE portal_domain = :domain 
            AND is_active = 1
        ");
        $stmt->execute([':domain' => $domain]);
        return $stmt->fetch();
    }
    
    public function updateSettings($domain, $settings) {
        $current = $this->getByDomain($domain);
        if (!$current) {
            return false;
        }
        
        $currentSettings = json_decode($current['settings'], true);
        $newSettings = array_merge($currentSettings, $settings);
        
        $stmt = $this->pdo->prepare("
            UPDATE portals 
            SET settings = :settings, updated_at = NOW()
            WHERE portal_domain = :domain
        ");
        
        return $stmt->execute([
            ':domain' => $domain,
            ':settings' => json_encode($newSettings, JSON_UNESCAPED_UNICODE)
        ]);
    }
    
    public function getAllActive() {
        $stmt = $this->pdo->query("
            SELECT portal_domain, settings, created_at 
            FROM portals 
            WHERE is_active = 1
        ");
        return $stmt->fetchAll();
    }
}
?>