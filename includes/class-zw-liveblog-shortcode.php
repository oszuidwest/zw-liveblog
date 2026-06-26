<?php
/**
 * Shortcode registration and rendering.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the [liveblog] shortcode.
 */
final class ZW_Liveblog_Shortcode {
	/**
	 * Register shortcode hooks.
	 */
	public function register_hooks(): void {
		add_shortcode( 'liveblog', [ $this, 'render' ] );
	}

	/**
	 * Render shortcode output.
	 *
	 * @param array<string, string|int>|string $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render( array|string $atts ): string {
		$atts        = shortcode_atts( [ 'id' => '' ], is_array( $atts ) ? $atts : [], 'liveblog' );
		$liveblog_id = sanitize_text_field( (string) $atts['id'] );

		if ( '' === $liveblog_id ) {
			return '';
		}

		return '<div id="LB24_LIVE_CONTENT" data-eid="' . esc_attr( $liveblog_id ) . '"></div>';
	}
}
