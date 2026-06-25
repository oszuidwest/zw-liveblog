<?php
/**
 * Plugin lifecycle handlers.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles cached transient cleanup.
 */
final class ZW_Liveblog_Lifecycle {
	/**
	 * Delete cached 24LiveBlog transients.
	 */
	public static function delete_transients(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE %s OR option_name LIKE %s',
				'_transient_zw_liveblog_%',
				'_transient_timeout_zw_liveblog_%'
			)
		);
	}
}
