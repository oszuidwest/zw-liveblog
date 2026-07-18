<?php
/**
 * Plugin Name: ZuidWest Liveblog
 * Description: Replaces the [liveblog id="123456"] shortcode with the 24LiveBlog embed code, hides advertisements, and adds LiveBlogPosting schema.
 * Version: 1.8.1
 * Author: Streekomroep ZuidWest
 * Author URI: https://www.zuidwesttv.nl
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Text Domain: zw-liveblog
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZW_LIVEBLOG_FILE', __FILE__ );
define( 'ZW_LIVEBLOG_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZW_LIVEBLOG_VERSION', get_file_data( __FILE__, [ 'Version' => 'Version' ] )['Version'] );

require_once ZW_LIVEBLOG_DIR . 'includes/class-zw-liveblog-lifecycle.php';
require_once ZW_LIVEBLOG_DIR . 'includes/class-zw-liveblog-content.php';
require_once ZW_LIVEBLOG_DIR . 'includes/class-zw-liveblog-api.php';
require_once ZW_LIVEBLOG_DIR . 'includes/class-zw-liveblog-shortcode.php';
require_once ZW_LIVEBLOG_DIR . 'includes/class-zw-liveblog-assets.php';
require_once ZW_LIVEBLOG_DIR . 'includes/class-zw-liveblog-card-badges.php';
require_once ZW_LIVEBLOG_DIR . 'includes/class-zw-liveblog-schema.php';
require_once ZW_LIVEBLOG_DIR . 'includes/class-zw-liveblog-plugin.php';

register_deactivation_hook( __FILE__, [ ZW_Liveblog_Lifecycle::class, 'delete_transients' ] );

add_action(
	'plugins_loaded',
	static function (): void {
		( new ZW_Liveblog_Plugin() )->register_hooks();
	}
);
