<?php

declare(strict_types=1);

/**
 * 04. Route::resource() / Route::controller()
 *
 * 実行: php example/04-resource-and-controller.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

final class UserController
{
    public function index($request, $response)
    {
        return $response;
    }
}

final class PostController
{
    public function index($request, $response)
    {
        return $response;
    }

    public function show($request, $response)
    {
        return $response;
    }
}

$app = AppFactory::create();

$routes = new Routes([
    // 標準的なCRUD Route(index/show/create/update/patch/delete)を一括生成
    Route::resource('/users', UserController::class),

    // only/except で生成対象を絞り込める(同時指定は不可)
    Route::resource('/comments', UserController::class, only: ['index', 'show']),

    // 同一Controllerを使うRoute群でController class記述を省略
    Route::group('/posts', [
        Route::controller(PostController::class, [
            Route::get('/', 'index'),
            Route::get('/{id}', 'show'),
        ]),
    ]),
]);

$routes->deploy($app);

echo $routes->dump(showHandler: true), "\n";
