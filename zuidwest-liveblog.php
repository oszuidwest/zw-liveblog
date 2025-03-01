<?php
/*
Plugin Name: ZuidWest Liveblog
Description: Replaces the [liveblog id="123456"] shortcode with the 24LiveBlog embed code and hides advertisements.
Version: 1.4
Author: Streekomroep ZuidWest
License: MIT
*/

// Prevent direct file access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler for [liveblog id="123456"].
 *
 * Usage in a post or page:
 * [liveblog id="YOUR_LIVEBLOG_ID"]
 */
function zw_liveblog_shortcode($atts)
{
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'id' => '',
    ), $atts, 'liveblog');

    $liveblog_id = sanitize_text_field($atts['id']);

    // If no ID is provided, return an empty string
    if (empty($liveblog_id)) {
        return '';
    }

    // Build and return the embed code
    return '<div id="LB24_LIVE_CONTENT" data-eid="' . esc_attr($liveblog_id) . '"></div>';
}
add_shortcode('liveblog', 'zw_liveblog_shortcode');

/**
 * Enqueue CSS and JavaScript assets if the liveblog shortcode is present in the post.
 */
function zw_liveblog_enqueue_assets()
{
    // Check if it's a singular post and the content contains the liveblog shortcode
    if (is_singular() && has_shortcode(get_post()->post_content, 'liveblog')) {
        // Enqueue the 24LiveBlog script
        wp_enqueue_script('liveblog-24-js', 'https://v.24liveblog.com/24.js', array(), null, true);

        // Register and enqueue custom CSS
        wp_register_style('zw-liveblog-style', false);
        wp_enqueue_style('zw-liveblog-style');

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
