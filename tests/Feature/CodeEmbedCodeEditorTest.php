<?php
/**
 * Secure Code Embeds Tests: Code Embed Code Editor
 *
 * @package wp-secure-code-embeds
 */

namespace Alley\WP\Secure_Code_Embeds\Tests\Feature;

use Alley\WP\Secure_Code_Embeds\Features\Code_Embed_Code_Editor;
use Alley\WP\Secure_Code_Embeds\Tests\TestCase;

/**
 * Tests for loading the WordPress code editor in the code embed editor.
 */
class CodeEmbedCodeEditorTest extends TestCase {
	/**
	 * Set up each test as an administrator.
	 */
	protected function setUp(): void {
		parent::setUp();

		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';

		$this->acting_as( 'administrator' );
	}

	/**
	 * Clean up the screen and enqueued assets.
	 */
	protected function tearDown(): void {
		set_current_screen( 'front' );
		wp_dequeue_script( 'code-editor' );
		wp_dequeue_style( 'code-editor' );
		wp_dequeue_style( 'wp-codemirror' );

		parent::tearDown();
	}

	/**
	 * The settings use HTML mode with line numbers and enqueue the code editor.
	 */
	public function test_settings_enable_html_mode_and_line_numbers(): void {
		$feature  = new Code_Embed_Code_Editor();
		$settings = $feature->get_settings();

		$this->assertIsArray( $settings );
		$this->assertIsArray( $settings['codemirror'] );
		$this->assertSame( 'htmlmixed', $settings['codemirror']['mode'] );
		$this->assertTrue( $settings['codemirror']['lineNumbers'] );
		$this->assertTrue( wp_script_is( 'code-editor', 'enqueued' ) );
	}

	/**
	 * Code is not linted against KSES, even for users who can't post
	 * unfiltered HTML.
	 */
	public function test_settings_do_not_lint_against_kses(): void {
		add_filter(
			'map_meta_cap',
			fn ( $caps, $cap ) => 'unfiltered_html' === $cap ? [ 'do_not_allow' ] : $caps,
			10,
			2,
		);

		$feature  = new Code_Embed_Code_Editor();
		$settings = $feature->get_settings();

		$this->assertIsArray( $settings );
		$this->assertIsArray( $settings['htmlhint'] );
		$this->assertArrayNotHasKey( 'kses', $settings['htmlhint'] );
	}

	/**
	 * The settings are false when the user turned off syntax highlighting.
	 */
	public function test_settings_are_false_when_syntax_highlighting_is_off(): void {
		update_user_meta( get_current_user_id(), 'syntax_highlighting', 'false' );

		$feature = new Code_Embed_Code_Editor();

		$this->assertFalse( $feature->get_settings() );
	}

	/**
	 * The code editor styles load in the canvas only when editing a code embed.
	 */
	public function test_canvas_styles_load_only_for_code_embeds(): void {
		$feature = new Code_Embed_Code_Editor();

		set_current_screen( 'page' );
		$feature->enqueue_canvas_styles();
		$this->assertFalse( wp_style_is( 'wp-codemirror', 'enqueued' ) );

		set_current_screen( 'secure_code_embed' );
		$feature->enqueue_canvas_styles();
		$this->assertTrue( wp_style_is( 'wp-codemirror', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'code-editor', 'enqueued' ) );
	}
}
