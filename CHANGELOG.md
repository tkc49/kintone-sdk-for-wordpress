# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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