<?php

declare(strict_types=1);

namespace Tanahiro2010\SlimRouterDsl;

use Slim\App;
use Tanahiro2010\SlimRouterDsl\Compiler\SlimRouteCompiler;
use Tanahiro2010\SlimRouterDsl\Contracts\RouteNode;
use Tanahiro2010\SlimRouterDsl\Exception\InvalidRouteNodeException;

final class Routes
{
    /**
     * @param RouteNode[] $routes
     */
    public function __construct(
        private readonly array $routes,
    ) {
        foreach ($this->routes as $route) {
            if (!$route instanceof RouteNode) {
                throw new InvalidRouteNodeException(
                    'Routes constructor requires an array of RouteNode instances.'
                );
            }
        }
    }

    public function deploy(App $app): void
    {
        $compiler = new SlimRouteCompiler($app);

        $compiler->compile($this->routes);
    }
}
