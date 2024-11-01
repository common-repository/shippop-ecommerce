<?php

class SPECM_Utility {

	public function __construct() {
	}

	function specm_select_SetEnvironment() {
		$is_sandbox   = get_option( 'specm_is_sandbox', 'Y' );
		$specm_server = get_option( 'specm_server', 'TH' );

		if ( strtoupper( $specm_server ) === 'TH' ) {
			$server = array(
				'dev' => 'https://mkpservice.shippop.dev',
				'prd' => 'https://mkpservice.shippop.com',
			);
		} else {
			$server = array(
				'dev' => 'https://mkpservice.mhoopay.com',
				'prd' => 'https://mkpservice.shippop.my',
			);
		}

		if ( strtoupper( $is_sandbox ) === 'Y' ) {
			return $server['dev'];
		} else {
			return $server['prd'];
		}
	}

	/**
	 * @param mixed $url
	 * @param mixed $post_data
	 * @param bool  $get_body
	 *
	 * @return void
	 */
	public function specm_post( $url, $post_data , $type = 'json' , $includeBearer = true ) {
		if ( strpos( $url, 'http' ) === false ) {
			$url = $this->specm_select_SetEnvironment() . '' . $url;
		}

		$contentType = ($type == 'json') ? 'application/json': 'application/x-www-form-urlencoded';
		$headers = [];
		$headers['Content-Type'] = $contentType;
		if ( $includeBearer ) {
			$headers['Authorization'] = 'Bearer ' . get_option( 'specm_bearer', '' );
		}
		$response = wp_remote_post(
			$url,
			array(
				'method'   => 'POST',
				'timeout'  => 90,
				'blocking' => true,
				'headers'  => $headers,
				'body'     => ( $contentType == 'application/json' ) ? wp_json_encode( $post_data ) : http_build_query( $post_data ) ,
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return array(
				'status'  => false,
				'message' => $error_message,
			);
		} else {
			if ( $response['response']['code'] == '200' ) {
				$body = json_decode( $response['body'], true );
				return $body;
			} else {
				return array(
					'status'  => false,
					'message' => json_encode( $response ),
				);
			}
		}
	}

	/**
	 * @param string $url
	 * @param string $email
	 * @param string $password
	 * @param bool  $get_body
	 *
	 * @return void
	 */
	public function specm_get( $url, $email = '' , $password = '' ) {
		$response = wp_remote_get(
			$url,
			array(
				'method'   => 'GET',
				'timeout'  => 90,
				'blocking' => true,
				'headers'  => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . base64_encode( $email . ':' . $password )
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return array(
				'status'  => false,
				'message' => $error_message,
			);
		} else {
			if ( $response['response']['code'] == '200' ) {
				$body = json_decode( $response['body'], true );
				return $body;
			} else {
				return array(
					'status'  => false,
					'message' => json_encode( $response ),
				);
			}
		}
	}
}