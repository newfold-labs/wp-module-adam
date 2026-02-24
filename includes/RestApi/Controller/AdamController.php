<?php

namespace NewfoldLabs\WP\Module\Adam\RestApi\Controller;

use NewfoldLabs\WP\Module\Adam\AdamItemCache;
use NewfoldLabs\WP\Module\Adam\Config;
use NewfoldLabs\WP\Module\Adam\RestApi\Permissions;
use NewfoldLabs\WP\ModuleLoader\Container;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API controller for Adam (getXSell) cross-sell content.
 *
 * Delegates request building, API call, and response sanitization to dedicated classes.
 */
class AdamController extends WP_REST_Controller {

	/**
	 * REST namespace. Set from Config in register_routes.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Container.
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
		$this->namespace = Config::get_rest_namespace();
	}

	/**
	 * Registers the items route.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/items',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( Permissions::class, 'can_access_items' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * GET handler: return cached items for the current user. If cache is empty, lazy-fill once then return.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_REST_Response( array( 'response' => array() ), 200 );
		}

		$items = AdamItemCache::get_cached_items( $user_id );

		if ( null === $items ) {
			$items = AdamItemCache::refresh_cache_for_user( $user_id );
		}

		return new WP_REST_Response( array( 'response' => $items ), 200 );
	}
}
