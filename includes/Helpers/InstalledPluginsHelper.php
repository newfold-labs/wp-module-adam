<?php

namespace NewfoldLabs\WP\Module\Adam\Helpers;

/**
 * Helper for installed plugins list (e.g. for Adam request payload).
 */
class InstalledPluginsHelper {

	/**
	 * Get list of installed plugin basenames (keys from get_plugins()).
	 *
	 * Not cached so the list reflects current state after installs/activations.
	 *
	 * @return array<int, string> Plugin basenames.
	 */
	public static function get_list() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		return is_array( $all_plugins ) ? array_keys( $all_plugins ) : array();
	}
}
