<?php
/**
 * Plugin composition root.
 *
 * @package ZuidWestLiveblog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires plugin services and hooks together.
 */
final class ZW_Liveblog_Plugin {
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
	 * Shortcode component.
	 *
	 * @var ZW_Liveblog_Shortcode
	 */
	private ZW_Liveblog_Shortcode $shortcode;

	/**
	 * Asset component.
	 *
	 * @var ZW_Liveblog_Assets
	 */
	private ZW_Liveblog_Assets $assets;

	/**
	 * Card badge component.
	 *
	 * @var ZW_Liveblog_Card_Badges
	 */
	private ZW_Liveblog_Card_Badges $card_badges;

	/**
	 * Schema component.
	 *
	 * @var ZW_Liveblog_Schema
	 */
	private ZW_Liveblog_Schema $schema;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->content     = new ZW_Liveblog_Content();
		$this->api         = new ZW_Liveblog_Api();
		$this->shortcode   = new ZW_Liveblog_Shortcode();
		$this->assets      = new ZW_Liveblog_Assets();
		$this->card_badges = new ZW_Liveblog_Card_Badges( $this->content );
		$this->schema      = new ZW_Liveblog_Schema( $this->content, $this->api );
	}

	/**
	 * Register component hooks.
	 */
	public function register_hooks(): void {
		$this->shortcode->register_hooks();
		$this->assets->register_hooks();
		$this->card_badges->register_hooks();
		$this->schema->register_hooks();
	}
}
