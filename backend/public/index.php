<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

// Crear la app Slim
$app = AppFactory::create();

// Configurar PDO manualmente
$settings = require __DIR__ . '/../src/settings.php';
$db = $settings['db'];
$pdo = new PDO(
    "{$db['driver']}:host={$db['host']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Middlewares
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Middleware CORS global
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Manejar preflight (OPTIONS)
$app->options('/{routes:.+}', function ($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Cargar rutas, pasando $pdo como parámetro
(require __DIR__ . '/../src/routes.php')($app, $pdo);

// Ejecutar app
$app->run();
