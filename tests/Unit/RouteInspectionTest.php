<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

final class RouteInspectionTest extends TestCase
{
    public function testToArrayFlattensSimpleTree(): void
    {
        $routes = new Routes([
            Route::get('/', 'Home'),
            Route::group('/api', [
                Route::get('/users', 'Index'),
                Route::post('/users', 'Create'),
            ]),
        ]);

        self::assertSame([
            [
                'methods' => ['GET'],
                'path' => '/',
                'handler' => 'Home',
                'middleware' => [],
                'name' => null,
            ],
            [
                'methods' => ['GET'],
                'path' => '/api/users',
                'handler' => 'Index',
                'middleware' => [],
                'name' => null,
            ],
            [
                'methods' => ['POST'],
                'path' => '/api/users',
                'handler' => 'Create',
                'middleware' => [],
                'name' => null,
            ],
        ], $routes->toArray());
    }

    public function testToArrayIncludesInheritedAndRouteLevelMiddlewareInOrder(): void
    {
        $routes = new Routes([
            Route::middleware('Json', [
                Route::get('/users/{id}', 'Show')
                    ->middleware('Auth')
                    ->name('users.show'),
            ]),
        ]);

        $result = $routes->toArray();

        self::assertSame(['Json', 'Auth'], $result[0]['middleware']);
        self::assertSame('users.show', $result[0]['name']);
    }

    public function testDumpFormatsMethodAndPath(): void
    {
        $routes = new Routes([
            Route::get('/', 'Home'),
            Route::group('/api', [
                Route::get('/users', 'Index'),
                Route::post('/users', 'Create'),
            ]),
        ]);

        $expected = implode("\n", [
            'GET     /',
            'GET     /api/users',
            'POST    /api/users',
        ]);

        self::assertSame($expected, $routes->dump());
    }

    public function testDumpJoinsMultipleMethodsWithComma(): void
    {
        $routes = new Routes([
            Route::map(['GET', 'HEAD'], '/resource', 'Handler'),
        ]);

        self::assertSame('GET,HEAD /resource', $routes->dump());
    }
}
