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
		'default_container'    => 'AMHPCardsV2',
		'channel'              => 'Web',
		'response_type'        => 'frag',
		'default_country_code' => 'US',
		'redirect_to_page'     => 'AM',
		'default_currency'     => 'USD',
		'default_env'          => 'production',
		'request_timeout'      => 30,
		'temp_domain_suffixes' => array( 'mybluehost.me' ),
		'default_hiive_url'    => 'https://hiive.cloud/api',
		'hiive_customer_path'  => '/sites/v1/customer',
		'default_brand'        => 'bluehost',
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
	 * Adam getXSell API URL.
	 *
	 * @return string
	 */
	public static function get_api_url() {
		return (string) self::get( 'api_url' );
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
