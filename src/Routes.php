<?php

declare(strict_types=1);

namespace Tanahiro2010\SlimRouterDsl;

use Closure;
use Slim\App;
use Tanahiro2010\SlimRouterDsl\Compiler\CompiledRoute;
use Tanahiro2010\SlimRouterDsl\Compiler\RouteFlattener;
use Tanahiro2010\SlimRouterDsl\Compiler\RouteInspector;
use Tanahiro2010\SlimRouterDsl\Compiler\RouteValidator;
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
     * @return CompiledRoute[]
     */
    public function compile(): array
    {
        return (new RouteFlattener())->flatten($this->routes);
    }

    public function findByName(string $name): ?CompiledRoute
    {
        return (new RouteInspector($this->compile()))->findByName($name);
    }

    /**
     * @return CompiledRoute[]
     */
    public function filterByMethod(string $method): array
    {
        return (new RouteInspector($this->compile()))->filterByMethod($method);
    }

    /**
     * @return CompiledRoute[]
     */
    public function findByPath(string $path): array
    {
        return (new RouteInspector($this->compile()))->findByPath($path);
    }

    /**
     * @return CompiledRoute[]
     */
    public function filterByMiddleware(string|object $middleware): array
    {
        return (new RouteInspector($this->compile()))->filterByMiddleware($middleware);
    }

    /**
     * @throws \Tanahiro2010\SlimRouterDsl\Exception\DuplicateRouteException
     * @throws \Tanahiro2010\SlimRouterDsl\Exception\DuplicateRouteNameException
     */
    public function validate(): void
    {
        (new RouteValidator())->validate($this->compile());
    }

    /**
     * @param callable(CompiledRoute): bool $predicate
     * @return CompiledRoute[]
     */
    public function filter(callable $predicate): array
    {
        return array_values(array_filter($this->compile(), $predicate));
    }

    /**
     * @return array<int, array{
     *     methods: string[],
     *     path: string,
     *     handler: mixed,
     *     middleware: array,
     *     name: string|null,
     *     metadata: array,
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
                'metadata' => $route->metadata,
            ],
            $this->compile(),
        );
    }

    public function dump(bool $showMiddleware = true, bool $showName = true, bool $showHandler = false): string
    {
        $routes = $this->compile();

        $headers = ['METHOD', 'PATH'];
        if ($showName) {
            $headers[] = 'NAME';
        }
        if ($showMiddleware) {
            $headers[] = 'MIDDLEWARE';
        }
        if ($showHandler) {
            $headers[] = 'HANDLER';
        }

        $rows = array_map(
            function (CompiledRoute $route) use ($showName, $showMiddleware, $showHandler): array {
                $row = [implode(',', $route->methods), $route->path];

                if ($showName) {
                    $row[] = $route->name ?? '-';
                }
                if ($showMiddleware) {
                    $row[] = $this->formatMiddlewareList($route->middleware);
                }
                if ($showHandler) {
                    $row[] = $this->formatHandler($route->handler);
                }

                return $row;
            },
            $routes,
        );

        return self::formatTable($headers, $rows);
    }

    /**
     * @param array $middleware
     */
    private function formatMiddlewareList(array $middleware): string
    {
        if ($middleware === []) {
            return '-';
        }

        return implode(', ', array_map(
            static fn (mixed $item): string => is_object($item) ? $item::class : (string) $item,
            $middleware,
        ));
    }

    private function formatHandler(mixed $handler): string
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $className = is_object($class) ? $class::class : (string) $class;

            return sprintf('%s::%s', $className, (string) $method);
        }

        if (is_string($handler)) {
            return $handler;
        }

        if ($handler instanceof Closure) {
            return 'Closure';
        }

        if (is_object($handler)) {
            return $handler::class;
        }

        return (string) $handler;
    }

    /**
     * @param string[] $headers
     * @param array<int, string[]> $rows
     */
    private static function formatTable(array $headers, array $rows): string
    {
        $widths = array_map('strlen', $headers);

        foreach ($rows as $row) {
            foreach ($row as $index => $cell) {
                $widths[$index] = max($widths[$index], strlen($cell));
            }
        }

        $formatRow = static function (array $cells) use ($widths): string {
            $padded = [];

            foreach ($cells as $index => $cell) {
                $padded[] = str_pad($cell, $widths[$index]);
            }

            return rtrim(implode('  ', $padded));
        };

        $lines = [$formatRow($headers)];

        foreach ($rows as $row) {
            $lines[] = $formatRow($row);
        }

        return implode("\n", $lines);
    }
}
