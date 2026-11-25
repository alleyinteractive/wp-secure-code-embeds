<?php
/**
 * Authoring_Capability class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Types\Feature;

/**
 * Grants the code embed authoring capability to site administrators.
 *
 * `manage_secure_code_embeds` is the plugin's trust boundary. Code embeds hold
 * raw code that is stored and output exactly as written, without sanitizing or
 * escaping, so only users trusted with that capability can author them.
 *
 * This is the single place that decides who can author code embeds. Super
 * admins pass every capability check, so on multisite they can author code
 * embeds on every site. A role that sets the capability explicitly (to true or
 * false) keeps that setting.
 */
final class Authoring_Capability implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'user_has_cap', $this->filter_user_has_cap( ... ) );
	}

	/**
	 * Grant the authoring capability to users who can manage options.
	 *
	 * @param mixed $allcaps The user's capabilities, keyed by capability name.
	 */
	public function filter_user_has_cap( mixed $allcaps ): mixed {
		if ( is_array( $allcaps ) && ! isset( $allcaps['manage_secure_code_embeds'] ) && ! empty( $allcaps['manage_options'] ) ) {
			$allcaps['manage_secure_code_embeds'] = true;
		}

		return $allcaps;
	}
}
