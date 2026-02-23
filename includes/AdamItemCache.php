<?php

namespace NewfoldLabs\WP\Module\Adam;

use NewfoldLabs\WP\Module\Adam\RestApi\Request\AdamApiClient;
use NewfoldLabs\WP\Module\Adam\RestApi\Request\AdamRequestBuilder;
use NewfoldLabs\WP\Module\Adam\RestApi\Response\AdamResponseSanitizer;
use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * Per-user cache for Adam cross-sell items. Refreshed on login; served by REST GET /items.
 */
class AdamItemCache {

	/**
	 * User meta key for cached items.
	 *
	 * @var string
	 */
	const USER_META_KEY = 'nfd_adam_items';

	/**
	 * Get cached Adam items for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array> Array of sanitized items, or empty array if none.
	 */
	public static function get_cached_items( $user_id ) {
		if ( ! $user_id || ! is_numeric( $user_id ) ) {
			return array();
		}
		$cached = get_user_meta( (int) $user_id, self::USER_META_KEY, true );
		return is_array( $cached ) ? $cached : array();
	}

	/**
	 * Fetch from Adam API, sanitize, and store in user meta. Only updates on success.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array> Sanitized items stored (or empty array on failure).
	 */
	public static function refresh_cache_for_user( $user_id ) {
		if ( ! $user_id || ! is_numeric( $user_id ) ) {
			return array();
		}
		$user_id = (int) $user_id;

		$container = container();
		$timeout   = Config::get_request_timeout();
		$sslverify = wp_get_environment_type() !== 'local';

		$request_builder = new AdamRequestBuilder( $container );
		$body            = $request_builder->build( Config::get_default_container() );

		$api_client = new AdamApiClient();
		$data       = $api_client->post(
			Config::get_api_url(),
			$body,
			array(
				'timeout'   => $timeout,
				'sslverify' => $sslverify,
			)
		);
		if ( is_wp_error( $data ) ) {
			return array();
		}

		$code = isset( $data['_http_code'] ) ? $data['_http_code'] : 0;
		if ( 200 !== $code ) {
			return array();
		}

		if ( ! empty( $data['error'] ) ) {
			$items = array();
		} elseif ( isset( $data['status'] ) && 'success' === $data['status'] && isset( $data['response'] ) && is_array( $data['response'] ) ) {
			$sanitizer = new AdamResponseSanitizer();
			$items     = $sanitizer->sanitize( $data['response'] );
		} else {
			$items = array();
		}

		update_user_meta( $user_id, self::USER_META_KEY, $items );
		return $items;
	}
}
