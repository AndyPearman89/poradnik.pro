<?php
get_header();
?>
<section class="container section">
    <h1><?php the_archive_title(); ?></h1>
    <?php if (have_posts()) : ?>
        <div class="article-list">
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/article/article', 'card'); ?>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</section>
<?php
get_footer();
