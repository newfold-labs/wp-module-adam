<?php

namespace NewfoldLabs\WP\Module\Adam\RestApi\Request;

use NewfoldLabs\WP\Module\Adam\Config;
use WP_Error;

/**
 * Client for the Adam getXSell API (POST request and response handling).
 */
class AdamApiClient {

	/**
	 * POST to Adam getXSell API and return decoded JSON body.
	 *
	 * @param string $url  Adam API URL.
	 * @param array  $body Request body (will be JSON-encoded).
	 * @param array  $args Optional. wp_remote_post args (timeout, sslverify, etc.).
	 * @return array|WP_Error Decoded response array or WP_Error on failure.
	 */
	public function post( $url, array $body, array $args = array() ) {
		$timeout   = isset( $args['timeout'] ) ? absint( $args['timeout'] ) : Config::get_request_timeout();
		$sslverify = isset( $args['sslverify'] ) ? (bool) $args['sslverify'] : ( wp_get_environment_type() !== 'local' );

		$request_args = array(
			'timeout'   => $timeout,
			'sslverify' => $sslverify,
			'headers'   => array(
				'Content-Type' => 'application/json',
			),
			'body'      => wp_json_encode( $body ),
		);

		$response = wp_remote_post( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'adam_invalid_response',
				__( 'Invalid response from cross-sell service.', 'wp-module-adam' ),
				array( 'status' => 502 )
			);
		}

		$data['_http_code'] = $code;
		return $data;
	}
}
