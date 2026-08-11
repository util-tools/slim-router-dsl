<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tanahiro2010\SlimRouterDsl\Contracts\RouteNode;
use Tanahiro2010\SlimRouterDsl\Exception\RouteTreeTooDeepException;
use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

final class RouteTreeDepthTest extends TestCase
{
    private function nest(int $depth): RouteNode
    {
        $node = Route::get('/leaf', 'Handler');

        for ($i = 0; $i < $depth; $i++) {
            $node = Route::group('/g', [$node]);
        }

        return $node;
    }

    public function testModeratelyNestedTreeCompilesSuccessfully(): void
    {
        $routes = new Routes([$this->nest(50)]);

        $compiled = $routes->compile();

        self::assertCount(1, $compiled);
    }

    public function testExcessivelyNestedTreeThrowsInsteadOfExhaustingMemory(): void
    {
        $routes = new Routes([$this->nest(10000)]);

        $this->expectException(RouteTreeTooDeepException::class);

        $routes->compile();
    }
}
