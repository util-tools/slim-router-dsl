# ガイド

このガイドでは、Slim Router DSLの各機能をユースケースごとに説明します。クラス・メソッドの詳細な仕様は [APIリファレンス](api-reference.md) を参照してください。

## 目次

- [HTTP Route](#http-route)
- [Handler仕様](#handler仕様)
- [Group](#group)
- [Middleware](#middleware)
- [Route単位のFluent Middleware / Route Name](#route単位のfluent-middleware--route-name)
- [Metadata](#metadata)
- [Inspection & Validation](#inspection--validation)
- [Route::resource()](#routeresource)
- [Route::controller()](#routecontroller)
- [deploy()](#deploy)

## HTTP Route

`Route` は各種HTTP Methodに対応するファクトリメソッドを提供します。

```php
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'create']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::patch('/users/{id}', [UserController::class, 'patch']);
Route::delete('/users/{id}', [UserController::class, 'delete']);
Route::options('/users', [UserController::class, 'options']);
Route::head('/users', [UserController::class, 'index']);
```

任意のHTTP Methodを組み合わせたい場合は `Route::map()` を使います。

```php
Route::map(['GET', 'HEAD'], '/resource', ResourceController::class);
```

空のmethod配列は `InvalidRouteException` になります。

```php
Route::map([], '/resource', ResourceController::class); // throws InvalidRouteException
```

主要なHTTP Method(`GET`/`POST`/`PUT`/`PATCH`/`DELETE`/`OPTIONS`)をまとめて登録したい場合は `Route::any()` を使います(`HEAD` は含まれません)。

```php
Route::any('/health', HealthController::class);
```

## Handler仕様

Handlerには以下のいずれかを指定できます。DSL自身はHandlerを解決・実行せず、解決はすべてSlim / Containerに委ねます。

```php
// Callable
Route::get('/', function ($request, $response) {
    return $response;
});

// Controller + Method
Route::get('/users', [UserController::class, 'index']);

// Invokable Class
Route::get('/health', HealthController::class);
```

`Route::controller()` を使うと、Controller Method名を文字列だけで指定できます([後述](#routecontroller))。

## Group

`Route::group()` はパスPrefixを複数の子ノードへ適用します。

```php
Route::group('/api', [
    Route::get('/users', ...),
    Route::get('/posts', ...),
]);
```

展開結果:

```text
GET /api/users
GET /api/posts
```

### Nested Group

Groupはネスト可能です。

```php
Route::group('/api', [
    Route::group('/v1', [
        Route::get('/users', ...),
    ]),
]);
```

展開結果: `GET /api/v1/users`

### Path結合の正規化

GroupとRouteのパス結合時、`/` の重複は自動的に正規化されます。以下はすべて `/api/users` に解決されます。

```text
/api  + /users
/api/ + /users
/api  + users
```

## Middleware

`Route::middleware()` は複数の子Routeへ指定したMiddlewareを適用します。

```php
Route::middleware(AuthMiddleware::class, [
    Route::get('/me', ...),
    Route::get('/settings', ...),
]);
```

### 複数Middleware

配列で複数のMiddlewareをまとめて指定できます(推奨)。

```php
Route::middleware([AuthMiddleware::class, JsonMiddleware::class], [
    Route::get('/me', ...),
]);
```

ネストして表現することも可能です(挙動は配列指定と同じです)。

```php
Route::middleware(AuthMiddleware::class, [
    Route::middleware(JsonMiddleware::class, [
        Route::get('/me', ...),
    ]),
]);
```

空配列を渡すと `InvalidMiddlewareException` になります。

### Middleware + Group

MiddlewareはGroupと自由にネストできます。

```php
Route::group('/api', [
    Route::middleware(AuthMiddleware::class, [
        Route::get('/me', ...),

        Route::group('/posts', [
            Route::get('/', ...),
            Route::post('/', ...),
        ]),
    ]),
]);
```

### Middleware適用順序

DSL上で記述した順序(外側 → 内側)が、そのままリクエスト処理時の実行順序になります。

```php
Route::middleware(A::class, [
    Route::middleware(B::class, [
        Route::get('/', ...),
    ]),
]);
// 実行順序: A → B → Route Handler
```

Slimの `Route::add()` はLIFO(後から追加したものが先に実行される)であるため、Compiler(`SlimRouteCompiler`)が登録順を自動的に調整しています。利用者側はこの内部実装を意識する必要はありません。

## Route単位のFluent Middleware / Route Name

`HttpRoute` はimmutableなFluent APIとして `middleware()` / `name()` を提供します。いずれも新しいインスタンスを返すため、元のインスタンスは変更されません。

```php
Route::get('/me', [UserController::class, 'me'])
    ->middleware(AuthMiddleware::class)
    ->name('users.me');
```

- `middleware()`: GroupやMiddlewareから継承したMiddlewareの**後**(Handlerに最も近い位置)に適用されます。複数回呼び出すと追記されます。
- `name()`: `deploy()` 時にSlimの `Route::setName()` に渡されます。Named Routeとして `$app->getRouteCollector()->getRouteParser()->urlFor('users.me')` のように利用できます。

## Metadata

`HttpRoute::meta()` を使うと、Route単位で任意のメタデータを付与できます。認可情報・ドキュメント生成・タグ付けなど、DSL自体が意味を解釈しない自由な用途に使えます。

```php
Route::get('/users/{id}', [UserController::class, 'show'])
    ->name('users.show')
    ->meta([
        'summary' => 'ユーザー取得',
        'auth' => true,
        'permission' => 'users.read',
    ]);
```

`meta()` を複数回呼び出すと、後から指定したキーが上書きされる形でマージされます。

```php
$route = Route::get('/users', 'Index')
    ->meta(['auth' => true, 'summary' => 'List users'])
    ->meta(['auth' => false]); // auth は false で上書き
```

**注意**: Metadataは **Route単位のみ** で、Group/Middlewareのようにネストした子へ継承されることはありません(v1.4時点)。

付与したメタデータは `compile()`/`toArray()` の結果、および `Routes::filter()` から参照できます。

```php
// authが必要なRouteだけ抽出
$authRequiredRoutes = $routes->filter(
    fn (CompiledRoute $route) => $route->metadata['auth'] ?? false
);
```

## Inspection & Validation

### compile()

`Routes::compile()` はRoute Treeを解決した `CompiledRoute[]` を返す、最も低レベルなAPIです。`toArray()`/`dump()`/以下のInspection APIはすべてこの結果を利用しています。

```php
foreach ($routes->compile() as $route) {
    echo $route->path, "\n";
}
```

`CompiledRoute` は `methods`/`path`/`handler`/`middleware`/`name`/`metadata` を持つreadonlyオブジェクトです。詳細は [APIリファレンス](api-reference.md#compiledroute) を参照してください。

### 検索・絞り込み

```php
$routes->findByName('users.show');                  // ?CompiledRoute
$routes->filterByMethod('POST');                     // CompiledRoute[]
$routes->findByPath('/api/users');                   // CompiledRoute[](同一pathに複数methodがあり得るため配列)
$routes->filterByMiddleware(AuthMiddleware::class);  // CompiledRoute[]
$routes->filter(fn ($route) => /* 任意の述語 */);     // CompiledRoute[]
```

`filterByMiddleware()` は文字列(クラス名)・オブジェクトインスタンスのどちらでも検索できます。オブジェクトを渡した場合は同一インスタンスかどうか(`===`)、クラス名の文字列を渡した場合はそのクラスのインスタンスが含まれるかどうかで判定します。

### validate()

`Routes::validate()` は `deploy()` 前に呼び出すことで、以下の不整合を検出できます。

- 同一Method + Pathの重複定義 → `DuplicateRouteException`
- 同一Route Nameの重複定義 → `DuplicateRouteNameException`

```php
try {
    $routes->validate();
} catch (DuplicateRouteException|DuplicateRouteNameException $e) {
    // ルート定義の不整合をここで検出
}

$routes->deploy($app);
```

いずれも最初に見つかった時点でthrowされます(method+pathの重複チェックが先、name重複チェックが後)。**`deploy()` は自動でvalidateしません**。既存のルート定義への影響を避けるため、検証したい場合は明示的に呼び出す運用にしてください。

### dump() / toArray()

```php
echo $routes->dump();
```

```text
METHOD  PATH                  NAME           MIDDLEWARE
GET     /                     -              -
GET     /api/users            users.index    Auth
GET     /api/users/{id}       users.show     Auth
POST    /api/users            users.create   Auth, Json
```

列の表示は引数で制御できます。

```php
$routes->dump(showMiddleware: true, showName: true, showHandler: false);
```

`toArray()` は同じ内容を配列として返します(各要素に `methods`/`path`/`handler`/`middleware`/`name`/`metadata` キーを持つ)。

## Route::resource()

REST APIで頻出するCRUD Routeを一括生成します。

```php
Route::resource('/users', UserController::class);
```

展開結果(Handlerはそれぞれ `[UserController::class, '<action>']`):

| Method | Path | Controller Method(action key) |
|---|---|---|
| GET | `/users` | `index` |
| GET | `/users/{id}` | `show` |
| POST | `/users` | `create` |
| PUT | `/users/{id}` | `update` |
| PATCH | `/users/{id}` | `patch` |
| DELETE | `/users/{id}` | `delete` |

`only`/`except` で生成対象を絞り込めます。同時指定は `InvalidRouteException` になります。

```php
Route::resource('/users', UserController::class, only: ['index', 'show']);
Route::resource('/users', UserController::class, except: ['delete']);
```

`Route::resource()` の戻り値は通常の `RouteGroup` なので、他のGroup/Middlewareと自由に組み合わせられます。

```php
Route::group('/api', [
    Route::middleware(AuthMiddleware::class, [
        Route::resource('/users', UserController::class),
    ]),
]);
```

## Route::controller()

同一Controllerを使うRoute群で、Controller class名の重複記述を減らせます。

```php
Route::controller(UserController::class, [
    Route::get('/', 'index'),
    Route::get('/{id}', 'show'),
    Route::post('/', 'create'),
]);
```

`Route::controller()` の配下では、Handlerに **文字列** を渡すと `[UserController::class, '<文字列>']` として解決されます。Closureや `[Class, method]` 配列など文字列以外のHandlerはこの変換の影響を受けません。

```php
Route::controller(UserController::class, [
    Route::get('/', 'index'),                       // -> [UserController::class, 'index']
    Route::get('/ping', fn ($req, $res) => $res),    // -> Closureのまま(変換されない)
    Route::get('/other', [OtherController::class, 'method']), // -> 変換されない
]);
```

**`Route::controller()` の外側で文字列Handlerを使う場合**(Slimのコンテナ名解決など)は、この変換は発生せず従来通りの挙動になります。

```php
Route::get('/health', 'HealthCheckContainerEntry'); // 文字列のまま(Slim/Containerに解決を委ねる)
```

`Route::group()`/`Route::middleware()` と自由にネストできます。

```php
Route::group('/api', [
    Route::middleware(AuthMiddleware::class, [
        Route::controller(UserController::class, [
            Route::get('/users', 'index'),
        ]),
    ]),
]);
```

## deploy()

```php
$routes = new Routes([...]);

$routes->deploy($app);
```

`Routes` の構築時点ではルートツリーをメモリ上に構築するだけで、`deploy(App $app)` を呼んだ時点で初めてSlimへ登録されます。`deploy()` 完了後は通常のSlim Routeとして動作し、DSL独自のRuntime overheadは発生しません。
