<?php

namespace NewfoldLabs\WP\Module\Adam;

use function NewfoldLabs\WP\ModuleLoader\container as getContainer;

/**
 * Feature registration for the Adam module.
 */
class AdamFeature extends \NewfoldLabs\WP\Module\Features\Feature {

	/**
	 * The feature name.
	 *
	 * @var string
	 */
	protected $name = 'adam';

	/**
	 * The feature value. Defaults to on.
	 *
	 * @var bool
	 */
	protected $value = true;

	/**
	 * Initialize Adam feature.
	 */
	public function initialize() {
		if ( function_exists( 'add_action' ) ) {
			add_action(
				'plugins_loaded',
				function () {
					new Adam( getContainer() );
				}
			);
		}
	}
}
