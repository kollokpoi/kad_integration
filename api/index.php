<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/api/config/',
        BASE_PATH . '/api/controllers/',
        BASE_PATH . '/api/models/',
        BASE_PATH . '/api/services/'
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // Если класс не найден
    throw new Exception("Class {$class} not found");
});

// Получаем путь запроса
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Убираем базовый путь из URL
$basePath = '/Apps/bg_kad_integration';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Парсим путь
$path = parse_url($requestUri, PHP_URL_PATH);

// Убираем /api/ если есть
if (strpos($path, '/api/') === 0) {
    $path = substr($path, 4); // Убираем '/api'
}

// Убираем слеш в начале
$path = ltrim($path, '/');

// Получаем данные запроса
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$queryParams = $_GET;

// Логирование для отладки
file_put_contents(
    BASE_PATH . '/debug.log',
    date('Y-m-d H:i:s') . " | Path: {$path} | Method: {$requestMethod}\n",
    FILE_APPEND
);

// Роутинг
try {
    switch ($path) {
        case '':
        case 'portal/register':
            if ($requestMethod === 'POST') {
                $controller = new TokenController();
                $controller->register($input);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case 'token/get':
            if ($requestMethod === 'GET') {
                $portalDomain = $queryParams['domain'] ?? '';
                $controller = new TokenController();
                $controller->getToken($portalDomain);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case 'token/refresh':
            if ($requestMethod === 'POST') {
                $portalDomain = $input['domain'] ?? '';
                $controller = new TokenController();
                $controller->refresh($portalDomain);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'token/update':
            if ($requestMethod === 'POST') {
                $portalDomain = $input['domain'] ?? '';
                $tokenData = $input['token'] ?? [];

                if (empty($portalDomain) || empty($tokenData['access_token'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields: domain and access_token']);
                    exit;
                }

                $controller = new TokenController();
                $controller->updateToken($portalDomain, $tokenData);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'portal/settings':
            if ($requestMethod === 'GET') {
                $portalDomain = $queryParams['domain'] ?? '';
                $controller = new PortalController();
                $controller->getSettings($portalDomain);
            } elseif ($requestMethod === 'POST') {
                $portalDomain = $input['domain'] ?? '';
                $settings = $input['settings'] ?? [];
                $controller = new PortalController();
                $controller->updateSettings($portalDomain, $settings);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case 'health':
        case 'api/health':
            echo json_encode([
                'status' => 'ok',
                'timestamp' => date('Y-m-d H:i:s'),
                'service' => 'kad-integration-api',
                'path' => $path,
                'request_uri' => $_SERVER['REQUEST_URI']
            ]);
            break;

        default:
            http_response_code(404);
            echo json_encode([
                'error' => 'Endpoint not found',
                'path' => $path,
                'method' => $requestMethod,
                'available_endpoints' => [
                    'POST /portal/register',
                    'GET /token/get?domain=PORTAL_DOMAIN',
                    'POST /token/refresh',
                    'GET /portal/settings?domain=PORTAL_DOMAIN',
                    'POST /portal/settings',
                    'GET /health'
                ]
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
