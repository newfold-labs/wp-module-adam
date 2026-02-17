<?php

namespace NewfoldLabs\WP\Module\Adam\RestApi\Request;

use NewfoldLabs\WP\Module\Adam\Config;
use NewfoldLabs\WP\Module\Adam\Helpers\InstalledPluginsHelper;
use NewfoldLabs\WP\Module\Adam\Helpers\ProdInstIdResolver;
use NewfoldLabs\WP\Module\Adam\Helpers\TempDomainHelper;
use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * Builds the request body payload for the Adam getXSell API.
 */
class AdamRequestBuilder {

	/**
	 * Container for brand/context.
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
	 * Build request body for Adam getXSell API.
	 *
	 * Populated from WordPress / Hiive / Config:
	 * - siteUrl, tempDomain, prodInstId, plugins from helpers
	 * - env, isLoggedIn from WordPress; currencyCode from Config
	 * - brand, channel, responseType, etc. from Config
	 *
	 * @param string $container_name Container name (e.g. AMHPCardsV2).
	 * @return array<string, mixed>
	 */
	public function build( $container_name ) {
		$env = wp_get_environment_type();
		if ( '' === $env ) {
			$env = Config::get_default_env();
		}

		$prod_inst_resolver = new ProdInstIdResolver();

		return array(
			'containerName'  => $container_name ? $container_name : Config::get_default_container(),
			'brand'          => Config::get_brand( $this->container ),
			'env'            => $env,
			'channel'        => Config::get_channel(),
			'responseType'   => Config::get_response_type(),
			'siteUrl'        => home_url(),
			'tempDomain'     => TempDomainHelper::is_temp_domain(),
			'prodInstId'     => $prod_inst_resolver->get(),
			'plugins'        => InstalledPluginsHelper::get_list(),
			'userId'         => null,
			'countryCode'    => Config::get_default_country_code(),
			'currencyCode'   => Config::get_default_currency(),
			'isLoggedIn'     => is_user_logged_in(),
			'isLargeUser'    => false,
			'cart'           => array(),
			'reDirectToPage' => Config::get_redirect_to_page(),
			'isFirstLogin'   => null,
		);
	}
}
