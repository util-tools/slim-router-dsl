# コアコンセプト

## Declarative First

Slim標準のルーティングAPIは「登録処理」として命令的に記述します。

```php
$app->group('/api', function (RouteCollectorProxy $group) {
    $group->get('/users', [UserController::class, 'index']);
})->add(AuthMiddleware::class);
```

Slim Router DSLでは、ルーティングをまず「ルート構造の定義」として記述し、登録処理とは分離します。

```php
$routes = new Routes([
    Route::group('/api', [
        Route::get('/users', [UserController::class, 'index']),
    ]),
]);

$routes->deploy($app);
```

## Define First, Deploy Later

`Route::*()` を呼び出した時点ではSlimへの登録は一切行われません。すべてのDSLメソッドはメモリ上に **immutableなデータ構造(Route Tree)** を構築するだけです。

`$routes->deploy($app)` を実行した時点で初めて、Route TreeがSlimのルートとして登録されます。この分離により、以下が可能になります。

- ルート定義をSlimへの副作用なしに構築・検査できる(`compile()`/`toArray()`/`dump()`/`validate()`)
- テストやCLIツールからSlimの `App` を起動せずにルート情報を扱える
- ルート定義を再利用・合成できる

## Route Tree

すべてのルート定義は木構造として表現されます。

```text
Routes
└── RouteGroup    /api
    ├── HttpRoute      GET /users
    └── MiddlewareGroup  Auth
        └── RouteGroup    /posts
            ├── HttpRoute  GET /
            └── HttpRoute  GET /{id}
```

- `RouteGroup` はパスPrefixを子ノードへ継承します。
- `MiddlewareGroup` はMiddlewareを子ノードへ継承します。
- `ControllerGroup`(v1.3〜)はController情報を子ノードへ継承します。

木のノードはすべて [`RouteNode`](api-reference.md#routenode) インターフェースを実装します。

## Immutability

ルートツリーを構成するノード(`HttpRoute`/`RouteGroup`/`MiddlewareGroup`/`ControllerGroup`)はすべて `readonly class` です。`HttpRoute` のFluentメソッド(`middleware()`/`name()`/`meta()`)は元のインスタンスを変更せず、常に新しいインスタンスを返します。

```php
$base = Route::get('/users/{id}', [UserController::class, 'show']);
$withAuth = $base->middleware(AuthMiddleware::class);

// $base は変更されない
$base->middleware === [];         // true
$withAuth->middleware === [AuthMiddleware::class]; // true
```

この設計により、Route定義がdeploy前に意図せず変更されることを防ぎ、ルートツリーの解析・デバッグが容易になります。

## Compile: Route Tree → CompiledRoute[]

Route Treeを直接扱うのではなく、`RouteFlattener` がGroup/Middleware/Controllerの継承をすべて解決した上で、フラットな `CompiledRoute[]` を生成します。

```text
Route DSL → RouteNode Tree → RouteFlattener → CompiledRoute[]
                                                  ├── SlimRouteCompiler (deploy)
                                                  ├── RouteInspector (find/filter)
                                                  ├── RouteValidator (validate)
                                                  ├── RouteDumper (toArray/dump)
                                                  └── CLI
```

`Routes::compile()` を呼ぶとこの `CompiledRoute[]` を直接取得できます。`toArray()`/`dump()`/`findByName()`などのAPIはすべて内部でこの `compile()` の結果を利用しています。詳細は [ガイド: Inspection & Validation](guide.md#inspection--validation) を参照してください。

## Slimを隠しすぎない

DSLはSlimの代替フレームワークではありません。Dependency Injection、Controllerの生成、PSR-7 Request/Response、PSR-15 Middleware、Error Handling、Application lifecycleといった責務はすべてSlimまたは利用者側に委ねます。DSLが担当するのは、あくまで **ルート定義とSlimへの展開のみ** です。
