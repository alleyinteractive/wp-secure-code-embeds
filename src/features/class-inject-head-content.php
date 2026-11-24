<?php
/**
 * Inject_Head_Content class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Types\Feature;
use WP_Post;

use function Alley\WP\Secure_Code_Embeds\allowed_post_types;

/**
 * Outputs the head code of every code embed placed on the current page.
 *
 * The page's blocks are parsed once per request, including blocks nested in
 * other blocks and in synced patterns. Each referenced entry's head code is
 * output once, however many times the entry is placed.
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
			$ids           = array_values( array_unique( $this->find_embed_ids( parse_blocks( $post->post_content ), $seen_patterns ) ) );

			if ( [] !== $ids ) {
				_prime_post_caches( $ids, false, true );
			}

			$this->embed_ids[ $post->ID ] = $ids;
		}

		return $this->embed_ids[ $post->ID ];
	}

	/**
	 * Recursively find picker blocks in a list of parsed blocks.
	 *
	 * @param array<mixed>     $blocks        Parsed blocks.
	 * @param array<int, true> $seen_patterns Synced pattern IDs already searched, to prevent loops.
	 * @return int[] Entry IDs, possibly with duplicates.
	 */
	private function find_embed_ids( array $blocks, array &$seen_patterns ): array {
		$ids = [];

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$attributes = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
			$block_name = $block['blockName'] ?? null;

			if ( 'wp-secure-code-embeds/code-embed' === $block_name ) {
				$embed_id = isset( $attributes['embedId'] ) && is_numeric( $attributes['embedId'] ) ? (int) $attributes['embedId'] : 0;

				if ( $embed_id > 0 ) {
					$ids[] = $embed_id;
				}
			} elseif ( 'core/block' === $block_name ) {
				$ids = [ ...$ids, ...$this->find_embed_ids_in_pattern( $attributes, $seen_patterns ) ];
			}

			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && [] !== $block['innerBlocks'] ) {
				$ids = [ ...$ids, ...$this->find_embed_ids( $block['innerBlocks'], $seen_patterns ) ];
			}
		}

		return $ids;
	}

	/**
	 * Find picker blocks inside a synced pattern.
	 *
	 * @param array<mixed>     $attributes    Attributes of the `core/block` block.
	 * @param array<int, true> $seen_patterns Synced pattern IDs already searched, to prevent loops.
	 * @return int[] Entry IDs, possibly with duplicates.
	 */
	private function find_embed_ids_in_pattern( array $attributes, array &$seen_patterns ): array {
		$ref = isset( $attributes['ref'] ) && is_numeric( $attributes['ref'] ) ? (int) $attributes['ref'] : 0;

		if ( $ref <= 0 || isset( $seen_patterns[ $ref ] ) ) {
			return [];
		}

		$seen_patterns[ $ref ] = true;

		$pattern = get_post( $ref );

		if ( ! $pattern instanceof WP_Post || 'wp_block' !== $pattern->post_type || 'publish' !== $pattern->post_status ) {
			return [];
		}

		return $this->find_embed_ids( parse_blocks( $pattern->post_content ), $seen_patterns );
	}
}
