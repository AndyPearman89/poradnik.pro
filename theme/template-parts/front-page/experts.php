<section class="section">
  <div class="container">
    <?php poradnik_section_title('Znajdź specjalistę'); ?>
    <div class="grid grid-3">
      <?php foreach (['Hydraulik','Mechanik','Elektryk','Prawnik','Doradca finansowy','Lekarz'] as $role) : ?>
        <article class="card">
          <div class="meta">Icon</div>
          <h3><?php echo esc_html($role); ?></h3>
          <a class="btn btn-accent" href="#">znajdź specjalistę</a>
        </article>
      <?php endforeach; ?>
    </div>
    <?php poradnik_section_title('Najlepsi specjaliści'); ?>
    <div class="cards-scroll">
      <?php for ($i = 1; $i <= 6; $i++) : ?>
        <article class="card">
          <div class="meta">Avatar</div>
          <h3>Specjalista <?php echo (int) $i; ?></h3>
          <div class="rating">★ 4.9</div>
          <p class="meta">City: Warszawa · Specialization: Hydraulika</p>
          <a class="btn" href="#">wyślij zapytanie</a>
        </article>
      <?php endfor; ?>
    </div>
  </div>
</section>
