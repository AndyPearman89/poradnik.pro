<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?php echo esc_url(get_stylesheet_directory_uri() . '/site.webmanifest'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/branding/logo-icon.svg'); ?>">
    <meta name="theme-color" content="#0b0f1a">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="container header-wrap">
        <a class="logo" href="<?php echo esc_url(home_url('/')); ?>">
            <?php
            $logo_path = get_stylesheet_directory() . '/assets/branding/logo-main.svg';
            if (is_readable($logo_path)) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                $svg = file_get_contents($logo_path);
                // Only output if file content looks like an SVG (starts with svg tag after optional whitespace/<?xml).
                if (is_string($svg) && preg_match('/<svg[\s>]/i', $svg)) {
                    echo wp_kses($svg, wp_kses_allowed_html('post') + [
                        'svg'      => ['xmlns' => true, 'viewbox' => true, 'role' => true, 'aria-label' => true, 'class' => true],
                        'defs'     => [],
                        'lineargradient' => ['id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true],
                        'stop'     => ['offset' => true, 'style' => true],
                        'filter'   => ['id' => true],
                        'fegaussianblur' => ['stddeviation' => true, 'result' => true],
                        'femerge'  => [],
                        'femergenode' => ['in' => true],
                        'rect'     => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true, 'filter' => true],
                        'text'     => ['x' => true, 'y' => true, 'font-family' => true, 'font-size' => true, 'font-weight' => true, 'text-anchor' => true, 'fill' => true],
                        'circle'   => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true],
                        'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true],
                        'path'     => ['d' => true, 'fill' => true],
                    ]);
                } else {
                    echo esc_html(get_bloginfo('name'));
                }
            } else {
                echo esc_html(get_bloginfo('name'));
            }
            ?>
        </a>
        <?php get_template_part('template-parts/nav/nav', 'main'); ?>
        <div class="header-right">
            <a href="<?php echo esc_url(home_url('/search/')); ?>"><?php esc_html_e('Search', 'generatepress-child-poradnik'); ?></a>
            <a href="<?php echo esc_url(home_url('/login/')); ?>"><?php esc_html_e('Login', 'generatepress-child-poradnik'); ?></a>
            <a href="<?php echo esc_url(home_url('/register/')); ?>"><?php esc_html_e('Register', 'generatepress-child-poradnik'); ?></a>
            <a class="btn" href="<?php echo esc_url(home_url('/pytania/')); ?>"><?php esc_html_e('Zadaj pytanie', 'generatepress-child-poradnik'); ?></a>
        </div>
    </div>
    <?php get_template_part('template-parts/nav/nav', 'mobile'); ?>
</header>
<main id="site-content">
