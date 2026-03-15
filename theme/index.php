<?php
get_header();
?>
<section class="container section">
    <?php if (have_posts()) : ?>
        <div class="article-list">
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/article/article', 'card'); ?>
            <?php endwhile; ?>
        </div>
        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p><?php esc_html_e('Brak treści.', 'generatepress-child-poradnik'); ?></p>
    <?php endif; ?>
</section>
<?php
get_footer();
