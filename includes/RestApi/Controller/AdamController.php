<?php

namespace NewfoldLabs\WP\Module\Adam\RestApi\Controller;

use NewfoldLabs\WP\Module\Adam\Config;
use NewfoldLabs\WP\Module\Adam\RestApi\Permissions;
use NewfoldLabs\WP\Module\Adam\RestApi\Request\AdamApiClient;
use NewfoldLabs\WP\Module\Adam\RestApi\Request\AdamRequestBuilder;
use NewfoldLabs\WP\Module\Adam\RestApi\Response\AdamResponseSanitizer;
use NewfoldLabs\WP\ModuleLoader\Container;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

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
	 * GET handler: fetch from Adam and return items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$timeout   = Config::get_request_timeout();
		$sslverify = wp_get_environment_type() !== 'local';

		$request_builder = new AdamRequestBuilder( $this->container );
		$body            = $request_builder->build( Config::get_default_container() );

		$api_client = new AdamApiClient();
		$data       = $api_client->post(
			Config::get_api_url(),
			$body,
			array(
				'timeout'   => $timeout,
				'sslverify' => $sslverify,
			)
		);

		if ( is_wp_error( $data ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 'adam_request_failed',
					'message' => $data->get_error_message(),
				),
				502
			);
		}

		$code = isset( $data['_http_code'] ) ? $data['_http_code'] : 0;
		if ( 200 !== $code ) {
			return new WP_REST_Response(
				array(
					'code'    => 'adam_invalid_response',
					'message' => __( 'Invalid response from cross-sell service.', 'wp-module-adam' ),
				),
				502
			);
		}

		// Adam returns 200 with { "error": true } when e.g. jarvis times out.
		if ( ! empty( $data['error'] ) ) {
			return new WP_REST_Response( array( 'response' => array() ), 200 );
		}

		if ( ! isset( $data['status'] ) || 'success' !== $data['status'] || ! isset( $data['response'] ) || ! is_array( $data['response'] ) ) {
			return new WP_REST_Response( array( 'response' => array() ), 200 );
		}

		$sanitizer = new AdamResponseSanitizer();
		$items     = $sanitizer->sanitize( $data['response'] );

		return new WP_REST_Response( array( 'response' => $items ), 200 );
	}
}
