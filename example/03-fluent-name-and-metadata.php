<?php

declare(strict_types=1);

/**
 * 03. Route単位のFluent Middleware / Route Name / Metadata
 *
 * 実行: php example/03-fluent-name-and-metadata.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

final class AuthMiddleware
{
    public function __invoke($request, $handler)
    {
        return $handler->handle($request);
    }
}

final class UserController
{
    public function show($request, $response)
    {
        return $response;
    }
}

$app = AppFactory::create();

$routes = new Routes([
    Route::get('/users/{id}', [UserController::class, 'show'])
        // Route単位のFluent Middleware。Group/Middlewareから継承したmiddlewareより
        // 後(Handlerに最も近い位置)で実行される。
        ->middleware(AuthMiddleware::class)
        // deploy()時にSlimのsetName()へ渡される。
        ->name('users.show')
        // 任意のメタデータ。DSL自体は解釈せず、認可判定やドキュメント生成などに使える。
        ->meta([
            'summary' => 'ユーザー取得',
            'auth' => true,
            'permission' => 'users.read',
        ]),

    Route::get('/health', fn ($req, $res) => $res), // metaなし
]);

$routes->deploy($app);

echo "-- dump() --\n";
echo $routes->dump(), "\n\n";

echo "-- authが必要なルートだけ抽出 (filter) --\n";
foreach ($routes->filter(fn ($route) => $route->metadata['auth'] ?? false) as $route) {
    printf("%s %s (permission: %s)\n", implode(',', $route->methods), $route->path, $route->metadata['permission']);
}

echo "\n-- findByName('users.show') --\n";
$route = $routes->findByName('users.show');
printf("path=%s name=%s\n", $route->path, $route->name);
