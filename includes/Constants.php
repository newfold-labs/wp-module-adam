<?php

namespace NewfoldLabs\WP\Module\Adam;

use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * Manages constants for the Adam module (build dir/url for frontend assets).
 */
class Constants {

	/**
	 * Constructor.
	 *
	 * @param Container $container The module container.
	 */
	public function __construct( Container $container ) {
		if ( ! defined( 'NFD_ADAM_BUILD_DIR' ) ) {
			define( 'NFD_ADAM_BUILD_DIR', dirname( __DIR__, 1 ) . '/build' );
		}

		if ( ! defined( 'NFD_ADAM_BUILD_URL' ) ) {
			define( 'NFD_ADAM_BUILD_URL', $container->plugin()->url . 'vendor/newfold-labs/wp-module-adam/build' );
		}
	}
}
