<?php
/**
 * Class SampleTest
 *
 * @package Kintone_Sdk_For_Wordpress
 */

/**
 * Sample test case.
 */

use tkc49\Kintone_SDK_For_WordPress\Kintone_API;

class Kintone_API_Test extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
//		$this->assertTrue( true );



		// TEST
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
        $this->assertTrue($res);


	}
}
