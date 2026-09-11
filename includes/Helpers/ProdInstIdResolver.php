<?php

namespace NewfoldLabs\WP\Module\Adam\Helpers;

use NewfoldLabs\WP\Module\Adam\Config;
use NewfoldLabs\WP\Module\Data\HiiveConnection;

/**
 * Resolves prodInstId (customer_id) from Hiive customer API, caching both the answer and a
 * recent failure so a Hiive outage is not met with a fresh call on every request.
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
	 * Transient key remembering that a lookup just failed.
	 *
	 * @var string
	 */
	const FAILURE_TRANSIENT_KEY = 'nfd_adam_prod_inst_id_failed';

	/**
	 * How long to leave Hiive alone after a failed lookup, in seconds.
	 *
	 * @var int
	 */
	const FAILURE_CACHE_TTL = 900; // 15 minutes.

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

		// Only successes used to be cached, so while Hiive was unhappy every request that wanted the
		// id asked it again. Sit out a short spell after a failure instead.
		if ( get_transient( self::FAILURE_TRANSIENT_KEY ) ) {
			return null;
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
			return $this->remember_failure();
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return $this->remember_failure();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || empty( $data['customer_id'] ) || ! is_string( $data['customer_id'] ) ) {
			return $this->remember_failure();
		}

		$customer_id = $data['customer_id'];
		set_transient( self::TRANSIENT_KEY, $customer_id, self::CACHE_TTL );
		delete_transient( self::FAILURE_TRANSIENT_KEY );

		return $customer_id;
	}

	/**
	 * Note that a lookup just failed, so the next few requests do not repeat it.
	 *
	 * Only the failures that cost a call are worth remembering. Everything checked before the
	 * request is local, so those cost nothing to reach again.
	 *
	 * @return null
	 */
	private function remember_failure() {
		set_transient( self::FAILURE_TRANSIENT_KEY, 1, self::FAILURE_CACHE_TTL );

		return null;
	}
}
