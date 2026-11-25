<?php
/**
 * Plugin Name: Secure Code Embeds
 * Plugin URI: https://github.com/alleyinteractive/wp-secure-code-embeds
 * Description: A WordPress plugin to allow authors and editors to securely embed admin-approved code via the block editor
 * Version: 0.1.0
 * Author: Alley
 * Author URI: https://github.com/alleyinteractive/wp-secure-code-embeds
 * Requires at least: 7.0
 * Requires PHP: 8.4
 * Tested up to: 7.1
 *
 * Text Domain: wp-secure-code-embeds
 * Domain Path: /languages/
 *
 * @package wp-secure-code-embeds
 */

namespace Alley\WP\Secure_Code_Embeds;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'WP_SECURE_CODE_EMBEDS_DIR', __DIR__ );

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
	if ( ! class_exists( \Composer\InstalledVersions::class ) ) {
		\add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Composer is not installed and wp-secure-code-embeds cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'wp-secure-code-embeds' ); ?></p>
				</div>
				<?php
			}
		);

		return;
	}
} else {
	// Load Composer dependencies.
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

// Load the plugin's main files.
require_once __DIR__ . '/src/assets.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/main.php';

main();
