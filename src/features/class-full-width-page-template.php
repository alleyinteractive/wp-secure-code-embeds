<?php
/**
 * Full_Width_Page_Template class file
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

namespace Alley\WP\Secure_Code_Embeds\Features;

use Alley\WP\Secure_Code_Embeds\Code_Embed;
use Alley\WP\Types\Feature;

/**
 * Registers an optional page template with the site header, the site footer,
 * and a full-width content area with no sidebar.
 *
 * Block themes get a plugin-registered block template. Classic themes get a
 * PHP template that calls `get_header()` and `get_footer()`. Both use the same
 * slug, so the choice survives a switch between theme types.
 */
final class Full_Width_Page_Template implements Feature {
	/**
	 * Template slug, stored as the page's `_wp_page_template` value.
	 */
	public const string SLUG = 'code-embed-full-width';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'init', $this->register_block_template( ... ) );
		add_filter( 'theme_templates', $this->filter_theme_templates( ... ), 10, 4 );
		add_filter( 'template_include', $this->filter_template_include( ... ) );
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return __( 'Full Width (Code Embeds)', 'wp-secure-code-embeds' );
	}

	/**
	 * Register the block template for block themes.
	 */
	public function register_block_template(): void {
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}

		register_block_template(
			'wp-secure-code-embeds//' . self::SLUG,
			[
				'title'       => $this->get_title(),
				'description' => __( 'Site header and footer with a full-width content area and no sidebar.', 'wp-secure-code-embeds' ),
				'post_types'  => Code_Embed::allowed_post_types(),
				'content'     => $this->get_block_template_content(),
			]
		);
	}

	/**
	 * Get the block template markup.
	 */
	public function get_block_template_content(): string {
		return <<<'HTML'
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<main class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:post-content {"align":"full","layout":{"type":"default"}} /--></main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
HTML;
	}

	/**
	 * Add the template to the template dropdown for classic themes.
	 *
	 * Block themes list the registered block template instead.
	 *
	 * @param mixed $templates Templates, keyed by slug.
	 * @param mixed $theme     The theme object.
	 * @param mixed $post      The post being edited, or null.
	 * @param mixed $post_type Post type to get the templates for.
	 */
	public function filter_theme_templates( mixed $templates, mixed $theme, mixed $post, mixed $post_type ): mixed {
		if (
		! is_array( $templates )
		|| wp_is_block_theme()
		|| ! in_array( $post_type, Code_Embed::allowed_post_types(), true )
		) {
			return $templates;
		}

		$templates[ self::SLUG ] = $this->get_title();

		return $templates;
	}

	/**
	 * Use the plugin's PHP template for classic themes.
	 *
	 * @param string $template Path to the template WordPress chose.
	 */
	public function filter_template_include( $template ): string {
		$post_types = Code_Embed::allowed_post_types();

		if (
			wp_is_block_theme()
			|| [] === $post_types
			|| ! is_singular( $post_types )
			|| self::SLUG !== get_page_template_slug()
		) {
			return $template;
		}

		return WP_SECURE_CODE_EMBEDS_DIR . '/templates/' . self::SLUG . '.php';
	}
}
