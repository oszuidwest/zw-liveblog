<?php

/**
 * Plugin Name: ZuidWest Liveblog
 * Description: Replaces the [liveblog id="123456"] shortcode with 24LiveBlog embed code and hides advertisements.
 * Version: 1.3
 * Author: Streekomroep ZuidWest
 * License: MIT
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler for [liveblog id="123456"].
 *
 * Usage in post/page:
 * [liveblog id="YOUR_LIVEBLOG_ID"]
 */
function zw_liveblog_shortcode($atts)
{
    global $zw_liveblog_present;
    $zw_liveblog_present = true; // Mark that the shortcode is used

    // Parse shortcode attributes
    $atts = shortcode_atts(
        array(
            'id' => '',
        ),
        $atts,
        'liveblog'
    );

    $liveblog_id = sanitize_text_field($atts['id']);

    // If no ID is provided, return empty string
    if (empty($liveblog_id)) {
        return '';
    }

    // Build and return the embed code
    return '<div id="LB24_LIVE_CONTENT" data-eid="' . esc_attr($liveblog_id) . '"></div>';
}
add_shortcode('liveblog', 'zw_liveblog_shortcode');

/**
 * Conditionally enqueue CSS and JavaScript if the liveblog shortcode is detected.
 */
function zw_liveblog_enqueue_assets()
{
    global $zw_liveblog_present;

    if (!empty($zw_liveblog_present)) {
        // Enqueue JavaScript
        wp_enqueue_script('24liveblog-js', 'https://v.24liveblog.com/24.js', array(), null, true);

        // Enqueue custom CSS
        wp_register_style('zw-liveblog-style', false);
        wp_enqueue_style('zw-liveblog-style');

        // Add inline CSS
        $custom_css = '
        /* Hide advertisements in the liveblog */
        .lb24-news-list-item.lb24-base-list-ad {
            display: none !important;
        }
        /* Translucent background on hover for news containers */
        #LB24 .lb24-theme-dark .lb24-base-news-container:hover {
            background: #0000002b !important;
        }
        ';

        wp_add_inline_style('zw-liveblog-style', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'zw_liveblog_enqueue_assets');
