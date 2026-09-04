<?php
/**
 * Plugin Name:       Perxel Plugin Name
 * Plugin URI:        https://github.com/perxel/wp-plugin-name
 * Description:        A short description of what this plugin does.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Perxel
 * Author URI:        https://perxel.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       perxel-plugin-name
 * Domain Path:       /languages
 *
 * @package Perxel_PluginName
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PXPREFIX_VERSION', '0.1.0' );
define( 'PXPREFIX_FILE', __FILE__ );
define( 'PXPREFIX_DIR', __DIR__ );
define( 'PXPREFIX_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PXPREFIX_OPTION_KEY', 'pxprefix_settings' );

/**
 * Human-readable product name. A brand name, deliberately not translated.
 */
define( 'PXPREFIX_NAME', 'Perxel Plugin Name' );

/**
 * PSR-4-ish autoloader for Perxel\PluginName\* -> includes/*.php.
 */
spl_autoload_register(
	static function ( $class_name ) {
		if ( strpos( $class_name, 'Perxel\\PluginName\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, strlen( 'Perxel\\PluginName\\' ) );
		$path     = PXPREFIX_DIR . '/includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

/**
 * Shared Perxel admin-UI kit. Standalone, versioned independently of this
 * plugin (github.com/perxel/wp-plugin-ui); vendored into vendor/perxel-ui/ via
 * bin/update-ui.sh. Overwriting it can never change plugin behaviour - the
 * loader keeps the highest registered version across active plugins and a
 * second copy is inert. We host the kit's component showcase as a hidden
 * maintainer-only screen, so suppress its own Tools page.
 */
define( 'PERXEL_UI_SHOWCASE_HOSTED', true );

if ( is_readable( PXPREFIX_DIR . '/vendor/perxel-ui/loader.php' ) ) {
	require_once PXPREFIX_DIR . '/vendor/perxel-ui/loader.php';
	Perxel_UI_Loader::register( '0.21.0', PXPREFIX_DIR . '/vendor/perxel-ui', PXPREFIX_URL . '/vendor/perxel-ui' );
}

register_activation_hook( __FILE__, array( 'Perxel\PluginName\Plugin', 'activate' ) );

add_action( 'plugins_loaded', 'pxprefix_init' );

/**
 * Boot the plugin.
 */
function pxprefix_init() {
	load_plugin_textdomain( 'perxel-plugin-name', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	Perxel\PluginName\Plugin::instance()->boot();
}
