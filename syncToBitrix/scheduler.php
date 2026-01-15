<?php

/**
 * Демон-планировщик для автоматической синхронизации
 * Запускается один раз и работает постоянно
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0); // Убираем лимит времени

$baseDir = dirname(__DIR__);

require_once $baseDir . '/api/config/Database.php';
require_once $baseDir . '/api/models/Portal.php';
require_once $baseDir . '/api/models/Token.php';
require_once $baseDir . '/api/services/BitrixAuthService.php';
require_once __DIR__ . '/KADSyncService.php';

class SchedulerDaemon
{
    private $checkInterval = 60; // Проверять каждые 60 секунд
    private $syncService;
    private $running = true;

    public function __construct()
    {
        $this->syncService = new KADSyncService();

        // Обработка сигналов для graceful shutdown
        pcntl_signal(SIGINT, [$this, 'shutdown']);
        pcntl_signal(SIGTERM, [$this, 'shutdown']);
    }

    public function run()
    {
        $this->log("Планировщик запущен. Проверка каждые {$this->checkInterval} секунд");

        while ($this->running) {
            pcntl_signal_dispatch(); // Обрабатываем сигналы

            try {
                $this->checkAndSync();
            } catch (Exception $e) {
                $this->log("Ошибка в планировщике: " . $e->getMessage());
            }

            sleep($this->checkInterval);
        }

        $this->log("Планировщик остановлен");
    }

    private function checkAndSync()
    {
        $this->log("Проверка порталов для синхронизации...");

        $portalModel = new Portal();
        $portals = $portalModel->getAllActive();

        foreach ($portals as $portal) {
            $this->log("Портал {$portal['portal_domain']}: ");
            $this->syncService->syncPortal($portal);
        }
    }

    public function shutdown($signo)
    {
        $this->log("Получен сигнал {$signo}, останавливаюсь...");
        $this->running = false;
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] {$message}\n";

        // Также пишем в файл
        file_put_contents(
            __DIR__ . '/logs/scheduler.log',
            "[{$timestamp}] {$message}\n",
            FILE_APPEND
        );
    }
}

if (php_sapi_name() === 'cli') {
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
    }

    $daemon = new SchedulerDaemon();
    $daemon->run();
} else {
    echo "Этот скрипт должен запускаться из командной строки (CLI)";
}
