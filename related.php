<?php
if (!defined('ABSPATH')) {
    exit;
}

function pse_get_related_articles(int $post_id, int $limit = 3): array
{
    $lang = function_exists('pse_get_post_lang') ? pse_get_post_lang($post_id) : 'pl';

    $cat_ids = wp_get_post_categories($post_id);
    if (empty($cat_ids)) {
        return [];
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'post__not_in' => [$post_id],
        'posts_per_page' => max(3, $limit),
        'category__in' => $cat_ids,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
    ]);

    $items = [];

    foreach ((array) $query->posts as $candidate) {
        if (!$candidate instanceof WP_Post) {
            continue;
        }

        if (function_exists('pse_get_post_lang') && function_exists('pse_get_settings')) {
            $settings = pse_get_settings();
            if (!empty($settings['multilang_enabled']) && pse_get_post_lang((int) $candidate->ID) !== $lang) {
                continue;
            }
        }

        $items[] = [
            'id' => (int) $candidate->ID,
            'title' => get_the_title((int) $candidate->ID),
            'url' => get_permalink((int) $candidate->ID),
        ];

        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

add_filter('the_content', function (string $content): string {
    if (!is_single() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $settings = function_exists('pse_get_settings') ? pse_get_settings() : ['related_enabled' => 1];
    if (empty($settings['related_enabled'])) {
        return $content;
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return $content;
    }

    $related = pse_get_related_articles($post_id, 3);
    if (empty($related)) {
        return $content;
    }

    $items = [];
    foreach ($related as $row) {
        $items[] = '<li><a href="' . esc_url((string) $row['url']) . '">' . esc_html((string) $row['title']) . '</a></li>';
    }

    $html = '<section class="pse-related-articles" aria-label="Related articles" style="margin:1.25rem 0;padding:1rem;border:1px solid #e5e7eb;border-radius:8px">'
        . '<h2 style="margin:0 0 .6rem">' . esc_html__('PowiÄ…zane poradniki', 'peartree-pro-seo-engine') . '</h2>'
        . '<ul style="margin:0;padding-left:1.2rem">' . implode('', $items) . '</ul>'
        . '</section>';

    return $content . $html;
}, 30);

