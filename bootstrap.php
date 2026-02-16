<?php

namespace NewfoldLabs\WP\Module\Adam;

if ( ! defined( 'NFD_ADAM_DIR' ) ) {
	define( 'NFD_ADAM_DIR', __DIR__ );
}

if ( function_exists( 'add_filter' ) ) {
	add_filter(
		'newfold/features/filter/register',
		function ( $features ) {
			return array_merge( $features, array( AdamFeature::class ) );
		}
	);
}
