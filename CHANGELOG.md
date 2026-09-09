# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.9.1] - 2026-09-09

### Added
- ページング取得に経過時間の上限を設けられるようにした
  - `getAllRecordsSortById()` と `getRecords()` が、次のページを取りに行く前に
    経過時間を確認し、上限を超えていれば `WP_Error( 'kintone_fetch_timeout' )` を返す
  - **既定は 0（無制限）で、これまでの挙動と変わらない。** 打ち切りたい呼び出し元だけが
    フィルタで秒数を指定する:
    ```php
    add_filter( 'kintone_sdk_max_elapsed_seconds', function () { return 50; } );
    ```
  - **打ち切り時に部分的なレコードは返さない。** 途中までの結果を返すと
    呼び出し元が不完全な集計をして誤った値を書き戻すため、必ず `WP_Error` にする

### なぜ必要か
数十万件規模のアプリでは、バーコードなどで絞っても 1 回の取得が
数分に達することがある（実測で 150 ページ / 355 秒）。
Webhook のように応答期限がある文脈では途中で諦める必要がある。

PHP の `max_execution_time` / `set_time_limit()` は Unix 系では
システムコールの待ち時間を数えないため、処理時間のほぼ全部が cURL の
応答待ちであるこの処理には効かない（実測で確認済み）。
そのため SDK 側で経過時間を見る必要がある。

## [1.9.0] - 2026-09-09

### Added
- `get_attachement_file_key()` に第2引数 `$file_data` を追加
  - `array( 'fileName' => ..., 'fileKey' => base64エンコードした中身 )` を渡すと
    `$_FILES` を経由せず直接アップロードできる
  - Webhook ハンドラや cron など、**`$_FILES` が存在しない文脈**で必要。
    従来はこれらの文脈で `$_FILES['file']` にアクセスして警告・Fatal になっていた

### Fixed
- `get_attachement_file_key()` の堅牢性を改善
  - `$_FILES` 未設定時に `WP_Error` を返す（以前は未定義キーアクセスで警告）
  - アップロードされたファイルが存在しない場合に `WP_Error` を返す
  - `finfo_open()` の失敗を検出する
    （以前は `finfo_file(): Argument #1 ($finfo) cannot be empty` で Fatal になっていた）
  - kintone がエラーや JSON 以外を返した場合に `WP_Error` を返す

### Breaking Changes
- **重要**: `get_attachement_file_key()` の戻り値が**生の JSON 文字列から
  デコード済み配列に変わりました**（PHPDoc の `@return array` に実装を合わせた形です）。
  - Before: `'{"fileKey":"2026..."}'` （文字列）
  - After: `array( 'fileKey' => '2026...' )` （配列）
  - 呼び出し側で `json_decode()` している場合は削除してください。
    `$result['fileKey']` で直接取得できます。

## [1.8.1] - 2026-09-09

### Fixed
- kintone が JSON 以外の応答を返したときに Fatal error になる問題を修正
  - 過負荷時の 502 / 503、ゲートウェイのエラーページ、切断されたレスポンスなどで
    `json_decode()` が `null` を返すと、`message` / `code` の判定にも掛からないため
    `null` がそのまま呼び出し元へ渡っていた
  - その結果 `getAllRecordsSortById()` / `getRecords()` の
    `array_merge( $all_records, $result['records'] )` が
    `TypeError: array_merge(): Argument #2 must be of type array, null given`
    で異常終了していた
  - `get()` が `WP_Error( 'kintone_invalid_response' )` を返すよう修正。
    応答本文の先頭 200 文字をエラーメッセージに含め、原因を追跡できるようにした
  - あわせて、JSON としては妥当でも `records` を含まない応答に対するガードを
    ページングループにも追加した

**影響**: 大量レコードを扱うアプリで、kintone 側の負荷が高いほど発生しやすい。
本番環境で 1 日に 18 回の Fatal が観測された事例がある。
このバージョンより前のすべてのリリースが対象。

## [1.8.0] - 2025-07-28

### Added
- 日本語・英語完全対応のREADME.md
- 全APIメソッドの詳細な使用例とサンプルコード
- エラーハンドリング、高度な使い方、トラブルシューティングのドキュメント
- Packagist、PHP Version、LicenseバッジをREADMEに追加
- 開発環境ファイルの追加:
  - `.editorconfig` - コード書式の統一
  - `phpcs.xml` - WordPress Coding Standards設定
  - `phpunit.xml.dist` - PHPUnitテスト設定
- composer.jsonに開発用スクリプト追加 (`test`, `lint`, `fix`等)
- 開発依存関係の追加 (PHPCS, PHPUnit, WordPress Coding Standards等)

### Changed
- APIメソッドの戻り値を改善:
  - `post()`: `true` → `array('id' => 'xxx', 'revision' => 'xxx')`
  - `posts()`: `true` → `array('ids' => [...], 'revisions' => [...])`
  - `put()`: `true` → `array('revision' => 'xxx')`
  - `puts()`: `true` → `array('records' => [...])`
- composer.jsonの説明文とキーワード改善
- `.gitignore`をより包括的な除外ルールに更新
- PHPDocコメントを新しい戻り値の型に合わせて更新

### Breaking Changes
- **重要**: `post()`, `posts()`, `put()`, `puts()`メソッドの戻り値が`boolean true`から詳細情報を含む`array`に変更されました
- 既存のコードで戻り値を厳密にチェックしている場合は修正が必要です

## [1.7.2] - 2024-10-05

### Fixed
- `getRecords`メソッドで`$limit`が500の時に発生する無限ループバグを修正
- 一般的なコードリファクタリングを実施

## [1.7.0] - 2023-01-11

### Fixed
- "Uncaught Error: Cannot use object of type WP_Error as array"エラーを修正

---

## Migration Guide

### v1.7.x から v1.8.0への移行

#### 戻り値の変更への対応

**Before (v1.7.x):**
```php
$result = Kintone_API::post($kintone, $data);
if ($result === true) {
    echo "Success!";
}
```

**After (v1.8.0):**
```php
$result = Kintone_API::post($kintone, $data);
if (!is_wp_error($result)) {
    echo "Success! Created record ID: " . $result['id'];
}
```

#### 影響を受けるメソッド
- `Kintone_API::post()`
- `Kintone_API::posts()`
- `Kintone_API::put()`
- `Kintone_API::puts()`

#### 推奨される対応
1. `=== true`の比較を`!is_wp_error()`に変更
2. 成功時に返される詳細情報を活用
3. エラーハンドリングは従来通り`is_wp_error()`を使用

詳細な使用例は[README.md](README.md)をご参照ください。