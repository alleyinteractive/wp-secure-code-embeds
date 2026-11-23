<?php
/**
 * Secure Code Embeds Tests: Bootstrap
 *
 * @package wp-secure-code-embeds
 */

declare(strict_types=1);

/**
 * Visit {@see https://mantle.alley.com/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	// Rsync the plugin to plugins/wp-secure-code-embeds when testing.
	->maybe_rsync_plugin()
	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../wp-secure-code-embeds.php' )
	->install();
