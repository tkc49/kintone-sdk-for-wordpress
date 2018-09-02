<?php

namespace Tkc49\Kintone_SDK_For_WordPress;

final class Kintone_API
{
    /*
     * constructor
     */
    private function __construct()
    {

    }

    /*
     * get instance
     */
    public static function getInstance()
    {
        /**
         * a variable that keeps the sole instance.
         */
        static $instance;

        if ( !isset( $instance ) ) {
            $instance = new Kintone_API();
        }
        return $instance;
    }

    /*
     * don't allow you to clone the instance.
     */
    public final function __clone() {
        throw new RuntimeException( 'Clone is not allowed against ' );
    }


    /*
     * Get Kintone auth header from Options API
     * https://cybozudev.zendesk.com/hc/ja/articles/201941754-REST-API%E3%81%AE%E5%85%B1%E9%80%9A%E4%BB%95%E6%A7%98#step7
     * @param  none
     * @return array An array of auth header
     * @since  0.1
     */
    public static function get_auth_header( $token )
    {
        if ( $token ) {
            return array( 'X-Cybozu-API-Token' => $token );
        } else {
            return new WP_Error( 'kintone', 'API Token is required' );
        }
    }


    /*
     * Get Kintone basic auth header from Options API
     * https://cybozudev.zendesk.com/hc/ja/articles/201941754-REST-API%E3%81%AE%E5%85%B1%E9%80%9A%E4%BB%95%E6%A7%98#step8
     * @param  none
     * @return array An array of basic auth header
     * @since  0.1
     */
    public static function get_basic_auth_header( $basic_auth_user = null, $basic_auth_pass = null )
    {
        if ( $basic_auth_user && $basic_auth_pass ) {
            $auth = base64_encode( $basic_auth_user.':'.$basic_auth_pass );
            return array( 'Authorization' => 'Basic '.$auth );
        } else {
            return array();
        }
    }


    /*
     * Get Kintone request headers from Options API
     * @param  none
     * @return array An array of request headers
     * @since  0.1
     */
    public static function get_request_headers( $token, $basic_auth_user = null, $basic_auth_pass = null )
    {
        if ( is_wp_error( self::get_auth_header( $token ) ) ) {
            return new WP_Error( 'kintone', 'API Token is required' );
        }

        $headers = array_merge(
            self::get_auth_header( $token ), self::get_basic_auth_header( $basic_auth_user, $basic_auth_pass )
        );

        return $headers;
    }


    /*
     * Get form controls json from REST API
     * @param  string $token  API token
     * @param  string $app_id Kintone App ID
     * @return array		Form html
     * @since  0.1
     */
    public static function get_form_json($token, $app_id, $domain = null, $basic_auth_user = null, $basic_auth_pass = null )
    {
        if ( !intval( $app_id ) ) {
            return new WP_Error( 'kintone', 'Application ID must be numeric.' );
        }

        $url = sprintf(
            'https://%s/k/v1/form.json?app=%d',
            $domain,
            $app_id
        );

        $headers = Kintone_API::get_request_headers( $token, $basic_auth_user, $basic_auth_pass );
        if ( is_wp_error( $headers ) ) {
            return $headers;
        }

        $res = wp_remote_get(
            $url,
            array(
                'headers' => $headers
            )
        );

        if ( is_wp_error( $res ) ) {
            return $res;
        } else {
            $return_value = json_decode( $res['body'], true );
            if ( isset( $return_value['message'] ) && isset( $return_value['code'] ) ) {
                return new WP_Error( $return_value['code'], $return_value['message'] );
            } else {
                return $return_value['properties'];
            }
        }
    }



    /*
     * Send form data to Kintone API
     * @param  string $kintone  Shortcode attributes it need for auth.
     *  $kintone: domain
     *  $kintone: token
     *  $kintone: basic_auth_user
     *  $kintone: basic_auth_pass
     *  $kintone: app
     * @param  string $data	 $_POST data
     * @return array			true or WP_Error object
     * @since  0.1
     */
    public static function post( $kintone, $data )
    {


        // Change Hosoya
        $url = sprintf(
            'https://%s/k/v1/record.json',
            $kintone['domain']
        );

        if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
            $headers = Kintone_API::get_request_headers( $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
        } else {
            $headers = Kintone_API::get_request_headers( $kintone['token'] );
        }
        if ( is_wp_error( $headers ) ) {
            return $headers;
        }

        $headers['Content-Type'] = 'application/json';


        $body = array(
            'app'	=> $kintone['app'],
            'record' => $data,
        );

        $res = wp_remote_post(
            $url,
            array(
                'method'  => 'POST',
                'headers' => $headers,
                'body'	=> json_encode( $body ),
            )
        );

        if ( is_wp_error( $res ) ) {
            return $res;
        } elseif (  $res['response']['code'] !== 200 ) {
            $message = json_decode( $res['body'], true );
            $e = new WP_Error();
            $e->add( 'validation-error', $message['message'], $message );
            return $e;
        } else {
            return true;
        }
    }


}


// EOF
