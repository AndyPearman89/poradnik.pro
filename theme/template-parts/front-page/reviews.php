<section class="section">
  <div class="container">
    <?php poradnik_section_title('Najlepsze recenzje produktów'); ?>
    <div class="cards-scroll">
      <?php for ($i = 1; $i <= 6; $i++) : ?>
        <article class="card">
          <div class="meta">Product image</div>
          <h3>Produkt <?php echo (int) $i; ?></h3>
          <div class="rating">★ 4.8</div>
          <p class="meta">Zalety: cena, wydajność, trwałość</p>
          <a class="btn" href="#">sprawdź cenę</a>
        </article>
      <?php endfor; ?>
    </div>
  </div>
</section>