# Slim Router DSL

Slim Framework のルーティングを「登録処理」ではなく「宣言的なルートツリー」として定義し、`Routes::deploy()` によってSlimへ展開する軽量DSLライブラリです。

```php
$routes = new Routes([
    Route::group('/api', [
        Route::middleware(AuthMiddleware::class, [
            Route::get('/users', [UserController::class, 'index']),
            Route::get('/users/{id}', [UserController::class, 'show']),
            Route::post('/users', [UserController::class, 'create']),
        ]),
    ]),
]);

$routes->deploy($app);
```

このコードだけで、`/api` 配下・Auth Middleware適用・usersエンドポイント群・`deploy()` によるSlimへの登録、という構造が一目でわかります。

## インストール

```bash
composer require tanahiro2010/slim-router-dsl
```

## なぜ使うか

Slim標準のルーティングは以下のように命令的に登録します。

```php
$app->group('/api', function (RouteCollectorProxy $group) {
    $group->get('/users', [UserController::class, 'index']);
})->add(AuthMiddleware::class);
```

ルート数やネストが増えると、構造と登録処理が混在し、Middlewareの適用範囲も視覚的に把握しにくくなります。

Slim Router DSLでは、ルーティングをまず**データ構造として定義**し、`deploy()` によって初めてSlimへ登録します。DSL定義の時点ではSlimへの副作用は一切発生しません。

## 基本API

### HTTP Route

```php
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'create']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::patch('/users/{id}', [UserController::class, 'patch']);
Route::delete('/users/{id}', [UserController::class, 'delete']);
Route::options('/users', [UserController::class, 'options']);

// 任意のHTTP Methodを指定
Route::map(['GET', 'HEAD'], '/resource', ResourceController::class);

// 主要HTTP Methodをまとめて登録
Route::any('/health', HealthController::class);

// HEAD
Route::head('/users', [UserController::class, 'index']);
```

Handlerには Callable、`[Controller::class, 'method']`、Invokable Classのいずれも指定できます。Controllerの解決は行わずSlim / Containerに委ねます。

### Group

```php
Route::group('/api', [
    Route::get('/users', ...),
    Route::group('/v1', [
        Route::get('/posts', ...),
    ]),
]);
```

Groupはネスト可能で、パスは子ノードへ継承されます。`/api` + `/users`、`/api/` + `/users`、`/api` + `users` はいずれも `/api/users` に正規化されます。

### Middleware

```php
// 単一Middleware
Route::middleware(AuthMiddleware::class, [
    Route::get('/me', ...),
]);

// 複数Middleware（配列形式）
Route::middleware([AuthMiddleware::class, JsonMiddleware::class], [
    Route::get('/me', ...),
]);

// 複数Middleware（ネスト形式）
Route::middleware(AuthMiddleware::class, [
    Route::middleware(JsonMiddleware::class, [
        Route::get('/me', ...),
    ]),
]);
```

MiddlewareはGroupと自由にネストでき、Middleware情報は子ノードへ継承されます。DSL上で記述した順序（外側 → 内側）と実行順序が一致するように `deploy()` 時に登録順が調整されます。

### Route単位のFluent Middleware / Route Name

```php
Route::get('/me', [UserController::class, 'me'])
    ->middleware(AuthMiddleware::class)
    ->name('users.me');
```

`HttpRoute` はimmutableなので、`middleware()` / `name()` はどちらも新しいインスタンスを返します。元のインスタンスは変更されません。Route単位のmiddlewareは、GroupやMiddlewareから継承したmiddlewareより後（Handlerに最も近い位置）に実行されます。`name()` で指定した名前は `deploy()` 時にSlimの `setName()` に渡されます。

### Route Dump / toArray() / compile()

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

列は `dump(bool $showMiddleware = true, bool $showName = true, bool $showHandler = false)` で調整できます。

```php
$routes->toArray();
```

```php
[
    [
        'methods' => ['GET'],
        'path' => '/api/users',
        'handler' => [UserController::class, 'index'],
        'middleware' => [AuthMiddleware::class],
        'name' => null,
    ],
    // ...
];
```

`compile()` は同じ内容を `CompiledRoute[]`（`methods`/`path`/`handler`/`middleware`/`name` を持つreadonlyオブジェクト）として返す、より低レベルなAPIです。`toArray()`/`dump()`/後述のInspection APIはすべてこの `compile()` の結果を利用しています。

いずれもSlimへ`deploy()`する前に呼び出せる、Slimに依存しないルートツリーの解析APIです。

### Route Inspection

```php
$routes->findByName('users.show');      // ?CompiledRoute
$routes->filterByMethod('POST');        // CompiledRoute[]
$routes->findByPath('/api/users');      // CompiledRoute[]（同一pathに複数methodがあり得るため配列）
$routes->filterByMiddleware(AuthMiddleware::class); // CompiledRoute[]
```

### Validation

```php
$routes->validate();
```

`deploy()` 前に呼び出すことで、以下を検出できます（**`deploy()` は自動でvalidateしません**、明示的に呼び出してください）。

- 同一Method + Pathの重複定義 → `DuplicateRouteException`
- 同一Route Nameの重複定義 → `DuplicateRouteNameException`

いずれも最初に見つかった時点でthrowされます（method+pathの重複チェックが先、name重複チェックが後）。

### deploy()

```php
$routes = new Routes([...]);

$routes->deploy($app);
```

`Routes` の構築時点ではルートツリーをメモリ上に構築するだけで、`deploy(App $app)` を呼んだ時点で初めてSlimへ登録されます。

## 例外

すべてのライブラリ独自例外は `RouterDslException`（`RuntimeException` を継承）を共通の基底とし、一括catchできます。

- `InvalidRouteNodeException`: `Routes` / Group / Middleware の children に `RouteNode` 以外が渡された場合
- `InvalidRouteException`: `Route::map()` に空のmethod配列が渡された場合
- `InvalidMiddlewareException`: `Route::middleware()` に空のmiddleware配列が渡された場合
- `DuplicateRouteException`: `Routes::validate()` が同一Method + Pathの重複を検出した場合
- `DuplicateRouteNameException`: `Routes::validate()` が同一Route Nameの重複を検出した場合

## v1 Scope

v1.0では以下を提供しました（MVP Scope + Optional Scope）。

- `Routes` / `Routes::deploy()` / `Routes::toArray()` / `Routes::dump()`
- `Route::get/post/put/patch/delete/options/head/map/any/group/middleware()`
- `HttpRoute::middleware()` / `HttpRoute::name()`（Fluent API、共にimmutable）
- Nested Group / Nested Middleware / Multiple Middleware / Route-level Middleware
- Slim 4 Compiler、Path正規化、Middleware順序保持、Route Name
- 基本的なバリデーションと例外

v1.1では以下を追加しました。

- `Routes::compile()` — `CompiledRoute[]` のPublic API化
- `Routes::findByName()` / `filterByMethod()` / `findByPath()` / `filterByMiddleware()`
- `Routes::validate()`（重複Route / 重複Route Nameの検出）
- `dump()` のテーブル形式化(NAME/MIDDLEWARE列、オプションでHANDLER列)

詳細は [CHANGELOG.md](CHANGELOG.md) と [slim-router-dsl-prd.md](slim-router-dsl-prd.md) を参照してください。

## 動作要件

- PHP >= 8.2
- Slim Framework 4.x

## テスト

```bash
composer install
composer test
```
