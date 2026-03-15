<div class="article-list">
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <?php get_template_part('template-parts/article/article', 'card'); ?>
  <?php endwhile; endif; ?>
</div>