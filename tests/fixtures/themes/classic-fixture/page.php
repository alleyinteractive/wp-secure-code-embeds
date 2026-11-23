<?php
/**
 * Default page template for the classic test theme.
 *
 * @package wp-secure-code-embeds
 */

get_header();
echo '<aside>classic-fixture-page-template</aside>';

while ( have_posts() ) {
	the_post();
	the_content();
}

get_footer();
