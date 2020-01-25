<?php

namespace Tkc49\Kintone_SDK_For_WordPress;

final class Kintone_API {

	const MAX_GET_RECORDS  = 500;
	const MAX_POST_RECORDS = 100;

	/*
	 * constructor
	 */
	private function __construct() {

	}

	/*
	 * get instance
	 */
	public static function getInstance() {
		/**
		 * a variable that keeps the sole instance.
		 */
		static $instance;

		if ( ! isset( $instance ) ) {
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
	public static function get_auth_token_header( $token ) {
		if ( $token ) {
			return array( 'X-Cybozu-API-Token' => $token );
		} else {
			return new \WP_Error( 'kintone', 'API Token is required' );
		}
	}

	public static function get_auth_user_header( $loginName, $password ) {
		if ( $loginName != "" && $password != "" ) {
			return array( 'X-Cybozu-Authorization' => base64_encode( $loginName . ':' . $password ) );
		} else {
			return new \WP_Error( 'kintone', 'API Token is required' );
		}
	}


	/*
	 * Get Kintone basic auth header from Options API
	 * https://cybozudev.zendesk.com/hc/ja/articles/201941754-REST-API%E3%81%AE%E5%85%B1%E9%80%9A%E4%BB%95%E6%A7%98#step8
	 * @param  none
	 * @return array An array of basic auth header
	 * @since  0.1
	 */
	public static function get_basic_auth_header( $basic_auth_user = null, $basic_auth_pass = null ) {
		if ( $basic_auth_user && $basic_auth_pass ) {
			$auth = base64_encode( $basic_auth_user . ':' . $basic_auth_pass );

			return array( 'Authorization' => 'Basic ' . $auth );
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
	public static function get_request_headers( $loginName, $password, $token, $basic_auth_user = null, $basic_auth_pass = null ) {

		if ( $loginName != "" && $password != "" ) {

			if ( is_wp_error( self::get_auth_user_header( $loginName, $password ) ) ) {
				return new \WP_Error( 'kintone', 'Login name & Password is required' );
			}

			$headers = array_merge(
				self::get_auth_user_header( $loginName, $password ),
				self::get_basic_auth_header( $basic_auth_user, $basic_auth_pass )
			);


		} else {

			if ( is_wp_error( self::get_auth_token_header( $token ) ) ) {
				return new \WP_Error( 'kintone', 'API Token is required' );
			}

			$headers = array_merge(
				self::get_auth_token_header( $token ),
				self::get_basic_auth_header( $basic_auth_user, $basic_auth_pass )
			);

		}


		return $headers;
	}


	/*
	 * Get form controls json from REST API
	 * @param  string $token  API token
	 * @param  string $app_id Kintone App ID
	 * @return array		Form html
	 * @since  0.1
	 */
	public static function get_form_json( $token, $app_id, $domain = null, $basic_auth_user = null, $basic_auth_pass = null ) {
		if ( ! intval( $app_id ) ) {
			return new \WP_Error( 'kintone', 'Application ID must be numeric.' );
		}

		$url = sprintf(
			'https://%s/k/v1/form.json?app=%d',
			$domain,
			$app_id
		);

		$headers = Kintone_API::get_request_headers( "", "", $token, $basic_auth_user, $basic_auth_pass );
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
				return new \WP_Error( $return_value['code'], $return_value['message'] );
			} else {
				return $return_value['properties'];
			}
		}
	}

	/*
	 * Get field controls json from REST API
	 * @param  string $token  API token
	 * @param  string $app_id Kintone App ID
	 * @return array		Form html
	 * @since  0.1
	 */
	public static function get_field_json( $kintone ) {

		$defaults = array(
			'domain'          => "",
			'app'             => "",
			'login_name'      => "",
			'password'        => "",
			'token'           => "",
			'basic_auth_user' => "",
			'basic_auth_pass' => ""
		);
		$kintone  = wp_parse_args( $kintone, $defaults );


		if ( ! intval( $kintone['app'] ) ) {
			return new \WP_Error( 'kintone', 'Application ID must be numeric.' );
		}

		$url = sprintf(
			'https://%s/k/v1/app/form/fields.json?app=%d',
			$kintone['domain'],
			$kintone['app']
		);

		$headers = Kintone_API::get_request_headers( $kintone['login_name'], $kintone['password'], $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );

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
				return new \WP_Error( $return_value['code'], $return_value['message'] );
			} else {
				return $return_value['properties'];
			}
		}
	}


