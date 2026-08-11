<?php

declare(strict_types=1);

/**
 * CLI(bin/router-dsl)向けのBootstrapファイルの例。
 * `Routes` インスタンスを return するだけでよく、Slim App のインスタンス化は不要。
 *
 * 実行例:
 *   vendor/bin/router-dsl routes --bootstrap=example/routes.php
 *   vendor/bin/router-dsl routes --bootstrap=example/routes.php --json
 *   vendor/bin/router-dsl routes --bootstrap=example/routes.php --method=POST
 *   vendor/bin/router-dsl validate --bootstrap=example/routes.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

return new Routes([
    Route::get('/', 'HomeController')->name('home'),

    Route::group('/api', [
        Route::middleware('JsonMiddleware', [
            Route::get('/health', 'HealthController'),

            Route::middleware('AuthMiddleware', [
                Route::resource('/users', 'UserController'),
            ]),
        ]),
    ]),
]);
