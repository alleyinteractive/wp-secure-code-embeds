<?php
/**
 * Full-width page template for classic themes.
 *
 * Renders the site header, the site footer, and a full-width content area with
 * no sidebar. Block themes use the block template registered in
 * {@see \Alley\WP\Secure_Code_Embeds\Features\Full_Width_Page_Template}.
 *
 * @package wp-secure-code-embeds
 */

get_header();
?>
<main id="main" class="wp-secure-code-embeds-full-width">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>
<?php
get_footer();
