# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-11

Initial release. Scope matches [slim-router-dsl-roadmap.md](slim-router-dsl-roadmap.md) section 14
("v1.0.0 — Initial Release").

### Added

- `Routes` / `Routes::deploy()` / `Routes::toArray()` / `Routes::dump()`
- HTTP Method DSL: `Route::get/post/put/patch/delete/options/head/map/any()`
- `Route::group()` with nested-group path normalization
- `Route::middleware()` supporting both array form (`[A::class, B::class]`) and nested form
- Route-level fluent middleware and name: `HttpRoute::middleware()` / `HttpRoute::name()`
- Immutable `RouteNode` tree (`HttpRoute`, `RouteGroup`, `MiddlewareGroup`)
- `RouteFlattener` / `CompiledRoute` — Slim-independent resolution of the route tree
- `SlimRouteCompiler` — deploys a flattened route tree to Slim 4, preserving declared
  middleware order despite Slim's LIFO `add()`
- Exception hierarchy: `RouterDslException`, `InvalidRouteNodeException`,
  `InvalidRouteException`, `InvalidMiddlewareException`
- PHPUnit test suite (Unit + Deploy)
- README
