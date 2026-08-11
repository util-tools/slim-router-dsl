<?php

declare(strict_types=1);

/**
 * 05. Inspection API と Validation
 *
 * 実行: php example/05-inspection-and-validation.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Tanahiro2010\SlimRouterDsl\Exception\DuplicateRouteException;
use Tanahiro2010\SlimRouterDsl\Exception\DuplicateRouteNameException;
use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

$routes = new Routes([
    Route::get('/users', 'UserIndex')->name('users.index'),
    Route::get('/users/{id}', 'UserShow')->name('users.show'),
    Route::post('/users', 'UserCreate'),
]);

echo "-- compile() --\n";
foreach ($routes->compile() as $route) {
    printf("%s %s\n", implode(',', $route->methods), $route->path);
}

echo "\n-- filterByMethod('GET') --\n";
foreach ($routes->filterByMethod('GET') as $route) {
    echo $route->path, "\n";
}

echo "\n-- findByPath('/users') (複数methodがあり得るため配列) --\n";
foreach ($routes->findByPath('/users') as $route) {
    printf("%s %s\n", implode(',', $route->methods), $route->path);
}

echo "\n-- validate() (正常系: エラーなし) --\n";
try {
    $routes->validate();
    echo "OK: 重複なし\n";
} catch (DuplicateRouteException|DuplicateRouteNameException $e) {
    echo 'NG: ', $e->getMessage(), "\n";
}

echo "\n-- validate() (異常系: method+path重複) --\n";
$invalidRoutes = new Routes([
    Route::get('/users/{id}', 'ShowA'),
    Route::get('/users/{id}', 'ShowB'),
]);

try {
    $invalidRoutes->validate();
    echo "OK: 重複なし\n";
} catch (DuplicateRouteException $e) {
    echo 'NG: ', $e->getMessage(), "\n";
}

echo "\n-- validate() (異常系: Route Name重複) --\n";
$duplicateNameRoutes = new Routes([
    Route::get('/users/{id}', 'Show')->name('users.show'),
    Route::get('/members/{id}', 'Show')->name('users.show'),
]);

try {
    $duplicateNameRoutes->validate();
    echo "OK: 重複なし\n";
} catch (DuplicateRouteNameException $e) {
    echo 'NG: ', $e->getMessage(), "\n";
}
