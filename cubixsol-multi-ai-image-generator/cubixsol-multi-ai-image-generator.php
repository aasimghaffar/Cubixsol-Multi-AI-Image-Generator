<?php
/**
 * Cubixsol Multi AI Image Generator — bootstrap file.
 *
 * Plugin Name:       Cubixsol Multi AI Image Generator
 * Plugin URI:        https://cubixsol.com/products/
 * Description:       Generate AI images with 9 engines (Pollinations FREE, OpenAI, Gemini, Grok, Stability, FLUX, Leonardo, Ideogram, DeepAI), stock photo search, SEO automation, auto-fallback and bulk generation.
 * Version:           1.0.6
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Cubixsol
 * Author URI:        https://cubixsol.com/products/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cubixsol-multi-ai-image-generator
 * Domain Path:       /languages
 *
 * @package AIISP
 */

// Security check: block direct file access. Every PHP file in this
// plugin carries this guard so nothing can be executed outside WP.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * -------------------------------------------------------------------------
 * Environment checks (run before anything is loaded)
 * -------------------------------------------------------------------------
 */

// Check: minimum PHP version. Bail gracefully instead of fataling.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Cubixsol Multi AI Image Generator requires PHP 7.4 or higher. The plugin has not been loaded.', 'cubixsol-multi-ai-image-generator' )
			);
		}
	);
	return;
}

// Check: prevent double loading (e.g. two copies of the plugin installed).
if ( defined( 'AIISP_VERSION' ) ) {
	return;
}

/*
 * -------------------------------------------------------------------------
 * Constants
 * -------------------------------------------------------------------------
 */

define( 'AIISP_VERSION', '1.0.6' );
define( 'AIISP_PLUGIN_FILE', __FILE__ );
define( 'AIISP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIISP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIISP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'AIISP_OPTION_KEY', 'aiisp_settings' );

/*
 * -------------------------------------------------------------------------
 * Autoloader
 * -------------------------------------------------------------------------
 * Maps the AIISP\ namespace onto the /includes directory using the
 * WordPress "class-{name}.php" file naming convention, e.g.:
 *   AIISP\Plugin                    -> includes/class-plugin.php
 *   AIISP\Admin\Meta_Box            -> includes/admin/class-meta-box.php
 *   AIISP\Providers\Stock\Pexels    -> includes/providers/stock/class-pexels.php
 */

require_once AIISP_PLUGIN_DIR . 'includes/class-autoloader.php';
AIISP\Autoloader::register();

/*
 * -------------------------------------------------------------------------
 * Activation / deactivation lifecycle
 * -------------------------------------------------------------------------
 */

register_activation_hook( __FILE__, array( 'AIISP\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AIISP\\Deactivator', 'deactivate' ) );

/*
 * -------------------------------------------------------------------------
 * Boot
 * -------------------------------------------------------------------------
 */

/**
 * Global accessor for the plugin singleton.
 *
 * Kept as a tiny prefixed function (allowed by WPCS) so views and
 * third-party code can reach plugin services without a global variable.
 *
 * @return AIISP\Plugin
 */
function aiisp() {
	return AIISP\Plugin::instance();
}

// Boot on plugins_loaded so all of WP core is available first.
add_action( 'plugins_loaded', 'aiisp' );
