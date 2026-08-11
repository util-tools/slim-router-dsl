<?php

declare(strict_types=1);

/**
 * 02. Group / Middleware のネスト
 *
 * 実行: php example/02-groups-and-middleware.php
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

final class JsonMiddleware
{
    public function __invoke($request, $handler)
    {
        return $handler->handle($request);
    }
}

$app = AppFactory::create();

$routes = new Routes([
    Route::get('/', fn ($req, $res) => $res),

    Route::group('/api', [
        // 複数Middlewareは配列形式でまとめて指定できる(候補A、推奨)
        Route::middleware([JsonMiddleware::class], [
            Route::get('/health', fn ($req, $res) => $res),

            Route::middleware(AuthMiddleware::class, [
                Route::group('/users', [
                    Route::get('/', fn ($req, $res) => $res),
                    Route::post('/', fn ($req, $res) => $res),
                    Route::get('/{id}', fn ($req, $res) => $res),
                ]),
            ]),
        ]),
    ]),
]);

$routes->deploy($app);

// dump() は宣言順(外側→内側)がそのままリクエスト時の実行順序になることを前提に
// Middleware列を表示する。SlimのRoute::add()はLIFOだが、Compilerが登録順を調整する。
echo $routes->dump(), "\n";
