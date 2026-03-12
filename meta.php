<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function (): void {
    remove_action('wp_head', 'rel_canonical');
}, 20);

function pse_generate_meta_title(int $post_id): string
{
    $title = wp_strip_all_tags((string) get_the_title($post_id));
    $site = get_bloginfo('name');

    return trim($title . ' | ' . $site);
}

function pse_generate_meta_description(int $post_id): string
{
    $intro = (string) get_post_meta($post_id, '_poradnik_intro', true);
    if ($intro !== '') {
        return mb_substr(wp_strip_all_tags($intro), 0, 160);
    }

    $excerpt = (string) get_the_excerpt($post_id);
    $excerpt = wp_strip_all_tags($excerpt);

    if ($excerpt === '') {
        $excerpt = wp_strip_all_tags((string) get_post_field('post_content', $post_id));
    }

    return mb_substr($excerpt, 0, 160);
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!is_single()) {
        return $title;
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return $title;
    }

    return pse_generate_meta_title($post_id);
}, 20);

function pse_locale_map(): array
{
    return [
        'pl' => 'pl_PL',
        'en' => 'en_US',
        'de' => 'de_DE',
        'es' => 'es_ES',
        'fr' => 'fr_FR',
    ];
}

add_action('wp_head', function (): void {
    if (!is_single()) {
        return;
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return;
    }

    $meta_title = pse_generate_meta_title($post_id);
    $meta_desc = pse_generate_meta_description($post_id);
    $url = get_permalink($post_id);
    $image = get_the_post_thumbnail_url($post_id, 'full');

    echo '<meta name="description" content="' . esc_attr($meta_desc) . '">' . "\n";
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($meta_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($meta_desc) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";

    $lang = function_exists('pse_get_post_lang') ? pse_get_post_lang($post_id) : 'pl';
    $locale_map = pse_locale_map();
    $og_locale = (string) ($locale_map[$lang] ?? 'pl_PL');
    echo '<meta property="og:locale" content="' . esc_attr($og_locale) . '">' . "\n";

    if (!empty($image)) {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
    }

    if (function_exists('pse_get_translation_urls')) {
        $langs = pse_languages();
        $urls = pse_get_translation_urls($post_id);

        foreach ($urls as $lang => $alt_url) {
            if (!isset($langs[$lang])) {
                continue;
            }
            echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url((string) $alt_url) . '">' . "\n";

            if (!empty($locale_map[$lang]) && $locale_map[$lang] !== $og_locale) {
                echo '<meta property="og:locale:alternate" content="' . esc_attr((string) $locale_map[$lang]) . '">' . "\n";
            }
        }

        $current_lang = function_exists('pse_get_post_lang') ? pse_get_post_lang($post_id) : 'pl';
        if (!empty($urls[$current_lang])) {
            echo '<link rel="canonical" href="' . esc_url((string) $urls[$current_lang]) . '">' . "\n";
        }

        if (!empty($urls[pse_default_language()])) {
            echo '<link rel="alternate" hreflang="x-default" href="' . esc_url((string) $urls[pse_default_language()]) . '">' . "\n";
        }
    }
}, 4);
