<?php

declare(strict_types=1);

/**
 * 01. 最小限のルート定義
 *
 * 実行: php example/01-basic-routes.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

$app = AppFactory::create();

$routes = new Routes([
    Route::get('/', function ($request, $response) {
        $response->getBody()->write('Hello, Slim Router DSL!');

        return $response;
    }),
    Route::get('/health', fn ($request, $response) => $response),
]);

// new Routes([...]) の時点ではSlimへの登録は一切行われない。
// deploy() を呼んだ時点で初めてSlimにルートが登録される。
$routes->deploy($app);

echo $routes->dump(), "\n";
