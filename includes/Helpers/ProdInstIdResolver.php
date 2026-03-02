<?php

namespace NewfoldLabs\WP\Module\Adam\Helpers;

use NewfoldLabs\WP\Module\Adam\Config;
use NewfoldLabs\WP\Module\Data\HiiveConnection;

/**
 * Resolves prodInstId (customer_id) from Hiive customer API with transient cache.
 */
class ProdInstIdResolver {

	/**
	 * Transient key for prodInstId cache.
	 *
	 * @var string
	 */
	const TRANSIENT_KEY = 'nfd_adam_prod_inst_id';

	/**
	 * Transient TTL in seconds (12 hours), to reduce Hiive API calls.
	 *
	 * @var int
	 */
	const CACHE_TTL = 43200;

	/**
	 * Get prodInstId (customer_id) from cache or Hiive customer API.
	 *
	 * Uses wp-module-data HiiveConnection when available. Returns null if not connected or on failure.
	 *
	 * @return string|null Customer ID or null if unavailable.
	 */
	public function get() {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( false !== $cached && is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		if ( ! class_exists( 'NewfoldLabs\WP\Module\Data\HiiveConnection' ) ) {
			return null;
		}

		if ( ! HiiveConnection::is_connected() ) {
			return null;
		}

		$token = HiiveConnection::get_auth_token();
		if ( ! $token ) {
			return null;
		}

		$url = Config::get_hiive_url() . Config::get_hiive_customer_path();

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || empty( $data['customer_id'] ) || ! is_string( $data['customer_id'] ) ) {
			return null;
		}

		$customer_id = $data['customer_id'];
		set_transient( self::TRANSIENT_KEY, $customer_id, self::CACHE_TTL );

		return $customer_id;
	}
}
