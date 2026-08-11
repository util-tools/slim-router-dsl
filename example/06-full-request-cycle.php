<?php

declare(strict_types=1);

/**
 * 06. deploy() 後に実際にリクエストをハンドリングする例
 *
 * deploy() 完了後は通常のSlim Routeとして動作することを示す。
 * ここでは実サーバーを起動する代わりに、slim/psr7でリクエストを組み立てて
 * $app->handle() へ直接渡している。
 *
 * 注意: slim/psr7 は require-dev の依存です。このサンプルを実行するには
 * `composer install`(--no-devを付けない)でdev依存を含めてインストールしてください。
 *
 * 実行: php example/06-full-request-cycle.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

final class AuthMiddleware
{
    public function __invoke($request, $handler)
    {
        $response = $handler->handle($request);
        $response->getBody()->write(' (via AuthMiddleware)');

        return $response;
    }
}

$app = AppFactory::create();

$routes = new Routes([
    Route::get('/users/{id}', function ($request, $response, array $args) {
        $response->getBody()->write('user id = ' . $args['id']);

        return $response;
    })->middleware(AuthMiddleware::class)->name('users.show'),
]);

$routes->deploy($app);

$request = (new ServerRequestFactory())->createServerRequest('GET', '/users/42');
$response = $app->handle($request);

printf("status: %d\n", $response->getStatusCode());
printf("body:   %s\n", (string) $response->getBody());
