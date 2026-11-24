<?php
/**
 * Code_Embed_Block_Settings class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Types\Feature;

use function Alley\WP\Secure_Code_Embeds\allowed_post_types;

/**
 * Passes the post types where code embeds can be placed to the Code Embed
 * block's editor script.
 *
 * The editor script compares them to the post open in the editor to decide
 * whether the block can be inserted. The block stays registered everywhere so
 * that an existing block still shows its placeholder in the editor.
 */
final class Code_Embed_Block_Settings implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'enqueue_block_editor_assets', $this->add_editor_settings( ... ) );
	}

	/**
	 * Add the settings before the picker block's editor script.
	 */
	public function add_editor_settings(): void {
		$handle = generate_block_asset_handle( 'wp-secure-code-embeds/code-embed', 'editorScript' );

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_add_inline_script(
			$handle,
			sprintf( 'window.wpSecureCodeEmbeds = %s;', wp_json_encode( $this->get_settings() ) ),
			'before',
		);
	}

	/**
	 * Get the settings for the Code Embed block's editor script.
	 *
	 * @return array{allowedPostTypes: string[]}
	 */
	public function get_settings(): array {
		return [
			'allowedPostTypes' => allowed_post_types(),
		];
	}
}
