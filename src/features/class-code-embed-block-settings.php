<?php
/**
 * Code_Embed_Block_Settings class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Secure_Code_Embeds\Code_Embed;
use Alley\WP\Types\Feature;
use WP_Screen;

/**
 * Passes settings to the picker block's editor script.
 *
 * The picker block can only be inserted in post types returned by
 * {@see Code_Embed::allowed_post_types()}. It stays registered everywhere so
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
		$handle = generate_block_asset_handle( Code_Embed::PICKER_BLOCK, 'editorScript' );

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
	 * Get the settings for the picker block's editor script.
	 *
	 * @return array{adminUrl: string, canInsert: bool, postType: string, restBase: string}
	 */
	public function get_settings(): array {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$post_type = $screen instanceof WP_Screen ? $screen->post_type : '';

		return [
			'adminUrl'  => admin_url(),
			'canInsert' => '' !== $post_type && in_array( $post_type, Code_Embed::allowed_post_types(), true ),
			'postType'  => Code_Embed::POST_TYPE,
			'restBase'  => Code_Embed::REST_BASE,
		];
	}
}
