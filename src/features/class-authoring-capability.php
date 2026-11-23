<?php
/**
 * Authoring_Capability class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Secure_Code_Embeds\Code_Embed;
use Alley\WP\Types\Feature;

/**
 * Grants the code embed authoring capability to site administrators.
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
		if ( is_array( $allcaps ) && ! isset( $allcaps[ Code_Embed::CAPABILITY ] ) && ! empty( $allcaps['manage_options'] ) ) {
			$allcaps[ Code_Embed::CAPABILITY ] = true;
		}

		return $allcaps;
	}
}
