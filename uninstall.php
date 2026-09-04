<?php
/**
 * Uninstall cleanup. Runs only when the plugin is deleted from the Plugins
 * screen. Remove the settings option here, plus any custom tables or extra
 * options the plugin created.
 *
 * @package Perxel_PluginName
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'pxprefix_settings' );

// If the plugin creates custom tables, drop them here, e.g.:
// require_once __DIR__ . '/includes/Db.php';
// Perxel\PluginName\Db::uninstall();
