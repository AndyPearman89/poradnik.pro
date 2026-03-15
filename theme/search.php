<?php
get_header();
?>
<section class="container section">
    <h1><?php printf(esc_html__('Wyniki wyszukiwania: %s', 'generatepress-child-poradnik'), get_search_query()); ?></h1>
    <?php get_search_form(); ?>
    <?php if (have_posts()) : ?>
        <div class="article-list">
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/article/article', 'card'); ?>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('Brak wyników.', 'generatepress-child-poradnik'); ?></p>
    <?php endif; ?>
</section>
<?php
get_footer();
