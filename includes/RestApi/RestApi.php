<?php

namespace NewfoldLabs\WP\Module\Adam\RestApi;

use NewfoldLabs\WP\Module\Adam\RestApi\Controller\AdamController;
use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * REST API registration. Registers Adam controllers on rest_api_init.
 */
class RestApi {

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		$controllers = array(
			new AdamController( $this->container ),
		);

		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}
}
