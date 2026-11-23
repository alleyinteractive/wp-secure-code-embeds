<?php
/**
 * Register_Meta class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Types\Feature;

use function Alley\WP\Secure_Code_Embeds\register_post_meta_from_defs;
use function Alley\WP\Secure_Code_Embeds\register_term_meta_from_defs;

/**
 * Registers post and term meta from the JSON files in `config/`.
 *
 * Meta is registered on `init` after post types, because meta with
 * `revisions_enabled` requires its post type to already support revisions.
 */
final class Register_Meta implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'init', $this->register( ... ), 20 );
	}

	/**
	 * Register the meta.
	 */
	public function register(): void {
		register_post_meta_from_defs();
		register_term_meta_from_defs();
	}
}
