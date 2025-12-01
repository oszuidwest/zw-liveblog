<?php
/*
Plugin Name: ZuidWest Liveblog
Description: Replaces the [liveblog id="123456"] shortcode with the 24LiveBlog embed code, hides advertisements, and adds LiveBlogPosting schema.
Version: 1.6.1
Author: Streekomroep ZuidWest
License: MIT
Requires at least: 6.8
Requires PHP: 8.3
*/

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler.
 */
function zw_liveblog_shortcode(array|string $atts): string
{
    $atts = shortcode_atts(['id' => ''], $atts, 'liveblog');
    $liveblog_id = sanitize_text_field($atts['id']);
    if ($liveblog_id === '') {
        return '';
    }
    return '<div id="LB24_LIVE_CONTENT" data-eid="' . esc_attr($liveblog_id) . '"></div>';
}
add_shortcode('liveblog', zw_liveblog_shortcode(...));

/**
 * Enqueue 24LiveBlog assets only when shortcode is used.
 */
function zw_liveblog_enqueue_assets(): void
{
    $post = get_post();
    if (is_singular() && $post && has_shortcode($post->post_content, 'liveblog')) {
        wp_enqueue_script('liveblog-24-js', 'https://v.24liveblog.com/24.js', [], null, [
            'in_footer' => true,
            'strategy' => 'defer'
        ]);
        wp_register_style('zw-liveblog-style', false);
        wp_enqueue_style('zw-liveblog-style');
        $css = '
            .lb24-news-list-item.lb24-base-list-ad { display: none !important; }
            #LB24 .lb24-theme-dark .lb24-base-news-container:hover {
                background: #0000002b !important;
            }
        ';
        wp_add_inline_style('zw-liveblog-style', $css);
    }
}
add_action('wp_enqueue_scripts', zw_liveblog_enqueue_assets(...));

/**
 * Fetch the latest 100 updates (cached for 60s).
 */
function zw_liveblog_fetch_updates(string $event_id): array
{
    $transient_key = 'zw_liveblog_updates_' . md5($event_id);
    $cached = get_transient($transient_key);
    if (is_array($cached)) {
        return $cached;
    }

    $url = 'https://data.24liveplus.com/v1/retrieve_server/x/event/' . $event_id . '/news/?inverted_order=1&last_nid=&limit=100';
    $response = wp_remote_get($url, ['timeout' => 5]);
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    if (!json_validate($body)) {
        return [];
    }
    $data = json_decode($body, true);
    $news = $data['data']['news'] ?? [];

    set_transient($transient_key, $news, 60);
    return $news;
}

/**
 * Fetch metadata (e.g., closed status, last_updated).
 */
function zw_liveblog_fetch_event_meta(string $event_id): ?array
{
    $transient_key = 'zw_liveblog_meta_' . md5($event_id);
    $cached = get_transient($transient_key);
    if (is_array($cached)) {
        return $cached;
    }

    $url = 'https://data.24liveplus.com/v1/retrieve_server/x/event/' . $event_id . '/';
    $response = wp_remote_get($url, ['timeout' => 5]);
    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    if (!json_validate($body)) {
        return null;
    }
    $data = json_decode($body, true);
    $meta = $data['data']['event'] ?? null;

    set_transient($transient_key, $meta, 60);
    return $meta;
}

/**
 * Convert timestamp to local timezone in ISO 8601.
 */
function zw_liveblog_format_wp_datetime(mixed $timestamp): string
{
    if (empty($timestamp) || !is_numeric($timestamp) || $timestamp <= 0) {
        return '';
    }
    try {
        $dt = (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone(wp_timezone());
        return $dt->format(DATE_W3C);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Extract all liveblog IDs from content.
 */
function zw_liveblog_extract_liveblog_ids(string $content): array
{
    preg_match_all('/\[liveblog\s+id=["\']?(\d+)["\']?\]/i', $content, $matches);
    return array_unique($matches[1]);
}

/**
 * Outputs LiveBlogPosting schema with up to 100 updates per liveblog.
 */
function zw_liveblog_add_schema_to_head(): void
{
    if (!is_singular()) {
        return;
    }

    $post = get_post();
    if (!$post) {
        return;
    }
    $ids = zw_liveblog_extract_liveblog_ids($post->post_content);
    if ($ids === []) {
        return;
    }

    $author_name = get_the_author_meta('display_name', (int) $post->post_author);
    $logo_id = get_theme_mod('custom_logo');
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
    // Get coverage start time - use post time if published, otherwise current time
    $coverage_start = get_post_time('U', true, $post);
    if ($coverage_start === false || $coverage_start === '') {
        // For drafts/previews, try to get start_time from first liveblog event
        // Otherwise use current time
        $coverage_start = time();
        foreach ($ids as $id) {
            $meta = zw_liveblog_fetch_event_meta($id);
            if ($meta !== null && isset($meta['start_time']) && $meta['start_time'] > 0) {
                $coverage_start = $meta['start_time'];
                break;
            }
        }
    }
    $coverage_start_iso = zw_liveblog_format_wp_datetime($coverage_start);
    $modified_time = get_post_modified_time('U', true, $post);
    $modified_iso = zw_liveblog_format_wp_datetime($modified_time ?: time());

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LiveBlogPosting',
        'mainEntityOfPage' => get_permalink($post),
        'headline' => html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'datePublished' => $coverage_start_iso,
        'dateModified' => $modified_iso,
        'coverageStartTime' => $coverage_start_iso,
        'author' => [
            '@type' => 'Person',
            'name' => html_entity_decode($author_name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logo_url,
            ]
        ],
        'liveBlogUpdate' => []
    ];

    foreach ($ids as $id) {
        $updates = zw_liveblog_fetch_updates($id);
        foreach ($updates as $update) {
            $text = trim(wp_strip_all_tags($update['contents'] ?? ''));
            if ($text === '') {
                continue;
            }

            $created_timestamp = $update['created'] ?? 0;
            $date_published = zw_liveblog_format_wp_datetime($created_timestamp);
            if ($date_published === '') {
                continue;
            }

            $update_item = [
                '@type' => 'BlogPosting',
                'articleBody' => $text,
                'datePublished' => $date_published,
                'url' => get_permalink($post) . '#liveblog-' . esc_attr($update['nid']),
            ];

            $headline = trim($update['newstitle'] ?? '');
            if ($headline !== '') {
                $update_item['headline'] = $headline;
            }

            $schema['liveBlogUpdate'][] = $update_item;
        }

        // Include coverageEndTime if event is closed.
        $meta = zw_liveblog_fetch_event_meta($id);
        if ($meta !== null && !empty($meta['closed']) && isset($meta['last_updated'])) {
            $coverage_end = zw_liveblog_format_wp_datetime($meta['last_updated']);
            if ($coverage_end !== '') {
                $schema['coverageEndTime'] = $coverage_end;
            }
        }
    }

    echo '<script type="application/ld+json">' .
        wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
        '</script>';
}
add_action('wp_head', zw_liveblog_add_schema_to_head(...));
