<section class="section">
  <div class="container">
    <?php poradnik_section_title('Najnowsze poradniki'); ?>
    <div class="article-list">
      <?php for ($i = 1; $i <= 10; $i++) : ?>
        <article class="card">
          <h3>Artykuł <?php echo (int) $i; ?></h3>
          <p class="meta">Latest article list item</p>
        </article>
      <?php endfor; ?>
    </div>
  </div>
</section>