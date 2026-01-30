<?php
require_once __DIR__ . '/models/Subscription.php';
require_once __DIR__ . '/services/ApiService.php';
class KADSyncService
{
    private $subscriptionModel;
    private $availibleSkopes = null;
    private $defaultSettings = [
        'last_sync' => null,
        'global_settings' => false,
        'frequency_days' => 7,
        'save_to_chat' => false,
        'save_to_timeline' => true,
        'save_to_calendar' => false,
    ];

    public function __construct()
    {
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

            $availibleSkopesResponse = $this->makeBitrixRequestByHook('scope', []);
            if (!empty($availibleSkopesResponse['result']) && is_array($availibleSkopesResponse['result'])) {
                $this->availibleSkopes = $availibleSkopesResponse['result'];
                $this->log("Доступные сущности: " . json_encode($this->availibleSkopes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }

            if (!$this->hasScope('crm')) {
                $this->log("Нет доступа к crm. Пропускаю подписку");
                return;
            }

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

                sleep(60);
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
        $user = $entity["ASSIGNED_BY_ID"] ?? 1;

        if (empty($caseNumber) && empty($innNumber)) {
            $this->log("Пропускаем сущность {$entity['ENTITY_TYPE']}#{$entity['ID']}: нет номера дела или ИНН");
            return;
        }

        $saveToCalendar = $settings['save_to_calendar'] ?? false;
        $this->log("Настройки сохранения в календарь: {$saveToCalendar}");

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
            $this->log("Нет настроек для сохранения результатов/ сохраняю в таймлайн");
            $saveToTimeline = true;
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
                    $this->log("Найдено дел по номеру:" . count($foundCases));
                }

                if ($saveToCalendar) {
                    if (!$this->hasScope('calendar')) {
                        $this->log("Нет доступа к календарю. Пропускаю синхронизацию событий");
                    } else {
                        $this->log("Поиск событий пользователя");

                        $userMeetings = $this->makeBitrixRequestByHook('calendar.event.get', [
                            "type" => "user",
                            "ownerId" => $user
                        ])['result'];
                        $this->log("События пользователя: " . count($userMeetings));

                        $this->log("Поиск заседаний");
                        $meetings = $this->fetchMeetings($caseNumber);
                        $this->log("События пользователя: " . count($userMeetings));
                        $entityIdToCreate = null;
                        switch ($entityType) {
                            case 'lead': {
                                    $entityIdToCreate = "L_{$entityId}";
                                }
                                break;
                            case 'deal': {
                                    $entityIdToCreate = "D_{$entityId}";
                                }
                                break;
                            case 'contact': {
                                    $entityIdToCreate = "CO_{$entityId}";
                                }
                                break;
                            case 'company': {
                                    $entityIdToCreate = "C_{$entityId}";
                                }
                                break;
                            default:
                                $entityIdToCreate = null;
                        }
                        if (isset($meetings['days_data']) && !empty($meetings['days_data'])) {
                            $foundMeetings = $meetings['days_data'];
                            $this->log("Найдено дней с заседаниями: " . count($foundMeetings));
                            foreach ($foundMeetings as $meeting) {
                                try {
                                    $this->log("Обработка заседания: {$meeting['date']}");
                                    $existingEvent = $this->findExistingEvent($userMeetings, $caseNumber, $meeting['date']);
                                    $this->log("existingEvent ID: " . ($existingEvent['ID'] ?? 'NOT FOUND'));
                                    $this->processSingleMeeting($meeting, $caseNumber, $user, $existingEvent, $entityIdToCreate);
                                } catch (Exception $e) {
                                    $this->log("Ошибка обработки заседания: {$meeting['date']}");
                                }
                            }
                        } else {
                            $this->log("Дней с заседаниями не найдено ");
                            $this->log("События пользователя: " . json_encode($meetings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                        }
                    }
                }
            } catch (Exception $e) {
                $this->log("Ошибка поиска по номеру дела: " . $e->getMessage());
            }
            // sleep(60);
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

    private function findExistingEvent($userMeetings, $caseNumber, $date)
    {
        // Генерируем тот же SYNC_ID
        $dateHash = md5($date . $caseNumber);
        $syncId = "KAD_{$dateHash}";

        foreach ($userMeetings as $meeting) {
            // Ищем SYNC_ID в описании
            if (strpos($meeting['DESCRIPTION'] ?? '', "SYNC_ID: {$syncId}") !== false) {
                $this->log("Найдено соответствие c: " . $meeting['id'] ?? $meeting['ID']);
                return $meeting;
            }

            // Или поиск по регулярке
            if (preg_match('/SYNC_ID:\s*(KAD_[a-f0-9]{32})/', $meeting['DESCRIPTION'] ?? '', $matches)) {
                if ($matches[1] === $syncId) {
                    $this->log("Найдено соответствие c: " . $meeting['id'] ?? $meeting['ID']);
                    return $meeting;
                }
            }
        }

        return null;
    }

    private function fetchMeetings($caseNumber)
    {
        $apiUrl = 'https://bgdev.site/api/kad/meetings';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'case_number' => $caseNumber,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 600,
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

    private function processSingleCase($domain, $accessToken, $entity, $case, $saveSettings)
    {
        $caseNumber = $case['case_number'] ?? null;

        try {
            $message = $this->formatCaseMessage($case, $entity);

            $this->saveToBitrix($domain, $accessToken, $entity, $message, $saveSettings);

            $this->log("Успешно обработано дело {$caseNumber}");
        } catch (Exception $e) {
            $this->log("Ошибка обработки дела {$caseNumber}: " . $e->getMessage());
        }
    }

    /**
     * Форматирует сообщение
     */
    private function formatCaseMessage($case, $entity)
    {
        $message = [
            'title' => '',
            'text' => '',
            'link' => '',
            'sync_id' => ''
        ];
        $message['title'] = "🔄 **Обновление из картотеки арбитражных дел**\n\n";

        $message['text'] .= "📋 **Дело:** {$case['case_number']}\n";
        $message['text'] .= "📅 **Дата регистрации:** {$case['date']}\n";
        $message['text'] .= "⚖️ **Суд:** {$case['court']}\n";

        if (!empty($case['judge'])) {
            $message['text'] .= "👨‍⚖️ **Судья:** {$case['judge']}\n";
        }

        if (!empty($case['plaintiff'])) {
            $message['text'] .= "👥 **Истец:** {$case['plaintiff']}\n";
        }

        if (!empty($case['respondent'])) {
            $message['text'] .= "👥 **Ответчик:** {$case['respondent']}\n";
        }

        // Если есть детали дела
        if (!empty($case['case_details']) && is_array($case['case_details'])) {
            $message['text'] .= "\n📜 **Cобытия:**\n";
            foreach ($case['case_details'] as $event) {
                $message['text'] .= "• {$event['date']} - {$event['type']}";
                if (!empty($event['result'])) {
                    $message['text'] .= " ({$event['result']})";
                }
                $message['text'] .= "\n";
            }
        }

        if (!empty($case['case_link'])) {
            $message['link'] = $case['case_link'];
        }

        $message['text'] .= "\n⏰ **Обновлено:** " . date('d.m.Y H:i');
        $dateHash = md5($case['case_number'] . $entity['ENTITY_TYPE'] . $entity['ID']);
        $message['sync_id'] = "KAD_{$dateHash}";


        return $message;
    }

    /**
     * Сохраняет в Bitrix24
     */
    private function saveToBitrix($domain, $accessToken, $entity, $message, $saveSettings)
    {
        if ($saveSettings['save_to_timeline'] ?? true) {
            if ($this->hasScope('crm'))
                $this->saveToTimeline($domain, $accessToken,  $message, $entity);
            else $this->log("Нет доступа к crm. сохранение отменяется");
        }

        if ($saveSettings['save_to_chat'] ?? false) {
            if ($this->hasScope('im'))
                $this->sendToChat($domain, $accessToken, $entity, $message);
            else $this->log("Нет доступа к чатам. сохранение отменяется");
        }
    }

    private function sendToChat($domain, $accessToken, $entity, $message)
    {
        try {
            $ids = [
                'lead' => 1,
                'deal' => 2,
                'contact' => 3,
                'company' => 4
            ];

            $entityId = $entity['ID'];
            $entityTypeId = $ids[$entity['ENTITY_TYPE']];

            $dialogId = null;

            $dialogIdResponse = $this->makeBitrixRequestByHook('crm.timeline.chat.get', [
                "entityId" => $entityId,
                "entityTypeId" => $entityTypeId
            ]);

            if (!empty($dialogIdResponse['result']) && !empty($dialogIdResponse['result']['chatId']))
                $dialogId = "chat" . $dialogIdResponse['result']['chatId'];

            if (empty($dialogId)) {
                $this->log("Не удалось получить ID диалога.");
                return;
            }
            $syncId = $message['sync_id'];
            try {
                $this->deleteMessages($dialogId, $syncId);
            } catch (Exception $e) {
                $this->log("Ошибка при удалении сообщений: " . $e->getMessage());
            }


            $messageParams = [
                "DIALOG_ID" => $dialogId,
                "MESSAGE" => $message['title'],
                "ATTACH" => [
                    "DESCRIPTION" => "SYNC_ID: " . $syncId,
                    "COLOR" => "#29619b",
                    "COLOR_TOKEN" => "secondary",
                    "BLOCKS" => [
                        [
                            "MESSAGE" => $message['text']
                        ]
                    ]
                ]
            ];

            if (!empty($message['link'])) {
                $messageParams['ATTACH']["BLOCKS"][] = [
                    "LINK" => [
                        "NAME" => "Ссылка на дело",
                        "LINK" => $message['link']
                    ]
                ];
            }

            $this->makeBitrixRequestByHook('im.message.add', $messageParams);
            $this->log("Успешно отправлено в чат пользователю ID:");
        } catch (Exception $e) {
            $this->log("Ошибка при отправке в чат: " . $e->getMessage());
        }
    }

    private function deleteMessages($dialogId, $syncId)
    {
        $messagesResponse = $this->makeBitrixRequestByHook('im.dialog.messages.get', [
            "DIALOG_ID" => $dialogId
        ]);

        if (empty($messagesResponse['result']) || empty($messagesResponse['result']['messages'])) {
            $this->log("Чат пустой. Нет сообщений");
            return;
        }

        $messagesToDelete = [];
        foreach ($messagesResponse['result']['messages'] as $message) {
            if (
                !empty($message['params']['ATTACH']) &&
                is_array($message['params']['ATTACH'])
            ) {

                foreach ($message['params']['ATTACH'] as $attach) {
                    if (
                        !empty($attach['DESCRIPTION']) &&
                        strpos($attach['DESCRIPTION'], "SYNC_ID: " . $syncId) !== false
                    ) {

                        $messagesToDelete[] = $message['id'];
                        break;
                    }
                }
            }
        }
        if (count($messagesToDelete) > 0) {
            $this->makeBitrixRequestByHook('im.v2.Chat.Message.delete', [
                "messageIds" => $messagesToDelete
            ]);
            $this->log("Удалены сообщения ID: " . json_encode($messagesToDelete, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }

    private function saveToTimeline($domain, $accessToken, $message, $entity)
    {
        try {
            $entityType = $entity['ENTITY_TYPE'];
            $entityId = $entity['ID'];

            try {
                $this->deleteTimeline($entityType, $entityId, $message['sync_id']);
            } catch (Exception $e) {
                $this->log("Ошибка при удалении комментариев: " . $e->getMessage());
            }

            $url = "https://{$domain}/rest/crm.timeline.comment.add";

            $messageText = $message['title'] . $message['text'];
            if (!empty($message['case_link'])) {
                $messageText .= "\n🔗 **Ссылка:** {$message['case_link']}\n";
            }
            $messageText .= "\nSYNC_ID: " . $message['sync_id'];


            $fields = [
                'ENTITY_ID' => $entityId,
                'ENTITY_TYPE' => $entityType,
                'COMMENT' => $messageText
            ];

            if ($entity && isset($entity['ASSIGNED_BY_ID']) && $entity['ASSIGNED_BY_ID'] > 0) {
                $fields['AUTHOR_ID'] = $entity['ASSIGNED_BY_ID'];
            }

            $result = $this->makeBitrixRequestByHook('crm.timeline.comment.add', ['fields' => $fields]);

            $this->log("Успешно сохранено в timeline");
        } catch (Exception $e) {
            $this->log("Ошибка при сохранении в timeline: " . $e->getMessage());
        }
    }

    private function deleteTimeline($entityType, $entityId, $syncId)
    {
        $timelineResponse = $this->makeBitrixRequestByHook('crm.timeline.comment.list', [
            "filter" => [
                "ENTITY_ID" => $entityId,
                "ENTITY_TYPE" => $entityType
            ]
        ]);

        if (empty($timelineResponse['result']) || !is_array($timelineResponse['result'])) {
            $this->log("Таймлайн пустой. Нет комментариев");
            return;
        }

        $commentsToDelete = [];
        foreach ($timelineResponse['result'] as $comment) {
            if (!empty($comment['COMMENT'])) {
                if (strpos($comment['COMMENT'] ?? '', "SYNC_ID: {$syncId}") !== false) {
                    $this->log("Найдено соответствие c: " . $comment['ID']);
                    $commentsToDelete[] = $comment['ID'];
                }

                // Или поиск по регулярке
                if (preg_match('/SYNC_ID:\s*(KAD_[a-f0-9]{32})/', $meeting['COMMENT'] ?? '', $matches)) {
                    if ($matches[1] === $syncId) {
                        $this->log("Найдено соответствие c: " . $comment['ID']);
                        $commentsToDelete[] = $comment['ID'];
                    }
                }
            }
        }
        foreach ($commentsToDelete as $commentId) {
            $this->makeBitrixRequestByHook('crm.timeline.comment.delete', [
                "id" => $commentId,
            ]);
            $this->log("Удален комментарий ID: " . $commentId);
        }
    }

    private function processSingleMeeting($meeting, $caseNumber, $userId, $existingEvent = null, $entityId = null)
    {
        if (empty($meeting['items']) || empty($meeting['items'][0]['Items'])) {
            return null;
        }

        // Получаем первый и последний Items из всех заседаний дня
        $allItems = [];
        foreach ($meeting['items'] as $dayData) {
            if (!empty($dayData['Items'])) {
                $allItems = array_merge($allItems, $dayData['Items']);
            }
        }

        if (empty($allItems)) {
            return null;
        }

        usort($allItems, function ($a, $b) {
            return strtotime($a['Date']) <=> strtotime($b['Date']);
        });

        $firstItem = $allItems[0];
        $lastItem = end($allItems);

        $description = "Дело: {$caseNumber}\n";
        $description .= "Суд: {$firstItem['Court']}\n";
        $description .= "Судья: {$firstItem['JudgeName']}\n";
        $description .= "Кабинет: {$firstItem['Place']}\n\n";

        $respondents = !empty($firstItem['Respondents'])
            ? implode(', ', $firstItem['Respondents'])
            : 'Не указаны';
        $description .= "Ответчик: {$respondents}\n\n";

        $description .= "Заседания:\n";
        foreach ($allItems as $index => $item) {
            $dateTime = date('d.m.Y H:i', strtotime($item['Date']));
            $description .= ($index + 1) . ". {$dateTime}";

            if ($item['Time']) {
                $description .= " ({$item['Time']})";
            }

            if ($item['Place'] && $item['Place'] != $firstItem['Place']) {
                $description .= ", каб. {$item['Place']}";
            }

            $description .= "\n";
        }

        $dateHash = md5($meeting['date'] . $caseNumber);
        $description .= "\n---\nSYNC_ID: KAD_{$dateHash}";


        $eventData = [
            'type' => 'user',
            'ownerId' => $userId,
            'from' => $firstItem['Date'],
            'to' => $lastItem['Date'],
            'name' => "Заседание по делу {$caseNumber}",
            'description' => $description,
            'timezone_from' => 'Europe/Moscow',
            'timezone_to' => 'Europe/Moscow',
            'importance' => 'high',
            'location' => "{$firstItem['Court']}, каб. {$firstItem['Place']}",
        ];
        if ($entityId) {
            $eventData['crm_fields'] = [
                $entityId
            ];
        }
        if ($existingEvent) {
            $eventData['id'] = $existingEvent['ID'] ?? $existingEvent['id'];
            $this->makeBitrixRequestByHook('calendar.event.update', $eventData);
            $this->log("Событие {$meeting['date']} $caseNumber обновлено");
        } else {
            $this->makeBitrixRequestByHook('calendar.event.add', $eventData);
            $this->log("Событие {$meeting['date']} $caseNumber создано");
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

    private function makeBitrixRequestByHook($path, $params = [])
    {
        $url = "https://b24-tqrxe2.bitrix24.ru/rest/1/ex3g1trf3is250xh/{$path}";

        // Логируем запрос
        $this->log("=== Bitrix24 API Запрос ===");
        $this->log("URL: {$url}");
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

    public function run()
    {
        $subscriptionModel = new Subscription();
        $subscriptions = $subscriptionModel->getAllActive();

        if (empty($subscriptions)) {
            $this->log("Нет активных подписок для синхронизации");
            return;
        }

        $this->log("Найдено подписок: " . count($subscriptions));

        foreach ($subscriptions as $subscription) {
            try {

                if ($subscription['portal']['b24Domain'] == "b24-tqrxe2.bitrix24.ru") {
                    $this->log("Обработка подписки портала: {$subscription['portal']['b24Domain']}");
                    $this->syncSubscription($subscription);
                }
            } catch (Exception $e) {
                $this->log("Ошибка подписки портала {$subscription['portal']['b24Domain']}: " . $e->getMessage());
            }
        }
    }

    private function hasScope($scope)
    {
        if (empty($this->availibleSkopes) || !is_array($this->availibleSkopes)) {
            // Запрашиваем scope если еще не получали
            $response = $this->makeBitrixRequestByHook('scope', []);
            $this->availibleSkopes = $response['result'] ?? [];
        }

        return in_array($scope, $this->availibleSkopes, true);
    }
}

if (php_sapi_name() === 'cli') {
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
    }

    $syncService = new KADSyncService();
    $syncService->run();
} else {
    echo "Запускай через командную строку: php73 run_sync.php";
}
