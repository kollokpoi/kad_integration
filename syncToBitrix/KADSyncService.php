<?php
class KADSyncService
{
    private $portalModel;
    private $subscriptionModel;
    private $tokenModel;
    private $defaultSettings = [
        'last_sync' => null,
        'global_settings' => false,
        'frequency_days' => 7,
        'save_to_chat' => false,
        'save_to_timeline' => true
    ];

    public function __construct()
    {
        $this->portalModel = new Portal();
        $this->subscriptionModel = new Subscription();
    }

    /**
     * Синхронизация одного портала
     */
    public function syncSubscription($subscription): void
    {
        $subscriptionId = $subscription['id'];
        $domain = $subscription['portal']['b24Domain'];
        $metadata = [];
        if (isset($subscription['metadata']) && !empty($subscription['metadata'])) {
            if (is_string($subscription['metadata'])) {
                $metadata = json_decode($subscription['metadata'], true);
            } elseif (is_array($subscription['metadata'])) {
                $metadata = $subscription['metadata'];
            }
        }

        if (empty($metadata['sync_settings'])) {
            $this->log("Нет настроек синхронизации, использую дефолтные");
            $metadata['sync_settings'] = $this->defaultSettings;
            $this->subscriptionModel->updateSettings($subscriptionId, $this->defaultSettings);
        }

        $settings = $metadata['sync_settings'];

        $this->log("Начинаю синхронизацию подписки: {$domain} {$subscriptionId}");

        try {
            $this->log('/api/subscription/' . $subscriptionId . '/getToken');
            $tokens = $this->subscriptionModel->getValidToken($subscriptionId);

            if (empty($tokens) || empty($tokens['access_token'])) {
                $this->log("Отсутсвует токен. Пропускаю: ");
                return;
            }
            $accessToken = $tokens['access_token'];

            $entities = $this->getEntitiesToSync($domain, $accessToken);
            if (empty($entities)) {
                $this->log("Нет сущностей для синхронизации");
                $settings['last_sync'] = date('Y-m-d H:i:s');
                $this->subscriptionModel->updateSettings($subscriptionId, $settings);
                return;
            }

            $this->log("Найдено сущностей для синхронизации: " . count($entities));


            $tariff = $subscription['tariff'];
            $limits = $tariff['limits'];
            $maxToSync = null;

            if (!empty($limits) && !empty($limits['maxToSync'])) {
                $maxToSync = intval($limits['maxToSync']);
                $this->log("Найдено ограничение на синхронизацию: " . $maxToSync);
            }

            $processed = 0;
            foreach ($entities as $entity) {
                $this->processEntity($domain, $accessToken, $entity, $settings);
                $processed++;

                if (isset($maxToSync) && $processed >= $maxToSync)
                    break;

                if ($processed % 5 === 0) {
                    sleep(1);
                }
            }

            if ($settings['global_settings']) {
                $settings['last_sync'] = date('Y-m-d H:i:s');
                $this->subscriptionModel->updateSettings($subscriptionId, $settings);
            }

            $this->log("Синхронизация завершена. Обработано: {$processed} сущностей");
        } catch (Exception $e) {
            $this->log("Ошибка синхронизации портала {$domain}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получает сущности для синхронизации
     */
    private function getEntitiesToSync($domain, $accessToken)
    {
        $entities = [];
        $entityTypes = ['lead', 'deal', 'contact', 'company'];

        foreach ($entityTypes as $entityType) {
            try {
                $url = "https://{$domain}/rest/crm.{$entityType}.list";
                $result = $this->makeBitrixRequest($url, $accessToken, [
                    'filter' => [
                        'UF_CRM_SHOULD_SYNC' => 1,
                    ],
                    'select' => [
                        'ID',
                        'UF_CRM_NUMBER_CASE',
                        'UF_CRM_SYNC_FREQUENCY',
                        'UF_CRM_LAST_SYNC_DATE',
                        'UF_CRM_SAVETO_ENUM',
                        'UF_CRM_INN',
                        'TITLE',
                        'ASSIGNED_BY_ID'
                    ]
                ]);

                if (isset($result['result']) && is_array($result['result'])) {
                    foreach ($result['result'] as $entity) {
                        if (!empty($entity['UF_CRM_NUMBER_CASE']) || !empty($entity['UF_CRM_INN'])) {
                            $entity['ENTITY_TYPE'] = $entityType;
                            $entities[] = $entity;
                        }
                    }
                }
            } catch (Exception $e) {
                $this->log("Ошибка получения {$entityType}: " . $e->getMessage());
                continue;
            }
        }

        return $entities;
    }

    /**
     * Обрабатывает одну сущность
     */
    private function processEntity($domain, $accessToken, $entity, $settings)
    {
        $caseNumber = $entity['UF_CRM_NUMBER_CASE'] ?? null;
        $innNumber = $entity['UF_CRM_INN'] ?? null;

        if (empty($caseNumber) && empty($innNumber)) {
            $this->log("Пропускаем сущность {$entity['ENTITY_TYPE']}#{$entity['ID']}: нет номера дела или ИНН");
            return;
        }

        $entitySyncFrequency = $entity['UF_CRM_SYNC_FREQUENCY'] ?? null;
        $entityLastSync = $entity['UF_CRM_LAST_SYNC_DATE'] ?? null;
        $entitySaveTo = $entity['UF_CRM_SAVETO_ENUM'] ?? null;

        $syncFrequency = null;
        if (!empty($entitySyncFrequency) && $entitySyncFrequency >= 0) {
            $syncFrequency = (int)$entitySyncFrequency;
            $this->log("Частота из сущности: {$syncFrequency} дней");
        } else {
            $syncFrequency = (int)($settings['frequency_days'] ?? 7);
            $this->log("Частота из глобальных: {$syncFrequency} дней");
        }
        $this->log("entityLastSync{$entityLastSync}");
        $lastSync = null;

        if ($settings['global_settings']) {
            if (!empty($entitySyncFrequency)) {
                $lastSync = $entityLastSync;
            } elseif (!empty($settings['last_sync'])) {
                $lastSync = $settings['last_sync'];
            }
        } elseif (!empty($entityLastSync))
            $lastSync = $entityLastSync;

        if ($lastSync) {
            $lastSyncTime = strtotime($lastSync);
            $nextSyncTime = $lastSyncTime + ($syncFrequency * 86400);

            if (time() < $nextSyncTime) {
                $daysLeft = ceil(($nextSyncTime - time()) / 86400);
                $this->log("Рано синхронизировать. Следующая через {$daysLeft} дней");
                return;
            }
        }

        $saveToChat = false;
        $saveToTimeline = true;

        if (!empty($entitySaveTo)) {
            if ($entitySaveTo == '55') {
                $saveToChat = true;
                $saveToTimeline = false;
            }
            if ($entitySaveTo == '53') {
                $saveToTimeline = true;
            }
            $this->log("Настройки сохранения из сущности: chat={$saveToChat}, timeline={$saveToTimeline}");
        } else {
            $saveToChat = $settings['save_to_chat'] ?? false;
            $saveToTimeline = $settings['save_to_timeline'] ?? true;
            $this->log("Настройки сохранения из глобальных: chat={$saveToChat}, timeline={$saveToTimeline}");
        }

        if (!$saveToChat && !$saveToTimeline) {
            $this->log("Нет настроек для сохранения результатов");
            return;
        }

        $entityType = $entity['ENTITY_TYPE'];
        $entityId = $entity['ID'];

        $this->log("Обработка: {$entityType}#{$entityId} (дело: {$caseNumber}, ИНН: {$innNumber})");

        $foundCases = [];

        if (!empty($caseNumber)) {
            $this->log("Поиск по номеру дела: {$caseNumber}");
            try {
                $kadData = $this->fetchKADDataByCaseNumber($caseNumber);

                if (isset($kadData['results']) && !empty($kadData['results'])) {
                    $foundCases = $kadData['results'];
                    $this->log("Найдено дел по номеру: 1");
                }
            } catch (Exception $e) {
                $this->log("Ошибка поиска по номеру дела: " . $e->getMessage());
            }
        }

        if (!empty($innNumber)) {
            $this->log("Поиск по ИНН: {$innNumber}");
            try {
                $kadData = $this->fetchKADDataByINN($innNumber);

                if (isset($kadData['results']) && !empty($kadData['results'])) {
                    $foundCases = $kadData['results'];
                    $this->log("Найдено дел по ИНН: " . count($foundCases));
                }
            } catch (Exception $e) {
                $this->log("Ошибка поиска по ИНН: " . $e->getMessage());
            }
        }

        if (empty($foundCases)) {
            $this->log("Не найдено дел в КАД");
            return;
        }

        foreach ($foundCases as $case) {
            $this->processSingleCase($domain, $accessToken, $entity, $case, [
                'save_to_chat' => $saveToChat,
                'save_to_timeline' => $saveToTimeline,
            ]);
        }
    }

    /**
     * Получает данные по номеру дела
     */
    private function fetchKADDataByCaseNumber($caseNumber)
    {
        $apiUrl = 'https://bgdev.site/api/kad/getbyid';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'case_number' => $caseNumber,
                'include_timeline' => true
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 500,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("CURL ошибка: {$curlError}");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP ошибка: {$httpCode}");
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            throw new Exception("API КАД: " . $data['error']);
        }

        return $data;
    }

    /**
     * Получает данные по ИНН
     */
    private function fetchKADDataByINN($inn)
    {
        $apiUrl = 'https://bgdev.site/api/kad/getlistbyinn';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'inn' => $inn,
                'include_timeline' => false
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("CURL ошибка: {$curlError}");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP ошибка: {$httpCode}");
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            throw new Exception("API КАД: " . $data['error']);
        }

        return $data;
    }

    /**
     * Обрабатывает одно найденное дело
     */
    private function processSingleCase($domain, $accessToken, $entity, $case, $saveSettings)
    {
        $caseNumber = $case['case_number'] ?? null;
        $entityType = $entity['ENTITY_TYPE'];
        $entityId = $entity['ID'];

        try {
            $message = $this->formatCaseMessage($case);

            $this->saveToBitrix($domain, $accessToken, $entity, $message, $saveSettings);

            $this->updateEntityLastSync($domain, $accessToken, $entity);

            $this->log("Успешно обработано дело {$caseNumber}");
        } catch (Exception $e) {
            $this->log("Ошибка обработки дела {$caseNumber}: " . $e->getMessage());
        }
    }

    /**
     * Форматирует сообщение
     */
    private function formatCaseMessage($case)
    {
        $message = "🔄 **Обновление из картотеки арбитражных дел**\n\n";

        $message .= "📋 **Дело:** {$case['case_number']}\n";
        $message .= "📅 **Дата регистрации:** {$case['date']}\n";
        $message .= "⚖️ **Суд:** {$case['court']}\n";

        if (!empty($case['judge'])) {
            $message .= "👨‍⚖️ **Судья:** {$case['judge']}\n";
        }

        if (!empty($case['plaintiff'])) {
            $message .= "👥 **Истец:** {$case['plaintiff']}\n";
        }

        if (!empty($case['respondent'])) {
            $message .= "👥 **Ответчик:** {$case['respondent']}\n";
        }

        // Если есть детали дела
        if (!empty($case['case_details']) && is_array($case['case_details'])) {
            $message .= "\n📜 **Cобытия:**\n";
            foreach ($case['case_details'] as $event) {
                $message .= "• {$event['date']} - {$event['type']}";
                if (!empty($event['result'])) {
                    $message .= " ({$event['result']})";
                }
                $message .= "\n";
            }
        }

        if (!empty($case['case_link'])) {
            $message .= "\n🔗 **Ссылка:** {$case['case_link']}\n";
        }

        $message .= "\n⏰ **Обновлено:** " . date('d.m.Y H:i');

        return $message;
    }

    /**
     * Сохраняет в Bitrix24
     */
    private function saveToBitrix($domain, $accessToken, $entity, $message, $saveSettings)
    {
        $entityType = $entity['ENTITY_TYPE'];
        $entityId = $entity['ID'];

        if ($saveSettings['save_to_timeline'] ?? true) {
            $this->saveToTimeline($domain, $accessToken, $entityType, $entityId, $message, $entity);
        }

        if ($saveSettings['save_to_chat'] ?? false) {
            $this->sendToChat($domain, $accessToken, $entity, $message);
        }
    }

    private function saveToTimeline($domain, $accessToken, $entityType, $entityId, $message, $entity = null)
    {
        try {
            $entityMap = [
                'lead' => 'lead',
                'deal' => 'deal',
                'contact' => 'contact',
                'company' => 'company'
            ];

            $bxEntityType = $entityMap[$entityType] ?? $entityType;

            $url = "https://{$domain}/rest/crm.timeline.comment.add";

            $fields = [
                'ENTITY_ID' => $entityId,
                'ENTITY_TYPE' => $bxEntityType,
                'COMMENT' => $message
            ];

            if ($entity && isset($entity['ASSIGNED_BY_ID']) && $entity['ASSIGNED_BY_ID'] > 0) {
                $fields['AUTHOR_ID'] = $entity['ASSIGNED_BY_ID'];
            }

            $result = $this->makeBitrixRequest($url, $accessToken, ['fields' => $fields]);

            $this->log("Успешно сохранено в timeline");
        } catch (Exception $e) {
            $this->log("Ошибка при сохранении в timeline: " . $e->getMessage());
        }
    }

    private function sendToChat($domain, $accessToken, $entity, $message)
    {
        try {
            $userId = $entity['ASSIGNED_BY_ID'] ?? 0;

            if (!$userId) {
                $this->log("Нет пользователя для отправки в чат (ASSIGNED_BY_ID пуст)");
                return;
            }

            $url = "https://{$domain}/rest/im.message.add";

            $result = $this->makeBitrixRequest($url, $accessToken, [
                'DIALOG_ID' => $userId,
                'MESSAGE' => $message
            ]);

            $this->log("Успешно отправлено в чат пользователю ID: {$userId}");
        } catch (Exception $e) {
            $this->log("Ошибка при отправке в чат: " . $e->getMessage());
        }
    }

    private function updateEntityLastSync($domain, $accessToken, $entity, $entityLastSyncField = 'UF_CRM_LAST_SYNC_DATE')
    {
        $entityType = $entity['ENTITY_TYPE'] ?? null;
        $entityId = $entity['ID'] ?? null;

        if (!$entityType || !$entityId) {
            $this->log("Не удалось обновить время синхронизации: нет типа или ID сущности");
            return false;
        }

        $currentTime = date('Y-m-d H:i:s');

        try {
            $url = "https://{$domain}/rest/crm.{$entityType}.update";

            $result = $this->makeBitrixRequest($url, $accessToken, [
                'id' => $entityId,
                'fields' => [
                    $entityLastSyncField => $currentTime
                ]
            ]);

            $this->log("Время синхронизации обновлено для {$entityType}#{$entityId}: {$currentTime}");
            return true;
        } catch (Exception $e) {
            $this->log("Исключение при обновлении времени синхронизации: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Универсальный метод для запросов к Bitrix24 API
     */
    private function makeBitrixRequest($url, $accessToken, $params = [])
    {
        // Добавляем access token к параметрам
        $params['auth'] = $accessToken;

        // Логируем запрос
        $this->log("=== Bitrix24 API Запрос ===");
        $this->log("URL: {$url}");
        $this->log("Access Token: " . substr($accessToken, 0, 20) . "...");
        $this->log("Параметры: " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->log("Метод: POST");

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
        ]);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $totalTime = round(($endTime - $startTime) * 1000, 2); // в мс

        // Получаем дополнительную информацию
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $requestSize = curl_getinfo($ch, CURLINFO_REQUEST_SIZE);
        $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);

        curl_close($ch);

        // Логируем детали запроса
        $this->log("--- Ответ ---");
        $this->log("HTTP код: {$httpCode}");
        $this->log("Время выполнения: {$totalTime} мс");
        $this->log("Размер запроса: {$requestSize} байт");
        $this->log("Размер ответа: " . strlen($response) . " байт");

        // Форматируем вывод ответа для читаемости
        $formattedResponse = $this->formatResponseForLog($response);
        $this->log("Тело ответа:\n" . $formattedResponse);

        if ($curlError) {
            $this->log("CURL ошибка: {$curlError}");
            throw new Exception("CURL ошибка: {$curlError}");
        }

        if ($httpCode !== 200) {
            $this->log("ОШИБКА: HTTP код {$httpCode}");
            throw new Exception("HTTP ошибка: {$httpCode}");
        }

        $result = json_decode($response, true);

        // Логируем результат парсинга
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("ОШИБКА парсинга JSON: " . json_last_error_msg());
            $this->log("Сырой ответ: " . substr($response, 0, 500));
            throw new Exception("Ошибка парсинга JSON ответа: " . json_last_error_msg());
        }

        // Логируем структурированный результат
        $this->log("Успешно распарсен JSON");
        if (isset($result['result'])) {
            $resultCount = is_array($result['result']) ? count($result['result']) : 1;
            $this->log("Результат содержит: {$resultCount} элементов");
        }

        if (isset($result['error'])) {
            $errorMsg = $result['error_description'] ?? $result['error'];
            $this->log("Bitrix24 API ошибка: {$errorMsg}");
            $this->log("Полный ответ об ошибке: " . json_encode($result, JSON_UNESCAPED_UNICODE));
            throw new Exception("Bitrix24 API ошибка: " . $errorMsg);
        }

        $this->log("=== Запрос завершен успешно ===");

        return $result;
    }

    /**
     * Форматирует ответ для красивого вывода в лог
     */
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

        // Также пишем в файл (без цветов)
        file_put_contents(
            __DIR__ . '/logs/api_requests.log',
            $formattedMessage,
            FILE_APPEND
        );
    }
}
