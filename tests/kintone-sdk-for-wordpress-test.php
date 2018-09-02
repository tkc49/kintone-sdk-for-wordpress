<?php
/**
 * Plugin Name:     Kintone Sdk For WordPress
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     kintone-sdk-for-wordpress
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Kintone_Sdk_For_Wordpress
 */

// Your code starts here.

define( 'KINTONE_SDK_FOR_WORDPRESS_URL',  plugins_url( '', __FILE__ ) );
define( 'KINTONE_SDK_FOR_WORDPRESS_PATH', dirname( __FILE__ ) );


require_once(dirname(__FILE__) . '/vendor/tkc49/kintone-sdk-for-wordpress/src/class-Kintone-sdk-for-WordPress.php');

$kintone_sdk_for_wordpress = new KintoneSDKForWordPress();
$kintone_sdk_for_wordpress->register();

class KintoneSDKForWordPress {

    private $version = '';
    private $langs   = '';
    private $nonce   = 'kintone_sdk_for_wordpress_';

    function __construct()
    {
        $data = get_file_data(
            __FILE__,
            array( 'ver' => 'Version', 'langs' => 'Domain Path' )
        );
        $this->version = $data['ver'];
        $this->langs   = $data['langs'];
    }

    public function register()
    {
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
    }

    public function plugins_loaded()
    {
        load_plugin_textdomain(
            'kintone',
            false,
            dirname( plugin_basename( __FILE__ ) ).$this->langs
        );


        $kintone = array(
            'domain' => '1p7cm.cybozu.com/',
            'token' => 'uSpYhTdBRqf8obyzU1xuCQ30bEBIGcRls02j8ANE',
            'basic_auth_user' => '',
            'basic_auth_pass' => '',
            'app' => '229',
        );

        $data = array(
            'メールアドレス' => array( 'value' => 'h.tkc49+test6@gmail.com' ),
            '姓' => array( 'value' => '細谷' ),
            '名' => array( 'value' => '崇' ),
        );

        $res = Kintone_API::post( $kintone, $data );
        error_log(var_export($res, true));


    }

}

