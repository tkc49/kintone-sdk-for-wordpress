# Kintone SDK for WordPress
test2

## Install
```
$ composer require tkc49/kintone-sdk-for-wordpress
```

## Activate automatic update in your WordPress plugin.
```
<?php
// Autoload
require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );

$kintone = array(
    'domain' => 'xxx.cybozu.com/',
    'token' => 'your-token',
    'basic_auth_user' => '',
    'basic_auth_pass' => '',
    'app' => 'your-app-id',
);

$data = array(
    'field_code' => array( 
        'value' => 'your-value' 
    ),
    'field_code' => array( 
        'value' => 'your-value' 
    ),
    'field_code' => array( 
        'value' => 'your-value' 
    ),
);
new tkc49\Kintone_SDK_For_WordPress\Kintone_API::post( $kintone, $data );
```

