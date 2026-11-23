<?php
/**
 * Server-side rendering of the wp-secure-code-embeds/code-embed block.
 *
 * The entry's body code is output exactly as saved. It is intentionally not
 * escaped or sanitized: only users with the authoring capability can save it.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- File doesn't load in global scope, just appears to to PHPCS.
 *
 * @var array<string, mixed> $attributes The block attributes.
 * @var WP_Block             $block      The block instance.
 *
 * @package wp-secure-code-embeds
 */

use Alley\WP\Secure_Code_Embeds\Code_Embed;

$code_embed_body = Code_Embed::get_block_body( $attributes, $block->context );

if ( '' === $code_embed_body ) {
	return;
}
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
<?php echo $code_embed_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
