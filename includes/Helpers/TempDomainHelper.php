<?php

namespace NewfoldLabs\WP\Module\Adam\Helpers;

use NewfoldLabs\WP\Module\Adam\Config;

/**
 * Helper for temp domain detection and site hostname extraction.
 */
class TempDomainHelper {

	/**
	 * Get the site hostname (domain only) from the home URL.
	 *
	 * Extracts only the host component: no scheme, port, path, or query.
	 * Works for subdomains (e.g. sub.example.com), paths (e.g. example.com/site),
	 * and standard domains. Returns empty string if parsing fails.
	 *
	 * @return string Hostname (e.g. example.com, sub.example.com) or empty string.
	 */
	public static function get_site_hostname() {
		$url = home_url();
		if ( ! is_string( $url ) || '' === trim( $url ) ) {
			return '';
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === trim( $host ) ) {
			return '';
		}
		return strtolower( trim( $host ) );
	}

	/**
	 * Whether the site is on a temp domain (hostname ends with any configured suffix).
	 *
	 * Uses the host from home_url() only; paths do not affect the result.
	 *
	 * @return bool
	 */
	public static function is_temp_domain() {
		$host = self::get_site_hostname();
		if ( '' === $host ) {
			return false;
		}
		$suffixes = Config::get_temp_domain_suffixes();
		foreach ( $suffixes as $suffix ) {
			if ( is_string( $suffix ) && '' !== $suffix ) {
				$suffix = strtolower( trim( $suffix ) );
				if ( '' !== $suffix && substr( $host, -strlen( $suffix ) ) === $suffix ) {
					return true;
				}
			}
		}
		return false;
	}
}
