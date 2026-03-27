<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?php echo esc_url(get_template_directory_uri() . '/site.webmanifest'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url(get_template_directory_uri() . '/assets/branding/logo-icon.svg'); ?>">
    <meta name="theme-color" content="#0b0f1a">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="container header-wrap">
        <a class="logo" href="<?php echo esc_url(home_url('/')); ?>">
            <?php echo file_get_contents(get_template_directory() . '/assets/branding/logo-main.svg'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents ?>
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
