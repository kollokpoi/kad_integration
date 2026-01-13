<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('APP_NAME', 'Интеграция С КАД');
define('CLIENT_ID', 'app.67ea84a23d9037.17281291');
define('CLIENT_SECRET', 'qLGVBQgHzqK0o4E6QiXbjrgOoL58dNIzQP9LJKIlVgrFTTTZ62');

// Обработка установки приложения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['portal'])) {
    $portal = trim($_POST['portal'] ?? '');
    
    // Получаем данные авторизации от Bitrix24
    $auth = $_POST['auth'] ?? [];
    $accessToken = $auth['access_token'] ?? '';
    $refreshToken = $auth['refresh_token'] ?? '';
    $memberId = $auth['member_id'] ?? '';
    $domain = $auth['domain'] ?? $portal;
    $userId = $auth['user_id'] ?? 0;
    
    if (!$portal || !$accessToken) {
        echo json_encode([
            "status" => "error",
            "message" => "Не передан access_token"
        ]);
        exit;
    }

    // Подключение к базе данных
    $dbHost = 'localhost';
    $dbName = 'u2400560_kad_integration_base';
    $dbUser = 'u2400560_kad_user';
    $dbPass = 'Ilovework123_';

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Ошибка подключения к БД",
            "error" => $e->getMessage()
        ]);
        exit;
    }

    // Создаем таблицы если нет
    $pdo->exec("CREATE TABLE IF NOT EXISTS portals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        portal_domain VARCHAR(255) NOT NULL UNIQUE,
        portal_name VARCHAR(255),
        settings JSON DEFAULT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_domain (portal_domain)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_oauth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        portal_domain VARCHAR(255) NOT NULL,
        client_id VARCHAR(100) NOT NULL,
        client_secret VARCHAR(255) NOT NULL,
        access_token TEXT NOT NULL,
        refresh_token TEXT NOT NULL,
        member_id VARCHAR(100) NOT NULL,
        user_id INT DEFAULT 0,
        expires_at DATETIME NOT NULL,
        token_type VARCHAR(20) DEFAULT 'Bearer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_portal (portal_domain),
        INDEX idx_expires (expires_at),
        FOREIGN KEY (portal_domain) REFERENCES portals(portal_domain) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 1. Сохраняем портал с дефолтными настройками
    $defaultSettings = json_encode([
        'notifications' => [
            'save_to_chat' => false,
            'save_to_timeline' => true
        ],
        'sync' => [
            'sync'=>false,
            'frequency_days' => 7
        ],
        'custom_fields' => [
            'case_number_field' => 'UF_CRM_NUMBER_CASE',
            'inn_field' => 'UF_CRM_INN'
        ]
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO portals (portal_domain, settings)
        VALUES (:portal, :settings)
        ON DUPLICATE KEY UPDATE 
            updated_at = NOW()
    ");
    
    $stmt->execute([
        ':portal' => $portal,
        ':settings' => $defaultSettings
    ]);

    // 2. Сохраняем OAuth токены (Bearer)
    $expiresAt = date('Y-m-d H:i:s', time() + 3500); // ~1 час
    
    $stmt = $pdo->prepare("
        INSERT INTO portal_oauth_tokens 
        (portal_domain, client_id, client_secret, access_token, refresh_token, member_id, user_id, expires_at)
        VALUES (:portal, :client_id, :client_secret, :access_token, :refresh_token, :member_id, :user_id, :expires_at)
    ");
    
    $result = $stmt->execute([
        ':portal' => $portal,
        ':client_id' => CLIENT_ID,
        ':client_secret' => CLIENT_SECRET,
        ':access_token' => $accessToken,
        ':refresh_token' => $refreshToken,
        ':member_id' => $memberId,
        ':user_id' => $userId,
        ':expires_at' => $expiresAt
    ]);

    if ($result) {
        echo json_encode([
            "status" => "ok",
            "message" => "Портал и Bearer токен сохранены",
            "data" => [
                "portal" => $portal,
                "auth_method" => "Bearer",
                "token_expires" => $expiresAt,
                "api_endpoint" => "https://{$domain}/rest/",
                "note" => "Используйте Authorization: Bearer {$accessToken}"
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Ошибка сохранения токенов"
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Установка <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            
            <div class="progress-bar" id="progressBar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            
            <div id="statusBox" class="status-box">
                <div id="statusMessage"></div>
            </div>
            
            <div id="fieldsStatus" class="fields-status"></div>
            
            <button class="btn" id="installBtn">
                <span id="btnText">Завершить установку</span>
                <div class="loader" id="btnLoader"></div>
            </button>
        </div>
    </div>

    <form id="installForm" style="display:none;">
        <input type="hidden" name="portal" id="portalField">
        <input type="hidden" name="auth[access_token]" id="accessTokenField">
        <input type="hidden" name="auth[refresh_token]" id="refreshTokenField">
        <input type="hidden" name="auth[member_id]" id="memberIdField">
        <input type="hidden" name="auth[domain]" id="domainField">
        <input type="hidden" name="auth[user_id]" id="userIdField">
    </form>

    <script src="//api.bitrix24.com/api/v1/"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('installBtn');
            const btnText = document.getElementById('btnText');
            const btnLoader = document.getElementById('btnLoader');
            const statusBox = document.getElementById('statusBox');
            const statusMessage = document.getElementById('statusMessage');
            const progressBar = document.getElementById('progressBar');
            const progressFill = document.getElementById('progressFill');
            const fieldsStatus = document.getElementById('fieldsStatus');
            
            let portalData = {};
            BX24.init(function() {
                const auth = BX24.getAuth();
                console.log('Bitrix24 OAuth Data:', auth);
                
                document.getElementById('portalField').value = auth.domain;
                document.getElementById('accessTokenField').value = auth.access_token;
                document.getElementById('refreshTokenField').value = auth.refresh_token;
                document.getElementById('memberIdField').value = auth.member_id || '';
                document.getElementById('domainField').value = auth.domain;
                document.getElementById('userIdField').value = auth.user_id || 0;
                
                portalData = auth;
            });
            
            btn.onclick = async function() {
                if (!portalData.access_token) {
                    showError('Ошибка: не получен токен от Bitrix24');
                    return;
                }
                
                btn.disabled = true;
                btnText.textContent = 'Сохранение данных...';
                btnLoader.style.display = 'block';
                statusBox.style.display = 'none';
                progressBar.style.display = 'block';
                progressFill.style.width = '10%';
                fieldsStatus.style.display = 'none';
                
                try {
                    const formData = new FormData(document.getElementById('installForm'));
                    
                    const response = await fetch('Install.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    console.log('Install Response:', data);
                    
                    if (data.status === 'ok') {
                        progressFill.style.width = '30%';
                        showSuccess('Токен сохранен. Создаю кастомные поля...');
                        
                        
                        const fieldsResult = await createCustomFields();
                        
                        progressFill.style.width = '90%';
                        
                        if (fieldsResult.created > 0) {
                            fieldsStatus.innerHTML = `
                                ✅ Создано ${fieldsResult.created} полей<br>
                                ${fieldsResult.errors > 0 ? `⚠️ ${fieldsResult.errors} ошибок (возможно, поля уже существуют)` : ''}
                            `;
                            fieldsStatus.style.display = 'block';
                            showSuccess('Установка завершена! Кастомные поля созданы.');
                        } else if (fieldsResult.errors > 0) {
                            fieldsStatus.innerHTML = `
                                ⚠️ Возможно, поля уже существуют в вашем Bitrix24.<br>
                                Проверьте в Настройках → Дополнительные поля CRM
                            `;
                            fieldsStatus.style.display = 'block';
                            showWarning('Поля не созданы (возможно уже существуют).');
                        }
                        
                        progressFill.style.width = '100%';
                        
                        btnText.textContent = 'Установлено!';
                        btn.style.background = 'linear-gradient(135deg, #4CAF50 0%, #388E3C 100%)';
                        
                        setTimeout(() => {
                            BX24.installFinish();
                        }, 3000);
                        
                    } else {
                        showError(data.message || 'Неизвестная ошибка');
                        btn.disabled = false;
                        btnText.textContent = 'Попробовать снова';
                        btnLoader.style.display = 'none';
                        progressBar.style.display = 'none';
                    }
                    
                } catch (error) {
                    console.error('Install Error:', error);
                    showError('Ошибка сети или сервера: ' + error.message);
                    btn.disabled = false;
                    btnText.textContent = 'Попробовать снова';
                    btnLoader.style.display = 'none';
                    progressBar.style.display = 'none';
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
            
            async function createCustomFields() {
                const entities = ['lead', 'deal', 'contact', 'company'];
                console.log("INSTALLING - Creating custom fields");
                
                const fieldConfig = {
                    fields: {
                        "FIELD_NAME": "UF_CRM_NUMBER_CASE",
                        "EDIT_FORM_LABEL": "Номер дела",
                        "LIST_COLUMN_LABEL": "Номер дела",
                        "USER_TYPE_ID": "string",
                        "XML_ID": "KAD_NUMBER_CASE"
                    }
                };
                
                const innFieldConfig = {
                    fields: {
                        "FIELD_NAME": "UF_CRM_INN",
                        "EDIT_FORM_LABEL": "ИНН",
                        "LIST_COLUMN_LABEL": "ИНН",
                        "USER_TYPE_ID": "string", 
                        "XML_ID": "KAD_INN_NUMBER"
                    }
                };
                
                const fieldsToCreate = [fieldConfig, innFieldConfig];
                let createdCount = 0;
                let errorCount = 0;
                let totalOperations = entities.length * fieldsToCreate.length;
                let completedOperations = 0;
                
                progressFill.style.width = '40%';

                function handleResult(result, entity, fieldType) {
                    completedOperations++;
                    

                    const progress = 40 + (completedOperations / totalOperations * 40);
                    progressFill.style.width = progress + '%';
                    
                    if (result.error()) {
                        const error = result.error();
                        console.error(`Ошибка создания поля "${fieldType}" для ${entity}:`, error);
                        
                        // Проверяем, если поле уже существует
                        if (error.error_description && 
                            (error.error_description.includes('already exists') || 
                             error.error_description.includes('уже существует'))) {
                            console.log(`Поле "${fieldType}" уже существует для ${entity}`);
                        } else {
                            errorCount++;
                        }
                    } else {
                        console.log(`Поле "${fieldType}" создано для ${entity}:`, result.data());
                        createdCount++;
                    }
                    
                    fieldsStatus.textContent = `Создано полей: ${createdCount} из ${totalOperations}...`;
                    fieldsStatus.style.display = 'block';
                }
                
                for (let i = 0; i < entities.length; i++) {
                    const entity = entities[i];
                    
                    for (let j = 0; j < fieldsToCreate.length; j++) {
                        const fieldConfig = fieldsToCreate[j];
                        const fieldType = j === 0 ? "Номер дела" : "ИНН";
                        const methodName = `crm.${entity}.userfield.add`;
                        
                        try {
                            await new Promise(resolve => setTimeout(resolve, 500));
                            
                            await new Promise((resolve) => {
                                BX24.callMethod(
                                    methodName,
                                    fieldConfig,
                                    function(result) {
                                        handleResult(result, entity, fieldType);
                                        resolve();
                                    }
                                );
                            });
                            
                        } catch (err) {
                            console.error(`Исключение при создании поля для ${entity}:`, err);
                            completedOperations++;
                            errorCount++;
                        }
                    }
                }
                
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                return {
                    created: createdCount,
                    errors: errorCount,
                    total: totalOperations
                };
            }
            
            async function createCustomFieldsSimple() {
                console.log("Creating custom fields (simple version)");
                
                const fieldConfig = {
                    fields: {
                        "FIELD_NAME": "UF_CRM_NUMBER_CASE",
                        "EDIT_FORM_LABEL": "Номер дела",
                        "LIST_COLUMN_LABEL": "Номер дела",
                        "USER_TYPE_ID": "string",
                        "XML_ID": "KAD_NUMBER_CASE_" + Date.now()
                    }
                };
                
                return new Promise((resolve) => {
                    BX24.callMethod(
                        'crm.deal.userfield.add',
                        fieldConfig,
                        function(result) {
                            if (result.error()) {
                                console.error('Ошибка создания поля для сделок:', result.error());
                                resolve({ created: 0, errors: 1, total: 1 });
                            } else {
                                console.log('Поле создано для сделок:', result.data());
                                resolve({ created: 1, errors: 0, total: 1 });
                            }
                        }
                    );
                });
            }
        });
    </script>
</body>
</html>