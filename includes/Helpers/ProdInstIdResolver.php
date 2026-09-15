<?php

namespace NewfoldLabs\WP\Module\Adam\Helpers;

use NewfoldLabs\WP\Module\Adam\Config;
use NewfoldLabs\WP\Module\Data\HiiveConnection;
use NewfoldLabs\WP\Module\Data\Helpers\Transient;

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
	 * Option holding the retry schedule after a failed lookup: how many have failed in a row, and
	 * the earliest time we may ask again.
	 *
	 * An option rather than a transient because the count has to outlive the wait it produces. A
	 * transient would expire along with the delay, so the count would reset to one every time and
	 * there would be no backoff at all.
	 *
	 * @var string
	 */
	const FAILURE_OPTION = 'nfd_adam_prod_inst_id_failure';

	/**
	 * Wait before the first retry, in seconds. Doubles per consecutive failure.
	 *
	 * @var int
	 */
	const FAILURE_RETRY_DELAY = 300; // 5 minutes.

	/**
	 * Ceiling for that wait, in seconds.
	 *
	 * @var int
	 */
	const FAILURE_RETRY_DELAY_MAX = 43200; // 12 hours.

	/**
	 * Cap on the stored failure count, which stops growing once the wait has reached its ceiling.
	 *
	 * @var int
	 */
	const FAILURE_MAX_COUNT = 16;

	/**
	 * Get prodInstId (customer_id) from cache or Hiive customer API.
	 *
	 * Uses wp-module-data HiiveConnection when available. Returns null if not connected or on failure.
	 *
	 * Caching goes through the wp-module-data transient helper rather than the core functions: on a
	 * site with an object-cache.php drop-in, get_transient() reads only that cache and never falls
	 * back to the database, so a broken or non-persistent one meant nothing was ever cached and
	 * every request asked Hiive again. See self::read_cache() for the older-wp-module-data case.
	 *
	 * @return string|null Customer ID or null if unavailable.
	 */
	public function get() {
		// Checked first because everything below needs wp-module-data, both to reach Hiive and to
		// store the answer. Without it there is nothing here to resolve.
		if ( ! class_exists( 'NewfoldLabs\WP\Module\Data\HiiveConnection' ) ) {
			return null;
		}

		$cached = $this->read_cache();
		if ( false !== $cached && is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		// Local checks come before the retry schedule is read, so a site that is not connected does
		// no cache work at all. Transient::get() is not free: it asks get_dropins() whether this
		// site has an object-cache drop-in, and that scans wp-content every time it is called.
		if ( ! HiiveConnection::is_connected() ) {
			return null;
		}

		$token = HiiveConnection::get_auth_token();
		if ( ! $token ) {
			return null;
		}

		// Only successes used to be cached, so while Hiive was unhappy every request that wanted the
		// id asked it again. Wait out the backoff instead.
		$failure = $this->read_failure_schedule();
		if ( time() < $failure['next'] ) {
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
			return $this->remember_failure( $failure );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return $this->remember_failure( $failure );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || empty( $data['customer_id'] ) || ! is_string( $data['customer_id'] ) ) {
			return $this->remember_failure( $failure );
		}

		$customer_id = $data['customer_id'];
		$this->write_cache( $customer_id );
		delete_option( self::FAILURE_OPTION );

		return $customer_id;
	}

	/**
	 * Read the cached id.
	 *
	 * Prefers the wp-module-data transient helper, because core get_transient() on a site with an
	 * object-cache.php drop-in reads only that cache and never falls back to the database. The
	 * helper is newer than the wp-module-data versions this module accepts, so fall back to the
	 * core functions rather than fatal where it is absent.
	 *
	 * @return mixed Cached value, or false when nothing is stored.
	 */
	private function read_cache() {
		if ( class_exists( Transient::class ) ) {
			return Transient::get( self::TRANSIENT_KEY );
		}

		return get_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Store the resolved id. See self::read_cache() for why the helper is preferred.
	 *
	 * @param string $customer_id Resolved customer id.
	 * @return void
	 */
	private function write_cache( $customer_id ) {
		if ( class_exists( Transient::class ) ) {
			Transient::set( self::TRANSIENT_KEY, $customer_id, self::CACHE_TTL );
			return;
		}

		set_transient( self::TRANSIENT_KEY, $customer_id, self::CACHE_TTL );
	}

	/**
	 * Read the retry schedule left by earlier failures.
	 *
	 * @return array{count:int, next:int}
	 */
	private function read_failure_schedule() {
		$stored = get_option( self::FAILURE_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array(
			'count' => isset( $stored['count'] ) ? max( 0, (int) $stored['count'] ) : 0,
			'next'  => isset( $stored['next'] ) ? (int) $stored['next'] : 0,
		);
	}

	/**
	 * Note that a lookup just failed and push the next attempt further out.
	 *
	 * Only the failures that cost a call are worth remembering. Everything checked before the
	 * request is local, so those cost nothing to reach again.
	 *
	 * The wait doubles because the sites that never resolve are the ones that would otherwise ask
	 * most often: a site Hiive has no customer record for, or one whose token was revoked, fails
	 * every time and would sit on a flat delay forever. A site that succeeds asks twice a day.
	 *
	 * @param array{count:int, next:int} $failure Schedule as it stood before this attempt.
	 * @return null
	 */
	private function remember_failure( array $failure ) {
		$count = min( $failure['count'] + 1, self::FAILURE_MAX_COUNT );
		$delay = (int) min( self::FAILURE_RETRY_DELAY * pow( 2, $count - 1 ), self::FAILURE_RETRY_DELAY_MAX );

		update_option(
			self::FAILURE_OPTION,
			array(
				'count' => $count,
				'next'  => time() + $delay,
			),
			false
		);

		return null;
	}
}
