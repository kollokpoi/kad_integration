<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/api/config/Database.php';
require_once $baseDir . '/api/models/Portal.php';
require_once $baseDir . '/api/models/Token.php';
require_once $baseDir . '/api/services/BitrixAuthService.php';
require_once __DIR__ . '/KADSyncService.php';

class OneTimeSync
{
    private $syncService;

    public function __construct()
    {
        $this->syncService = new KADSyncService();
    }

    public function run()
    {
        $this->log("=== Запуск синхронизации " . date('Y-m-d H:i:s') . " ===");

        $portalModel = new Portal();
        $portals = $portalModel->getAllActive();

        if (empty($portals)) {
            $this->log("Нет активных порталов для синхронизации");
            return;
        }

        $this->log("Найдено порталов: " . count($portals));

        foreach ($portals as $portal) {
            try {
                $this->log("Обработка портала: {$portal['portal_domain']}");
                $this->syncService->syncPortal($portal);
            } catch (Exception $e) {
                $this->log("Ошибка портала {$portal['portal_domain']}: " . $e->getMessage());
            }
        }

        $this->log("=== Синхронизация завершена ===");
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMsg = "[{$timestamp}] {$message}\n";
        
        echo $logMsg;
        file_put_contents(__DIR__ . '/logs/sync.log', $logMsg, FILE_APPEND);
    }
}

if (php_sapi_name() === 'cli') {
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
    }
    
    $sync = new OneTimeSync();
    $sync->run();
} else {
    echo "Запускай через командную строку: php73 run_sync.php";
}