<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="container header-wrap">
        <a class="logo" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
        <?php get_template_part('template-parts/nav/nav', 'main'); ?>
        <div class="header-right">
            <a href="<?php echo esc_url(home_url('/search/')); ?>"><?php esc_html_e('Search', 'generatepress-child-poradnik'); ?></a>
            <a href="<?php echo esc_url(home_url('/login/')); ?>"><?php esc_html_e('Login', 'generatepress-child-poradnik'); ?></a>
            <a href="<?php echo esc_url(home_url('/register/')); ?>"><?php esc_html_e('Register', 'generatepress-child-poradnik'); ?></a>
            <a class="btn btn-accent" href="<?php echo esc_url(home_url('/pytania/')); ?>"><?php esc_html_e('Zadaj pytanie', 'generatepress-child-poradnik'); ?></a>
        </div>
    </div>
    <?php get_template_part('template-parts/nav/nav', 'mobile'); ?>
</header>
<main id="site-content">
