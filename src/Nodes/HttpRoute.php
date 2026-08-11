<?php

declare(strict_types=1);

namespace Tanahiro2010\SlimRouterDsl\Nodes;

use Tanahiro2010\SlimRouterDsl\Contracts\RouteNode;
use Tanahiro2010\SlimRouterDsl\Exception\InvalidRouteException;

final readonly class HttpRoute implements RouteNode
{
    /**
     * @param string[] $methods
     */
    public function __construct(
        public array $methods,
        public string $path,
        public mixed $handler,
    ) {
        if ($this->methods === []) {
            throw new InvalidRouteException('HttpRoute requires at least one HTTP method.');
        }
    }
}
