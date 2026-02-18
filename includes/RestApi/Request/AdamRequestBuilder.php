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
	 * Payload keys: containerName, brand, env, channel, responseType, countryCode, currencyCode,
	 * isLoggedIn, isLargeUser, cart, reDirectToPage, siteUrl, locale, tempDomain, prodInstId, plugins.
	 * testOffers is included only when NFD_ADAM_TEST_OFFERS is defined.
	 *
	 * @param string $container_name Container name (e.g. WPAdmin, AMHPCardsV2).
	 * @return array<string, mixed>
	 */
	public function build( $container_name ) {
		$prod_inst_resolver = new ProdInstIdResolver();

		$payload = array(
			'containerName'   => $container_name ? $container_name : Config::get_default_container(),
			'brand'           => Config::get_brand( $this->container ),
			'env'             => Config::get_env(),
			'channel'         => Config::get_channel(),
			'responseType'    => Config::get_response_type(),
			'countryCode'     => Config::get_default_country_code(),
			'currencyCode'    => Config::get_default_currency(),
			'isLoggedIn'      => is_user_logged_in(),
			'isLargeUser'     => false,
			'cart'            => array(),
			'reDirectToPage'  => Config::get_redirect_to_page(),
			'siteUrl'         => home_url(),
			'locale'          => get_locale(),
			'tempDomain'      => TempDomainHelper::is_temp_domain(),
			'prodInstId'      => $prod_inst_resolver->get(),
			'plugins'         => InstalledPluginsHelper::get_list(),
		);

		$test_offers = Config::get_test_offers();
		if ( null !== $test_offers ) {
			$payload['testOffers'] = $test_offers;
		}

		return $payload;
	}
}
