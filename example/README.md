# サンプルコード

Slim Router DSLの各機能を実際に動かして確認できるサンプル集です。すべてリポジトリルートから実行してください。

```bash
composer install
```

## 一覧

| ファイル | 内容 |
|---|---|
| [01-basic-routes.php](01-basic-routes.php) | 最小限のルート定義とdeploy() |
| [02-groups-and-middleware.php](02-groups-and-middleware.php) | Group / Middlewareのネスト、複数Middleware |
| [03-fluent-name-and-metadata.php](03-fluent-name-and-metadata.php) | Route単位のFluent Middleware / Route Name / Metadata |
| [04-resource-and-controller.php](04-resource-and-controller.php) | `Route::resource()` / `Route::controller()` |
| [05-inspection-and-validation.php](05-inspection-and-validation.php) | `compile()`/検索API/`validate()` |
| [06-full-request-cycle.php](06-full-request-cycle.php) | `deploy()` 後に実際にリクエストを処理する例(要dev依存) |
| [routes.php](routes.php) | CLI(`bin/router-dsl`)向けBootstrapファイルの例 |

## 実行方法

```bash
php example/01-basic-routes.php
php example/02-groups-and-middleware.php
php example/03-fluent-name-and-metadata.php
php example/04-resource-and-controller.php
php example/05-inspection-and-validation.php
php example/06-full-request-cycle.php
```

`06-full-request-cycle.php` は `slim/psr7`(require-dev)を利用します。`composer install --no-dev` でインストールした環境では動作しないため、`--no-dev` を付けずにインストールしてください。

## CLIサンプル

`routes.php` はCLI(`bin/router-dsl`)向けのBootstrapファイルの例です。`Routes` インスタンスを `return` するだけで、Slimの `App` を一切生成していません。

```bash
vendor/bin/router-dsl routes --bootstrap=example/routes.php
vendor/bin/router-dsl routes --bootstrap=example/routes.php --json
vendor/bin/router-dsl routes --bootstrap=example/routes.php --method=POST
vendor/bin/router-dsl routes --bootstrap=example/routes.php --name=home
vendor/bin/router-dsl validate --bootstrap=example/routes.php
```

詳細は [`/docs/cli.md`](../docs/cli.md) を参照してください。

## 関連ドキュメント

- [`/docs`](../docs/README.md) — 全ドキュメントの目次
- [`/docs/guide.md`](../docs/guide.md) — 各機能の詳細な使い方
