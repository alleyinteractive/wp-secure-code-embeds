<?php
/**
 * Fallback template for the classic test theme.
 *
 * @package wp-secure-code-embeds
 */

get_header();

while ( have_posts() ) {
	the_post();
	the_content();
}

get_footer();
