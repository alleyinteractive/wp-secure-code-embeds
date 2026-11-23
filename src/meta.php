<?php
/**
 * Contains functions for working with meta.
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds;

use function Mantle\Support\Helpers\register_meta_from_file;

/**
 * Reads the post meta definitions from config and registers them.
 */
function register_post_meta_from_defs(): void {
	register_meta_from_file( WP_SECURE_CODE_EMBEDS_DIR . '/config/post-meta.json', 'post', false );
}

/**
 * Reads the term meta definitions from config and registers them.
 */
function register_term_meta_from_defs(): void {
	register_meta_from_file( WP_SECURE_CODE_EMBEDS_DIR . '/config/term-meta.json', 'term', false );
}
