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
	 * Returns null when the cache has never been populated (meta key absent), so the
	 * caller can distinguish "not yet fetched" from "fetched but Adam returned 0 items"
	 * (which stores an empty array). This prevents the lazy-load from firing on every
	 * page load when Adam legitimately has no offers to show.
	 *
	 * get_user_meta with $single=true returns '' when the key doesn't exist, and []
	 * when the key was explicitly stored as an empty array.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array>|null Sanitized items, empty array if cached-but-empty, or null if never cached.
	 */
	public static function get_cached_items( $user_id ) {
		if ( ! $user_id || ! is_numeric( $user_id ) ) {
			return null;
		}
		$cached = get_user_meta( (int) $user_id, self::USER_META_KEY, true );
		if ( '' === $cached ) {
			return null; // Meta key absent — never been fetched.
		}
		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * Invalidate the cached items for a user by deleting the user meta key.
	 *
	 * Called on login so the next REST GET /items request fetches fresh data from Adam
	 * rather than serving a potentially stale cache. No API call is made here — the
	 * fetch is deferred to when the user actually visits the page.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function delete_cache_for_user( $user_id ) {
		if ( ! $user_id || ! is_numeric( $user_id ) ) {
			return;
		}
		delete_user_meta( (int) $user_id, self::USER_META_KEY );
	}

	/**
	 * Fetch from Adam API, sanitize, and store in user meta. Only updates on success.
	 *
	 * @param int      $user_id User ID.
	 * @param int|null $timeout Optional request timeout in seconds. Defaults to Config value (30s).
	 *                          Pass a lower value (e.g. 5) for login-time refreshes so the login
	 *                          request is not held up by a slow Adam API.
	 * @return array<int, array> Sanitized items stored (or empty array on failure).
	 */
	public static function refresh_cache_for_user( $user_id, $timeout = null ) {
		if ( ! $user_id || ! is_numeric( $user_id ) ) {
			return array();
		}
		$user_id = (int) $user_id;

		$container = container();
		$timeout   = null !== $timeout ? (int) $timeout : Config::get_request_timeout();
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
