<?php
/**
 * Code_Embeds_REST_Controller class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds;

use WP_Error;
use WP_REST_Posts_Controller;
use WP_REST_Request;

/**
 * REST controller for code embed entries.
 *
 * The core posts controller lets anyone read published entries of any post
 * type that is shown in REST. This controller limits reads to users who can
 * select entries in the picker block. Writes still go through the post type's
 * capabilities, which require {@see Code_Embed::CAPABILITY}.
 */
class Code_Embeds_REST_Controller extends WP_REST_Posts_Controller {
	/**
	 * Check if a given request has access to read entries.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! Code_Embed::current_user_can_select() ) {
			return $this->forbidden();
		}

		return parent::get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to read an entry.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object or false otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! Code_Embed::current_user_can_select() ) {
			return $this->forbidden();
		}

		return parent::get_item_permissions_check( $request );
	}

	/**
	 * Build the error returned when a user cannot read entries.
	 */
	private function forbidden(): WP_Error {
		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to view code embeds.', 'wp-secure-code-embeds' ),
			[ 'status' => rest_authorization_required_code() ],
		);
	}
}
