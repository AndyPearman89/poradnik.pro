<article class="card">
  <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
  <p class="meta"><?php echo esc_html(get_post_type()); ?></p>
  <?php the_excerpt(); ?>
</article>