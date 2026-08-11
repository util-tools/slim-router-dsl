<?php

declare(strict_types=1);

namespace Tanahiro2010\SlimRouterDsl;

use Tanahiro2010\SlimRouterDsl\Contracts\RouteNode;
use Tanahiro2010\SlimRouterDsl\Nodes\HttpRoute;
use Tanahiro2010\SlimRouterDsl\Nodes\MiddlewareGroup;
use Tanahiro2010\SlimRouterDsl\Nodes\RouteGroup;
use Tanahiro2010\SlimRouterDsl\Support\MiddlewareList;

final class Route
{
    /**
     * @var string[]
     */
    private const ANY_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    public static function get(string $path, mixed $handler): HttpRoute
    {
        return self::map(['GET'], $path, $handler);
    }

    public static function post(string $path, mixed $handler): HttpRoute
    {
        return self::map(['POST'], $path, $handler);
    }

    public static function put(string $path, mixed $handler): HttpRoute
    {
        return self::map(['PUT'], $path, $handler);
    }

    public static function patch(string $path, mixed $handler): HttpRoute
    {
        return self::map(['PATCH'], $path, $handler);
    }

    public static function delete(string $path, mixed $handler): HttpRoute
    {
        return self::map(['DELETE'], $path, $handler);
    }

    public static function options(string $path, mixed $handler): HttpRoute
    {
        return self::map(['OPTIONS'], $path, $handler);
    }

    public static function head(string $path, mixed $handler): HttpRoute
    {
        return self::map(['HEAD'], $path, $handler);
    }

    public static function any(string $path, mixed $handler): HttpRoute
    {
        return self::map(self::ANY_METHODS, $path, $handler);
    }

    /**
     * @param string[] $methods
     */
    public static function map(array $methods, string $path, mixed $handler): HttpRoute
    {
        return new HttpRoute($methods, $path, $handler);
    }

    /**
     * @param RouteNode[] $children
     */
    public static function group(string $prefix, array $children): RouteGroup
    {
        return new RouteGroup($prefix, $children);
    }

    /**
     * @param string|object|array $middleware A single middleware, or an array of middleware.
     * @param RouteNode[] $children
     */
    public static function middleware(string|object|array $middleware, array $children): MiddlewareGroup
    {
        return new MiddlewareGroup(MiddlewareList::normalize($middleware), $children);
    }
}
