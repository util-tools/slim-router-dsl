# CLI

`bin/router-dsl` は、Slimの `App` を起動せずにルート定義を確認・検証できるCLIツールです。`Routes::compile()`/`dump()`/`toArray()`/`validate()` のみを利用しており、DIコンテナ全体の初期化は不要です。

## インストール

Composerでインストールすると `vendor/bin/router-dsl` が使えるようになります。

```bash
composer require tanahiro2010/slim-router-dsl
vendor/bin/router-dsl routes
```

## Bootstrapファイル

CLIは `--bootstrap` オプションで指定したPHPファイルを読み込みます。そのファイルは **`Tanahiro2010\SlimRouterDsl\Routes` インスタンスを `return` する必要があります**。

```php
<?php
// routes.php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Tanahiro2010\SlimRouterDsl\Route;
use Tanahiro2010\SlimRouterDsl\Routes;

return new Routes([
    Route::get('/', HomeController::class),
    Route::group('/api', [
        Route::get('/users', [UserController::class, 'index'])->name('users.index'),
    ]),
]);
```

`--bootstrap` を省略した場合、カレントディレクトリの `./routes.php` が探索されます。

```bash
vendor/bin/router-dsl routes --bootstrap=routes.php
```

**セキュリティ上の注意**: `--bootstrap` にはローカルのファイルパスのみを指定してください。`phar://`/`http://`/`data://` などのstream wrapper URIはv1.4.1以降拒否されます(意図しないファイルインクルードやリモートコード実行相当のリスクを避けるため)。`--bootstrap` の値を外部から受け取った未検証の入力から組み立てることは絶対に避けてください。

## コマンド一覧

### `routes` — ルート一覧の表示

```bash
vendor/bin/router-dsl routes --bootstrap=routes.php
```

```text
METHOD  PATH        NAME         MIDDLEWARE
GET     /           -            -
GET     /api/users  users.index  -
```

JSON形式で出力する場合は `--json` を付けます(`Routes::toArray()` と同じ形式)。

```bash
vendor/bin/router-dsl routes --bootstrap=routes.php --json
```

```json
[
    {
        "methods": ["GET"],
        "path": "/",
        "handler": "HomeController",
        "middleware": [],
        "name": null,
        "metadata": []
    }
]
```

`--method=` / `--name=` で絞り込めます(内部的に `filterByMethod()`/`findByName()` を利用)。

```bash
vendor/bin/router-dsl routes --bootstrap=routes.php --method=POST
vendor/bin/router-dsl routes --bootstrap=routes.php --name=users.index
```

### `validate` — ルート定義の検証

```bash
vendor/bin/router-dsl validate --bootstrap=routes.php
```

成功時:

```text
No validation errors.
```
(exit code: `0`)

重複が見つかった場合、標準エラー出力にメッセージを表示し、exit code `1` で終了します。

```text
GET /users/{id} is defined multiple times.
```

## exit code一覧

| exit code | 意味 |
|---|---|
| `0` | 成功 |
| `1` | コマンド未指定 / 未知のコマンド / `validate` が重複を検出 |
| `2` | Bootstrapファイルが見つからない、`Routes` インスタンスを返していない、または`--bootstrap`にstream wrapper URIが指定された |

## CI / pre-commitフックへの組み込み例

デプロイ前にルート定義の整合性をチェックしたい場合、CIで以下のように実行できます。

```bash
vendor/bin/router-dsl validate --bootstrap=routes.php || exit 1
```
