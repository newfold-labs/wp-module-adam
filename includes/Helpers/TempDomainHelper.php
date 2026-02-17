<?php

namespace NewfoldLabs\WP\Module\Adam\Helpers;

use NewfoldLabs\WP\Module\Adam\Config;

/**
 * Helper for temp domain detection (suffix from Config).
 */
class TempDomainHelper {

	/**
	 * Whether the site is on a temp domain (hostname ends with any configured suffix).
	 *
	 * Uses the host from home_url() only; paths do not affect the result.
	 *
	 * @return bool
	 */
	public static function is_temp_domain() {
		$url  = home_url();
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}
		$host     = strtolower( $host );
		$suffixes = Config::get_temp_domain_suffixes();
		foreach ( $suffixes as $suffix ) {
			if ( is_string( $suffix ) && '' !== $suffix && substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}
		return false;
	}
}
