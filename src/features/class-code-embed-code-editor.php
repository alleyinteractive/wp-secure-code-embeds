<?php
/**
 * Code_Embed_Code_Editor class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Types\Feature;

/**
 * Loads the code editor that ships with WordPress (CodeMirror) for the head
 * and body fields of a code embed.
 *
 * The settings are passed to the Code Embed Fields block's editor script,
 * which swaps its textareas for code editors. They are `false` when the user
 * turned off syntax highlighting in their profile, and the block keeps its
 * plain textareas.
 */
final class Code_Embed_Code_Editor implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'enqueue_block_editor_assets', $this->add_editor_settings( ... ) );
		add_action( 'enqueue_block_assets', $this->enqueue_canvas_styles( ... ) );
	}

	/**
	 * Enqueue the code editor and add its settings before the Code Embed
	 * Fields block's editor script.
	 */
	public function add_editor_settings(): void {
		if ( ! $this->is_code_embed_screen() ) {
			return;
		}

		$handle = generate_block_asset_handle( 'wp-secure-code-embeds/code-embed-fields', 'editorScript' );

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_add_inline_script(
			$handle,
			sprintf( 'window.wpSecureCodeEmbedsCodeEditor = %s;', wp_json_encode( $this->get_settings() ) ),
			'before',
		);
	}

	/**
	 * Enqueue the code editor styles in the editor canvas.
	 *
	 * The canvas can be an iframe, which only receives styles enqueued on
	 * `enqueue_block_assets`.
	 */
	public function enqueue_canvas_styles(): void {
		if ( ! $this->is_code_embed_screen() || ! wp_get_code_editor_settings( [ 'type' => 'text/html' ] ) ) {
			return;
		}

		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_style( 'code-editor' );
	}

	/**
	 * Get the code editor settings, enqueueing the code editor.
	 *
	 * Core lints HTML against KSES for users who can't post unfiltered HTML,
	 * which would flag the scripts and styles that code embeds exist to hold.
	 * Users who can author code embeds may save any code, so that rule is
	 * removed.
	 *
	 * @return array<mixed>|false Settings, or false if the user turned off syntax highlighting.
	 */
	public function get_settings(): array|false {
		$settings = wp_enqueue_code_editor( [ 'type' => 'text/html' ] );

		if ( is_array( $settings ) && isset( $settings['htmlhint'] ) && is_array( $settings['htmlhint'] ) ) {
			unset( $settings['htmlhint']['kses'] );
		}

		return $settings;
	}

	/**
	 * Whether the current admin screen edits a code embed.
	 */
	private function is_code_embed_screen(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen instanceof \WP_Screen && 'secure_code_embed' === $screen->post_type;
	}
}
