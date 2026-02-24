<?php

namespace NewfoldLabs\WP\Module\Adam;

use NewfoldLabs\WP\Module\Adam\RestApi\RestApi;
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

		new RestApi( $container );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_filter( 'newfold_runtime', array( __CLASS__, 'add_to_runtime' ) );
		add_action( 'wp_login', array( $this, 'on_login_refresh_adam_cache' ), 10, 2 );

		new Constants( $container );
	}

	/**
	 * On login (or SSO): refresh Adam items cache for the user so GET /items can serve from cache.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user      Logged-in user.
	 */
	public function on_login_refresh_adam_cache( $user_login, $user ) {
		if ( ! $user instanceof \WP_User || ! $user->ID ) {
			return;
		}
		if ( ! user_can( $user, 'manage_options' ) ) {
			return;
		}
		AdamItemCache::refresh_cache_for_user( $user->ID );
	}

	/**
	 * Add Adam REST namespace to NewfoldRuntime so the frontend can build the items URL without hardcoding.
	 *
	 * @param array<string, mixed> $runtime Runtime array passed by wp-module-runtime.
	 * @return array<string, mixed>
	 */
	public static function add_to_runtime( $runtime ) {
		$runtime['adam'] = array(
			'restNamespace' => Config::get_rest_namespace(),
		);
		return $runtime;
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
			$asset  = include $asset_file;
			$deps   = isset( $asset['dependencies'] ) ? $asset['dependencies'] : array();
			$deps[] = 'nfd-portal-registry';

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
