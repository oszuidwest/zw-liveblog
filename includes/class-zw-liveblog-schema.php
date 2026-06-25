<?php
/**
 * LiveBlogPosting JSON-LD schema.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs LiveBlogPosting schema for singular liveblog posts.
 */
final class ZW_Liveblog_Schema {
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
		add_action( 'wp_head', [ $this, 'add_to_head' ] );
	}

	/**
	 * Output LiveBlogPosting schema with up to 100 updates per liveblog.
	 */
	public function add_to_head(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$ids = $this->content->extract_liveblog_ids( $post->post_content );
		if ( [] === $ids ) {
			return;
		}

		$schema = $this->build_schema( $post, $ids );
		$json   = wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return;
		}

		wp_print_inline_script_tag( $json, [ 'type' => 'application/ld+json' ] );
	}

	/**
	 * Build the JSON-LD schema array.
	 *
	 * @param WP_Post           $post Post object.
	 * @param array<int,string> $ids Liveblog event IDs.
	 * @return array<string, mixed>
	 */
	private function build_schema( WP_Post $post, array $ids ): array {
		$author_name        = get_the_author_meta( 'display_name', (int) $post->post_author );
		$logo_id            = get_theme_mod( 'custom_logo' );
		$logo_url           = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
		$coverage_start_iso = $this->format_wp_datetime( $this->get_coverage_start( $post, $ids ) );
		$modified_time      = get_post_modified_time( 'U', true, $post );
		$modified_iso       = $this->format_wp_datetime( $modified_time ? $modified_time : time() );

		$schema = [
			'@context'          => 'https://schema.org',
			'@type'             => 'LiveBlogPosting',
			'mainEntityOfPage'  => get_permalink( $post ),
			'headline'          => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'datePublished'     => $coverage_start_iso,
			'dateModified'      => $modified_iso,
			'coverageStartTime' => $coverage_start_iso,
			'author'            => [
				'@type' => 'Person',
				'name'  => html_entity_decode( $author_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			],
			'publisher'         => [
				'@type' => 'Organization',
				'name'  => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'logo'  => [
					'@type' => 'ImageObject',
					'url'   => $logo_url ? $logo_url : '',
				],
			],
			'liveBlogUpdate'    => [],
		];

		foreach ( $ids as $id ) {
			$this->append_updates( $schema, $post, $id );
			$this->append_coverage_end( $schema, $id );
		}

		return $schema;
	}

	/**
	 * Resolve coverage start time.
	 *
	 * @param WP_Post           $post Post object.
	 * @param array<int,string> $ids Liveblog event IDs.
	 * @return int Unix timestamp.
	 */
	private function get_coverage_start( WP_Post $post, array $ids ): int {
		$coverage_start = get_post_time( 'U', true, $post );
		if ( false !== $coverage_start && '' !== $coverage_start ) {
			return (int) $coverage_start;
		}

		foreach ( $ids as $id ) {
			$meta = $this->api->fetch_event_meta( $id );
			if ( null !== $meta && isset( $meta['start_time'] ) && is_numeric( $meta['start_time'] ) && 0 < (int) $meta['start_time'] ) {
				return (int) $meta['start_time'];
			}
		}

		return time();
	}

	/**
	 * Append liveblog updates to the schema.
	 *
	 * @param array<string, mixed> $schema   Schema array, passed by reference.
	 * @param WP_Post              $post     Post object.
	 * @param string               $event_id Liveblog event ID.
	 */
	private function append_updates( array &$schema, WP_Post $post, string $event_id ): void {
		foreach ( $this->api->fetch_updates( $event_id ) as $update ) {
			$update_item = $this->build_update_item( $post, $update );
			if ( null !== $update_item ) {
				$schema['liveBlogUpdate'][] = $update_item;
			}
		}
	}

	/**
	 * Build a single BlogPosting update item.
	 *
	 * @param WP_Post              $post Post object.
	 * @param array<string, mixed> $update 24LiveBlog update data.
	 * @return array<string, mixed>|null
	 */
	private function build_update_item( WP_Post $post, array $update ): ?array {
		$text = trim( wp_strip_all_tags( (string) ( $update['contents'] ?? '' ) ) );
		if ( '' === $text ) {
			return null;
		}

		$date_published = $this->format_wp_datetime( $update['created'] ?? 0 );
		if ( '' === $date_published ) {
			return null;
		}

		$update_item = [
			'@type'         => 'BlogPosting',
			'articleBody'   => $text,
			'datePublished' => $date_published,
			'url'           => get_permalink( $post ) . '#liveblog-' . rawurlencode( (string) ( $update['nid'] ?? '' ) ),
		];

		$headline = trim( wp_strip_all_tags( (string) ( $update['newstitle'] ?? '' ) ) );
		if ( '' !== $headline ) {
			$update_item['headline'] = $headline;
		}

		return $update_item;
	}

	/**
	 * Append coverageEndTime when the event is closed.
	 *
	 * @param array<string, mixed> $schema   Schema array, passed by reference.
	 * @param string               $event_id Liveblog event ID.
	 */
	private function append_coverage_end( array &$schema, string $event_id ): void {
		$meta = $this->api->fetch_event_meta( $event_id );
		if ( null === $meta || empty( $meta['closed'] ) || ! isset( $meta['last_updated'] ) ) {
			return;
		}

		$coverage_end = $this->format_wp_datetime( $meta['last_updated'] );
		if ( '' !== $coverage_end ) {
			$schema['coverageEndTime'] = $coverage_end;
		}
	}

	/**
	 * Convert timestamp to local timezone in ISO 8601.
	 *
	 * @param mixed $timestamp Unix timestamp.
	 * @return string Formatted timestamp, or empty string when unavailable.
	 */
	private function format_wp_datetime( mixed $timestamp ): string {
		if ( empty( $timestamp ) || ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
			return '';
		}

		try {
			$dt = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
			return $dt->format( DATE_W3C );
		} catch ( Exception ) {
			return '';
		}
	}
}
