<?php
/**
 * Uninstall cleanup. Runs only when the plugin is deleted from the Plugins
 * screen. Remove the settings option here, plus any custom tables or extra
 * options the plugin created.
 *
 * @package Perxel_Example
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'pxex_settings' );

// If the plugin creates custom tables, drop them here, e.g.:
// require_once __DIR__ . '/includes/Db.php';
// Perxel\Example\Db::uninstall();
