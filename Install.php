<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('APP_NAME', 'Интеграция С КАД');

// HTML часть остается
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Установка <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 26px;
            font-weight: 600;
        }

        .content {
            padding: 30px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #2196F3;
        }

        .info-card h3 {
            color: #2196F3;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-card ul {
            padding-left: 20px;
            color: #555;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
        }

        .btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }

        .loader {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .status-box {
            background: #e8f5e9;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            display: none;
            border: 2px solid #4CAF50;
        }

        .status-box.error {
            background: #ffebee;
            border-color: #f44336;
        }

        .status-box.warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .token-display {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
            display: none;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            margin: 15px 0;
            overflow: hidden;
            display: none;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            width: 0%;
            transition: width 0.3s;
        }

        .fields-status {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            text-align: center;
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><?php echo APP_NAME; ?></h1>
            <p>Установка интеграции</p>
        </div>

        <div class="content">
            <div class="info-card">
                <h3>Что будет настроено:</h3>
                <ul>
                    <li>Кастомные поля в CRM (Номер дела, ИНН)</li>
                    <li>Настройки портала</li>
                </ul>
            </div>

            <div id="statusBox" class="status-box">
                <div id="statusMessage"></div>
            </div>

            <button class="btn" id="installBtn">
                <span id="btnText">Завершить установку</span>
                <div class="loader" id="btnLoader"></div>
            </button>
        </div>
    </div>

    <script src="//api.bitrix24.com/api/v1/"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('installBtn');
            const btnText = document.getElementById('btnText');
            const btnLoader = document.getElementById('btnLoader');
            const statusBox = document.getElementById('statusBox');
            const statusMessage = document.getElementById('statusMessage');

            let portalData = {};

            BX24.init(function() {
                const auth = BX24.getAuth();
                console.log('Bitrix24 OAuth Data:', auth);
                portalData = auth;

                // Показываем кнопку только если есть токен
                if (auth.access_token) {
                    btn.disabled = false;
                }
            });

            btn.onclick = async function() {
                if (!portalData.access_token) {
                    showError('Ошибка: не получен токен от Bitrix24');
                    return;
                }

                btn.disabled = true;
                btnText.textContent = 'Устанавливаем приложение...';
                btnLoader.style.display = 'block';

                try {
                    // Отправляем запрос на регистрацию в нашу API
                    const response = await fetch('api/portal/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            portal: portalData.domain,
                            auth: {
                                access_token: portalData.access_token,
                                refresh_token: portalData.refresh_token,
                                member_id: portalData.member_id || '',
                                domain: portalData.domain,
                                user_id: portalData.user_id || 0
                            }
                        })
                    });

                    const data = await response.json();
                    console.log('Registration Response:', data);

                    if (data.success) {
                        showSuccess('Приложение успешно установлено!');

                        // Создаем кастомные поля в Bitrix24
                        const fieldsResult = await createCustomFields();

                        if (fieldsResult.created > 0) {
                            showSuccess(`Установка завершена! Создано ${fieldsResult.created} полей.`);
                        } else {
                            showWarning('Поля не созданы (возможно уже существуют).');
                        }

                        btnText.textContent = 'Установлено!';

                        setTimeout(() => {
                            BX24.installFinish();
                        }, 2000);

                    } else {
                        showError(data.error || 'Ошибка установки');
                        btn.disabled = false;
                        btnText.textContent = 'Попробовать снова';
                        btnLoader.style.display = 'none';
                    }

                } catch (error) {
                    console.error('Install Error:', error);
                    showError('Ошибка сети или сервера: ' + error.message);
                    btn.disabled = false;
                    btnText.textContent = 'Попробовать снова';
                    btnLoader.style.display = 'none';
                }
            };

            function showSuccess(message) {
                statusBox.className = 'status-box';
                statusMessage.innerHTML = `✅ ${message}`;
                statusBox.style.display = 'block';
            }

            function showError(message) {
                statusBox.className = 'status-box error';
                statusMessage.textContent = `❌ ${message}`;
                statusBox.style.display = 'block';
            }

            function showWarning(message) {
                statusBox.className = 'status-box warning';
                statusMessage.textContent = `⚠️ ${message}`;
                statusBox.style.display = 'block';
            }

            // Функция создания кастомных полей остается без изменений
            async function createCustomFields() {
                const entities = ['lead', 'deal', 'contact', 'company'];
                const innEntities = ['contact', 'company'];

                console.log("INSTALLING - Creating custom fields");

                const fieldsConfig = {
                    'UF_CRM_NUMBER_CASE': {
                        fields: {
                            "FIELD_NAME": "UF_CRM_NUMBER_CASE",
                            "EDIT_FORM_LABEL": "Номер дела (кад)",
                            "LIST_COLUMN_LABEL": "Номер дела (кад)",
                            "USER_TYPE_ID": "string",
                            "XML_ID": "KAD_NUMBER_CASE"
                        },
                        label: "Номер дела",
                        forEntities: entities
                    },
                    'UF_CRM_SYNC_FREQUENCY': {
                        fields: {
                            "FIELD_NAME": "UF_CRM_SYNC_FREQUENCY",
                            "EDIT_FORM_LABEL": "Частота синхронизации в днях (кад)",
                            "LIST_COLUMN_LABEL": "Частота синхронизации (кад)",
                            "USER_TYPE_ID": "integer",
                            "XML_ID": "KAD_SYNC_FREQUENCY",
                            "SETTINGS": {
                                "DEFAULT_VALUE": "7"
                            }
                        },
                        label: "Частота синхронизации",
                        forEntities: entities
                    },
                    'UF_CRM_LAST_SYNC_DATE': {
                        fields: {
                            "FIELD_NAME": "UF_CRM_LAST_SYNC_DATE",
                            "EDIT_FORM_LABEL": "Дата последней синхронизации (кад)",
                            "LIST_COLUMN_LABEL": "Дата последней синхронизации (кад)",
                            "USER_TYPE_ID": "datetime",
                            "XML_ID": "KAD_LAST_SYNC_DATE",
                            "SHOW_IN_LIST": "Y",
                            "EDIT_IN_LIST": "N",
                            "IS_SEARCHABLE": "Y",
                        },
                        label: "Дата последней синхронизации",
                        forEntities: entities
                    },
                    'UF_CRM_SAVETO_ENUM': {
                        fields: {
                            "FIELD_NAME": "UF_CRM_SAVETO_ENUM",
                            "EDIT_FORM_LABEL": {
                                "ru": "Сохранять в",
                                "en": "Save to"
                            },
                            "LIST_COLUMN_LABEL": {
                                "ru": "Сохранять в",
                                "en": "Save to"
                            },
                            "USER_TYPE_ID": "enumeration",
                            "XML_ID": "KAD_CRM_SAVETO_ENUM",
                            "MULTIPLE": "N",
                            "MANDATORY": "N",
                            "SETTINGS": {
                                "DISPLAY": "LIST", 
                                "LIST_HEIGHT": 1,
                                "CAPTION_NO_VALUE": "Не выбрано"
                            },
                            "LIST": [ 
                                {
                                    "VALUE": "Таймлайн",
                                    "SORT": "100",
                                    "DEF": "Y",
                                    "XML_ID": "save_to_timeline"
                                },
                                {
                                    "VALUE": "Чат",
                                    "SORT": "200",
                                    "DEF": "Y",
                                    "XML_ID": "save_to_chat"
                                },
                            ]
                        },
                        label: "Куда сохранять",
                        forEntities: entities
                    },
                    'UF_CRM_INN': {
                        fields: {
                            "FIELD_NAME": "UF_CRM_INN",
                            "EDIT_FORM_LABEL": "ИНН (кад)",
                            "LIST_COLUMN_LABEL": "ИНН (кад)",
                            "USER_TYPE_ID": "string",
                            "XML_ID": "KAD_INN_NUMBER"
                        },
                        label: "ИНН",
                        forEntities: innEntities
                    },
                    'UF_CRM_SHOULD_SYNC': {
                        fields: {
                            "FIELD_NAME": "UF_CRM_SHOULD_SYNC",
                            "EDIT_FORM_LABEL": "Синхронизировать с КАД ",
                            "LIST_COLUMN_LABEL": "Синх. с КАД",
                            "USER_TYPE_ID": "boolean",
                            "XML_ID": "KAD_SHOULD_SYNC",
                            "SETTINGS": {
                                "DEFAULT_VALUE": "0"
                            }
                        },
                        label: "Синхронизация",
                        forEntities: entities
                    }
                };

                let createdCount = 0;
                let updatedCount = 0;
                let errorCount = 0;
                let totalOperations = 0;
                let completedOperations = 0;

                // Подсчет операций
                for (const fieldKey in fieldsConfig) {
                    totalOperations += fieldsConfig[fieldKey].forEntities.length;
                }

                // Функция для проверки существования поля
                async function checkFieldExists(entity, fieldName) {
                    return new Promise((resolve) => {
                        BX24.callMethod(
                            `crm.${entity}.userfield.list`, {
                                filter: {
                                    "FIELD_NAME": fieldName
                                }
                            },
                            function(result) {
                                if (result.error()) {
                                    resolve(null);
                                } else {
                                    const fields = result.data();
                                    resolve(fields && fields.length > 0 ? fields[0].ID : null);
                                }
                            }
                        );
                    });
                }

                // Функция для создания или обновления поля
                async function createOrUpdateField(entity, config, fieldKey) {
                    const entityUpper = entity.toUpperCase(); // lead -> LEAD
                    const fields = {
                        ...config.fields,
                        ENTITY_ID: `CRM_${entityUpper}` // Добавляем ENTITY_ID
                    };

                    // Проверяем существование поля
                    const existingFieldId = await checkFieldExists(entity, fields.FIELD_NAME);

                    if (existingFieldId) {
                        // Обновляем существующее поле
                        return new Promise((resolve) => {
                            BX24.callMethod(
                                `crm.${entity}.userfield.update`, {
                                    id: existingFieldId, // ID обязательно для update
                                    fields: fields
                                },
                                function(result) {
                                    if (result.error()) {
                                        console.error(`Ошибка обновления поля "${config.label}" для ${entity}:`, result);
                                        errorCount++;
                                    } else {
                                        showSuccess(`Поле "${config.label}" обновлено для ${entity}`)
                                        console.log(`Поле "${config.label}" обновлено для ${entity}`);
                                        updatedCount++;
                                    }
                                    completedOperations++;
                                    resolve();
                                }
                            );
                        });
                    } else {
                        // Создаем новое поле
                        return new Promise((resolve) => {
                            BX24.callMethod(
                                `crm.${entity}.userfield.add`, {
                                    fields: fields
                                },
                                function(result) {
                                    if (result.error()) {
                                        console.error(`Ошибка создания поля "${config.label}" для ${entity}:`, result.error());
                                        errorCount++;
                                    } else {
                                        showSuccess(`Поле "${config.label}" создано для ${entity}`)
                                        console.log(`Поле "${config.label}" создано для ${entity}:`, result.data());
                                        createdCount++;
                                    }
                                    completedOperations++;
                                    resolve();
                                }
                            );
                        });
                    }
                }

                // Создаем поля
                const promises = [];

                for (const fieldKey in fieldsConfig) {
                    const config = fieldsConfig[fieldKey];

                    for (const entity of config.forEntities) {
                        // Добавляем небольшую задержку между запросами
                        await new Promise(resolve => setTimeout(resolve, 300));

                        try {
                            await createOrUpdateField(entity, config, fieldKey);
                        } catch (err) {
                            console.error(`Исключение для поля "${config.label}" и сущности ${entity}:`, err);
                            completedOperations++;
                            errorCount++;
                        }
                    }
                }

                // Ждем завершения всех операций
                await Promise.all(promises);

                await new Promise(resolve => setTimeout(resolve, 1000));

                return {
                    created: createdCount,
                    updated: updatedCount,
                    errors: errorCount,
                    total: totalOperations,
                    summary: {
                        numberCase: entities.length,
                        inn: innEntities.length,
                        sync: entities.length
                    }
                };
            }
        });
    </script>
</body>

</html>