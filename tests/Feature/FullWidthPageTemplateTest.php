<?php
/**
 * Secure Code Embeds Tests: Full Width Page Template
 *
 * @package wp-secure-code-embeds
 */

namespace Alley\WP\Secure_Code_Embeds\Tests\Feature;

use Alley\WP\Secure_Code_Embeds\Features\Full_Width_Page_Template;
use Alley\WP\Secure_Code_Embeds\Tests\TestCase;

/**
 * Tests for the optional full-width page template.
 */
class FullWidthPageTemplateTest extends TestCase {
	public function test_block_template_is_registered_for_pages(): void {
		$this->assertTrue( wp_is_block_theme() );

		$template = get_block_template( get_stylesheet() . '//' . Full_Width_Page_Template::SLUG );

		$this->assertNotNull( $template );
		$this->assertContains( 'page', $template->post_types );
		$this->assertStringContainsString( '"slug":"header"', $template->content );
		$this->assertStringContainsString( 'wp:post-content', $template->content );
		$this->assertStringContainsString( '"slug":"footer"', $template->content );

		$page_templates = wp_get_theme()->get_page_templates( null, 'page' );
		$this->assertArrayHasKey( Full_Width_Page_Template::SLUG, $page_templates );
		$this->assertArrayNotHasKey( Full_Width_Page_Template::SLUG, wp_get_theme()->get_page_templates( null, 'post' ) );
	}

	public function test_block_theme_uses_the_block_template(): void {
		$page = static::factory()->page->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>Full width body</p><!-- /wp:paragraph -->',
				'meta_input'   => [ '_wp_page_template' => Full_Width_Page_Template::SLUG ],
			]
		);

		$this->get( get_permalink( $page ) )
			->assertOk()
			->assertSee( 'Full width body' )
			->assertSee( 'entry-content alignfull wp-block-post-content' );
	}

	public function test_classic_theme_uses_the_php_template(): void {
		register_theme_directory( __DIR__ . '/../fixtures/themes' );
		search_theme_directories( true );
		switch_theme( 'classic-fixture' );

		// The default theme's functions.php is already loaded and enqueues a
		// stylesheet relative to the active theme, which the fixture lacks.
		$this->ignoreIncorrectUsage( 'wp_maybe_inline_styles' );

		try {
			$this->assertFalse( wp_is_block_theme() );
			$this->assertArrayHasKey( Full_Width_Page_Template::SLUG, wp_get_theme()->get_page_templates( null, 'page' ) );

			$page = static::factory()->page->create(
				[
					'post_content' => '<!-- wp:paragraph --><p>Classic body</p><!-- /wp:paragraph -->',
					'meta_input'   => [ '_wp_page_template' => Full_Width_Page_Template::SLUG ],
				]
			);

			$this->get( get_permalink( $page ) )
				->assertOk()
				->assertSee( 'classic-fixture-header' )
				->assertSee( '<main id="main" class="wp-secure-code-embeds-full-width">' )
				->assertSee( 'Classic body' )
				->assertSee( 'classic-fixture-footer' )
				->assertDontSee( 'classic-fixture-page-template' );
		} finally {
			switch_theme( WP_DEFAULT_THEME );
		}
	}
}
