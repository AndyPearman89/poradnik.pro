<section class="section">
  <div class="container">
    <?php poradnik_section_title('Popularne problemy'); ?>
    <div class="grid grid-3">
      <?php foreach (['Jak naprawić pralkę', 'Jak naprawić lodówkę', 'Jak naprawić zmywarkę', 'Jak naprawić piekarnik', 'Jak naprawić telewizor'] as $item) : ?>
        <a class="card" href="#"><?php echo esc_html($item); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>