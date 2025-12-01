<?php
/**
 * Uninstall handler for ZuidWest Liveblog plugin.
 *
 * Removes all plugin transients from the database when the plugin is deleted.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE %s OR option_name LIKE %s',
        '_transient_zw_liveblog_%',
        '_transient_timeout_zw_liveblog_%'
    )
);
