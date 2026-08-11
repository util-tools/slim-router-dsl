# 例外

すべてのライブラリ独自例外は `Tanahiro2010\SlimRouterDsl\Exception\RouterDslException`(`RuntimeException` を継承)を共通の基底とします。一括catchしたい場合はこの基底クラスを利用してください。

```php
use Tanahiro2010\SlimRouterDsl\Exception\RouterDslException;

try {
    $routes = new Routes([...]);
    $routes->validate();
    $routes->deploy($app);
} catch (RouterDslException $e) {
    // ライブラリ独自の例外をまとめてcatch
}
```

## 例外一覧

| クラス | 発生条件 | 導入バージョン |
|---|---|---|
| `RouterDslException` | (共通基底、直接throwはされない) | v1.0 |
| `InvalidRouteNodeException` | `Routes` / `RouteGroup` / `MiddlewareGroup` / `ControllerGroup` の children に `RouteNode` 以外が渡された場合 | v1.0 |
| `InvalidRouteException` | `Route::map()` に空のmethod配列が渡された場合、または `Route::resource()` に `only`/`except` を同時指定した場合 | v1.0 |
| `InvalidMiddlewareException` | `Route::middleware()` / `HttpRoute::middleware()` に空のmiddleware配列が渡された場合 | v1.0 |
| `DuplicateRouteException` | `Routes::validate()` が同一Method + Pathの重複定義を検出した場合 | v1.1 |
| `DuplicateRouteNameException` | `Routes::validate()` が同一Route Nameの重複定義を検出した場合 | v1.1 |
| `RouteTreeTooDeepException` | `Route`のネスト(Group/Middleware/Controller)が最大深度(既定256)を超えた場合。深いネストによるメモリ枯渇を防ぐためのガード | v1.4.1 |

## 各例外の詳細

### `InvalidRouteNodeException`

```php
new Routes(['hello']); // throws InvalidRouteNodeException
Route::group('/api', ['hello']); // throws InvalidRouteNodeException
```

### `InvalidRouteException`

```php
Route::map([], '/', HomeController::class); // throws InvalidRouteException

Route::resource('/users', UserController::class, only: ['index'], except: ['show']);
// throws InvalidRouteException(only/exceptは同時指定不可)
```

### `InvalidMiddlewareException`

```php
Route::middleware([], [Route::get('/', ...)]); // throws InvalidMiddlewareException
```

### `DuplicateRouteException`

```php
$routes = new Routes([
    Route::get('/users/{id}', 'ShowA'),
    Route::get('/users/{id}', 'ShowB'),
]);

$routes->validate();
// throws DuplicateRouteException: "GET /users/{id} is defined multiple times."
```

### `DuplicateRouteNameException`

```php
$routes = new Routes([
    Route::get('/users/{id}', 'Show')->name('users.show'),
    Route::get('/members/{id}', 'Show')->name('users.show'),
]);

$routes->validate();
// throws DuplicateRouteNameException: 'Route name "users.show" is already defined.'
```

`validate()` はmethod+pathの重複チェックを先に行い、次にname重複チェックを行います。両方の不整合がある場合、先にthrowされるのは `DuplicateRouteException` です。

### `RouteTreeTooDeepException`

```php
$node = Route::get('/leaf', 'Handler');
for ($i = 0; $i < 10000; $i++) {
    $node = Route::group('/g', [$node]);
}

$routes = new Routes([$node]);
$routes->compile(); // throws RouteTreeTooDeepException
```

`Route::group()`/`Route::middleware()`/`Route::controller()` のネストが最大深度(既定256)を超えると `compile()`(および内部でこれを呼ぶ `deploy()`/`toArray()`/`dump()`/`validate()`/`filter()`/CLIの各コマンド)が本例外をthrowします。外部データから動的にネスト深さを決めてRoute Treeを構築するようなケースで、無制限なネストによるメモリ枯渇(uncaughtなFatal Error)を防ぐためのガードです。

## セキュリティ上の注意

DSLはHandler文字列の `eval()`、Middleware文字列からの独自コード生成、Pathの危険な正規表現変換、serialize/unserializeによるRoute定義のロードなどを一切行いません。Handler/Middlewareの解決は原則Slimへ委ねます。

CLI(`bin/router-dsl`)の `--bootstrap` は、ローカルのPHPファイルパスのみを受け付けます。`phar://`/`http://`/`data://` などのstream wrapper URIを渡した場合は `RuntimeException` としてエラーになります(意図しないファイルインクルード/リモートコード実行相当のリスクを避けるため)。
