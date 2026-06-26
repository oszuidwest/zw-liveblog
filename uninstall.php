<?php
/**
 * Uninstall handler for ZuidWest Liveblog plugin.
 *
 * Removes all plugin transients from the database when the plugin is deleted.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-zw-liveblog-lifecycle.php';

ZW_Liveblog_Lifecycle::delete_transients();
