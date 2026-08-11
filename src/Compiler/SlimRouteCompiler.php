<?php

declare(strict_types=1);

namespace Tanahiro2010\SlimRouterDsl\Compiler;

use Slim\App;
use Tanahiro2010\SlimRouterDsl\Contracts\RouteNode;
use Tanahiro2010\SlimRouterDsl\Exception\InvalidRouteNodeException;
use Tanahiro2010\SlimRouterDsl\Nodes\HttpRoute;
use Tanahiro2010\SlimRouterDsl\Nodes\MiddlewareGroup;
use Tanahiro2010\SlimRouterDsl\Nodes\RouteGroup;

final class SlimRouteCompiler
{
    public function __construct(
        private readonly App $app,
    ) {
    }

    /**
     * @param RouteNode[] $routes
     */
    public function compile(array $routes): void
    {
        $context = new RouteContext();

        foreach ($routes as $route) {
            $this->compileNode($route, $context);
        }
    }

    private function compileNode(RouteNode $node, RouteContext $context): void
    {
        match (true) {
            $node instanceof HttpRoute => $this->compileHttpRoute($node, $context),
            $node instanceof RouteGroup => $this->compileRouteGroup($node, $context),
            $node instanceof MiddlewareGroup => $this->compileMiddlewareGroup($node, $context),
            default => throw new InvalidRouteNodeException(
                sprintf('Unknown RouteNode implementation: %s', $node::class)
            ),
        };
    }

    private function compileHttpRoute(HttpRoute $node, RouteContext $context): void
    {
        $path = RouteContext::joinPaths($context->prefix, $node->path);

        $route = $this->app->map($node->methods, $path, $node->handler);

        // DSL order (outer -> inner) must run in that order at request time.
        // Slim's Route::add() is LIFO, so registering in reverse DSL order
        // makes the outermost middleware execute first.
        foreach (array_reverse($context->middleware) as $middleware) {
            $route->add($middleware);
        }
    }

    private function compileRouteGroup(RouteGroup $node, RouteContext $context): void
    {
        $childContext = $context->withPrefix($node->prefix);

        foreach ($node->children as $child) {
            $this->compileNode($child, $childContext);
        }
    }

    private function compileMiddlewareGroup(MiddlewareGroup $node, RouteContext $context): void
    {
        $childContext = $context->withMiddleware($node->middleware);

        foreach ($node->children as $child) {
            $this->compileNode($child, $childContext);
        }
    }
}
