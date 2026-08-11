<?php

declare(strict_types=1);

namespace Tanahiro2010\SlimRouterDsl\Compiler;

final readonly class RouteContext
{
    /**
     * @param array $middleware
     */
    public function __construct(
        public string $prefix = '',
        public array $middleware = [],
    ) {
    }

    public function withPrefix(string $prefix): self
    {
        return new self(
            self::joinPaths($this->prefix, $prefix),
            $this->middleware,
        );
    }

    /**
     * @param array $middleware
     */
    public function withMiddleware(array $middleware): self
    {
        return new self(
            $this->prefix,
            [...$this->middleware, ...$middleware],
        );
    }

    public static function joinPaths(string $left, string $right): string
    {
        $normalized = rtrim($left, '/') . '/' . ltrim($right, '/');
        $normalized = (string) preg_replace('#/+#', '/', $normalized);

        if ($normalized === '') {
            return '';
        }

        if ($normalized !== '/' && str_ends_with($normalized, '/')) {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized === '' ? '/' : $normalized;
    }
}
