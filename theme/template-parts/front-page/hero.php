<section class="section">
  <div class="container">
    <h1><?php esc_html_e('Znajdź rozwiązanie swojego problemu', 'generatepress-child-poradnik'); ?></h1>
    <p class="section-subtitle"><?php esc_html_e('Największy portal poradników i specjalistów', 'generatepress-child-poradnik'); ?></p>
    <form class="hero-search search-sticky" data-sticky-search data-hero-search-form action="<?php echo esc_url(home_url('/')); ?>" method="get">
      <input type="search" name="s" placeholder="Wpisz pytanie" aria-label="Wpisz pytanie" />
      <button class="btn" type="submit">Szukaj</button>
      <a class="btn btn-accent" href="<?php echo esc_url(home_url('/specjalisci/')); ?>">Znajdź specjalistę</a>
    </form>
    <div class="tag-list" aria-label="Popular topics">
      <?php foreach (['Hydraulika','Motoryzacja','Finanse','Prawo','Technologia','Zdrowie'] as $topic) : ?>
        <span class="tag"><?php echo esc_html($topic); ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>
