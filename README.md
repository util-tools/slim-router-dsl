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

## v1 Scope

v1では以下を提供します。

- `Routes` / `Routes::deploy()`
- `Route::get/post/put/patch/delete/options/map/any/group/middleware()`
- Nested Group / Nested Middleware / Multiple Middleware
- Slim 4 Compiler、Path正規化、Middleware順序保持
- 基本的なバリデーションと例外

Route Name、Route単位のFluent Middleware、`dump()`、`toArray()` などはv1のOptional Scopeとして今後の対応候補です。詳細は [slim-router-dsl-prd.md](slim-router-dsl-prd.md) を参照してください。

## 動作要件

- PHP >= 8.2
- Slim Framework 4.x

## テスト

```bash
composer install
composer test
```
