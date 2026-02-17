<?php

namespace NewfoldLabs\WP\Module\Adam\RestApi;

use WP_Error;

/**
 * Permission checks for Adam REST routes.
 */
class Permissions {

	/**
	 * Check if the current user can access Adam containers endpoint.
	 *
	 * @return true|WP_Error True if allowed, WP_Error otherwise.
	 */
	public static function can_access_containers() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to access this endpoint.', 'wp-module-adam' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}
