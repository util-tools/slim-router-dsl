# はじめに

## 動作要件

- PHP >= 8.2
- Slim Framework 4.x

## インストール

```bash
composer require tanahiro2010/slim-router-dsl
```

`slim/slim` に依存しているため、Slim本体も併せてインストールされます。PSR-7実装(`slim/psr7` など)は別途必要です。

```bash
composer require slim/psr7
```

## 最初のルート定義

`routes.php` のようなファイルにルート定義をまとめ、`deploy()` でSlimへ展開します。

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

$app = AppFactory::create();

$routes = new Routes([
    Route::get('/', function ($request, $response) {
        $response->getBody()->write('Hello, Slim Router DSL!');
        return $response;
    }),
]);

$routes->deploy($app);

$app->run();
```

`new Routes([...])` の時点ではSlimへの登録は一切行われません。`$routes->deploy($app)` を呼んだ時点で初めてSlimにルートが登録されます([コアコンセプト](core-concepts.md)を参照)。

## 次のステップ

- ルート構造をより実践的にするには [ガイド](guide.md) を参照してください(Group / Middleware / Resource など)。
- `/example` ディレクトリの [サンプルコード](../example/README.md) を実際に動かしてみるのもおすすめです。
- ルート一覧をコマンドラインから確認したい場合は [CLI](cli.md) を参照してください。
