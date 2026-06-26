<?php
/**
 * Live badges for theme cards and tiles.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds LIVE badges to posts that contain a liveblog shortcode.
 */
final class ZW_Liveblog_Card_Badges {
	/**
	 * Content helper.
	 *
	 * @var ZW_Liveblog_Content
	 */
	private ZW_Liveblog_Content $content;

	/**
	 * API client.
	 *
	 * @var ZW_Liveblog_Api
	 */
	private ZW_Liveblog_Api $api;

	/**
	 * Collected frontend post IDs.
	 *
	 * @var array<int, true>
	 */
	private array $live_post_ids = [];

	/**
	 * Cached open-liveblog status per post.
	 *
	 * @var array<int, bool>
	 */
	private array $open_cache = [];

	/**
	 * Constructor.
	 *
	 * @param ZW_Liveblog_Content $content Content helper.
	 * @param ZW_Liveblog_Api     $api API client.
	 */
	public function __construct( ZW_Liveblog_Content $content, ZW_Liveblog_Api $api ) {
		$this->content = $content;
		$this->api     = $api;
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks(): void {
		add_filter( 'the_posts', [ $this, 'collect_liveblog_posts' ] );
		add_action( 'wp_footer', [ $this, 'print_footer_assets' ], 5 );
	}

	/**
	 * Collect liveblog post IDs from frontend queries.
	 *
	 * @param array<int, WP_Post> $posts Query posts.
	 * @return array<int, WP_Post> Unchanged query posts.
	 */
	public function collect_liveblog_posts( array $posts ): array {
		if ( ! $this->should_run_on_frontend() ) {
			return $posts;
		}

		foreach ( $posts as $post ) {
			if ( 'publish' === $post->post_status && $this->has_open_liveblog( $post ) ) {
				$this->live_post_ids[ $post->ID ] = true;
			}
		}

		return $posts;
	}

	/**
	 * Whether a post contains at least one liveblog event that is still open.
	 *
	 * Mirrors the schema's closed-event detection so a finished liveblog stops
	 * showing a LIVE badge. Unknown status (API failure) is treated as open so a
	 * transient outage never hides a genuinely live badge.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool True when the post has an open (non-closed) liveblog event.
	 */
	private function has_open_liveblog( WP_Post $post ): bool {
		if ( array_key_exists( $post->ID, $this->open_cache ) ) {
			return $this->open_cache[ $post->ID ];
		}

		$open = false;
		foreach ( $this->content->extract_liveblog_ids( $post->post_content ) as $id ) {
			$meta = $this->api->fetch_event_meta( $id );
			if ( null === $meta || empty( $meta['closed'] ) ) {
				$open = true;
				break;
			}
		}

		$this->open_cache[ $post->ID ] = $open;
		return $open;
	}

	/**
	 * Print badge assets when collected IDs exist.
	 */
	public function print_footer_assets(): void {
		if ( ! $this->should_run_on_frontend() || [] === $this->live_post_ids ) {
			return;
		}

		$live_post_ids = array_values( array_filter( array_map( 'absint', array_keys( $this->live_post_ids ) ) ) );
		if ( [] === $live_post_ids ) {
			return;
		}

		$settings_json = wp_json_encode(
			[
				'ids'   => $live_post_ids,
				'label' => __( 'LIVE', 'zw-liveblog' ),
			]
		);

		if ( false === $settings_json ) {
			return;
		}

		wp_enqueue_style(
			'zw-liveblog-card-badges',
			plugins_url( 'assets/card-badges.css', ZW_LIVEBLOG_FILE ),
			[],
			ZW_LIVEBLOG_VERSION
		);
		wp_print_styles( [ 'zw-liveblog-card-badges' ] );

		wp_enqueue_script(
			'zw-liveblog-card-badges',
			plugins_url( 'assets/card-badges.js', ZW_LIVEBLOG_FILE ),
			[],
			ZW_LIVEBLOG_VERSION,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);
		wp_add_inline_script( 'zw-liveblog-card-badges', 'window.zwLiveblogCardBadges = ' . $settings_json . ';', 'before' );
	}

	/**
	 * Whether card badge behavior should run in the current request.
	 */
	private function should_run_on_frontend(): bool {
		return ! is_admin() && ! is_feed() && ! wp_is_json_request();
	}
}
