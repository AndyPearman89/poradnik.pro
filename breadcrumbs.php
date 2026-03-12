<?php
if (!defined('ABSPATH')) {
    exit;
}

function pse_get_breadcrumb_items(int $post_id): array
{
    $items = [];

    $items[] = [
        'name' => 'Home',
        'url' => home_url('/'),
    ];

    $cats = get_the_category($post_id);
    if (!empty($cats)) {
        usort($cats, static function ($a, $b) {
            return (int) $a->term_id <=> (int) $b->term_id;
        });

        $primary = $cats[0];
        $parents = array_reverse(get_ancestors((int) $primary->term_id, 'category'));

        foreach ($parents as $parent_id) {
            $parent = get_term((int) $parent_id, 'category');
            if ($parent instanceof WP_Term) {
                $items[] = [
                    'name' => $parent->name,
                    'url' => get_term_link($parent),
                ];
            }
        }

        $items[] = [
            'name' => $primary->name,
            'url' => get_term_link($primary),
        ];
    }

    $items[] = [
        'name' => get_the_title($post_id),
        'url' => get_permalink($post_id),
    ];

    return $items;
}

function pse_render_breadcrumbs_html(int $post_id): string
{
    $items = pse_get_breadcrumb_items($post_id);
    if (count($items) < 2) {
        return '';
    }

    $parts = [];
    $last = count($items) - 1;
    foreach ($items as $idx => $item) {
        if ($idx === $last) {
            $parts[] = '<span>' . esc_html((string) $item['name']) . '</span>';
        } else {
            $parts[] = '<a href="' . esc_url((string) $item['url']) . '">' . esc_html((string) $item['name']) . '</a>';
        }
    }

    return '<nav class="pse-breadcrumbs" aria-label="Breadcrumb" style="font-size:.92rem;color:#555;margin:0 0 1rem">'
        . implode(' &rarr; ', $parts)
        . '</nav>';
}

add_filter('the_content', function (string $content): string {
    if (!is_single() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $settings = function_exists('pse_get_settings') ? pse_get_settings() : ['breadcrumbs_enabled' => 1];
    if (empty($settings['breadcrumbs_enabled'])) {
        return $content;
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return $content;
    }

    $html = pse_render_breadcrumbs_html($post_id);

    return $html === '' ? $content : ($html . $content);
}, 5);
