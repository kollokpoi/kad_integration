<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/models/Subscription.php';
require_once __DIR__ . '/services/ApiService.php';
require_once __DIR__ . '/KADSyncService.php';

class OneTimeSync
{
    private $syncService;


    public function run()
    {
        $this->log("=== Запуск синхронизации " . date('Y-m-d H:i:s') . " ===");

        $subscriptions = Subscription::getAllActive();

        if (empty($subscriptions)) {
            $this->log("Нет активных подписок для синхронизации");
            return;
        }

        $this->log("Найдено подписок: " . count($subscriptions));

        foreach ($subscriptions as $subscription) {
            try {
                if ($subscription['portal']['b24Domain'] == "b24-tqrxe2.bitrix24.ru") {
                    $this->log("Обработка подписки портала: {$subscription['portal']['b24Domain']}");
                    $syncService = new KADSyncService($subscription);
                    $syncService->syncSubscription();
                }
            } catch (Exception $e) {
                $this->log("Ошибка подписки портала {$subscription['portal']['b24Domain']}: " . $e->getMessage());
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
