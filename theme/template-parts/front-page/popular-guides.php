<section class="section">
  <div class="container">
    <?php poradnik_section_title('Popularne poradniki'); ?>
    <div class="grid grid-4">
      <?php for ($i = 1; $i <= 8; $i++) : ?>
        <article class="card">
          <div class="meta">Image</div>
          <div class="meta">Category</div>
          <div class="meta">Kategoria</div>
          <h3>Poradnik <?php echo (int) $i; ?></h3>
          <p>Krótki opis poradnika i problemu użytkownika.</p>
          <div class="meta">5 min czytania</div>
          <a class="btn" href="#">czytaj</a>
        </article>
      <?php endfor; ?>
    </div>
  </div>
</section>
