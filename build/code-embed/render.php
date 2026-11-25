<?php
/**
 * Server-side rendering of the wp-secure-code-embeds/code-embed block.
 *
 * The entry's body code is output exactly as saved. It is intentionally not
 * escaped or sanitized: only users with the `manage_secure_code_embeds`
 * capability can save it.
 *
 * Nothing is output in feeds, in post types that do not allow code embeds, or
 * when the selected entry is missing, unpublished, or has no body code.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- File doesn't load in global scope, just appears to to PHPCS.
 *
 * @var array<string, mixed> $attributes The block attributes.
 * @var WP_Block             $block      The block instance.
 *
 * @package wp-secure-code-embeds
 */

use function Alley\WP\Secure_Code_Embeds\allowed_post_types;

if ( is_feed() ) {
	return;
}

$code_embed_host_id   = $block->context['postId'] ?? null;
$code_embed_post_type = $block->context['postType'] ?? get_post_type( is_int( $code_embed_host_id ) ? $code_embed_host_id : null );

if ( ! is_string( $code_embed_post_type ) || ! in_array( $code_embed_post_type, allowed_post_types(), true ) ) {
	return;
}

$code_embed_id = isset( $attributes['ref'] ) && is_numeric( $attributes['ref'] ) ? (int) $attributes['ref'] : 0;
$code_embed    = $code_embed_id > 0 ? get_post( $code_embed_id ) : null;

if ( ! $code_embed instanceof WP_Post || 'secure_code_embed' !== $code_embed->post_type || 'publish' !== $code_embed->post_status ) {
	return;
}

$code_embed_body = get_post_meta( $code_embed->ID, 'secure_code_embed_body', true );

if ( ! is_string( $code_embed_body ) || '' === trim( $code_embed_body ) ) {
	return;
}
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
<?php echo $code_embed_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
