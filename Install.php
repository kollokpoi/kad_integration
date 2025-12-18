<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('APP_NAME', 'Интеграция С КАД');

// Оригинальная обработка установки приложения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['portal'])) {
    $portal = trim($_POST['portal'] ?? '');
    $product = trim($_POST['product'] ?? '');

    // Проверяем наличие обязательных параметров
    if (!$portal || !$product) {
        echo json_encode([
            "status" => "error",
            "message" => "Не переданы обязательные параметры."
        ]);
        exit;
    }

    // Подключение к базе данных
    $dbHost = 'localhost';
    $dbName = 'u2400560_market_app';
    $dbUser = 'u2400560';
    $dbPass = 'kE3kU8yW0gvV6bW1';

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Ошибка подключения к БД",
            "error" => $e->getMessage()
        ]);
        exit;
    }

    // Создаём таблицу, если её нет (без app_key и tg_id)
    $createTableSQL = "CREATE TABLE IF NOT EXISTS installations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            portal VARCHAR(255) NOT NULL,
            product TEXT NOT NULL,
            installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_install (portal)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    $pdo->exec($createTableSQL);

    // Проверяем наличие записи по portal
    $stmt = $pdo->prepare("SELECT id, product FROM installations WHERE portal = :portal");
    $stmt->execute([':portal' => $portal]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $currentProducts = json_decode($existing['product'], true);
        if (!is_array($currentProducts)) {
            $currentProducts = [];
        }
        if (!in_array($product, $currentProducts)) {
            $currentProducts[] = $product;
        }
        $stmt = $pdo->prepare("UPDATE installations
                                  SET product = :product,
                                      installed_at = CURRENT_TIMESTAMP
                                  WHERE id = :id");
        $stmt->execute([
            ':product' => json_encode($currentProducts, JSON_UNESCAPED_UNICODE),
            ':id' => $existing['id']
        ]);
        $msg = "Запись обновлена. Продукты: " . implode(", ", $currentProducts);
    } else {
        $productsArray = [$product];
        $stmt = $pdo->prepare("INSERT INTO installations (portal, product)
                               VALUES (:portal, :product)");
        $stmt->execute([
            ':portal' => $portal,
            ':product' => json_encode($productsArray, JSON_UNESCAPED_UNICODE)
        ]);
        $msg = "Запись успешно добавлена.";
    }

    echo json_encode([
        "status" => "ok",
        "message" => $msg
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Установка приложения <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" />
    <style>
        body {
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .install-container {
            margin-top: 5rem;
        }

        /* Стили для модального окна регистрации */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-title {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .form-submit:hover {
            background-color: #45a049;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            margin-left: 10px;
            vertical-align: middle;
        }

        #registrationError {
            color: red;
            margin-top: 10px;
            text-align: center;
            display: none;
        }

        .cancel-button {
            background-color: #f44336;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }

        .cancel-button:hover {
            background-color: #d32f2f;
        }

        .button-group {
            display: flex;
            flex-direction: column;
        }
    </style>
</head>

<body>
    <!-- Модальное окно регистрации -->
    <div id="registrationModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">Регистрация</div>
            <form id="registrationForm">
                <div class="form-group">
                    <label for="phone" class="required-field">Телефон</label>
                    <input type="tel" id="phone" name="phone" required placeholder="+7 (999) 123-45-67">
                </div>
                <div class="form-group">
                    <label for="fullName" class="required-field">ФИО</label>
                    <input type="text" id="fullName" name="fullName" required placeholder="Иванов Иван Иванович">
                </div>
                <div class="form-group">
                    <label for="position" class="required-field">Должность</label>
                    <input type="text" id="position" name="position" required placeholder="Менеджер">
                </div>
                <div class="form-group">
                    <label for="company" class="required-field">Компания</label>
                    <input type="text" id="company" name="company" required placeholder="ООО Ромашка">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="ivanov@example.com">
                </div>
                <div class="button-group">
                    <button type="submit" class="form-submit" id="submitBtn">
                        Зарегистрироваться
                        <img src="https://bg59.online/We/loading_big.gif" class="loading-spinner" id="loadingSpinner">
                    </button>
                    <button type="button" id="cancelBtn" class="cancel-button">
                        Нет, спасибо
                    </button>
                </div>
                <div id="registrationError"></div>
            </form>
        </div>
    </div>

    <!-- Основное содержимое приложения -->
    <div id="appContent">
        <div class="container center-align install-container">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Приложение «<?php echo APP_NAME; ?>» установлено!</span>
                    <p class="grey-text text-darken-2">
                        Пожалуйста, нажмите «Завершить установку», чтобы сохранить данные в базе без дублирования.
                    </p>
                </div>
                <div class="card-action">
                    <button class="btn waves-effect waves-light" id="finishButton">
                        Завершить установку
                    </button>
                </div>
            </div>
        </div>

        <form id="installForm" style="display:none;">
            <input type="hidden" name="portal" id="portalField" value="">
            <input type="hidden" name="product" id="productField" value="kad_app">
        </form>
    </div>

    <script src="//api.bitrix24.com/api/v1/"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Проверяем, была ли уже пройдена регистрация
        function checkRegistration() {
            // Здесь можно проверить в localStorage или в базе данных, прошёл ли пользователь регистрацию
            // Для примера используем localStorage
            if (localStorage.getItem('registrationCompleted')) {
                document.getElementById('appContent').style.display = 'block';
                document.getElementById('registrationModal').style.display = 'none';
            } else {
                document.getElementById('registrationModal').style.display = 'block';
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            checkRegistration();

            BX24.init(function () {
                const authInfo = BX24.getAuth();
                console.log('B24: authInfo: ', authInfo);
                // Если authInfo вернул объект, заполняем поле portal:
                var portal = authInfo && authInfo.domain ? authInfo.domain : window.location.hostname;
                document.getElementById('portalField').value = portal;
            });

            // Обработчик формы регистрации
            document.getElementById('registrationForm').addEventListener('submit', function (e) {
                e.preventDefault();

                const phone = document.getElementById('phone').value.trim();
                const fullName = document.getElementById('fullName').value.trim();
                const position = document.getElementById('position').value.trim();
                const company = document.getElementById('company').value.trim();
                const email = document.getElementById('email').value.trim();

                if (!phone || !fullName || !position || !company) {
                    document.getElementById('registrationError').textContent = "Заполните все обязательные поля";
                    document.getElementById('registrationError').style.display = 'block';
                    return;
                }

                document.getElementById('loadingSpinner').style.display = 'inline-block';
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('registrationError').style.display = 'none';

                // Формируем данные для лида
                const registrationData = {
                    phone: phone,
                    fullName: fullName,
                    position: position,
                    company: company,
                    email: email,
                    registrationDate: new Date().toLocaleString()
                };

                // Формируем COMMENT для Bitrix24
                const commentText = `Новая регистрация в приложении ИНТЕГРАЦИЯ С КАД:
Дата: ${registrationData.registrationDate}
ФИО: ${registrationData.fullName}
Телефон: ${registrationData.phone}
Должность: ${registrationData.position}
Компания: ${registrationData.company}
Email: ${registrationData.email || 'не указан'}`;

                // Отправляем данные в Bitrix24
                const bitrix24Url = 'https://vedernikov.bitrix24.ru/rest/1194/1sx1ae9pjriutopo/crm.lead.add.json';
                const params = {
                    fields: {
                        TITLE: `Регистрация В КАД: ${registrationData.fullName}`,
                        NAME: registrationData.fullName,
                        PHONE: [{ VALUE: registrationData.phone, VALUE_TYPE: 'WORK' }],
                        COMPANY_TITLE: registrationData.company,
                        POST: registrationData.position,
                        EMAIL: registrationData.email ? [{ VALUE: registrationData.email, VALUE_TYPE: 'WORK' }] : [],
                        SOURCE_ID: 'SELF',
                        COMMENTS: commentText
                    }
                };

                // Отправляем запрос к Bitrix24 API
                fetch(bitrix24Url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(params)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Ошибка при отправке данных в Bitrix24:', data.error);
                            showRegistrationError('Ошибка при отправке данных. Пожалуйста, попробуйте еще раз.');
                        } else {
                            // Сохраняем в localStorage, что регистрация пройдена
                            localStorage.setItem('registrationCompleted', 'true');
                            document.getElementById('registrationModal').style.display = 'none';
                            document.getElementById('appContent').style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка при отправке данных:', error);
                        showRegistrationError('Ошибка при отправке данных. Пожалуйста, попробуйте еще раз.');
                    })
                    .finally(() => {
                        document.getElementById('loadingSpinner').style.display = 'none';
                        document.getElementById('submitBtn').disabled = false;
                    });

                function showRegistrationError(message) {
                    document.getElementById('registrationError').textContent = message;
                    document.getElementById('registrationError').style.display = 'block';
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('submitBtn').disabled = false;
                }
            });

            // Обработчик кнопки "Нет, спасибо"
            document.getElementById('cancelBtn').addEventListener('click', function () {
                document.getElementById('registrationModal').style.display = 'none';
                document.getElementById('appContent').style.display = 'block';
            });

            document.getElementById('finishButton').addEventListener('click', function () {
                var formData = new FormData(document.getElementById('installForm'));
                fetch('Install.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Install response:", data);
                        if (data.status === "ok") {
                            BX24.installFinish();
                        } else {
                            alert("Ошибка: " + data.message);
                        }
                    })
                    .catch(err => {
                        console.error("Ошибка при установке:", err);
                        alert("Ошибка при установке");
                    });

                const entities = ['lead', 'deal', 'contact', 'company'];
                console.log("INSTALLING");
                // Общая конфигурация поля
                const fieldConfig = {
                    fields: {
                        "FIELD_NAME": "NumberCase",
                        "EDIT_FORM_LABEL": "Номер дела",
                        "LIST_COLUMN_LABEL": "Номер дела",
                        "USER_TYPE_ID": "string",
                        "XML_ID": "NumberCase"
                    }
                };
                const innFieldConfig = {
                    fields: {
                        "FIELD_NAME": "INNNumber",
                        "EDIT_FORM_LABEL": "ИНН",
                        "LIST_COLUMN_LABEL": "ИНН",
                        "USER_TYPE_ID": "string",
                        "XML_ID": "INNNumber"
                    }
                };

                // Обработчик результата (одинаковый для всех запросов)
                function handleResult(result) {
                    if (result.error()) {
                        console.error(result.error());
                    } else {
                        console.dir(result.data());
                    }
                }

                // Итерация по массиву сущностей
                entities.forEach(entity => {
                    const methodName = `crm.${entity}.userfield.add`;
                    BX24.callMethod(
                        methodName,
                        fieldConfig,
                        handleResult
                    );
                    BX24.callMethod(
                        methodName,
                        innFieldConfig,
                        handleResult
                    );
                });
                BX24.installFinish();
            });
        });
    </script>
</body>

</html>