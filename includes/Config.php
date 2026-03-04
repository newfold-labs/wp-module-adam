<?php

namespace NewfoldLabs\WP\Module\Adam;

use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * Configuration for the Adam module. Single array of defaults; getters return values.
 */
class Config {

	/**
	 * Default configuration.
	 *
	 * @var array<string, mixed>
	 */
	private static $config = array(
		'rest_namespace'       => 'newfold-adam/v1',
		'api_url'              => 'https://adam.bluehost.com/api/v1/getXSell',
		'default_container'    => 'WPAdmin',
		'channel'              => 'Web',
		'response_type'        => 'frag',
		'default_country_code' => 'US',
		'redirect_to_page'     => 'AM',
		'default_currency'     => 'USD',
		'default_env'          => 'prod',
		'request_timeout'      => 30,
		'temp_domain_suffixes' => array( 'mybluehost.me' ),
		'default_hiive_url'    => 'https://hiive.cloud/api',
		'hiive_customer_path'  => '/sites/v1/customer',
		'default_brand'        => 'bluehost',
	);

	/**
	 * Map WordPress environment type (wp_get_environment_type) to Adam API env values.
	 * Adam accepts: qa, stg, prod, development.
	 *
	 * @var array<string, string>
	 */
	private static $env_map = array(
		'production'  => 'prod',
		'staging'     => 'prod',
		'development' => 'development',
		'local'       => 'development',
	);

	/**
	 * Default hostnames allowed for the Adam API URL (NFD_ADAM_URL or filter override).
	 * Prevents sending sensitive data (brand, plugins, customer_id, site URL) to untrusted hosts.
	 *
	 * @var array<int, string>
	 */
	private static $default_allowed_adam_hosts = array(
		'adam.bluehost.com',
		'global-nfd-nfd-adserver-bh.apps.atlanta1.newfoldmb.com',
	);

	/**
	 * Get a config value by key.
	 *
	 * @param string $key Config key.
	 * @return mixed
	 */
	private static function get( $key ) {
		return isset( self::$config[ $key ] ) ? self::$config[ $key ] : null;
	}

	/**
	 * REST API namespace (e.g. newfold-adam/v1).
	 *
	 * @return string
	 */
	public static function get_rest_namespace() {
		return (string) self::get( 'rest_namespace' );
	}

	/**
	 * Allowed hostnames for the Adam API URL. Filterable for custom environments.
	 *
	 * @return array<int, string>
	 */
	public static function get_allowed_adam_api_hosts() {
		$hosts = self::$default_allowed_adam_hosts;
		return array_values( array_filter( (array) apply_filters( 'nfd_adam_allowed_api_hosts', $hosts ) ) );
	}

	/**
	 * Validates that a URL is HTTPS and its host is in the allowed list.
	 *
	 * @param string $url Adam API URL (e.g. from NFD_ADAM_URL).
	 * @return bool True if URL is safe to use.
	 */
	public static function is_valid_adam_api_url( $url ) {
		if ( ! is_string( $url ) || '' === trim( $url ) ) {
			return false;
		}
		$parsed = wp_parse_url( trim( $url ) );
		if ( empty( $parsed['host'] ) || empty( $parsed['scheme'] ) ) {
			return false;
		}
		if ( 'https' !== strtolower( $parsed['scheme'] ) ) {
			return false;
		}
		$host    = strtolower( $parsed['host'] );
		$allowed = array_map( 'strtolower', self::get_allowed_adam_api_hosts() );
		return in_array( $host, $allowed, true );
	}

	/**
	 * Adam getXSell API URL. Uses NFD_ADAM_URL constant if defined and valid (see is_valid_adam_api_url),
	 * otherwise config default. Rejects invalid custom URLs to avoid sending sensitive data to untrusted hosts.
	 * Final URL is filterable via nfd_adam_api_url.
	 *
	 * @return string
	 */
	public static function get_api_url() {
		$url = (string) self::get( 'api_url' );
		if ( defined( 'NFD_ADAM_URL' ) ) {
			$constant_url = NFD_ADAM_URL;
			if ( self::is_valid_adam_api_url( $constant_url ) ) {
				$url = $constant_url;
			} else {
				do_action( 'nfd_adam_invalid_api_url_rejected', $constant_url );
			}
		}
		$filtered = apply_filters( 'nfd_adam_api_url', $url );
		if ( is_string( $filtered ) && self::is_valid_adam_api_url( $filtered ) ) {
			return $filtered;
		}
		return $url;
	}

