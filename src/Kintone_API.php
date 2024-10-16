<?php
/**
 * Kintone_SDK_For_WordPress
 *
 * @version 1.7.2
 */
namespace Tkc49\Kintone_SDK_For_WordPress;

/**
 * Class Kintone_API
 *
 * @package Tkc49\Kintone_SDK_For_WordPress
 */
final class Kintone_API {

	const MAX_GET_RECORDS  = 500;
	const MAX_POST_RECORDS = 100;

	/**
	 * Constructor
	 */
	private function __construct() {
	}

	/*
	 * get instance
	 */
	public static function getInstance() {
		/**
		 * A variable that keeps the sole instance.
		 */
		static $instance;

		if ( ! isset( $instance ) ) {
			$instance = new Kintone_API();
		}

		return $instance;
	}

	/**
	 * Don't allow you to clone the instance.
	 *
	 * @throws \RuntimeException .
	 */
	public function __clone() {
		throw new \RuntimeException( 'Clone is not allowed against ' );
	}


	/**
	 * Get Kintone auth header from Options API.
	 * https://cybozudev.zendesk.com/hc/ja/articles/201941754-REST-API%E3%81%AE%E5%85%B1%E9%80%9A%E4%BB%95%E6%A7%98#step7
	 *
	 * @param  string $token API token.
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

	/**
	 * Get Kintone auth user header from Options API.
	 *
	 * @param  string $login_name ログイン名.
	 * @param  string $password パスワード.
	 * @return array An array of auth user header
	 * @since  0.1
	 */
	public static function get_auth_user_header( $login_name, $password ) {
		if ( '' !== $login_name && '' !== $password ) {
			return array( 'X-Cybozu-Authorization' => base64_encode( $login_name . ':' . $password ) );
		} else {
			return new \WP_Error( 'kintone', 'API Token is required' );
		}
	}


