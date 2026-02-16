<?php

namespace NewfoldLabs\WP\Module\Adam;

use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * REST API controller for Adam (getXSell) cross-sell content.
 */
class RestController extends \WP_REST_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'newfold-adam/v1';

	/**
	 * Adam getXSell API URL.
	 *
	 * @var string
	 */
	const ADAM_API_URL = 'https://adam.bluehost.com/api/v1/getXSell';

	/**
	 * Transient cache TTL in seconds (30 minutes).
	 *
	 * @var int
	 */
	const CACHE_TTL = 1800;

	/**
	 * Transient key prefix.
	 *
	 * @var string
	 */
	const CACHE_KEY_PREFIX = 'nfd_adam_';

	/**
	 * Default request timeout in seconds.
	 *
	 * @var int
	 */
	const ADAM_REQUEST_TIMEOUT = 30;

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
	}

	/**
	 * Registers the containers route.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/containers',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'container' => array(
							'type'              => 'string',
							'default'           => 'AMHPCardsV2',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * GET handler: return cached items or fetch from Adam and cache.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$container = $request->get_param( 'container' );
		$cache_key = self::CACHE_KEY_PREFIX . $container . '_hardcoded';

		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return new \WP_REST_Response( array( 'response' => $cached ), 200 );
		}

		$timeout   = apply_filters( 'nfd_adam_timeout', self::ADAM_REQUEST_TIMEOUT );
		$sslverify = apply_filters( 'nfd_adam_sslverify', wp_get_environment_type() !== 'local' );
		$body      = $this->build_request_body( $container );
		$args      = array(
			'timeout'   => absint( $timeout ),
			'sslverify' => $sslverify,
			'headers'   => array(
				'Content-Type' => 'application/json',
			),
			'body'      => wp_json_encode( $body ),
		);

		$raw = wp_remote_post( self::ADAM_API_URL, $args );

		if ( is_wp_error( $raw ) ) {
			return new \WP_REST_Response(
				array(
					'code'    => 'adam_request_failed',
					'message' => $raw->get_error_message(),
				),
				502
			);
		}

		$code = wp_remote_retrieve_response_code( $raw );
		$body_response = wp_remote_retrieve_body( $raw );
		$data = json_decode( $body_response, true );

		if ( 200 !== $code || ! is_array( $data ) ) {
			return new \WP_REST_Response(
				array(
					'code'    => 'adam_invalid_response',
					'message' => __( 'Invalid response from cross-sell service.', 'wp-module-adam' ),
				),
				502
			);
		}

		// Adam returns 200 with { "error": true } when e.g. jarvis times out.
		if ( ! empty( $data['error'] ) ) {
			set_transient( $cache_key, array(), self::CACHE_TTL );
			return new \WP_REST_Response( array( 'response' => array() ), 200 );
		}

		if ( ! isset( $data['status'] ) || 'success' !== $data['status'] || ! isset( $data['response'] ) || ! is_array( $data['response'] ) ) {
			set_transient( $cache_key, array(), self::CACHE_TTL );
			return new \WP_REST_Response( array( 'response' => array() ), 200 );
		}

		$items = $this->sanitize_response_items( $data['response'] );
		set_transient( $cache_key, $items, self::CACHE_TTL );

		return new \WP_REST_Response( array( 'response' => $items ), 200 );
	}

	/**
	 * Build request body for Adam getXSell API.
	 *
	 * @param string $container_name Container name (e.g. AMHPCardsV2).
	 * @return array
	 */
	protected function build_request_body( $container_name ) {
		return array(
			'containerName'  => $container_name ? $container_name : 'AMHPCardsV2',
			'brand'          => 'BLUEHOST',
			'env'            => 'qa',
			'channel'        => 'Web',
			'responseType'   => 'frag',
			'userId'         => 549174251,
			'countryCode'    => 'AF',
			'currencyCode'   => 'USD',
			'isLoggedIn'     => true,
			'isLargeUser'    => false,
			'cart'           => array(),
			'reDirectToPage' => 'AM',
			'isFirstLogin'   => null,
		);
	}

	/**
	 * Sanitize each item's productMarkup with wp_kses.
	 *
	 * @param array $items Raw response items from Adam.
	 * @return array Items with sanitized bodyContent/htmlString.
	 */
	protected function sanitize_response_items( array $items ) {
		$allowed_html = $this->get_allowed_html_for_frag();
		$out          = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['adDetails']['productMarkup'] ) ) {
				continue;
			}
			$markup = &$item['adDetails']['productMarkup'];
			if ( ! empty( $markup['bodyContent'] ) && is_string( $markup['bodyContent'] ) ) {
				$markup['bodyContent'] = $this->strip_cloudflare_scripts( $markup['bodyContent'] );
				$markup['bodyContent'] = wp_kses( $markup['bodyContent'], $allowed_html );
			}
			if ( ! empty( $markup['htmlString'] ) && is_string( $markup['htmlString'] ) ) {
				$markup['htmlString'] = $this->strip_cloudflare_scripts( $markup['htmlString'] );
				$markup['htmlString'] = wp_kses( $markup['htmlString'], $allowed_html );
			}
			$out[] = $item;
		}

		return $out;
	}

	/**
	 * Remove Cloudflare challenge script blocks from HTML.
	 *
	 * @param string $html Raw HTML string.
	 * @return string HTML with CF challenge scripts removed.
	 */
	protected function strip_cloudflare_scripts( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		return preg_replace_callback(
			'/<script\b[^>]*>(.*?)<\/script>/si',
			function ( $match ) {
				$full = $match[0];
				if (
					false !== strpos( $full, '__CF$cv$params' ) ||
					false !== strpos( $full, 'challenge-platform' ) ||
					false !== strpos( $full, 'cdn-cgi/challenge-platform' )
				) {
					return '';
				}
				return $full;
			},
			$html
		);
	}

	/**
	 * Allowed HTML for fragment content.
	 *
	 * @return array
	 */
	protected function get_allowed_html_for_frag() {
		return array(
			'div'    => array(
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'span'   => array(
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'a'      => array(
				'class'                => true,
				'href'                 => true,
				'target'               => true,
				'rel'                  => true,
				'data-element-type'   => true,
				'data-outcome'         => true,
				'data-description'     => true,
				'adtrackingbannerid'   => true,
			),
			'img'    => array(
				'src'    => true,
				'alt'    => true,
				'class'  => true,
				'width'  => true,
				'height' => true,
			),
			'p'      => array( 'class' => true, 'style' => true ),
			'style'  => array(),
			'link'   => array(
				'rel'  => true,
				'href' => true,
			),
			'script' => array(
				'type' => true,
				'src'  => true,
			),
		);
	}

	/**
	 * Permission check for routes.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to access this endpoint.', 'wp-module-adam' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}
