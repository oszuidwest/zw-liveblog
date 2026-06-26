<?php
/**
 * Post content helpers.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses posts for liveblog shortcodes.
 */
final class ZW_Liveblog_Content {
	/**
	 * Cached post liveblog presence.
	 *
	 * @var array<int, bool>
	 */
	private array $post_has_liveblog_cache = [];

	/**
	 * Extract all liveblog IDs from content.
	 *
	 * @param string $content Post content.
	 * @return array<int, string>
	 */
	public function extract_liveblog_ids( string $content ): array {
		if ( '' === $content || ! has_shortcode( $content, 'liveblog' ) ) {
			return [];
		}

		$ids = [];
		preg_match_all( '/' . get_shortcode_regex( [ 'liveblog' ] ) . '/', $content, $matches, PREG_SET_ORDER );
		foreach ( $matches as $shortcode ) {
			if ( ! isset( $shortcode[2], $shortcode[3], $shortcode[6] ) || 'liveblog' !== $shortcode[2] ) {
				continue;
			}

			if ( '[' === $shortcode[1] && ']' === $shortcode[6] ) {
				continue;
			}

			$atts = shortcode_parse_atts( $shortcode[3] );
			if ( empty( $atts['id'] ) ) {
				continue;
			}

			$id = sanitize_text_field( (string) $atts['id'] );
			if ( preg_match( '/^\d+$/', $id ) ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Check whether a post contains at least one valid liveblog shortcode.
	 *
	 * @param WP_Post|int|null $post Post object or ID.
	 * @return bool True when the post contains a valid liveblog shortcode.
	 */
	public function post_has_liveblog( WP_Post|int|null $post ): bool {
		$post = get_post( $post );
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( array_key_exists( $post->ID, $this->post_has_liveblog_cache ) ) {
			return $this->post_has_liveblog_cache[ $post->ID ];
		}

		$this->post_has_liveblog_cache[ $post->ID ] = [] !== $this->extract_liveblog_ids( $post->post_content );
		return $this->post_has_liveblog_cache[ $post->ID ];
	}
}
