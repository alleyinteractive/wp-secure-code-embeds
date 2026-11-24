<?php
/**
 * Code_Embeds_REST_Controller class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds;

use WP_Error;
use WP_Post_Type;
use WP_REST_Posts_Controller;
use WP_REST_Request;

/**
 * REST controller for code embed entries.
 *
 * The core posts controller lets anyone read published entries of any post
 * type that is shown in REST. This controller limits reads to users who can
 * select entries in the Code Embed block. Writes still go through the post
 * type's capabilities, which require `manage_secure_code_embeds`.
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
		if ( ! $this->current_user_can_select() ) {
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
		if ( ! $this->current_user_can_select() ) {
			return $this->forbidden();
		}

		return parent::get_item_permissions_check( $request );
	}

	/**
	 * Whether the current user can select entries in the Code Embed block.
	 *
	 * Authors of entries can always select them. Otherwise, users who can edit
	 * any post type that allows code embeds have read-only access.
	 */
	private function current_user_can_select(): bool {
		if ( current_user_can( 'manage_secure_code_embeds' ) ) {
			return true;
		}

		foreach ( allowed_post_types() as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );

			if ( ! $post_type_object instanceof WP_Post_Type ) {
				continue;
			}

			$edit_cap = $post_type_object->cap->edit_posts ?? null;

			if ( is_string( $edit_cap ) && current_user_can( $edit_cap ) ) {
				return true;
			}
		}

		return false;
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
