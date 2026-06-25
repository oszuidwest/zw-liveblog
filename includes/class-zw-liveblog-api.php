<?php
/**
 * 24LiveBlog API client.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches and caches event data from 24LiveBlog.
 */
final class ZW_Liveblog_Api {
	private const BASE_URL  = 'https://data.24liveplus.com/v1/retrieve_server/x/event/';
	private const CACHE_TTL = 60;

	/**
	 * Fetch the latest 100 updates.
	 *
	 * @param string $event_id The 24LiveBlog event ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function fetch_updates( string $event_id ): array {
		if ( ! $this->is_valid_event_id( $event_id ) ) {
			return [];
		}

		$transient_key = 'zw_liveblog_updates_' . md5( $event_id );
		$cached        = get_transient( $transient_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$data = $this->get_json( self::BASE_URL . $event_id . '/news/?inverted_order=1&last_nid=&limit=100' );
		if ( null === $data ) {
			return [];
		}

		$news = is_array( $data['data']['news'] ?? null ) ? $data['data']['news'] : [];

		set_transient( $transient_key, $news, self::CACHE_TTL );
		return $news;
	}

	/**
	 * Fetch metadata, such as closed status and last_updated.
	 *
	 * @param string $event_id The 24LiveBlog event ID.
	 * @return array<string, mixed>|null
	 */
	public function fetch_event_meta( string $event_id ): ?array {
		if ( ! $this->is_valid_event_id( $event_id ) ) {
			return null;
		}

		$transient_key = 'zw_liveblog_meta_' . md5( $event_id );
		$cached        = get_transient( $transient_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$data = $this->get_json( self::BASE_URL . $event_id . '/' );
		$meta = is_array( $data['data']['event'] ?? null ) ? $data['data']['event'] : null;

		if ( null !== $meta ) {
			set_transient( $transient_key, $meta, self::CACHE_TTL );
		}

		return $meta;
	}

	/**
	 * Request and decode JSON from 24LiveBlog.
	 *
	 * @param string $url API URL.
	 * @return array<string, mixed>|null
	 */
	private function get_json( string $url ): ?array {
		$response = wp_remote_get( $url, [ 'timeout' => 5 ] );
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body || ! json_validate( $body ) ) {
			return null;
		}

		$data = json_decode( $body, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Validate a 24LiveBlog event ID.
	 *
	 * @param string $event_id Event ID.
	 * @return bool True when the ID is numeric.
	 */
	private function is_valid_event_id( string $event_id ): bool {
		return 1 === preg_match( '/^\d+$/', $event_id );
	}
}