	public static function getRecords( $kintone, $query, $limit = 100, $offset = 0, $fields = array(), $total_count_flag = false ) {
		if ( ! intval( $kintone['app'] ) ) {
			return new \WP_Error( 'kintone', 'Application ID must be numeric.' );
		}

		$all_flg = false;
		if ( $limit === - 1 ) {
			$all_flg = true;
			$limit   = self::MAX_GET_RECORDS;
		} elseif ( self::MAX_GET_RECORDS < $limit ) {

			$limit      = self::MAX_GET_RECORDS;
			$loop_count = ceil( $limit / self::MAX_GET_RECORDS );
			$hamidashi  = $limit - floor( $limit / self::MAX_GET_RECORDS ) * self::MAX_GET_RECORDS;

		}

		$all_records = [];
		$continue    = true;

		$current_loop_count = 0;
		$totalCount         = 0;

		while ( $continue ) {

			$current_loop_count ++;

			$result = Kintone_API::get( $kintone, $query . ' limit ' . $limit . ' offset ' . $offset, $fields );
			if ( ! is_wp_error( $result ) ) {

				$all_records = array_merge( $all_records, $result['records'] );

				$totalCount = $result['totalCount'];
				if ( $totalCount <= $limit ) {
					// 再取得なし
					$continue = false;

				} else {

					if ( $all_flg ) {

						if ( count( $all_records ) == $totalCount ) {
							$continue = false;
						} else {
							$offset = $offset + $limit;
						}

					} else {

						if ( self::MAX_GET_RECORDS <= $limit ) {

							// limit : 700 で totalCount : 900 の時
							if ( $current_loop_count == $loop_count ) {
								//  $loop_count : 2回で終了
								$continue = false;
							} else {
								if ( $current_loop_count == $loop_count - 1 ) {
									$offset = $offset + $hamidashi;
								} else {
									$offset = $offset + $limit;
								}
							}
						} else {

							$continue = false;

						}

					}
				}

			} else {

				error_log( 'エラー' );
				error_log( $result->get_error_code() );
				error_log( $result->get_error_message() );
				error_log( $query . ' limit ' . $limit . ' offset ' . $offset );

				return new \WP_Error( $result['code'], $result['message'] );


			}


		}

		if ( $total_count_flag ) {
			return array( 'records' => $all_records, 'total_count' => $totalCount );
		} else {
			return $all_records;
		}


	}


	private static function get( $kintone, $query = '', $fields = array() ) {

		$defaults = array(
			'domain'          => "",
			'app'             => "",
			'login_name'      => "",
			'password'        => "",
			'token'           => "",
			'basic_auth_user' => "",
			'basic_auth_pass' => ""
		);
		$kintone  = wp_parse_args( $kintone, $defaults );

		if ( $query ) {
			$query = '&query=' . urlencode( $query );
		}

		$count     = 0;
		$filed_txt = '';
		foreach ( $fields as $field ) {
			$filed_txt .= '&fields[' . $count . ']=' . urlencode( $field );
			$count ++;
		}

		$url = sprintf(
			'https://%s/k/v1/records.json?app=%d&totalCount=true%s%s',
			$kintone['domain'],
			$kintone['app'],
			$query,
			$filed_txt
		);


		$headers = Kintone_API::get_request_headers( $kintone['login_name'], $kintone['password'], $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );

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

				error_log( var_export( $return_value, true ) );

				return new \WP_Error( $return_value['code'], $return_value['message'] );
			} else {
				return $return_value;
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
	public static function post( $kintone, $data ) {


		// Change Hosoya
		$url = sprintf(
			'https://%s/k/v1/record.json',
			$kintone['domain']
		);

		if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
			$headers = Kintone_API::get_request_headers( "", "", $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
		} else {
			$headers = Kintone_API::get_request_headers( "", "", $kintone['token'] );
		}
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$headers['Content-Type'] = 'application/json';


		$body = array(
			'app'    => $kintone['app'],
			'record' => $data,
		);

		$res = wp_remote_post(
			$url,
			array(
				'method'  => 'POST',
				'headers' => $headers,
				'body'    => json_encode( $body ),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		} elseif ( $res['response']['code'] !== 200 ) {
			$message = json_decode( $res['body'], true );
			$e       = new \WP_Error();
			$e->add( 'validation-error', $message['message'], $message );

			return $e;
		} else {
			return true;
		}
	}

	public static function put( $kintone, $data, $update_key_date = array() ) {

		// Change Hosoya
		$url = sprintf(
			'https://%s/k/v1/record.json',
			$kintone['domain']
		);

		if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
			$headers = Kintone_API::get_request_headers( "", "", $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
		} else {
			$headers = Kintone_API::get_request_headers( "", "", $kintone['token'] );
		}
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$headers['Content-Type'] = 'application/json';


		if ( empty( $update_key_date ) ) {
			$body = array(
				'app'    => $kintone['app'],
				'id'     => $kintone['id'],
				'record' => $data
			);
		} else {
			$body = array(
				'app'       => $kintone['app'],
				'record'    => $data,
				'updateKey' => $update_key_date,
			);
		}


		$res = wp_remote_post(
			$url,
			array(
				'method'  => 'PUT',
				'headers' => $headers,
				'body'    => json_encode( $body ),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		} elseif ( $res['response']['code'] !== 200 ) {
			$message = json_decode( $res['body'], true );
			$e       = new \WP_Error();
			$e->add( 'validation-error', $message['message'], $message );

			return $e;
		} else {
			return true;
		}
	}

}
