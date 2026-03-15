<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('generatepress-child-poradnik', get_stylesheet_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'main' => __('Main Menu', 'generatepress-child-poradnik'),
        'mobile' => __('Mobile Menu', 'generatepress-child-poradnik'),
        'footer' => __('Footer Menu', 'generatepress-child-poradnik'),
    ]);
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('gp-parent-style', get_template_directory_uri() . '/style.css', [], wp_get_theme('generatepress')->get('Version'));
    wp_enqueue_style('poradnik-main', get_stylesheet_directory_uri() . '/assets/css/main.css', ['gp-parent-style'], '1.0.0');
    wp_enqueue_style('poradnik-layout', get_stylesheet_directory_uri() . '/assets/css/layout.css', ['poradnik-main'], '1.0.0');
    wp_enqueue_style('poradnik-components', get_stylesheet_directory_uri() . '/assets/css/components.css', ['poradnik-layout'], '1.0.0');
    wp_enqueue_style('poradnik-responsive', get_stylesheet_directory_uri() . '/assets/css/responsive.css', ['poradnik-components'], '1.0.0');

    wp_enqueue_script('poradnik-main', get_stylesheet_directory_uri() . '/assets/js/main.js', [], '1.0.0', true);
    wp_enqueue_script('poradnik-search', get_stylesheet_directory_uri() . '/assets/js/search.js', ['poradnik-main'], '1.0.0', true);
    wp_enqueue_script('poradnik-ajax', get_stylesheet_directory_uri() . '/assets/js/ajax.js', ['poradnik-main'], '1.0.0', true);
    wp_enqueue_script('poradnik-filters', get_stylesheet_directory_uri() . '/assets/js/filters.js', ['poradnik-main'], '1.0.0', true);

    wp_localize_script('poradnik-ajax', 'poradnikAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => esc_url_raw(rest_url('peartree/v1/')),
        'nonce' => wp_create_nonce('wp_rest'),
        'modules' => [
            'listings' => esc_url_raw(rest_url('peartree/v1/listings')),
            'leads' => esc_url_raw(rest_url('peartree/v1/leads')),
            'reviews' => esc_url_raw(rest_url('peartree/v1/reviews')),
            'affiliate' => esc_url_raw(rest_url('peartree/v1/affiliate')),
            'seo' => esc_url_raw(rest_url('peartree/v1/seo')),
            'claim' => esc_url_raw(rest_url('peartree/v1/claim')),
        ],
    ]);
});

add_action('init', static function (): void {
    $postTypes = [
        'poradnik' => __('Poradniki', 'generatepress-child-poradnik'),
        'ranking' => __('Rankingi', 'generatepress-child-poradnik'),
        'recenzja' => __('Recenzje', 'generatepress-child-poradnik'),
        'produkt' => __('Produkty', 'generatepress-child-poradnik'),
        'porownanie' => __('Porównania', 'generatepress-child-poradnik'),
        'pytanie' => __('Pytania', 'generatepress-child-poradnik'),
        'specjalista' => __('Specjaliści', 'generatepress-child-poradnik'),
    ];

    foreach ($postTypes as $slug => $label) {
        register_post_type($slug, [
            'label' => $label,
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments'],
            'rewrite' => ['slug' => $slug],
        ]);
    }

    register_taxonomy('kategoria', ['poradnik', 'ranking', 'recenzja', 'produkt', 'porownanie', 'pytanie'], [
        'label' => __('Kategorie', 'generatepress-child-poradnik'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);

    register_taxonomy('tag', ['poradnik', 'ranking', 'recenzja', 'produkt', 'porownanie', 'pytanie'], [
        'label' => __('Tagi', 'generatepress-child-poradnik'),
        'public' => true,
        'hierarchical' => false,
        'show_in_rest' => true,
    ]);

    register_taxonomy('miasto', ['specjalista', 'pytanie'], [
        'label' => __('Miasta', 'generatepress-child-poradnik'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);

    register_taxonomy('usluga', ['specjalista'], [
        'label' => __('Usługi', 'generatepress-child-poradnik'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
});

if (!function_exists('poradnik_section_title')) {
    function poradnik_section_title(string $title): void
    {
        echo '<h2 class="section-title">' . esc_html($title) . '</h2>';
    }
}

add_action('wp_head', static function (): void {
    if (!is_front_page()) {
        return;
    }

    get_template_part('template-parts/schema/schema', 'article');
    get_template_part('template-parts/schema/schema', 'review');
    get_template_part('template-parts/schema/schema', 'product');
    get_template_part('template-parts/schema/schema', 'faq');
    get_template_part('template-parts/schema/schema', 'breadcrumb');
}, 20);
