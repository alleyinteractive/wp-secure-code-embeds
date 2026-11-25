<?php
/**
 * Inject_Head_Content class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Types\Feature;
use WP_Block_Processor;
use WP_Post;

use function Alley\WP\Secure_Code_Embeds\allowed_post_types;

/**
 * Outputs the head code of every code embed placed on the current page.
 *
 * The page's content is scanned once per request for Code Embed blocks,
 * including blocks nested in other blocks and in synced patterns. Each
 * referenced entry's head code is output once, however many times the entry is
 * placed.
 */
final class Inject_Head_Content implements Feature {
	/**
	 * Entry IDs found in each post, keyed by post ID.
	 *
	 * @var array<int, int[]>
	 */
	private array $embed_ids = [];

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'wp_head', $this->print_head_content( ... ) );
	}

	/**
	 * Print the head code for the code embeds on the current page.
	 */
	public function print_head_content(): void {
		$post_types = allowed_post_types();

		if ( [] === $post_types || ! is_singular( $post_types ) ) {
			return;
		}

		// Use the global post rather than the queried object so previews use the previewed content.
		$post = get_post();

		if ( ! $post instanceof WP_Post || post_password_required( $post ) ) {
			return;
		}

		foreach ( $this->get_embed_ids( $post ) as $embed_id ) {
			$embed = get_post( $embed_id );

			if ( ! $embed instanceof WP_Post || 'secure_code_embed' !== $embed->post_type || 'publish' !== $embed->post_status ) {
				continue;
			}

			$head = get_post_meta( $embed->ID, 'secure_code_embed_head', true );

			if ( ! is_string( $head ) || '' === trim( $head ) ) {
				continue;
			}

			// Intentionally unescaped. Only users with the authoring capability can save this code.
			echo $head . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Get the IDs of the entries placed in a post, in the order they first appear.
	 *
	 * Results are cached for the rest of the request, and the entries are
	 * loaded into the object cache so rendering the blocks does not query them
	 * again.
	 *
	 * @param WP_Post $post Post to search.
	 * @return int[] Unique entry IDs.
	 */
	public function get_embed_ids( WP_Post $post ): array {
		if ( ! isset( $this->embed_ids[ $post->ID ] ) ) {
			$seen_patterns = [];
			$ids           = array_values( array_unique( $this->find_embed_ids( $post->post_content, $seen_patterns ) ) );

			if ( [] !== $ids ) {
				_prime_post_caches( $ids, false, true );
			}

			$this->embed_ids[ $post->ID ] = $ids;
		}

		return $this->embed_ids[ $post->ID ];
	}

	/**
	 * Find Code Embed blocks in block content, including synced patterns.
	 *
	 * The content is scanned delimiter by delimiter rather than parsed into a
	 * block tree, and only the attributes of matching blocks are decoded.
	 * Nested blocks need no special handling because the scan is flat.
	 *
	 * @param string           $content       Block content.
	 * @param array<int, true> $seen_patterns Synced pattern IDs already searched, to prevent loops.
	 * @return int[] Entry IDs, possibly with duplicates.
	 */
	private function find_embed_ids( string $content, array &$seen_patterns ): array {
		if ( ! has_block( 'wp-secure-code-embeds/code-embed', $content ) && ! has_block( 'core/block', $content ) ) {
			return [];
		}

		$ids       = [];
		$processor = new WP_Block_Processor( $content );

		while ( $processor->next_block() ) {
			$is_embed   = $processor->is_block_type( 'wp-secure-code-embeds/code-embed' );
			$is_pattern = ! $is_embed && $processor->is_block_type( 'core/block' );

			if ( ! $is_embed && ! $is_pattern ) {
				continue;
			}

			$attributes = $processor->allocate_and_return_parsed_attributes() ?? [];
			$ref        = isset( $attributes['ref'] ) && is_numeric( $attributes['ref'] ) ? (int) $attributes['ref'] : 0;

			if ( $ref <= 0 ) {
				continue;
			}

			if ( $is_embed ) {
				$ids[] = $ref;
			} else {
				$ids = [ ...$ids, ...$this->find_embed_ids_in_pattern( $ref, $seen_patterns ) ];
			}
		}

		return $ids;
	}

	/**
	 * Find Code Embed blocks inside a synced pattern.
	 *
	 * @param int              $ref           Synced pattern ID.
	 * @param array<int, true> $seen_patterns Synced pattern IDs already searched, to prevent loops.
	 * @return int[] Entry IDs, possibly with duplicates.
	 */
	private function find_embed_ids_in_pattern( int $ref, array &$seen_patterns ): array {
		if ( isset( $seen_patterns[ $ref ] ) ) {
			return [];
		}

		$seen_patterns[ $ref ] = true;

		$pattern = get_post( $ref );

		if ( ! $pattern instanceof WP_Post || 'wp_block' !== $pattern->post_type || 'publish' !== $pattern->post_status ) {
			return [];
		}

		return $this->find_embed_ids( $pattern->post_content, $seen_patterns );
	}
}
