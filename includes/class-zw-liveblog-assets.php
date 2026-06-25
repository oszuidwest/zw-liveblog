<?php
/**
 * Front-end asset loading.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues 24LiveBlog and plugin front-end assets.
 */
final class ZW_Liveblog_Assets {
	/**
	 * Register asset hooks.
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Enqueue assets only on singular content containing a liveblog shortcode.
	 */
	public function enqueue(): void {
		$post = get_post();
		if ( ! is_singular() || ! $post instanceof WP_Post || ! has_shortcode( $post->post_content, 'liveblog' ) ) {
			return;
		}

		wp_enqueue_script(
			'liveblog-24-js',
			'https://v.24liveblog.com/24.js',
			[],
			ZW_LIVEBLOG_VERSION,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);

		wp_enqueue_style(
			'zw-liveblog-style',
			plugins_url( 'assets/liveblog.css', ZW_LIVEBLOG_FILE ),
			[],
			ZW_LIVEBLOG_VERSION
		);

		wp_enqueue_script(
			'zw-liveblog-enhancements',
			plugins_url( 'assets/liveblog-enhancements.js', ZW_LIVEBLOG_FILE ),
			[ 'liveblog-24-js' ],
			ZW_LIVEBLOG_VERSION,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);

		$settings_json = wp_json_encode(
			[
				'inputtingLabel'       => __( 'Aan het typen...', 'zw-liveblog' ),
				'recentlyUpdatedLabel' => __( 'Net bijgewerkt', 'zw-liveblog' ),
			]
		);

		if ( false !== $settings_json ) {
			wp_add_inline_script( 'zw-liveblog-enhancements', 'window.zwLiveblogEnhancements = ' . $settings_json . ';', 'before' );
		}
	}
}
