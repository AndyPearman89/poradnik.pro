<?php get_header(); while (have_posts()) : the_post(); get_template_part('template-parts/content/single', 'produkt'); endwhile; get_footer();
