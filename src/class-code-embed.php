<?php
/**
 * Code_Embed class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds;

use WP_Post;
use WP_Post_Type;

/**
 * Shared constants and helpers for code embed entries.
 *
 * Code embed entries hold raw head and body code authored by trusted users. The
 * code is stored and output exactly as written: it is intentionally not run
 * through `unfiltered_html` checks, sanitized, or escaped. The trust boundary is
 * the {@see Code_Embed::CAPABILITY} capability, which controls who can author
 * entries.
 */
final class Code_Embed {
	/**
	 * Post type that stores code embed entries.
	 */
	public const string POST_TYPE = 'secure_code_embed';

	/**
	 * REST base for the post type.
	 */
	public const string REST_BASE = 'secure-code-embeds';

	/**
	 * The single capability that controls who can create, edit, and delete entries.
	 */
	public const string CAPABILITY = 'manage_secure_code_embeds';

	/**
	 * Meta key for code output in the document `<head>`.
	 */
	public const string HEAD_META_KEY = 'secure_code_embed_head';

	/**
	 * Meta key for code output where the picker block is placed.
	 */
	public const string BODY_META_KEY = 'secure_code_embed_body';

	/**
	 * Name of the block that places an entry on a page.
	 */
	public const string PICKER_BLOCK = 'wp-secure-code-embeds/code-embed';

	/**
	 * Name of the block that edits an entry's head and body code.
	 */
	public const string FIELDS_BLOCK = 'wp-secure-code-embeds/code-embed-fields';

	/**
	 * Get the post types that can contain the picker block.
	 *
	 * @return string[]
	 */
	public static function allowed_post_types(): array {
		/**
		 * Filters the post types that can contain the code embed picker block.
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

	/**
	 * Whether the current user can select entries in the picker block.
	 *
	 * Authors of entries can always select them. Otherwise, users who can edit
	 * any post type that allows the picker block have read-only access.
	 */
	public static function current_user_can_select(): bool {
		if ( current_user_can( self::CAPABILITY ) ) {
			return true;
		}

		foreach ( self::allowed_post_types() as $post_type ) {
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
	 * Get a published entry by ID.
	 *
	 * @param int $id Entry ID.
	 * @return WP_Post|null The entry, or null if it does not exist or is not published.
	 */
	public static function get_published( int $id ): ?WP_Post {
		if ( $id <= 0 ) {
			return null;
		}

		$post = get_post( $id );

		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return $post;
	}

	/**
	 * Get an entry's head code.
	 *
	 * @param WP_Post $post Entry.
	 * @return string Raw, unescaped code.
	 */
	public static function get_head( WP_Post $post ): string {
		return self::get_meta_string( $post, self::HEAD_META_KEY );
	}

	/**
	 * Get an entry's body code.
	 *
	 * @param WP_Post $post Entry.
	 * @return string Raw, unescaped code.
	 */
	public static function get_body( WP_Post $post ): string {
		return self::get_meta_string( $post, self::BODY_META_KEY );
	}

	/**
	 * Get the body code a picker block should output.
	 *
	 * Returns an empty string when the block is in a post type that does not
	 * allow code embeds, when rendering a feed, or when the selected entry is
	 * missing or not published.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param array<mixed> $context    Block context.
	 * @return string Raw, unescaped code.
	 */
	public static function get_block_body( array $attributes, array $context ): string {
		if ( is_feed() ) {
			return '';
		}

		$post_type = isset( $context['postType'] ) && is_string( $context['postType'] )
			? $context['postType']
			: get_post_type();

		if ( ! in_array( $post_type, self::allowed_post_types(), true ) ) {
			return '';
		}

		$embed = self::get_published( self::get_block_embed_id( $attributes ) );

		if ( ! $embed instanceof WP_Post ) {
			return '';
		}

		$body = self::get_body( $embed );

		return '' === trim( $body ) ? '' : $body;
	}

	/**
	 * Get the entry ID from a picker block's attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @return int Entry ID, or 0 if none is set.
	 */
	public static function get_block_embed_id( array $attributes ): int {
		$embed_id = $attributes['embedId'] ?? 0;

		return is_numeric( $embed_id ) ? max( 0, (int) $embed_id ) : 0;
	}

	/**
	 * Get a meta value as a string.
	 *
	 * @param WP_Post $post Entry.
	 * @param string  $key  Meta key.
	 */
	private static function get_meta_string( WP_Post $post, string $key ): string {
		$value = get_post_meta( $post->ID, $key, true );

		return is_string( $value ) ? $value : '';
	}
}
