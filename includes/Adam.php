<?php

namespace NewfoldLabs\WP\Module\Adam;

use NewfoldLabs\WP\ModuleLoader\Container;
use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * Adam (Ads and More) module: REST API for cross-sell content and frontend asset registration.
 */
class Adam {

	/**
	 * Script handle for the Adam frontend bundle.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'nfd-adam';

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container The module container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;

		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );

		new Constants( $container );
	}

	/**
	 * Register REST routes for the Adam API (containers/items proxy).
	 */
	public function register_rest_routes() {
		$controller = new RestController( $this->container );
		$controller->register_routes();
	}

	/**
	 * Register and enqueue Adam script only when the current page is the brand plugin's admin page.
	 * Uses the container's plugin id so the module works with any host (e.g. bluehost, hostgator).
	 */
	public static function register_assets() {
		$build_dir  = NFD_ADAM_BUILD_DIR;
		$build_url  = NFD_ADAM_BUILD_URL;
		$asset_file = $build_dir . '/adam/adam.min.asset.php';

		if ( is_readable( $asset_file ) ) {
			$asset       = include $asset_file;
			$deps        = isset( $asset['dependencies'] ) ? $asset['dependencies'] : array();
			$deps[]      = 'nfd-portal-registry';

			wp_register_script(
				self::SCRIPT_HANDLE,
				$build_url . '/adam/adam.min.js',
				$deps,
				isset( $asset['version'] ) ? $asset['version'] : '1.0.0',
				true
			);

			wp_register_style(
				self::SCRIPT_HANDLE,
				$build_url . '/adam/adam.min.css',
				array(),
				isset( $asset['version'] ) ? $asset['version'] : '1.0.0'
			);
		}

		$plugin_id = container()->plugin()->id;
		$screen    = get_current_screen();
		if ( $screen && isset( $screen->id ) && false !== strpos( $screen->id, $plugin_id ) ) {
			wp_enqueue_script( self::SCRIPT_HANDLE );
			wp_enqueue_style( self::SCRIPT_HANDLE );
		}
	}
}
