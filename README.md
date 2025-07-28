# Kintone SDK for WordPress

[![Packagist Version](https://img.shields.io/packagist/v/tkc49/kintone-sdk-for-wordpress.svg)](https://packagist.org/packages/tkc49/kintone-sdk-for-wordpress)
[![PHP Version](https://img.shields.io/packagist/php-v/tkc49/kintone-sdk-for-wordpress.svg)](https://packagist.org/packages/tkc49/kintone-sdk-for-wordpress)
[![License](https://img.shields.io/packagist/l/tkc49/kintone-sdk-for-wordpress.svg)](https://packagist.org/packages/tkc49/kintone-sdk-for-wordpress)

WordPress plugin development library for Kintone REST API integration.

[日本語](#日本語) | [English](#english)

---

## 日本語

### 概要

Kintone SDK for WordPress は、WordPress プラグインから Kintone REST API を簡単に利用するための PHP ライブラリです。

### 要件

- PHP 5.6 以上
- WordPress 4.7 以上
- Composer

### インストール

Composer を使用してインストールします：

```bash
composer require tkc49/kintone-sdk-for-wordpress
```

### 基本的な使い方

#### 初期設定

```php
<?php
// Composerのオートローダーを読み込み
require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );

use Tkc49\Kintone_SDK_For_WordPress\Kintone_API;

// Kintone接続情報
$kintone = array(
    'domain'          => 'your-subdomain.cybozu.com', // 末尾のスラッシュは不要
    'app'             => 123, // アプリID（数値）
    'token'           => 'your-api-token', // APIトークン
    'basic_auth_user' => '', // Basic認証ユーザー（必要な場合）
    'basic_auth_pass' => '', // Basic認証パスワード（必要な場合）
);
```

### 主な機能

#### 1. レコードの取得

```php
// 単一条件での取得
$records = Kintone_API::getRecords(
    $kintone,
    'ステータス in ("未処理", "処理中")', // クエリ
    100, // 取得件数上限
    0,   // オフセット
    array('レコード番号', '件名', 'ステータス') // 取得フィールド
);

if ( is_wp_error( $records ) ) {
    // エラー処理
    echo $records->get_error_message();
} else {
    foreach ( $records as $record ) {
        echo $record['件名']['value'] . PHP_EOL;
    }
}

// 全レコード取得（500件を超える場合も自動的に取得）
$all_records = Kintone_API::getAllRecordsSortById(
    $kintone,
    '', // クエリ（空の場合は全件）
    array('レコード番号', '件名') // 取得フィールド
);
```

#### 2. レコードの作成

```php
// 1件のレコードを作成
$data = array(
    '件名' => array(
        'value' => '新しいタスク'
    ),
    'ステータス' => array(
        'value' => '未処理'
    ),
    '詳細' => array(
        'value' => 'タスクの詳細説明'
    )
);

$result = Kintone_API::post( $kintone, $data );

if ( is_wp_error( $result ) ) {
    // エラー処理
    echo $result->get_error_message();
} else {
    // 成功時：作成されたレコードのIDとリビジョン番号が返される
    echo '作成されたレコードID: ' . $result['id'];
}

// 複数レコードの一括作成
$records = array(
    array(
        '件名' => array('value' => 'タスク1'),
        'ステータス' => array('value' => '未処理')
    ),
    array(
        '件名' => array('value' => 'タスク2'),
        'ステータス' => array('value' => '未処理')
    )
);

$result = Kintone_API::posts( $kintone, $records );
```

#### 3. レコードの更新

```php
// 1件のレコードを更新
$kintone['id'] = 100; // 更新対象のレコードID

$update_data = array(
    'ステータス' => array(
        'value' => '完了'
    )
);

$result = Kintone_API::put( $kintone, $update_data );

if ( is_wp_error( $result ) ) {
    echo $result->get_error_message();
} else {
    echo '更新後のリビジョン: ' . $result['revision'];
}

// 複数レコードの一括更新
$update_records = array(
    array(
        'id' => 100,
        'record' => array(
            'ステータス' => array('value' => '完了')
        )
    ),
    array(
        'id' => 101,
        'record' => array(
            'ステータス' => array('value' => '完了')
        )
    )
);

$result = Kintone_API::puts( $kintone, $update_records );
```

#### 4. レコードの削除

```php
// 複数レコードの削除
$ids = array(100, 101, 102);
$result = Kintone_API::delete( $kintone, $ids );

if ( is_wp_error( $result ) ) {
    echo $result->get_error_message();
} else {
    echo 'レコードを削除しました';
}
```

#### 5. フォーム情報の取得

```php
// フォームフィールド情報の取得
$fields = Kintone_API::get_field_json( $kintone );

if ( is_wp_error( $fields ) ) {
    echo $fields->get_error_message();
} else {
    foreach ( $fields as $field_code => $field_info ) {
        echo $field_info['label'] . ' (' . $field_info['type'] . ')' . PHP_EOL;
    }
}
```

#### 6. ファイル操作

```php
// ファイルのアップロード（フォームからのアップロード）
// $_FILES['file'] が存在する前提
$file_key = Kintone_API::get_attachement_file_key( $kintone );
$file_data = json_decode( $file_key['body'], true );

// レコード作成時にファイルを添付
$data = array(
    '件名' => array('value' => 'ファイル付きレコード'),
    '添付ファイル' => array(
        'value' => array(
            array('fileKey' => $file_data['fileKey'])
        )
    )
);

$result = Kintone_API::post( $kintone, $data );

// URLからファイルをアップロード
$file_key = Kintone_API::get_attachement_file_key_from_url(
    $kintone,
    'https://example.com/image.jpg',
    'image.jpg'
);
```

### エラーハンドリング

すべてのメソッドは、エラー時に`WP_Error`オブジェクトを返します：

```php
$result = Kintone_API::post( $kintone, $data );

if ( is_wp_error( $result ) ) {
    $error_code = $result->get_error_code();
    $error_message = $result->get_error_message();
    $error_data = $result->get_error_data();

    // エラーログに記録
    error_log( sprintf(
        'Kintone API Error [%s]: %s',
        $error_code,
        $error_message
    ));

    // 詳細なエラー情報がある場合
    if ( $error_data ) {
        error_log( print_r( $error_data, true ) );
    }
}
```

### 高度な使い方

#### ページネーション処理

```php
$limit = 100;
$offset = 0;
$all_records = array();

do {
    $result = Kintone_API::getRecords(
        $kintone,
        '',
        $limit,
        $offset
    );

    if ( is_wp_error( $result ) ) {
        break;
    }

    $all_records = array_merge( $all_records, $result );
    $offset += $limit;

} while ( count( $result ) === $limit );
```

#### 条件付き更新（楽観的ロック）

```php
// 現在のレコードを取得
$current = Kintone_API::getRecords(
    $kintone,
    '$id = "100"',
    1
);

if ( ! is_wp_error( $current ) && ! empty( $current ) ) {
    $update_key = array(
        'field' => '更新日時',
        'value' => $current[0]['更新日時']['value']
    );

    $update_data = array(
        'ステータス' => array('value' => '完了')
    );

    $result = Kintone_API::put( $kintone, $update_data, $update_key );
}
```

### トラブルシューティング

#### よくあるエラーと対処法

1. **「API Token is required」エラー**

   - API トークンが正しく設定されているか確認してください
   - API トークンに必要な権限が付与されているか確認してください

2. **「Application ID must be numeric」エラー**

   - アプリ ID は文字列ではなく数値で指定してください
   - 正しい: `'app' => 123`
   - 誤り: `'app' => '123'`

3. **SSL 証明書エラー**

   - WordPress の `wp-config.php` に以下を追加：

   ```php
   define( 'WP_HTTP_BLOCK_EXTERNAL', false );
   ```

4. **タイムアウトエラー**
   - 大量のデータを扱う場合は、`set_time_limit()` で実行時間を延長してください

### 貢献方法

1. このリポジトリをフォーク
2. 新しいブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add some amazing feature'`)
4. ブランチにプッシュ (`git push origin feature/amazing-feature`)
5. プルリクエストを作成

### ライセンス

このプロジェクトは GPL-2.0-or-later ライセンスの下で公開されています。

---

## English

### Overview

Kintone SDK for WordPress is a PHP library that makes it easy to use Kintone REST API from WordPress plugins.

### Requirements

- PHP 5.6 or higher
- WordPress 4.7 or higher
- Composer

### Installation

Install via Composer:

```bash
composer require tkc49/kintone-sdk-for-wordpress
```

### Basic Usage

#### Initial Setup

```php
<?php
// Load Composer autoloader
require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );

use Tkc49\Kintone_SDK_For_WordPress\Kintone_API;

// Kintone connection settings
$kintone = array(
    'domain'          => 'your-subdomain.cybozu.com', // No trailing slash
    'app'             => 123, // App ID (numeric)
    'token'           => 'your-api-token', // API token
    'basic_auth_user' => '', // Basic auth user (if needed)
    'basic_auth_pass' => '', // Basic auth password (if needed)
);
```

### Main Features

#### 1. Retrieving Records

```php
// Get records with conditions
$records = Kintone_API::getRecords(
    $kintone,
    'Status in ("Pending", "In Progress")', // Query
    100, // Limit
    0,   // Offset
    array('Record_number', 'Title', 'Status') // Fields to retrieve
);

if ( is_wp_error( $records ) ) {
    // Error handling
    echo $records->get_error_message();
} else {
    foreach ( $records as $record ) {
        echo $record['Title']['value'] . PHP_EOL;
    }
}
```

#### 2. Creating Records

```php
// Create a single record
$data = array(
    'Title' => array(
        'value' => 'New Task'
    ),
    'Status' => array(
        'value' => 'Pending'
    )
);

$result = Kintone_API::post( $kintone, $data );

if ( is_wp_error( $result ) ) {
    echo $result->get_error_message();
} else {
    // Returns created record ID and revision
    echo 'Created record ID: ' . $result['id'];
}
```

#### 3. Updating Records

```php
// Update a single record
$kintone['id'] = 100; // Record ID to update

$update_data = array(
    'Status' => array(
        'value' => 'Completed'
    )
);

$result = Kintone_API::put( $kintone, $update_data );
```

#### 4. Deleting Records

```php
// Delete multiple records
$ids = array(100, 101, 102);
$result = Kintone_API::delete( $kintone, $ids );
```

### Error Handling

All methods return a `WP_Error` object on failure:

```php
$result = Kintone_API::post( $kintone, $data );

if ( is_wp_error( $result ) ) {
    $error_code = $result->get_error_code();
    $error_message = $result->get_error_message();

    error_log( sprintf(
        'Kintone API Error [%s]: %s',
        $error_code,
        $error_message
    ));
}
```

### Contributing

1. Fork this repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

### License

This project is licensed under the GPL-2.0-or-later License.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed list of changes.

## Support

- [GitHub Issues](https://github.com/tkc49/kintone-sdk-for-wordpress/issues)
- [Kintone Developer Documentation](https://developer.cybozu.io/hc/ja)