	/**
	 * Test offer codes for getXSell (QA). Returns array only when NFD_ADAM_TEST_OFFERS is defined and is array; otherwise null (omit key from payload).
	 *
	 * @return array<int, string>|null
	 */
	public static function get_test_offers() {
		if ( defined( 'NFD_ADAM_TEST_OFFERS' ) && is_array( NFD_ADAM_TEST_OFFERS ) ) {
			return NFD_ADAM_TEST_OFFERS;
		}
		return null;
	}

	/**
	 * Default container name for getXSell requests.
	 *
	 * @return string
	 */
	public static function get_default_container() {
		return (string) self::get( 'default_container' );
	}

	/**
	 * Brand identifier sent to Adam (e.g. BLUEHOST). From container or config default.
	 *
	 * @param Container $container Module container.
	 * @return string
	 */
	public static function get_brand( Container $container ) {
		$plugin = $container->plugin();
		$brand  = ( is_object( $plugin ) && isset( $plugin->brand ) ) ? $plugin->brand : self::get( 'default_brand' );
		return strtoupper( sanitize_title( $brand ) );
	}

	/**
	 * Channel value for getXSell (e.g. Web).
	 *
	 * @return string
	 */
	public static function get_channel() {
		return (string) self::get( 'channel' );
	}

	/**
	 * Response type for getXSell (e.g. frag).
	 *
	 * @return string
	 */
	public static function get_response_type() {
		return (string) self::get( 'response_type' );
	}

	/**
	 * Default country code when not available from context.
	 *
	 * @return string
	 */
	public static function get_default_country_code() {
		return (string) self::get( 'default_country_code' );
	}

	/**
	 * Redirect-to-page value for getXSell (e.g. AM).
	 *
	 * @return string
	 */
	public static function get_redirect_to_page() {
		return (string) self::get( 'redirect_to_page' );
	}

	/**
	 * Default currency when WooCommerce is not active.
	 *
	 * @return string
	 */
	public static function get_default_currency() {
		return (string) self::get( 'default_currency' );
	}

	/**
	 * Default environment when wp_get_environment_type() is empty.
	 *
	 * @return string
	 */
	public static function get_default_env() {
		return (string) self::get( 'default_env' );
	}

	/**
	 * Environment type for getXSell. Maps wp_get_environment_type() to Adam API values (qa, stg, prod, development).
	 *
	 * @return string One of: qa, stg, prod, development.
	 */
	public static function get_env() {
		$wp_env = wp_get_environment_type();
		if ( '' === $wp_env ) {
			return self::get_default_env();
		}
		return isset( self::$env_map[ $wp_env ] ) ? self::$env_map[ $wp_env ] : self::get_default_env();
	}

	/**
	 * Default request timeout in seconds for Adam API.
	 *
	 * @return int
	 */
	public static function get_request_timeout() {
		return (int) self::get( 'request_timeout' );
	}

	/**
	 * Hostname suffixes that indicate a temp domain (e.g. mybluehost.me).
	 *
	 * @return array<int, string>
	 */
	public static function get_temp_domain_suffixes() {
		$suffixes = self::get( 'temp_domain_suffixes' );
		return is_array( $suffixes ) ? $suffixes : array();
	}

	/**
	 * Hiive API base URL. Uses NFD_HIIVE_URL constant if defined, otherwise default_hiive_url from config.
	 *
	 * @return string
	 */
	public static function get_hiive_url() {
		if ( defined( 'NFD_HIIVE_URL' ) ) {
			return NFD_HIIVE_URL;
		}
		return (string) self::get( 'default_hiive_url' );
	}

	/**
	 * Hiive customer API path (e.g. /sites/v1/customer).
	 *
	 * @return string
	 */
	public static function get_hiive_customer_path() {
		return (string) self::get( 'hiive_customer_path' );
	}
}
