<?php
/**
 * Plugin functions
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds;

/**
 * Get the post types where code embeds can be placed.
 *
 * The Code Embed block can only be inserted and rendered in these post types,
 * and head code is only output on them.
 *
 * @return string[] Post type slugs.
 */
function allowed_post_types(): array {
	/**
	 * Filters the post types where code embeds can be placed.
	 *
	 * Code embeds are limited to pages by default to keep raw code out of
	 * feeds and syndicated content.
	 *
	 * @param mixed $post_types Post type slugs. Default `[ 'page' ]`.
	 */
	$post_types = apply_filters( 'wp_secure_code_embeds_allowed_post_types', [ 'page' ] );

	if ( ! is_array( $post_types ) ) {
		return [ 'page' ];
	}

	return array_values( array_filter( $post_types, is_string( ... ) ) );
}