	/**
	 * Get Kintone basic auth header from Options API.
	 *
	 * @param  string $basic_auth_user ベーシック認証ユーザー名.
	 * @param  string $basic_auth_pass ベーシック認証パスワード.
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


	/**
	 * Get Kintone request headers from Options API.
	 *
	 * @param  string $login_name ログイン名.
	 * @param  string $password パスワード.
	 * @param  string $token APIトークン.
	 * @param  string $basic_auth_user ベーシック認証ユーザー名.
	 * @param  string $basic_auth_pass ベーシック認証パスワード.
	 * @return array An array of request headers
	 * @since  0.1
	 */
	public static function get_request_headers( $login_name, $password, $token, $basic_auth_user = null, $basic_auth_pass = null ) {

		if ( '' !== $login_name && '' !== $password ) {
			if ( is_wp_error( self::get_auth_user_header( $login_name, $password ) ) ) {
				return new \WP_Error( 'kintone', 'Login name & Password is required' );
			}
			$headers = array_merge(
				self::get_auth_user_header( $login_name, $password ),
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


	/**
	 * Get form controls json from REST API.
	 *
	 * @param  string $token  API token.
	 * @param  string $app_id Kintone App ID.
	 * @param  string $domain ドメイン.
	 * @param  string $basic_auth_user ベーシック認証ユーザー名.
	 * @param  string $basic_auth_pass ベーシック認証パスワード.
	 * @return array Form html.
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

		$headers = self::get_request_headers( '', '', $token, $basic_auth_user, $basic_auth_pass );
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$res = wp_remote_get(
			$url,
			array(
				'headers' => $headers,
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

	/**
	 * Get field controls json from REST API.
	 *
	 * @param  array $kintone  Shortcode attributes it need for auth.
	 *  $kintone['domain'] .
	 *  $kintone['app'] .
	 *  $kintone['login_name'] .
	 *  $kintone['password'] .
	 *  $kintone['token'] .
	 *  $kintone['basic_auth_user'] .
	 *  $kintone['basic_auth_pass'] .
	 * @return array Form html
	 * @since  0.1
	 */
	public static function get_field_json( $kintone ) {

		$defaults = array(
			'domain'          => '',
			'app'             => '',
			'login_name'      => '',
			'password'        => '',
			'token'           => '',
			'basic_auth_user' => '',
			'basic_auth_pass' => '',
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

		$headers = self::get_request_headers( $kintone['login_name'], $kintone['password'], $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );

		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$res = wp_remote_get(
			$url,
			array(
				'headers' => $headers,
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

	/**
	 * Get all records from Kintone API.
	 *
	 * @param  array   $kintone  Shortcode attributes it need for auth.
	 *    $kintone['domain'] .
	 *    $kintone['app'] .
	 *    $kintone['login_name'] .
	 *    $kintone['password'] .
	 *    $kintone['token'] .
	 *    $kintone['basic_auth_user'] .
	 *    $kintone['basic_auth_pass'] .
	 * @param   string  $param_query  Query string.
	 * @param   array   $fields  Fields to get.
	 * @param  boolean $total_count_flag  Whether to return total count.
	 * @return array|WP_Error All records.
	 * @since  0.1
	 */
	public static function getAllRecordsSortById( $kintone, $param_query, $fields = array(), $total_count_flag = false ) {
		if ( ! intval( $kintone['app'] ) ) {
			return new \WP_Error( 'kintone', 'Application ID must be numeric.' );
		}

		$limit       = self::MAX_GET_RECORDS;
		$all_records = array();

		$total_count = 0;
		$current_id  = 0;

		$fields[] = '$id';
		$fields   = array_unique( $fields );

		$continue = true;
		while ( $continue ) {

			if ( $param_query ) {
				$query = $param_query . ' and $id > "' . $current_id . '" order by $id asc';
			} else {
				$query = '$id > "' . $current_id . '" order by $id asc';
			}

			$result = self::get( $kintone, $query . ' limit ' . $limit, $fields );
			if ( ! is_wp_error( $result ) ) {

				$all_records = array_merge( $all_records, $result['records'] );

				$total_count = $result['totalCount'];
				if ( $total_count <= $limit ) {
					// 再取得なし
					$continue = false;

				} elseif ( count( $all_records ) === $total_count ) {
						$continue = false;
				} else {
					$current_id = $result['records'][ count( $result['records'] ) - 1 ]['$id']['value'];
				}
			} else {
				return new \WP_Error( $result->get_error_code(), $result->get_error_message() );
			}
		}

		if ( $total_count_flag ) {
			return array(
				'records'     => $all_records,
				'total_count' => $total_count,
			);
		} else {
			return $all_records;
		}
	}

	/**
	 * Get records from Kintone API.
	 *
	 * @param  array   $kintone  Shortcode attributes it need for auth.
	 *    $kintone['domain'] .
	 *    $kintone['app'] .
	 *    $kintone['login_name'] .
	 *    $kintone['password'] .
	 *    $kintone['token'] .
	 *    $kintone['basic_auth_user'] .
	 *    $kintone['basic_auth_pass'] .
	 * @param  string  $query  Query string.
	 * @param  int     $limit  Limit of records to get.
	 * @param  int     $offset  Offset of records to get.
	 * @param  array   $fields  Fields to get.
	 * @param  boolean $total_count_flag  Whether to return total count.
	 * @return array Records
	 * @since  0.1
	 */
	public static function getRecords( $kintone, $query, $limit = 100, $offset = 0, $fields = array(), $total_count_flag = false ) {
		if ( ! intval( $kintone['app'] ) ) {
			return new \WP_Error( 'kintone', 'Application ID must be numeric.' );
		}

		$all_flg = false;
		if ( -1 === $limit ) {
			$all_flg = true;
			$limit   = self::MAX_GET_RECORDS;
		} elseif ( self::MAX_GET_RECORDS <= $limit ) {
			$limit      = self::MAX_GET_RECORDS;
			$loop_count = ceil( $limit / self::MAX_GET_RECORDS );
			$hamidashi  = $limit - floor( $limit / self::MAX_GET_RECORDS ) * self::MAX_GET_RECORDS;
		}

		$all_records = array();
		$continue    = true;

		$current_loop_count = 0;
		$total_count        = 0;

		while ( $continue ) {

			++$current_loop_count;

			$result = self::get( $kintone, $query . ' limit ' . $limit . ' offset ' . $offset, $fields );
			if ( ! is_wp_error( $result ) ) {

				$all_records = array_merge( $all_records, $result['records'] );

				$total_count = $result['totalCount'];
				if ( $total_count <= $limit ) {
					// 再取得なし.
					$continue = false;

				} elseif ( $all_flg ) {

					if ( count( $all_records ) === $total_count ) {
						$continue = false;
					} else {
						$offset = $offset + $limit;
					}
				} elseif ( self::MAX_GET_RECORDS <= $limit ) {
					if ( $current_loop_count === $loop_count ) {
						// $loop_count : 2回で終了
						$continue = false;
					} elseif ( $current_loop_count === $loop_count - 1 ) {
						$offset = $offset + $hamidashi;
					} else {
						$offset = $offset + $limit;
					}
				} else {
					$continue = false;
				}
			} else {
				return $result;
			}
		}

		if ( $total_count_flag ) {
			return array(
				'records'     => $all_records,
				'total_count' => $total_count,
			);
		} else {
			return $all_records;
		}
	}


	/**
	 * Get records from Kintone API.
	 *
	 * @param  array  $kintone  Shortcode attributes it need for auth.
	 *   $kintone['domain'] .
	 *   $kintone['app'] .
	 *   $kintone['login_name'] .
	 *   $kintone['password'] .
	 *   $kintone['token'] .
	 *   $kintone['basic_auth_user'] .
	 *   $kintone['basic_auth_pass'] .
	 * @param  string $query  Query string.
	 * @param  array  $fields  Fields to get.
	 * @return array|WP_Error Records
	 * @since  0.1
	 */
	private static function get( $kintone, $query = '', $fields = array() ) {

		$defaults = array(
			'domain'          => '',
			'app'             => '',
			'login_name'      => '',
			'password'        => '',
			'token'           => '',
			'basic_auth_user' => '',
			'basic_auth_pass' => '',
		);
		$kintone  = wp_parse_args( $kintone, $defaults );

		if ( $query ) {
			$query = '&query=' . urlencode( $query );
		}

		$count     = 0;
		$filed_txt = '';
		foreach ( $fields as $field ) {
			$filed_txt .= '&fields[' . $count . ']=' . urlencode( $field );
			++$count;
		}

		$url = sprintf(
			'https://%s/k/v1/records.json?app=%d&totalCount=true%s%s',
			$kintone['domain'],
			$kintone['app'],
			$query,
			$filed_txt
		);

		$headers = self::get_request_headers( $kintone['login_name'], $kintone['password'], $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );

		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$res = wp_remote_get(
			$url,
			array(
				'headers' => $headers,
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


	/**
	 * Send form data to Kintone API.
	 *
	 * @param  array  $kintone  Shortcode attributes it need for auth.
	 *  $kintone['domain'] .
	 *  $kintone['token'] .
	 *  $kintone['basic_auth_user'] .
	 *  $kintone['basic_auth_pass'] .
	 *  $kintone['app'] .
	 * @param  string $data  $_POST data.
	 * @return array true or WP_Error object
	 * @since  0.1
	 */
	public static function post( $kintone, $data ) {

		$url = sprintf(
			'https://%s/k/v1/record.json',
			$kintone['domain']
		);

		if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
			$headers = self::get_request_headers( '', '', $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
		} else {
			$headers = self::get_request_headers( '', '', $kintone['token'] );
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

	/**
	 * Send form data to Kintone API.
	 *
	 * @param  array  $kintone  Shortcode attributes it need for auth.
	 *  $kintone['domain'] .
	 *  $kintone['token'] .
	 *  $kintone['basic_auth_user'] .
	 *  $kintone['basic_auth_pass'] .
	 *  $kintone['app'] .
	 * @param  string $data  $_POST data.
	 * @return array true or WP_Error object
	 */
	public static function posts( $kintone, $data ) {

		$url = sprintf(
			'https://%s/k/v1/records.json',
			$kintone['domain']
		);

		if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
			$headers = self::get_request_headers( '', '', $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
		} else {
			$headers = self::get_request_headers( '', '', $kintone['token'] );
		}
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$headers['Content-Type'] = 'application/json';

		$body = array(
			'app'     => $kintone['app'],
			'records' => $data,
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
		} elseif ( 200 !== $res['response']['code'] ) {
			$message = json_decode( $res['body'], true );
			$e       = new \WP_Error();
			$e->add( 'validation-error', $message['message'], $message );

			return $e;
		} else {
			return true;
		}
	}

	/**
	 * Delete records from Kintone API.
	 *
	 * @param  array  $kintone  Shortcode attributes it need for auth.
	 *  $kintone['domain'] .
	 *  $kintone['token'] .
	 *  $kintone['basic_auth_user'] .
	 *  $kintone['basic_auth_pass'] .
	 *  $kintone['app'] .
	 * @param  string $ids  IDs of records to delete.
	 * @return array true or WP_Error object
	 * @since  0.1
	 */
	public static function delete( $kintone, $ids ) {

		$url = sprintf(
			'https://%s/k/v1/records.json',
			$kintone['domain']
		);

		if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
			$headers = self::get_request_headers( '', '', $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
		} else {
			$headers = self::get_request_headers( '', '', $kintone['token'] );
		}
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$headers['Content-Type'] = 'application/json';

		$body = array(
			'app' => $kintone['app'],
			'ids' => $ids,
		);

		$res = wp_remote_post(
			$url,
			array(
				'method'  => 'DELETE',
				'headers' => $headers,
				'body'    => json_encode( $body ),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		} elseif ( 200 !== $res['response']['code'] ) {
			$message = json_decode( $res['body'], true );
			$e       = new \WP_Error();
			$e->add( 'validation-error', $message['message'], $message );

			return $e;
		} else {
			return true;
		}
	}

	/**
	 * Update records from Kintone API.
	 *
	 * @param  array  $kintone  Shortcode attributes it need for auth.
	 *  $kintone['domain'] .
	 *  $kintone['token'] .
	 *  $kintone['basic_auth_user'] .
	 *  $kintone['basic_auth_pass'] .
	 *  $kintone['app'] .
	 * @param  string $data  $_POST data.
	 * @param  array  $update_key_date  Update key date.
	 * @return array true or WP_Error object
	 * @since  0.1
	 */
	public static function put( $kintone, $data, $update_key_date = array() ) {

		$url = sprintf(
			'https://%s/k/v1/record.json',
			$kintone['domain']
		);

		if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
			$headers = self::get_request_headers( '', '', $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
		} else {
			$headers = self::get_request_headers( '', '', $kintone['token'] );
		}
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$headers['Content-Type'] = 'application/json';

		if ( empty( $update_key_date ) ) {
			$body = array(
				'app'    => $kintone['app'],
				'id'     => $kintone['id'],
				'record' => $data,
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

	/**
	 * Update records from Kintone API.
	 *
	 * @param  array  $kintone  Shortcode attributes it need for auth.
	 *  $kintone['domain'] .
	 *  $kintone['token'] .
	 *  $kintone['basic_auth_user'] .
	 *  $kintone['basic_auth_pass'] .
	 *  $kintone['app'] .
	 * @param  string $data  $_POST data.
	 * @return array true or WP_Error object
	 * @since  0.1
	 */
	public static function puts( $kintone, $data ) {

		$url = sprintf(
			'https://%s/k/v1/records.json',
			$kintone['domain']
		);

		if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
			$headers = self::get_request_headers( '', '', $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
		} else {
			$headers = self::get_request_headers( '', '', $kintone['token'] );
		}
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$headers['Content-Type'] = 'application/json';
		$body                    = array(
			'app'     => $kintone['app'],
			'records' => $data,
		);

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

	/**
	 * Get attachement file from Kintone API.
	 *
	 * @param  array $kintone  Shortcode attributes it need for auth.
	 * $kintone['domain'] .
	 * $kintone['token'] .
	 * $kintone['basic_auth_user'] .
	 * $kintone['basic_auth_pass'] .
	 * @param  array $file_info  File info.
	 * $file_info['fileKey'] .
	 * @return array true or WP_Error object
	 * @since  0.1
	 */
	public static function get_attachement_file( $kintone, $file_info ) {
		$url = sprintf(
			'https://%s/k/v1/file.json?fileKey=%s',
			$kintone['domain'],
			$file_info['fileKey']
		);

		if ( isset( $kintone['basic_auth_user'] ) && isset( $kintone['basic_auth_pass'] ) ) {
			$headers = self::get_request_headers( '', '', $kintone['token'], $kintone['basic_auth_user'], $kintone['basic_auth_pass'] );
		} else {
			$headers = self::get_request_headers( '', '', $kintone['token'] );
		}
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$headers['Content-Type'] = $file_info['fileKey'];

		$res = wp_remote_post(
			$url,
			array(
				'method'  => 'GET',
				'headers' => $headers,
			)
		);

		return $res['body'];
	}

	/**
	 * Get attachement file key from Kintone API.
	 *
	 * @param  array $kintone  Shortcode attributes it need for auth.
	 * $kintone['domain'] .
	 * $kintone['token'] .
	 * $kintone['basic_auth_user'] .
	 * $kintone['basic_auth_pass'] .
	 * @return array true or WP_Error object
	 * @since  0.1
	 */
	public static function get_attachement_file_key( $kintone ) {
		$file_path = $_FILES['file']['tmp_name'];
		$file_name = $_FILES['file']['name'];
		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $file_path );
		$file_data = file_get_contents( $file_path );
		finfo_close( $finfo );

		$boundary = '----' . microtime( true );
		$body     = '--' . $boundary . "\r\n" . 'Content-Disposition: form-data; name="file"; filename="' . $file_name . '"' . "\r\n" . 'Content-Type: ' . $mime_type . "\r\n\r\n" . $file_data . "\r\n" . '--' . $boundary . '--';

		$request_url = sprintf(
			'https://%s/k/v1/file.json',
			$kintone['domain']
		);

		$res = wp_remote_post(
			$request_url,
			array(
				'headers' => array(
					'Content-Type'       => "multipart/form-data; boundary={$boundary}",
					'X-Cybozu-API-Token' => $kintone['token'],
					'Content-Length'     => strlen( $body ),
				),
				'body'    => $body,
			)
		);

		return $res['body'];
	}

	/**
	 * Get attachement file key from Kintone API.
	 *
	 * @param  array  $kintone  Shortcode attributes it need for auth.
	 *  $kintone['domain'] .
	 *  $kintone['token'] .
	 *  $kintone['basic_auth_user'] .
	 *  $kintone['basic_auth_pass'] .
	 * @param  string $url  URL of file.
	 * @param  string $file_name  File name.
	 * @return array true or WP_Error object
	 * @since  0.1
	 */
	public static function get_attachement_file_key_from_url( $kintone, $url, $file_name ) {
		$file_data = file_get_contents( $url );
		$enc_file  = base64_encode( $file_data );
		$img_info  = getimagesize( 'data:application/octet-stream;base64,' . $enc_file );

		$boundary = '----' . microtime( true );
		$body     = '--' . $boundary . "\r\n" . 'Content-Disposition: form-data; name="file"; filename="' . $file_name . '"' . "\r\n" . 'Content-Type: ' . $img_info['mime'] . "\r\n\r\n" . $file_data . "\r\n" . '--' . $boundary . '--';

		$request_url = sprintf(
			'https://%s/k/v1/file.json',
			$kintone['domain']
		);

		$res      = wp_remote_post(
			$request_url,
			array(
				'headers' => array(
					'Content-Type'       => "multipart/form-data; boundary={$boundary}",
					'X-Cybozu-API-Token' => $kintone['token'],
					'Content-Length'     => strlen( $body ),
				),
				'body'    => $body,
			)
		);
		$file_key = json_decode( $res['body'], true );

		return $file_key;
	}
}
