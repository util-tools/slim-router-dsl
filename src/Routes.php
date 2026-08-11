<?php

declare(strict_types=1);

namespace Tanahiro2010\SlimRouterDsl;

use Slim\App;
use Tanahiro2010\SlimRouterDsl\Compiler\CompiledRoute;
use Tanahiro2010\SlimRouterDsl\Compiler\RouteFlattener;
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

    /**
     * @return array<int, array{
     *     methods: string[],
     *     path: string,
     *     handler: mixed,
     *     middleware: array,
     *     name: string|null,
     * }>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (CompiledRoute $route): array => [
                'methods' => $route->methods,
                'path' => $route->path,
                'handler' => $route->handler,
                'middleware' => $route->middleware,
                'name' => $route->name,
            ],
            (new RouteFlattener())->flatten($this->routes),
        );
    }

    public function dump(): string
    {
        $lines = array_map(
            static fn (array $route): string => sprintf('%-7s %s', implode(',', $route['methods']), $route['path']),
            $this->toArray(),
        );

        return implode("\n", $lines);
    }
}
