<section class="section">
  <div class="container">
    <?php poradnik_section_title('Najlepsze rankingi'); ?>
    <div class="grid grid-4">
      <?php foreach (['Ranking hostingów','Ranking laptopów','Ranking fotowoltaiki','Ranking ubezpieczeń'] as $title) : ?>
        <article class="card">
          <div class="meta">Image</div>
          <h3><?php echo esc_html($title); ?></h3>
          <p class="meta">Top3 products: Produkt A, Produkt B, Produkt C</p>
          <a class="btn" href="#">zobacz ranking</a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
