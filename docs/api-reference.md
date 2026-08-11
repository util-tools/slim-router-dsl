# APIリファレンス

名前空間ルート: `Tanahiro2010\SlimRouterDsl`

- 実際の使い方は [ガイド](guide.md) を参照してください。
- 例外については [例外](exceptions.md) を参照してください。

## 目次

- [`Routes`](#routes)
- [`Route`](#route)
- [`Contracts\RouteNode`](#contractsroutenode)
- [`Nodes\HttpRoute`](#nodeshttproute)
- [`Nodes\RouteGroup`](#nodesroutegroup)
- [`Nodes\MiddlewareGroup`](#nodesmiddlewaregroup)
- [`Nodes\ControllerGroup`](#nodescontrollergroup)
- [`Compiler\CompiledRoute`](#compilercompiledroute)
- [`Compiler\RouteContext`](#compilerroutecontext)
- [`Compiler\RouteFlattener`](#compilerrouteflattener)
- [`Compiler\RouteInspector`](#compilerrouteinspector)
- [`Compiler\RouteValidator`](#compilerroutevalidator)
- [`Compiler\RouteDumper`](#compilerroutedumper)
- [`Compiler\SlimRouteCompiler`](#compilerslimroutecompiler)
- [`Support\MiddlewareList`](#supportmiddlewarelist)
- [`Cli\Application`](#cliapplication)
- [`Cli\Commands\RoutesCommand`](#clicommandsroutescommand)
- [`Cli\Commands\ValidateCommand`](#clicommandsvalidatecommand)

---

## `Routes`

DSL全体のルートコンテナ。`RouteNode[]` を受け取り、`deploy()` でSlimへ展開する。

```php
final class Routes
{
    /**
     * @param RouteNode[] $routes
     * @throws InvalidRouteNodeException 要素にRouteNode以外が含まれる場合
     */
    public function __construct(array $routes);

    /** Slim Applicationへルートを展開する。 */
    public function deploy(App $app): void;

    /**
     * ルートツリーを解決した CompiledRoute[] を返す。他の全APIの基盤。
     * @return CompiledRoute[]
     */
    public function compile(): array;

    /** 指定したnameに一致するルートを1件返す。見つからなければnull。 */
    public function findByName(string $name): ?CompiledRoute;

    /**
     * 指定したHTTP Methodを含むルートを絞り込む。
     * @return CompiledRoute[]
     */
    public function filterByMethod(string $method): array;

    /**
     * 指定したpathに完全一致するルートを絞り込む(複数methodがあり得るため配列)。
     * @return CompiledRoute[]
     */
    public function findByPath(string $path): array;

    /**
     * 指定したMiddleware(クラス名文字列 or インスタンス)を含むルートを絞り込む。
     * @return CompiledRoute[]
     */
    public function filterByMiddleware(string|object $middleware): array;

    /**
     * ルート定義の重複を検証する。deploy()からは自動実行されない。
     * @throws DuplicateRouteException 同一Method+Pathの重複
     * @throws DuplicateRouteNameException 同一Route Nameの重複
     */
    public function validate(): void;

    /**
     * 任意の述語でルートを絞り込む。
     * @param callable(CompiledRoute): bool $predicate
     * @return CompiledRoute[]
     */
    public function filter(callable $predicate): array;

    /**
     * compile()の結果を配列表現にして返す。
     * @return array<int, array{
     *     methods: string[], path: string, handler: mixed,
     *     middleware: array, name: string|null, metadata: array,
     * }>
     */
    public function toArray(): array;

    /** compile()の結果を整形済みテーブル文字列として返す。 */
    public function dump(bool $showMiddleware = true, bool $showName = true, bool $showHandler = false): string;
}
```

| メソッド | 導入バージョン |
|---|---|
| `__construct`, `deploy`, `toArray`, `dump()`(引数なし版) | v1.0 |
| `compile`, `findByName`, `filterByMethod`, `findByPath`, `filterByMiddleware`, `validate`, `dump()` の引数追加 | v1.1 |
| `filter`, `toArray()` の `metadata` キー | v1.2 |

---

## `Route`

ルートツリーを構築するためのDSL Factory。すべてのメソッドはstatic。

```php
final class Route
{
    // HTTP Method DSL
    public static function get(string $path, mixed $handler): HttpRoute;
    public static function post(string $path, mixed $handler): HttpRoute;
    public static function put(string $path, mixed $handler): HttpRoute;
    public static function patch(string $path, mixed $handler): HttpRoute;
    public static function delete(string $path, mixed $handler): HttpRoute;
    public static function options(string $path, mixed $handler): HttpRoute;
    public static function head(string $path, mixed $handler): HttpRoute;

    /** GET/POST/PUT/PATCH/DELETE/OPTIONSをまとめて登録(HEADは含まない)。 */
    public static function any(string $path, mixed $handler): HttpRoute;

    /**
     * 任意のHTTP Methodを指定する。
     * @param string[] $methods
     * @throws InvalidRouteException $methodsが空の場合
     */
    public static function map(array $methods, string $path, mixed $handler): HttpRoute;

    /**
     * パスPrefixを子ノードへ適用する。
     * @param RouteNode[] $children
     */
    public static function group(string $prefix, array $children): RouteGroup;

    /**
     * Middlewareを子ノードへ適用する。単一middleware or 配列のどちらも受け付ける。
     * @param string|object|array $middleware
     * @param RouteNode[] $children
     * @throws InvalidMiddlewareException 空配列が渡された場合
     */
    public static function middleware(string|object|array $middleware, array $children): MiddlewareGroup;

    /**
     * Controllerを子ノードへ適用する(v1.3〜)。配下のHttpRouteで文字列Handlerを
     * 渡すと [$controller, $handler] として解決される。
     * @param RouteNode[] $children
     */
    public static function controller(mixed $controller, array $children): ControllerGroup;

    /**
     * 標準的なCRUD Route(index/show/create/update/patch/delete)を一括生成する(v1.3〜)。
     * @param string[]|null $only 生成対象のaction keyを限定する
     * @param string[]|null $except 除外するaction key(onlyと同時指定不可)
     * @throws InvalidRouteException $onlyと$exceptを同時指定した場合
     */
    public static function resource(
        string $prefix,
        mixed $controller,
        ?array $only = null,
        ?array $except = null,
    ): RouteGroup;
}
```

`resource()` のaction key ↔ HTTP Method ↔ path対応表:

| action key | HTTP Method | Path |
|---|---|---|
| `index` | GET | `/{prefix}` |
| `show` | GET | `/{prefix}/{id}` |
| `create` | POST | `/{prefix}` |
| `update` | PUT | `/{prefix}/{id}` |
| `patch` | PATCH | `/{prefix}/{id}` |
| `delete` | DELETE | `/{prefix}/{id}` |

| メソッド | 導入バージョン |
|---|---|
| `get`/`post`/`put`/`patch`/`delete`/`options`/`any`/`map`/`group`/`middleware` | v1.0 |
| `head` | v1.0(Optional Scope) |
| `controller`, `resource` | v1.3 |

---

## `Contracts\RouteNode`

```php
interface RouteNode
{
}
```

すべてのルートツリーのノード(`HttpRoute`/`RouteGroup`/`MiddlewareGroup`/`ControllerGroup`)が実装するマーカーインターフェース。メンバーは持たない。

---

## `Nodes\HttpRoute`

単一HTTP Routeを表すimmutableな値オブジェクト。

```php
final readonly class HttpRoute implements RouteNode
{
    /**
     * @param string[] $methods
     * @param array $middleware Route単位のmiddleware(Handlerに最も近い位置で適用される)
     * @param array $metadata 任意のメタデータ(v1.2〜)
     * @throws InvalidRouteException $methodsが空の場合
     */
    public function __construct(
        public array $methods,
        public string $path,
        public mixed $handler,
        public array $middleware = [],
        public ?string $name = null,
        public array $metadata = [],
    );

    /** 新しいインスタンスを返す(元のインスタンスは変更されない)。middlewareは追記される。 */
    public function middleware(string|object|array $middleware): self;

    /** 新しいインスタンスを返す。deploy()時にSlimのsetName()へ渡される。 */
    public function name(string $name): self;

    /** 新しいインスタンスを返す。既存metadataへ後勝ちでマージされる(v1.2〜)。 */
    public function meta(array $metadata): self;
}
```

通常は `Route::get()` 等のファクトリメソッド経由で生成し、直接 `new HttpRoute(...)` することは想定していません。

---

## `Nodes\RouteGroup`

```php
final readonly class RouteGroup implements RouteNode
{
    /**
     * @param RouteNode[] $children
     * @throws InvalidRouteNodeException childrenにRouteNode以外が含まれる場合
     */
    public function __construct(
        public string $prefix,
        public array $children,
    );
}
```

`Route::group()` / `Route::resource()` から生成されます。

---

## `Nodes\MiddlewareGroup`

```php
final readonly class MiddlewareGroup implements RouteNode
{
    /**
     * @param array $middleware
     * @param RouteNode[] $children
     * @throws InvalidRouteNodeException childrenにRouteNode以外が含まれる場合
     */
    public function __construct(
        public array $middleware,
        public array $children,
    );
}
```

`Route::middleware()` から生成されます。

---

## `Nodes\ControllerGroup`

*(v1.3〜)*

```php
final readonly class ControllerGroup implements RouteNode
{
    /**
     * @param RouteNode[] $children
     * @throws InvalidRouteNodeException childrenにRouteNode以外が含まれる場合
     */
    public function __construct(
        public mixed $controller,
        public array $children,
    );
}
```

`Route::controller()` から生成されます。

---

## `Compiler\CompiledRoute`

Route Treeを解決した、単一の完全なルート情報を表すimmutableな値オブジェクト。Slimに依存しない。

```php
final readonly class CompiledRoute
{
    /**
     * @param string[] $methods
     * @param array $middleware 宣言順(外側→内側)
     * @param array $metadata HttpRoute::meta()で付与されたメタデータ(v1.2〜)
     */
    public function __construct(
        public array $methods,
        public string $path,
        public mixed $handler,
        public array $middleware,
        public ?string $name,
        public array $metadata = [],
    );
}
```

`Routes::compile()` の戻り値の要素型です。

---

## `Compiler\RouteContext`

再帰的なフラット化処理で現在の状態(prefix/middleware/controller)を保持するimmutableなコンテキスト。通常、利用者が直接使うことはありません。

```php
final readonly class RouteContext
{
    public function __construct(
        public string $prefix = '',
        public array $middleware = [],
        public mixed $controller = null,
    );

    /** prefixを結合した新しいコンテキストを返す(`/`の重複を正規化)。 */
    public function withPrefix(string $prefix): self;

    /** middlewareを末尾に追記した新しいコンテキストを返す。 */
    public function withMiddleware(array $middleware): self;

    /** controllerを置き換えた新しいコンテキストを返す(マージではなく置換)。 */
    public function withController(mixed $controller): self;

    /** 2つのpathセグメントを結合し、`/`の重複を正規化する。 */
    public static function joinPaths(string $left, string $right): string;
}
```

---

## `Compiler\RouteFlattener`

Route Treeを再帰的に走査し、`CompiledRoute[]` へ変換する。Slimに一切依存しない。

```php
final class RouteFlattener
{
    /**
     * @param RouteNode[] $routes
     * @return CompiledRoute[]
     * @throws InvalidRouteNodeException 未知のRouteNode実装が含まれる場合
     * @throws RouteTreeTooDeepException ネストが最大深度(既定256)を超える場合(v1.4.1〜)
     */
    public function flatten(array $routes): array;
}
```

`Routes::compile()`、`SlimRouteCompiler::compile()` の両方から利用されます。

---

## `Compiler\RouteInspector`

*(v1.1〜)* 既にフラット化済みの `CompiledRoute[]` に対する検索・絞り込みロジック。`Routes` の各Inspectionメソッドの内部実装。

```php
final class RouteInspector
{
    /** @param CompiledRoute[] $routes */
    public function __construct(array $routes);

    public function findByName(string $name): ?CompiledRoute;

    /** @return CompiledRoute[] */
    public function filterByMethod(string $method): array;

    /** @return CompiledRoute[] */
    public function findByPath(string $path): array;

    /** @return CompiledRoute[] */
    public function filterByMiddleware(string|object $middleware): array;
}
```

---

## `Compiler\RouteValidator`

*(v1.1〜)* `CompiledRoute[]` に対する重複検出ロジック。`Routes::validate()` の内部実装。

```php
final class RouteValidator
{
    /**
     * @param CompiledRoute[] $routes
     * @throws DuplicateRouteException
     * @throws DuplicateRouteNameException
     */
    public function validate(array $routes): void;
}
```

---

## `Compiler\RouteDumper`

*(v1.4〜、元は`Routes`内に直接実装されていたものをv1.4で抽出)* `CompiledRoute[]` を配列表現・テーブル文字列へ整形する。`Routes::toArray()`/`dump()` およびCLIの `routes` コマンドの内部実装。

```php
final class RouteDumper
{
    /**
     * @param CompiledRoute[] $routes
     * @return array<int, array{...}> Routes::toArray()と同じ形状
     */
    public function toArray(array $routes): array;

    /** @param CompiledRoute[] $routes */
    public function dump(
        array $routes,
        bool $showMiddleware = true,
        bool $showName = true,
        bool $showHandler = false,
    ): string;
}
```

---

## `Compiler\SlimRouteCompiler`

`CompiledRoute[]` をSlimの `App` へ実際に登録する。

```php
final class SlimRouteCompiler
{
    public function __construct(private readonly App $app);

    /** @param RouteNode[] $routes */
    public function compile(array $routes): void;
}
```

内部でMiddlewareを宣言順(外側→内側)がリクエスト時に外側から実行されるよう、逆順で `Route::add()` します(SlimのMiddlewareスタックはLIFOのため)。`name` が設定されていれば `Route::setName()` を呼びます。

---

## `Support\MiddlewareList`

```php
final class MiddlewareList
{
    /**
     * @throws InvalidMiddlewareException 正規化後のリストが空の場合
     */
    public static function normalize(string|object|array $middleware): array;
}
```

`Route::middleware()` と `HttpRoute::middleware()` が共有する、middleware引数(単一値 or 配列)の正規化ロジック。

---

## `Cli\Application`

*(v1.4〜)* `bin/router-dsl` のエントリポイント。詳細は [CLI](cli.md) を参照。

```php
final class Application
{
    /**
     * @param string[] $argv
     * @return int exit code(0=成功, 1=コマンドエラー, 2=Bootstrapエラー)
     */
    public function run(array $argv): int;
}
```

---

## `Cli\Commands\RoutesCommand`

*(v1.4〜)*

```php
final class RoutesCommand
{
    /**
     * @param array{json?: bool, method?: string, name?: string} $options
     */
    public function execute(Routes $routes, array $options): string;
}
```

---

## `Cli\Commands\ValidateCommand`

*(v1.4〜)*

```php
final class ValidateCommand
{
    /** @return int 0=成功, 1=検証エラー */
    public function execute(Routes $routes): int;
}
```
